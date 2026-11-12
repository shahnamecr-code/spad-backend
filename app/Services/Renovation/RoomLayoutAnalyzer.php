<?php

namespace App\Services\Renovation;

use Illuminate\Support\Facades\Http;

/**
 * Reads one equirectangular 360° room panorama with the Claude API and returns
 * the room's layout in image coordinates (u: 0..1 left→right, v: 0..1
 * top→bottom): wall corners where they meet the floor and the ceiling, the
 * ceiling height, every recognisable element (windows, curtains, doors,
 * cabinets, hob, hood, sink, lights, furniture...) as a box, and where new
 * items would best go. Claude only reads the image; nothing is generated. The
 * studio turns this into a 3D room: materials are laid on the real photo and
 * catalog items replace the detected elements or go to the suggested spots.
 */
final class RoomLayoutAnalyzer
{
    public const KINDS = ['window', 'door', 'opening', 'radiator', 'fireplace', 'appliance', 'furniture', 'stairs', 'column', 'other'];
    public const ROOMS = ['living', 'kitchen', 'bedroom', 'bathroom', 'hall', 'entrance', 'balcony', 'other'];
    public const FLOORS = ['ceramic', 'stone', 'parquet', 'laminate', 'carpet', 'mosaic', 'concrete', 'other'];
    /** Element kinds Claude may report; the studio knows how to replace most of them. */
    public const ELEMENTS = [
        'window', 'door', 'opening', 'curtain', 'blind', 'radiator', 'fireplace', 'stairs', 'column',
        'cabinet_upper', 'cabinet_lower', 'cabinet_tall', 'countertop', 'hob', 'hood', 'oven', 'sink', 'fridge',
        'chandelier', 'ceiling_light', 'sconce', 'sofa', 'armchair', 'table', 'bed', 'tv', 'rug', 'plant',
        'wardrobe', 'toilet', 'vanity', 'shower', 'bathtub', 'mirror', 'appliance', 'furniture', 'other',
    ];
    /** Items Claude may suggest a free spot for. */
    public const SUGGEST = ['chandelier', 'sofa', 'tv', 'table', 'rug', 'plant', 'sconce', 'hob', 'hood', 'sink', 'oven', 'vanity', 'toilet', 'shower'];
    /** How each element kind maps to the older exclusion list (kept for saved designs and the admin). */
    private const LEGACY = [
        'window' => 'window', 'door' => 'door', 'opening' => 'opening', 'radiator' => 'radiator', 'fireplace' => 'fireplace',
        'stairs' => 'stairs', 'column' => 'column', 'hob' => 'appliance', 'hood' => 'appliance', 'oven' => 'appliance',
        'sink' => 'appliance', 'fridge' => 'appliance', 'appliance' => 'appliance',
    ];

    public static function configured(): bool
    {
        return (string) config('spad-claude.key', '') !== '';
    }

    /** A plain box room, used until (or instead of) an analysis. */
    public static function defaultLayout(): array
    {
        return [
            'room_type' => 'other', 'name_fa' => '', 'ceiling_height_m' => 2.9,
            'corners' => [
                ['u' => 0.125, 'vf' => 0.70, 'vc' => 0.30], ['u' => 0.375, 'vf' => 0.70, 'vc' => 0.30],
                ['u' => 0.625, 'vf' => 0.70, 'vc' => 0.30], ['u' => 0.875, 'vf' => 0.70, 'vc' => 0.30],
            ],
            'exclusions' => [], 'cabinets' => [], 'elements' => [], 'suggest' => [], 'floor_material' => 'other', 'confidence' => 0,
        ];
    }

    /**
     * Two passes. 1) The whole panorama (plus the neighbouring rooms of the same
     * tour as context) gives the room outline, every element and free spots.
     * 2) Close-ups of the original panorama around each corner and each key
     * element, with a fine grid and the first estimate marked, are sent back so
     * the positions can be corrected to the exact edges.
     *
     * @param array<int, array{name: string, bytes: string}> $neighbours
     * @return array{layout: array, model: string}
     */
    public function analyze(string $imageBytes, array $neighbours = [], string $roomName = ''): array
    {
        $model = (string) config('spad-claude.model', 'claude-opus-5-5');
        $content = [self::image($this->prepare($imageBytes))];
        foreach (array_slice($neighbours, 0, 2) as $i => $nb) {
            try {
                $content[] = ['type' => 'text', 'text' => 'Image ' . ($i + 2) . ': the neighbouring space "' . mb_substr($nb['name'], 0, 40) . '" of the same home (another tour stop), for context only — shared walls, doors and the kitchen or windows seen from another angle.'];
                $content[] = self::image($this->thumb($nb['bytes']));
            } catch (\Throwable $e) {
                // context is optional
            }
        }
        $content[] = ['type' => 'text', 'text' => self::prompt() . ($roomName !== '' ? "\nThe tour calls this space: " . mb_substr($roomName, 0, 40) . '.' : '')
            . (count($content) > 1 ? "\nReport geometry and elements for image 1 only." : '')];
        $data = $this->ask($model, $content, 6000);
        $layout = self::normalize($data);
        try {
            $layout = $this->refine($model, $imageBytes, $layout);
        } catch (\Throwable $e) {
            // keep the first pass
        }
        return ['layout' => $layout, 'model' => $model];
    }

    private static function image(string $jpeg): array
    {
        return ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => base64_encode($jpeg)]];
    }

    private function ask(string $model, array $content, int $maxTokens): array
    {
        $reply = Http::withHeaders([
            'x-api-key' => (string) config('spad-claude.key'),
            'anthropic-version' => '2023-06-01',
        ])->acceptJson()->timeout(150)->connectTimeout(15)->post((string) config('spad-claude.endpoint'), [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => [['role' => 'user', 'content' => $content]],
        ]);
        if (!$reply->successful()) {
            throw new \RuntimeException('claude-http-' . $reply->status());
        }
        $text = '';
        foreach ((array) $reply->json('content', []) as $block) {
            if (($block['type'] ?? '') === 'text') $text .= $block['text'];
        }
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) throw new \RuntimeException('claude-no-json');
        $data = json_decode(substr($text, $start, $end - $start + 1), true);
        if (!is_array($data)) throw new \RuntimeException('claude-bad-json');
        return $data;
    }

    /** Elements whose exact edges matter most when a catalog item replaces them. */
    private const REFINE_KINDS = ['window', 'curtain', 'blind', 'door', 'hob', 'hood', 'sink', 'oven', 'chandelier', 'cabinet_lower', 'cabinet_upper', 'countertop'];

    /** Second pass: close-ups of the original panorama around every corner and key element. */
    public function refine(string $model, string $imageBytes, array $layout): array
    {
        $crops = $this->closeUps($imageBytes, $layout);
        if (!$crops['images']) return $layout;
        $content = [];
        foreach ($crops['images'] as $c) {
            $content[] = ['type' => 'text', 'text' => $c['label']];
            $content[] = self::image($c['jpeg']);
        }
        $content[] = ['type' => 'text', 'text' => self::refinePrompt()];
        $fix = $this->ask($model, $content, 4000);

        $corners = $layout['corners'];
        foreach ((array) ($fix['corners'] ?? []) as $c) {
            $i = (int) ($c['i'] ?? -1);
            if (!isset($corners[$i])) continue;
            if (!empty($c['drop'])) { $corners[$i]['drop'] = true; continue; }
            foreach (['u' => 'u', 'v_floor' => 'vf', 'v_ceiling' => 'vc'] as $from => $to) {
                if (isset($c[$from]) && is_numeric($c[$from]) && abs((float) $c[$from] - $corners[$i][$to]) < 0.06) $corners[$i][$to] = (float) $c[$from];
            }
        }
        $elements = $layout['elements'];
        foreach ((array) ($fix['elements'] ?? []) as $e) {
            $i = (int) ($e['i'] ?? -1);
            if (!isset($elements[$i])) continue;
            foreach (['u0', 'u1', 'v0', 'v1'] as $k) {
                if (isset($e[$k]) && is_numeric($e[$k]) && abs((float) $e[$k] - $elements[$i][$k]) < 0.08) $elements[$i][$k] = (float) $e[$k];
            }
        }
        $data = $layout;
        $data['corners'] = array_values(array_map(fn ($c) => ['u' => $c['u'], 'v_floor' => $c['vf'], 'v_ceiling' => $c['vc']],
            array_filter($corners, fn ($c) => empty($c['drop']))));
        $data['elements'] = $elements;
        $out = self::normalize($data);
        $out['refined'] = true;
        return $out;
    }

    /** @return array{images: array<int, array{label: string, jpeg: string}>} */
    public function closeUps(string $bytes, array $layout): array
    {
        $previous = ini_get('memory_limit');
        @ini_set('memory_limit', '1024M');
        try {
            $src = @imagecreatefromstring($bytes);
            if (!$src) throw new \RuntimeException('pano-unreadable');
            $images = [];
            foreach ($layout['corners'] as $i => $c) {
                $images[] = ['label' => "Corner close-up C{$i} (first estimate u={$c['u']}, v_floor={$c['vf']}, v_ceiling={$c['vc']}, marked with cyan dots):",
                    'jpeg' => $this->crop($src, $c['u'] - 0.05, $c['u'] + 0.05, 0.08, 0.92, 360, 1100, [[$c['u'], $c['vf']], [$c['u'], $c['vc']]], null)];
            }
            $n = 0;
            foreach ($layout['elements'] as $i => $e) {
                if ($n >= 10 || !in_array($e['kind'], self::REFINE_KINDS, true)) continue;
                $span = $e['u0'] <= $e['u1'] ? $e['u1'] - $e['u0'] : 1 - $e['u0'] + $e['u1'];
                $mu = max(0.03, $span * 0.5);
                $mv = max(0.04, ($e['v1'] - $e['v0']) * 0.5);
                $images[] = ['label' => "Element close-up E{$i} ({$e['kind']}, first estimate u0={$e['u0']} u1={$e['u1']} v0={$e['v0']} v1={$e['v1']}, cyan rectangle):",
                    'jpeg' => $this->crop($src, $e['u0'] - $mu, $e['u0'] + $span + $mu, max(0.0, $e['v0'] - $mv), min(1.0, $e['v1'] + $mv), 900, 700, [], [$e['u0'], $e['u0'] + $span, $e['v0'], $e['v1']])];
                $n++;
            }
            imagedestroy($src);
            return ['images' => $images];
        } finally {
            @ini_set('memory_limit', (string) $previous);
        }
    }

    /** A region of the equirectangular image (u may wrap), scaled to fit, with a 0.01 grid and the estimate drawn on it. */
    private function crop($src, float $u0, float $u1, float $v0, float $v1, int $maxW, int $maxH, array $dots, ?array $rect): string
    {
        $W = imagesx($src); $H = imagesy($src);
        $sw = ($u1 - $u0) * $W; $sh = ($v1 - $v0) * $H;
        $k = min($maxW / $sw, $maxH / $sh, 1.6);
        $dw = max(60, (int) round($sw * $k)); $dh = max(60, (int) round($sh * $k));
        $img = imagecreatetruecolor($dw, $dh);
        // copy, splitting where the panorama wraps around
        $x = $u0 * $W; $done = 0.0;
        while ($done < $sw - 0.5) {
            $sx = fmod(fmod($x + $done, $W) + $W, $W);
            $len = min($sw - $done, $W - $sx);
            imagecopyresampled($img, $src, (int) round($done * $k), 0, (int) $sx, (int) round($v0 * $H), (int) ceil($len * $k), $dh, (int) ceil($len), (int) round($sh));
            $done += $len;
        }
        $minor = imagecolorallocatealpha($img, 255, 214, 0, 80);
        $major = imagecolorallocatealpha($img, 255, 64, 64, 45);
        $ink = imagecolorallocate($img, 255, 255, 255);
        $shadow = imagecolorallocate($img, 0, 0, 0);
        $cyan = imagecolorallocate($img, 0, 235, 255);
        $px = fn ($u) => (int) round(($u - $u0) / ($u1 - $u0) * $dw);
        $py = fn ($v) => (int) round(($v - $v0) / ($v1 - $v0) * $dh);
        for ($g = ceil($u0 * 100); $g <= floor($u1 * 100); $g++) {
            $X = $px($g / 100);
            imageline($img, $X, 0, $X, $dh, $g % 5 ? $minor : $major);
            if ($g % 2 === 0 || ($u1 - $u0) < 0.12) {
                $label = number_format(fmod($g / 100 + 1, 1.0), 2);
                imagestring($img, 2, $X + 3, 3, $label, $shadow); imagestring($img, 2, $X + 2, 2, $label, $ink);
            }
        }
        for ($g = ceil($v0 * 100); $g <= floor($v1 * 100); $g++) {
            $Y = $py($g / 100);
            imageline($img, 0, $Y, $dw, $Y, $g % 5 ? $minor : $major);
            if ($g % 2 === 0) {
                $label = 'v' . number_format($g / 100, 2);
                imagestring($img, 2, 3, $Y + 2, $label, $shadow); imagestring($img, 2, 2, $Y + 1, $label, $ink);
            }
        }
        foreach ($dots as [$u, $v]) {
            imageellipse($img, $px($u), $py($v), 14, 14, $cyan);
            imageellipse($img, $px($u), $py($v), 15, 15, $cyan);
        }
        if ($rect) {
            imagesetthickness($img, 2);
            imagerectangle($img, $px($rect[0]), $py($rect[2]), $px($rect[1]), $py($rect[3]), $cyan);
        }
        ob_start();
        imagejpeg($img, null, 86);
        $out = (string) ob_get_clean();
        imagedestroy($img);
        return $out;
    }

    /** Small context image of a neighbouring room. */
    private function thumb(string $bytes): string
    {
        $src = @imagecreatefromstring($bytes);
        if (!$src) throw new \RuntimeException('pano-unreadable');
        $img = imagecreatetruecolor(1200, 600);
        imagecopyresampled($img, $src, 0, 0, 0, 0, 1200, 600, imagesx($src), imagesy($src));
        imagedestroy($src);
        ob_start();
        imagejpeg($img, null, 78);
        $out = (string) ob_get_clean();
        imagedestroy($img);
        return $out;
    }

    private static function refinePrompt(): string
    {
        return <<<'TXT'
These are close-ups of the same 360° equirectangular panorama, at full resolution, with a fine grid every 0.01 in u (horizontal) and v (vertical); grid labels give the absolute panorama coordinates. Each close-up shows a first estimate:
- Corner close-ups C<i>: two cyan circles mark where the vertical wall corner was thought to meet the floor (lower) and the ceiling (upper). Move them exactly onto the real corner: u is the vertical corner edge; v_floor is where that edge meets the floor (or the skirting bottom); v_ceiling is where it meets the ceiling (below any cornice or cove the wall ends at). If the edge is hidden by furniture or cabinets, extend the visible floor and ceiling lines. If this is not a real wall corner (for example a door frame or furniture edge), answer drop: true.
- Element close-ups E<i>: the cyan rectangle is the estimated box; give the tight box around the element itself (for a curtain the whole fabric from rail to hem, for a hob or sink its outline on the counter, for cabinets the fronts).
Read the values against the grid labels to 0.005 where you can. Reply with JSON only:
{"corners":[{"i":0,"u":0.123,"v_floor":0.712,"v_ceiling":0.301}],"elements":[{"i":3,"u0":0.41,"u1":0.47,"v0":0.36,"v1":0.62}]}
TXT;
    }

    /** Downscale to 2400x1200 and draw a labelled u/v grid so positions can be read precisely. */
    private function prepare(string $bytes): string
    {
        $previous = ini_get('memory_limit');
        @ini_set('memory_limit', '768M');
        try {
            $src = @imagecreatefromstring($bytes);
            if (!$src) throw new \RuntimeException('pano-unreadable');
            $w = 2400; $h = 1200;
            $img = imagecreatetruecolor($w, $h);
            imagecopyresampled($img, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
            imagedestroy($src);
            $line = imagecolorallocatealpha($img, 255, 214, 0, 72);
            $major = imagecolorallocatealpha($img, 255, 64, 64, 40);
            $ink = imagecolorallocate($img, 255, 255, 255);
            $shadow = imagecolorallocate($img, 0, 0, 0);
            for ($i = 1; $i < 20; $i++) {
                $x = (int) round($w * $i / 20);
                imageline($img, $x, 0, $x, $h, $i % 2 ? $line : $major);
                $label = number_format($i / 20, 2);
                foreach ([4, $h / 2 - 8, $h - 18] as $y) {
                    imagestring($img, 3, $x + 3, (int) $y + 1, $label, $shadow);
                    imagestring($img, 3, $x + 2, (int) $y, $label, $ink);
                }
            }
            for ($j = 1; $j < 20; $j++) {
                $y = (int) round($h * $j / 20);
                imageline($img, 0, $y, $w, $y, $j % 2 ? $line : $major);
                $label = 'v' . number_format($j / 20, 2);
                foreach ([4, $w / 2 + 6, $w - 62] as $x) {
                    imagestring($img, 3, (int) $x + 1, $y + 3, $label, $shadow);
                    imagestring($img, 3, (int) $x, $y + 2, $label, $ink);
                }
            }
            ob_start();
            imagejpeg($img, null, 84);
            $out = (string) ob_get_clean();
            imagedestroy($img);
            return $out;
        } finally {
            @ini_set('memory_limit', (string) $previous);
        }
    }

    private static function prompt(): string
    {
        return <<<'TXT'
You are the vision engine of an interior-renovation studio. A user will repaint walls, change floors and cabinets, and replace or add curtains, appliances, lights and furniture on this photo, so you must find the room's geometry and every element precisely.

The image is a full equirectangular 360° panorama (2:1) of one room in an Iranian home, shot from a tripod at roughly 1.5 m height. A labelled grid is drawn on it: vertical lines mark u from 0 (left edge) to 1 (right edge) every 0.05; horizontal lines mark v from 0 (top) to 1 (bottom) every 0.05. The horizon is v=0.5. The left and right edges are the same direction (the image wraps around). Straight vertical edges stay vertical in this projection; horizontal edges become curves.

Read every position against the grid as precisely as you can (two decimals, three where it matters):
1. corners: every vertical wall corner around the camera, ordered by increasing u, all the way around 360°. For each give u, v_floor (where the corner meets the floor, below the horizon) and v_ceiling (where it meets the ceiling, above the horizon). A simple rectangular room has 4. For an L-shaped or open-plan space give the main corners of the space around the camera (at most 10). If a corner is hidden behind furniture or cabinets, estimate it from the floor-wall and ceiling-wall lines and keep it consistent with the counter (0.9 m) and ceiling heights. Two neighbouring corners are always less than 0.5 apart in u (the camera is inside the room); in a corner hidden behind a tall unit, still report it.
2. elements: a tight box (u0, u1, v0, v1) around every visible element of these kinds:
   window, door (the door leaf and frame), opening (doorless passage), curtain (each curtain or sheer panel; report the whole hanging fabric from rail to hem), blind (roller or zebra blind), radiator, fireplace, stairs, column,
   cabinet_upper, cabinet_lower, cabinet_tall (kitchen cabinet fronts, one box per continuous run), countertop (the worktop strip), hob, hood, oven, sink, fridge,
   chandelier, ceiling_light, sconce, sofa, armchair, table, bed, tv, rug, plant, wardrobe, toilet, vanity, shower, bathtub, mirror,
   appliance or furniture (anything else of that sort), other.
   Use u0 > u1 only if a box crosses the left/right edge. Do not merge separate windows or separate curtains.
3. suggest: where a new item would best go in this room if the user adds one, for items the room does not already have and that make sense in it. Choose free spots that do not overlap existing furniture, windows or doors:
   chandelier (centre of the ceiling or above the dining/sitting area; give a small box around that ceiling point), sofa (box on the floor against a free wall, from the floor contact to the top of a 0.85 m back), tv (box on a free wall facing the sitting area, about 1.1 to 1.9 m above the floor), table (dining table footprint up to 0.75 m high), rug (floor box in the sitting area), plant (a free corner), sconce (wall point beside a mirror, bed or tv), and for kitchens without them: hob, hood, sink, oven (on or above the lower cabinet run); for bathrooms: vanity, toilet, shower.
4. ceiling height in metres, room type, a short Persian room name and the current floor material.

Reply with JSON only, no prose:
{"room_type":"living|kitchen|bedroom|bathroom|hall|entrance|balcony|other","name_fa":"...","ceiling_height_m":2.9,"floor_material":"ceramic|stone|parquet|laminate|carpet|mosaic|concrete|other","corners":[{"u":0.12,"v_floor":0.71,"v_ceiling":0.29}],"elements":[{"kind":"curtain","u0":0.40,"u1":0.47,"v0":0.31,"v1":0.66}],"suggest":[{"for":"chandelier","u0":0.49,"u1":0.51,"v0":0.10,"v1":0.12}],"confidence":0.8}
TXT;
    }

    public static function normalize(array $d): array
    {
        $f = fn($v, $lo, $hi, $def) => is_numeric($v) ? max($lo, min($hi, (float) $v)) : $def;
        $corners = [];
        foreach (array_slice((array) ($d['corners'] ?? []), 0, 12) as $c) {
            if (!is_array($c) || !is_numeric($c['u'] ?? null)) continue;
            $u = fmod((float) $c['u'] + 1, 1.0);
            $corners[] = [
                'u' => round($u, 4),
                'vf' => round($f($c['v_floor'] ?? $c['vf'] ?? null, 0.52, 0.985, 0.70), 4),
                'vc' => round($f($c['v_ceiling'] ?? $c['vc'] ?? null, 0.015, 0.48, 0.30), 4),
            ];
        }
        usort($corners, fn($a, $b) => $a['u'] <=> $b['u']);
        $clean = [];
        foreach ($corners as $c) {
            if ($clean && abs(end($clean)['u'] - $c['u']) < 0.012) continue;
            $clean[] = $c;
        }
        if (count($clean) < 3) {
            throw new \RuntimeException('layout-too-few-corners');
        }
        // The camera must be inside the room: no wall may span half the panorama or more.
        foreach ($clean as $i => $c) {
            $next = $clean[($i + 1) % count($clean)]['u'];
            if (fmod($next - $c['u'] + 1, 1.0) >= 0.49) throw new \RuntimeException('layout-wall-too-wide');
        }
        $boxes = function ($list, array $kinds, int $max) use ($f) {
            $out = [];
            foreach (array_slice((array) $list, 0, $max) as $b) {
                if (!is_array($b)) continue;
                $v0 = $f($b['v0'] ?? null, 0, 1, null);
                $v1 = $f($b['v1'] ?? null, 0, 1, null);
                $u0 = $f($b['u0'] ?? null, 0, 1, null);
                $u1 = $f($b['u1'] ?? null, 0, 1, null);
                if ($v0 === null || $v1 === null || $u0 === null || $u1 === null || $v1 - $v0 < 0.005) continue;
                $kind = in_array($b['kind'] ?? '', $kinds, true) ? $b['kind'] : $kinds[count($kinds) - 1];
                $out[] = ['kind' => $kind, 'u0' => round($u0, 4), 'u1' => round($u1, 4), 'v0' => round($v0, 4), 'v1' => round($v1, 4)];
            }
            return $out;
        };
        // New schema: one element list. Older replies (and admin layouts) used exclusions + cabinets.
        $elements = $boxes($d['elements'] ?? [], self::ELEMENTS, 48);
        if (!$elements) {
            foreach ($boxes($d['exclusions'] ?? [], self::KINDS, 24) as $b) $elements[] = $b;
            foreach ($boxes($d['cabinets'] ?? [], ['upper', 'lower', 'tall'], 6) as $b) $elements[] = ['kind' => 'cabinet_' . $b['kind']] + $b;
        }
        $suggest = [];
        foreach (array_slice((array) ($d['suggest'] ?? []), 0, 16) as $g) {
            if (!is_array($g) || !in_array($g['for'] ?? '', self::SUGGEST, true)) continue;
            $one = $boxes([['kind' => 'other'] + $g], ['other'], 1);
            if ($one) $suggest[] = ['for' => $g['for']] + array_diff_key($one[0], ['kind' => 1]);
        }
        $exclusions = [];
        $cabinets = [];
        foreach ($elements as $e) {
            if (strncmp($e['kind'], 'cabinet_', 8) === 0) {
                if (count($cabinets) < 6) $cabinets[] = ['kind' => substr($e['kind'], 8)] + array_diff_key($e, ['kind' => 1]);
                continue;
            }
            if ($e['kind'] === 'rug') continue;
            $kind = self::LEGACY[$e['kind']] ?? (in_array($e['kind'], ['curtain', 'blind', 'chandelier', 'ceiling_light', 'sconce', 'countertop', 'mirror', 'other'], true) ? 'other' : 'furniture');
            if (count($exclusions) < 24) $exclusions[] = ['kind' => $kind] + array_diff_key($e, ['kind' => 1]);
        }
        return [
            'room_type' => in_array($d['room_type'] ?? '', self::ROOMS, true) ? $d['room_type'] : 'other',
            'name_fa' => mb_substr(trim((string) ($d['name_fa'] ?? '')), 0, 40),
            'ceiling_height_m' => round($f($d['ceiling_height_m'] ?? null, 2.3, 4.5, 2.9), 2),
            'floor_material' => in_array($d['floor_material'] ?? '', self::FLOORS, true) ? $d['floor_material'] : 'other',
            'corners' => $clean,
            'exclusions' => $exclusions,
            'cabinets' => $cabinets,
            'elements' => $elements,
            'suggest' => $suggest,
            'schema' => 2,
            'confidence' => round($f($d['confidence'] ?? null, 0, 1, 0.5), 2),
        ];
    }
}

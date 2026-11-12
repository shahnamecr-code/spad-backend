<?php

namespace App\Services\Renovation;

use Illuminate\Support\Facades\Http;

/**
 * Reads one equirectangular 360° room panorama with the Claude API and returns
 * the room's layout in image coordinates (u: 0..1 left→right, v: 0..1
 * top→bottom): wall corners where they meet the floor and the ceiling, the
 * ceiling height, and boxes for windows, doors, radiators, cabinets, etc.
 * Claude only reads the image; nothing is generated. The studio turns the
 * layout into a 3D room so materials can be previewed on the real photo.
 */
final class RoomLayoutAnalyzer
{
    public const KINDS = ['window', 'door', 'opening', 'radiator', 'fireplace', 'appliance', 'furniture', 'stairs', 'column', 'other'];
    public const ROOMS = ['living', 'kitchen', 'bedroom', 'bathroom', 'hall', 'entrance', 'balcony', 'other'];
    public const FLOORS = ['ceramic', 'stone', 'parquet', 'laminate', 'carpet', 'mosaic', 'concrete', 'other'];

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
            'exclusions' => [], 'cabinets' => [], 'floor_material' => 'other', 'confidence' => 0,
        ];
    }

    /** @return array{layout: array, model: string} */
    public function analyze(string $imageBytes): array
    {
        $jpeg = $this->prepare($imageBytes);
        $model = (string) config('spad-claude.model', 'claude-opus-5-5');
        $reply = Http::withHeaders([
            'x-api-key' => (string) config('spad-claude.key'),
            'anthropic-version' => '2023-06-01',
        ])->acceptJson()->timeout(120)->connectTimeout(15)->post((string) config('spad-claude.endpoint'), [
            'model' => $model,
            'max_tokens' => 3000,
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => base64_encode($jpeg)]],
                    ['type' => 'text', 'text' => self::prompt()],
                ],
            ]],
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
        return ['layout' => self::normalize($data), 'model' => $model];
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
You are measuring a room for an interior-renovation preview tool.

The image is a full equirectangular 360° panorama (2:1) of one room in an Iranian apartment, shot from a tripod at roughly 1.5 m height. A labelled grid is drawn on it: vertical lines mark u from 0 (left edge) to 1 (right edge) every 0.05; horizontal lines mark v from 0 (top) to 1 (bottom) every 0.05. The left and right edges are the same direction (the image wraps around).

Find the room's structural layout, reading positions against the grid as precisely as you can (two decimals or better):
1. Every vertical wall corner of the room, ordered by increasing u, all the way around 360°. For each corner give u, v_floor (where the corner meets the floor, below the horizon v=0.5) and v_ceiling (where it meets the ceiling, above the horizon). For a simple rectangular room there are 4 corners. For an L-shaped or open-plan space give the main corners of the space that surrounds the camera (at most 10). If a corner is hidden behind furniture, estimate it from the floor-wall and ceiling-wall lines.
2. Rectangular boxes (u0, u1, v0, v1) for things that are not plain wall/floor/ceiling surface: windows, doors and openings, radiators, fireplace, appliances, built-in or loose furniture, stairs, columns. Use u0 > u1 only if a box crosses the left/right edge.
3. Kitchen cabinet fronts as separate boxes (upper, lower, tall), if any.
4. Estimated floor-to-ceiling height in metres, the room type, a short Persian room name, and the current floor material.

Reply with JSON only, no prose:
{"room_type":"living|kitchen|bedroom|bathroom|hall|entrance|balcony|other","name_fa":"...","ceiling_height_m":2.9,"floor_material":"ceramic|stone|parquet|laminate|carpet|mosaic|concrete|other","corners":[{"u":0.12,"v_floor":0.71,"v_ceiling":0.29}],"exclusions":[{"kind":"window|door|opening|radiator|fireplace|appliance|furniture|stairs|column|other","u0":0.4,"u1":0.48,"v0":0.35,"v1":0.55}],"cabinets":[{"kind":"upper|lower|tall","u0":0.6,"u1":0.7,"v0":0.3,"v1":0.42}],"confidence":0.8}
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
        return [
            'room_type' => in_array($d['room_type'] ?? '', self::ROOMS, true) ? $d['room_type'] : 'other',
            'name_fa' => mb_substr(trim((string) ($d['name_fa'] ?? '')), 0, 40),
            'ceiling_height_m' => round($f($d['ceiling_height_m'] ?? null, 2.3, 4.5, 2.9), 2),
            'floor_material' => in_array($d['floor_material'] ?? '', self::FLOORS, true) ? $d['floor_material'] : 'other',
            'corners' => $clean,
            'exclusions' => $boxes($d['exclusions'] ?? [], self::KINDS, 24),
            'cabinets' => $boxes($d['cabinets'] ?? [], ['upper', 'lower', 'tall'], 6),
            'confidence' => round($f($d['confidence'] ?? null, 0, 1, 0.5), 2),
        ];
    }
}

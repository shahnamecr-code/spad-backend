<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * SPAD WhatsApp banners (v2): fully drawn in HTML/CSS/SVG with live data only.
 * No photographic artwork, no sample numbers; every price comes from $page.
 * Fonts: the main site IRANSans FaNum, read from the licensed site install.
 */
class SpadWhatsAppPosterRenderer
{
    public const WIDTH = 1122;
    public const HEIGHT = 1402;

    private const KINDS = ['market', 'city'];
    private const LOGO = 'resources/wa-posters/spad-logo.png';

    /** Market layout: two featured quotes, then three grouped columns. */
    private const FEATURED = ['usd', 'gold_18'];
    private const GROUPS = [
        ['title' => 'ارز', 'keys' => ['eur', 'gbp', 'aed', 'try', 'usdt']],
        ['title' => 'طلا', 'keys' => ['gold_24', 'mesghal_gold', 'melted_gold', 'gold_ounce']],
        ['title' => 'سکه', 'keys' => ['new_coin', 'old_coin', 'half_coin', 'quarter_coin']],
    ];

    public function available(): bool
    {
        return is_file(base_path(self::LOGO))
            && is_file(base_path('resources/wa-posters/render-wa-poster.cjs'))
            && is_file($this->browserModule() . '/index.js')
            && is_file($this->siteFont('IRANSansWeb(FaNum).woff'))
            && function_exists('proc_open');
    }

    public function render(array $page): string
    {
        if (!$this->available()) {
            throw new RuntimeException('موتور مرورگر، لوگو یا فونت اصلی اسپاد روی سرور آماده نیست.', 503);
        }
        $kind = $page['kind'] ?? '';
        if (!in_array($kind, self::KINDS, true)) {
            throw new RuntimeException('نوع قالب واتساپ معتبر نیست.', 422);
        }

        $process = new Process(
            ['node', base_path('resources/wa-posters/render-wa-poster.cjs')],
            base_path(),
            ['SPAD_WA_PLAYWRIGHT' => $this->browserModule()]
        );
        $process->setInput($this->html($page));
        $process->setTimeout(55);
        $process->run();
        $png = $process->getOutput();
        if (!$process->isSuccessful()
            || strlen($png) < 40000
            || substr($png, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            Log::warning('SPAD WhatsApp poster browser rendering failed', [
                'kind' => $kind,
                'exit_code' => $process->getExitCode(),
                'stderr_excerpt' => mb_substr($process->getErrorOutput(), 0, 350),
            ]);
            throw new RuntimeException('ساخت تصویر نهایی با موتور مرورگر موفق نبود؛ ارسال لغو شد.', 503);
        }
        return $png;
    }

    private function browserModule(): string
    {
        return '/home/spadesta/spad-staging/admin-browser-probe/node_modules/playwright';
    }

    private function siteFont(string $file): string
    {
        return '/home/spadesta/spad-web/assets/fonts/IranSans_FaNum/' . $file;
    }

    private function fontCss(): string
    {
        $fonts = [
            ['IRANSansWeb(FaNum).woff', 400],
            ['IRANSansWeb(FaNum)_Medium.woff', 500],
            ['IRANSansWeb(FaNum)_Bold.woff', 700],
            ['IRANSansWeb(FaNum)_Black.woff', 900],
        ];
        $rules = '';
        foreach ($fonts as [$filename, $weight]) {
            $path = $this->siteFont($filename);
            if (!is_file($path)) continue;
            $source = base64_encode(file_get_contents($path));
            $rules .= "@font-face{font-family:'SPAD-IRANSans';font-weight:$weight;font-style:normal;"
                . "src:url(data:font/woff;base64,$source) format('woff');}";
        }
        return $rules;
    }

    private function fa($value): string
    {
        return strtr((string) $value, [
            '0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴',
            '5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹',
        ]);
    }

    private function e($text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function clock(): string
    {
        $now = Carbon::now('Asia/Tehran');
        $jalali = Jalalian::fromCarbon($now);
        return $this->fa($jalali->format('l j F Y') . ' | ساعت ' . $now->format('H:i'));
    }

    /** "ساعت ۱۳:۲۹" for the provider timestamp, or '' when unknown. */
    private function updatedAt($value): string
    {
        if (!$value) return '';
        try {
            return $this->fa(Carbon::parse($value)->setTimezone('Asia/Tehran')->format('H:i'));
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function price($value, string $unit): string
    {
        if (!is_numeric($value) || (float) $value <= 0) return '—';
        $amount = (float) $value;
        if ($unit === 'تومان / مترمربع') {
            $millions = $amount / 1000000;
            $valueText = ($millions >= 100 || abs($millions - round($millions)) < 0.01)
                ? number_format(round($millions))
                : number_format($millions, 1);
            return $this->fa($valueText);
        }
        if ($unit === 'دلار') return $this->fa(number_format($amount, $amount < 100 ? 2 : 1));
        return $this->fa(number_format(round($amount)));
    }

    /**
     * Inlined vector illustrations rather than font/emoji glyphs. Headless
     * Chromium on production does not guarantee country flags, location pins,
     * currency symbols or icon fonts; SVG works without external fonts/network.
     */
    private function marketIcon(string $key): string
    {
        $start = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60" width="58" height="58" aria-hidden="true" focusable="false">';
        $end = '</svg>';
        $circle = '<circle cx="30" cy="30" r="25.5" fill="#0c5ba0" stroke="#fff" stroke-width="1.3"/>';
        if ($key === 'usd') {
            $stripes = '';
            for ($i = 0; $i < 7; $i++) {
                $stripes .= '<rect x="5" y="' . (8 + $i * 7) . '" width="50" height="3.8" fill="#e64152"/>';
            }
            $dots = '';
            for ($row = 0; $row < 3; $row++) {
                for ($col = 0; $col < 3; $col++) {
                    $dots .= '<circle cx="' . (10 + $col * 5.5) . '" cy="' . (12 + $row * 5) . '" r="1" fill="#fff"/>';
                }
            }
            return $start . '<defs><clipPath id="wa-us-clip"><circle cx="30" cy="30" r="24.5"/></clipPath></defs>'
                . '<g clip-path="url(#wa-us-clip)"><circle cx="30" cy="30" r="25" fill="#fff"/>'
                . $stripes . '<rect x="5" y="5" width="24" height="24" fill="#174a8e"/>'
                . $dots . '</g><circle cx="30" cy="30" r="25" fill="none" stroke="#fff" stroke-width="1.5"/>' . $end;
        }
        if ($key === 'eur') {
            $stars = '';
            for ($i = 0; $i < 12; $i++) {
                $angle = $i * M_PI / 6;
                $x = round(30 + 16 * sin($angle), 2);
                $y = round(30 - 16 * cos($angle), 2);
                $stars .= '<circle cx="' . $x . '" cy="' . $y . '" r="2.25" fill="#ffcf42"/>';
            }
            return $start . $circle . $stars . $end;
        }
        if ($key === 'gbp') {
            return $start . '<defs><clipPath id="wa-uk-clip"><circle cx="30" cy="30" r="25"/></clipPath></defs>'
                . '<g clip-path="url(#wa-uk-clip)"><circle cx="30" cy="30" r="25" fill="#183b87"/>'
                . '<path d="M5 7 55 53M55 7 5 53" stroke="#fff" stroke-width="15"/>'
                . '<path d="M5 7 55 53M55 7 5 53" stroke="#d6384d" stroke-width="7"/>'
                . '<path d="M5 30H55M30 5V55" stroke="#fff" stroke-width="19"/>'
                . '<path d="M5 30H55M30 5V55" stroke="#d6384d" stroke-width="10"/></g>'
                . '<circle cx="30" cy="30" r="25" fill="none" stroke="#fff" stroke-width="1.3"/>' . $end;
        }
        if ($key === 'aed') {
            return $start . '<defs><clipPath id="wa-ae-clip"><circle cx="30" cy="30" r="25"/></clipPath></defs>'
                . '<g clip-path="url(#wa-ae-clip)"><rect x="5" y="5" width="50" height="17" fill="#158750"/>'
                . '<rect x="5" y="22" width="50" height="16" fill="#fff"/>'
                . '<rect x="5" y="38" width="50" height="17" fill="#222b34"/>'
                . '<rect x="5" y="5" width="16" height="50" fill="#df3545"/></g>'
                . '<circle cx="30" cy="30" r="25" fill="none" stroke="#fff" stroke-width="1.3"/>' . $end;
        }
        if ($key === 'try') {
            return $start . '<circle cx="30" cy="30" r="25" fill="#dc2334"/>'
                . '<circle cx="27" cy="30" r="14" fill="#fff"/><circle cx="33" cy="26.5" r="12" fill="#dc2334"/>'
                . '<path d="m42 19 2.3 6.3 6.7.3-5.2 4.2 1.8 6.4-5.6-3.8-5.6 3.8 1.8-6.4-5.2-4.2 6.7-.3z" fill="#fff" transform="translate(1 -3) scale(.68) translate(20 15)"/>'
                . '<circle cx="30" cy="30" r="25" fill="none" stroke="#fff" stroke-width="1.2"/>' . $end;
        }
        if ($key === 'usdt') {
            return $start . '<circle cx="30" cy="30" r="25" fill="#0a9a73"/>'
                . '<path d="M15 17h30v9H34v20h-8V26H15z" fill="#fff"/>'
                . '<ellipse cx="30" cy="28.5" rx="17" ry="6" fill="none" stroke="#fff" stroke-width="2.8"/>'
                . '<path d="M28 32h4v13h-4z" fill="#fff"/>' . $end;
        }
        if (in_array($key, ['gold_18','gold_24','gold_ounce','melted_gold','mesghal_gold'], true)) {
            return $start . '<circle cx="30" cy="30" r="25" fill="#174b7b"/>'
                . '<path d="M11 34 18 24h20l9 10-8 10H19z" fill="#edb64e" stroke="#fce29d" stroke-width="1.6"/>'
                . '<path d="M11 34h36l-8 10H19z" fill="#bc7c23"/>'
                . '<path d="M18 24h20l9 10H11z" fill="#ffe5a2"/>'
                . '<path d="M18 24 11 34l8 10M38 24l9 10-8 10" fill="none" stroke="#a76d1d" stroke-width="1"/>'
                . '<path d="M21 16h15l6 9H16z" fill="#e2a83b" stroke="#ffe8aa" stroke-width="1.5"/>'
                . '<path d="M21 16 16 25h26l-6-9z" fill="#ffdb84"/>' . $end;
        }
        if (in_array($key, ['new_coin','old_coin','half_coin','quarter_coin'], true)) {
            return $start . '<circle cx="30" cy="30" r="25" fill="#c89035" stroke="#ffe49a" stroke-width="2"/>'
                . '<circle cx="30" cy="30" r="20.5" fill="#f6c963" stroke="#9d661d" stroke-width="1.4"/>'
                . '<circle cx="30" cy="30" r="16.2" fill="none" stroke="#b37b25" stroke-width="1.5" stroke-dasharray="1 3"/>'
                . '<path d="M30 17v26M19 30h22M23 38l7-16 7 16" stroke="#a36c23" stroke-width="2.3" stroke-linecap="round" fill="none"/>'
                . '<circle cx="30" cy="30" r="4" fill="#fceaaa" stroke="#a36c23" stroke-width="1.4"/>' . $end;
        }
        return $start . $circle .
            '<path d="M17 38V27M26 38V22M35 38V28M44 38V17" stroke="#fff" stroke-width="4" stroke-linecap="round"/>'
            . $end;
    }

    private function trendIcon(bool $isUp): string
    {
        return $isUp
            ? '<svg viewBox="0 0 12 12" width="12" height="12" aria-hidden="true"><path d="M6 1 11 10H1z" fill="currentColor"/></svg>'
            : '<svg viewBox="0 0 12 12" width="12" height="12" aria-hidden="true"><path d="M6 11 1 2h10z" fill="currentColor"/></svg>';
    }

    private function changeChip(array $row): string
    {
        $value = $row['value'] ?? null;
        $change = $row['change'] ?? null;
        if (!is_numeric($value) || (float) $value <= 0 || !is_numeric($change) || (float) $change == 0.0) {
            return '<span class="chip flat">بدون تغییر</span>';
        }
        $previous = (float) $value - (float) $change;
        if ($previous <= 0) return '<span class="chip flat">—</span>';
        $percent = abs((float) $change / $previous * 100);
        if ($percent > 100) return '<span class="chip flat">—</span>';
        $up = (float) $change > 0;
        return '<span class="chip ' . ($up ? 'up' : 'down') . '">' . $this->trendIcon($up)
            . '<span dir="ltr">' . $this->e($this->fa(number_format($percent, 2))) . '٪</span></span>';
    }

    private function quote(array $row, bool $featured): string
    {
        $key = (string) ($row['key'] ?? '');
        $unit = (string) ($row['unit'] ?? 'تومان');
        $available = is_numeric($row['value'] ?? null) && (float) $row['value'] > 0;
        $number = $available ? $this->e($this->price($row['value'], $unit)) : '—';
        $unitText = $available ? $this->e($unit) : 'نرخ موجود نیست';
        $class = $featured ? 'q featured' : 'q';
        return '<div class="' . $class . '">'
            . '<span class="q-icon">' . $this->marketIcon($key) . '</span>'
            . '<span class="q-name">' . $this->e($row['name'] ?? '') . '</span>'
            . '<span class="q-price"><b dir="ltr">' . $number . '</b><small>' . $unitText . '</small></span>'
            . '<span class="q-change">' . ($available ? $this->changeChip($row) : '') . '</span>'
            . '</div>';
    }

    private function marketBody(array $rows): string
    {
        $byKey = [];
        foreach ($rows as $row) {
            if (!empty($row['key'])) $byKey[(string) $row['key']] = $row;
        }
        $featured = '';
        foreach (self::FEATURED as $key) {
            if (isset($byKey[$key])) $featured .= $this->quote($byKey[$key], true);
        }
        $columns = '';
        foreach (self::GROUPS as $group) {
            $items = '';
            foreach ($group['keys'] as $key) {
                if (isset($byKey[$key])) $items .= $this->quote($byKey[$key], false);
            }
            if ($items === '') continue;
            $columns .= '<section class="col"><h3>' . $this->e($group['title']) . '</h3>'
                . '<div class="col-rows">' . $items . '</div></section>';
        }
        return '<div class="featured">' . $featured . '</div><div class="cols">' . $columns . '</div>';
    }

    private function cityBody(array $rows, int $page): string
    {
        $valid = [];
        foreach (array_slice($rows, 0, 12) as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '' || !is_numeric($row['value'] ?? null) || (float) $row['value'] <= 0) continue;
            $valid[] = ['name' => $name, 'value' => (float) $row['value']];
        }
        $max = $valid ? max(array_column($valid, 'value')) : 1;
        $first = ($page - 1) * 12;
        $list = '';
        foreach ($valid as $i => $row) {
            // Bar length is proportional to price per m2 (relative to the top row).
            $width = round(max(6, 100 * $row['value'] / $max), 1);
            $list .= '<li><span class="rank">' . $this->fa($first + $i + 1) . '</span>'
                . '<span class="hood">' . $this->e($row['name']) . '</span>'
                . '<span class="bar"><i style="width:' . $width . '%"></i></span>'
                . '<span class="ppm"><b>' . $this->e($this->price($row['value'], 'تومان / مترمربع')) . '</b></span></li>';
        }
        return '<div class="city-head"><p>میانگین قیمت هر متر مربع آپارتمان در گران‌ترین محله‌ها</p>'
            . '<span class="unit">ارقام به میلیون تومان</span></div><ol class="ranking">' . $list . '</ol>';
    }

    private function html(array $page): string
    {
        $kind = (string) ($page['kind'] ?? '');
        $city = trim((string) ($page['city'] ?? ''));
        $title = $kind === 'market' ? 'نرخ لحظه‌ای ارز و طلا و سکه'
            : 'نرخ لحظه‌ای قیمت ملک' . ($city !== '' ? ' (' . $city . ')' : '');
        $logo = base64_encode(file_get_contents(base_path(self::LOGO)));
        $updated = $this->updatedAt($page['updated_at'] ?? null);
        if ($kind === 'market') {
            $body = $this->marketBody($page['rows'] ?? []);
            $note = 'نرخ‌ها از بازار آزاد دریافت شده‌اند و ممکن است با نرخ نهایی معامله متفاوت باشند.'
                . ($updated !== '' ? ' آخرین به‌روزرسانی قیمت‌ها: ساعت ' . $updated . '.' : '');
        } else {
            $body = $this->cityBody($page['rows'] ?? [], max(1, (int) ($page['page'] ?? 1)));
            $note = 'منبع داده: نمودا (nemuda.com/data، مجوز CC BY 4.0). برآورد قیمت پیشنهادی بازار است و قیمت قطعی معامله یا کارشناسی نیست.';
        }
        $globe = '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><circle cx="12" cy="12" r="9.2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M2.8 12h18.4M12 2.8c2.6 2.6 3.9 5.6 3.9 9.2s-1.3 6.6-3.9 9.2M12 2.8C9.4 5.4 8.1 8.4 8.1 12s1.3 6.6 3.9 9.2" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>';
        $css = <<<'CSS'
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:1122px;height:1402px;overflow:hidden}
body{font-family:'SPAD-IRANSans',Tahoma,sans-serif;direction:rtl;color:#0a2240;background:#eef3f9}
.wa-poster{position:relative;width:1122px;height:1402px;display:flex;flex-direction:column;background:#eef3f9;overflow:hidden}
/* header */
.hd{position:relative;height:300px;padding:34px 48px 0;color:#fff;
 background:linear-gradient(160deg,#0f3666 0%,#0a2850 52%,#071c3a 100%);overflow:hidden}
.hd::before{content:"";position:absolute;inset:0;
 background:repeating-linear-gradient(135deg,rgba(255,255,255,.035) 0 2px,transparent 2px 26px);pointer-events:none}
.hd::after{content:"";position:absolute;left:0;right:0;bottom:0;height:5px;
 background:linear-gradient(90deg,#8a6a26,#e2c47a 30%,#c9a14a 60%,#8a6a26)}
.hd-top{position:relative;display:flex;justify-content:space-between;align-items:center}
.logo{display:flex;align-items:center;justify-content:center;width:236px;height:112px;border-radius:22px;background:#fff;
 box-shadow:0 10px 30px rgba(0,0,0,.25)}
.logo img{width:212px;height:106px;object-fit:contain}
.title{position:relative;margin-top:26px;font-size:60px;font-weight:900;line-height:1.35;color:#fff;letter-spacing:-.5px}
.meta{position:relative;display:inline-flex;align-items:center;gap:12px;padding:10px 22px;border-radius:999px;
 background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);font-size:24px;font-weight:500;color:#dbe7f5}
.live{width:13px;height:13px;border-radius:50%;background:#3ddc84;box-shadow:0 0 0 6px rgba(61,220,132,.22)}
/* body */
.bd{position:relative;flex:1;padding:28px 44px 0;display:flex;flex-direction:column;min-height:0}
/* market */
.featured{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.q{display:grid;grid-template-columns:auto 1fr;grid-template-rows:auto auto;column-gap:14px;align-items:center}
.q.featured{background:#fff;border-radius:24px;padding:18px 24px;border:1px solid #dde6f1;
 box-shadow:0 10px 24px rgba(10,34,64,.08);grid-template-columns:auto 1fr auto;row-gap:2px;position:relative;overflow:hidden}
.q.featured::before{content:"";position:absolute;right:0;top:0;bottom:0;width:7px;background:linear-gradient(#e2c47a,#b28a35)}
.q-icon{grid-row:1/3;display:flex}
.q-icon svg{width:54px;height:54px;border-radius:50%;box-shadow:0 2px 6px rgba(10,34,64,.18)}
.q.featured .q-icon svg{width:72px;height:72px}
.q-name{font-size:20px;font-weight:700;color:#0a2240;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.q.featured .q-name{font-size:25px;grid-column:2/4}
.q-price{display:flex;align-items:baseline;gap:7px;white-space:nowrap}
.q-price b{font-size:27px;font-weight:900;color:#071c3a;letter-spacing:-.5px;font-variant-numeric:tabular-nums}
.q.featured .q-price b{font-size:44px}
.q-price small{font-size:15px;font-weight:500;color:#5b6f86}
.q.featured .q-price small{font-size:18px}
.q-change{grid-column:2;justify-self:start}
.q.featured .q-change{grid-column:3;grid-row:2;justify-self:end}
.chip{display:inline-flex;align-items:center;gap:6px;padding:2px 11px;border-radius:999px;font-size:15px;font-weight:700;line-height:1.6;direction:ltr}
.chip svg{width:11px;height:11px}
.chip.up{color:#0f7a45;background:#def3e7}
.chip.down{color:#b4342a;background:#fbe6e3}
.chip.flat{color:#6b7c90;background:#edf1f6;font-weight:500;direction:rtl}
.q.featured .chip{font-size:19px;padding:4px 14px}
.cols{flex:1;display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:20px;min-height:0;padding-bottom:18px}
.col{background:#fff;border-radius:22px;border:1px solid #dde6f1;padding:14px 18px 6px;overflow:hidden;display:flex;flex-direction:column;min-height:0;
 box-shadow:0 8px 20px rgba(10,34,64,.06)}
.col h3{font-size:22px;font-weight:900;color:#0f3666;padding-bottom:8px;margin-bottom:2px;border-bottom:2px solid #e2c47a;display:flex;align-items:center;gap:10px}
.col-rows{flex:1;display:flex;flex-direction:column;justify-content:space-around}
.col .q{padding:7px 0;border-bottom:1px solid #edf1f6;row-gap:1px}
.col .q:last-child{border-bottom:0}
.col .q-name{grid-column:2}
.col .q-price{grid-column:2}
.col .q-change{grid-column:2}
/* city */
.city-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.city-head p{font-size:25px;font-weight:700;color:#0f3666}
.unit{font-size:19px;font-weight:500;color:#5b6f86;padding:6px 16px;border-radius:999px;background:#fff;border:1px solid #dde6f1}
.ranking{list-style:none;flex:1;display:flex;flex-direction:column;gap:9px;padding-bottom:18px;min-height:0}
.ranking li{flex:1;display:grid;grid-template-columns:52px 260px 1fr 140px;align-items:center;column-gap:18px;
 background:#fff;border-radius:18px;border:1px solid #dde6f1;padding:0 20px;box-shadow:0 6px 14px rgba(10,34,64,.05)}
.rank{width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;
 background:#0f3666;color:#fff;font-size:21px;font-weight:700}
.ranking li:nth-child(-n+3) .rank{background:linear-gradient(150deg,#e9cf8c,#b28a35);color:#2b1d02}
.hood{font-size:25px;font-weight:700;color:#0a2240;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bar{height:16px;border-radius:999px;background:#edf1f6;overflow:hidden;direction:rtl}
.bar i{display:block;height:100%;border-radius:999px;background:linear-gradient(270deg,#e2c47a,#b28a35)}
.ppm{text-align:left;direction:ltr}
.ppm b{font-size:34px;font-weight:900;color:#071c3a;font-variant-numeric:tabular-nums}
/* footer */
.ft{padding:0 44px 0}
.note{font-size:17px;line-height:1.75;color:#5b6f86;padding:0 4px 16px}
.band{height:88px;margin:0 -44px;background:#071c3a;display:flex;align-items:center;justify-content:center;gap:14px;padding:0 48px;
 color:#fff;border-top:4px solid #c9a14a}
.band svg{color:#e2c47a;width:28px;height:28px}
.band strong{font-size:25px;font-weight:700}
.band span{color:#e2c47a;letter-spacing:.3px}
CSS;
        return '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"/>'
            . '<style>' . $this->fontCss() . $css . '</style></head><body>'
            . '<main class="wa-poster" data-kind="' . $this->e($kind) . '">'
            . '<header class="hd"><div class="hd-top">'
            . '<span class="logo"><img src="data:image/png;base64,' . $logo . '" alt="اسپاد"/></span>'
            . '<div class="meta"><span class="live"></span>' . $this->e($this->clock()) . '</div></div>'
            . '<h1 class="title">' . $this->e($title) . '</h1></header>'
            . '<div class="bd">' . $body . '</div>'
            . '<footer class="ft"><p class="note">' . $this->e($note) . '</p>'
            . '<div class="band">' . $globe . '<strong>برای مشاهده جزئیات بیشتر به <span>www.spad.estate</span> مراجعه کنید</strong></div></footer>'
            . '</main></body></html>';
    }
}

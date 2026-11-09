<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * SPAD-approved photographic WhatsApp posters, with live data rendered over the
 * original artwork. No example price/date is ever exposed in a published tile.
 * The main site IRANSans FaNum is read from the licensed site installation.
 */
class SpadWhatsAppPosterRenderer
{
    public const WIDTH = 1122;
    public const HEIGHT = 1402;

    private const ART = [
        'market' => 'resources/wa-posters/approved-market.jpg',
        'city' => 'resources/wa-posters/approved-city.jpg',
    ];

    public function available(): bool
    {
        foreach (self::ART as $art) {
            if (!is_file(base_path($art))) return false;
        }
        return is_file(base_path('resources/wa-posters/render-wa-poster.cjs'))
            && is_file($this->browserModule() . '/index.js')
            && is_file($this->siteFont('IRANSansWeb(FaNum).woff'))
            && function_exists('proc_open');
    }

    public function render(array $page): string
    {
        if (!$this->available()) {
            throw new RuntimeException('قالب تصویری یا موتور مرورگر و فونت اصلی اسپاد روی سرور آماده نیست.', 503);
        }
        $kind = $page['kind'] ?? '';
        if (!array_key_exists($kind, self::ART)) {
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
            Log::warning('SPAD approved WhatsApp poster browser rendering failed', [
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
        return $this->fa($jalali->format('l | j F Y') . ' | ساعت ' . $now->format('H:i'));
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

    private function pinIcon(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="29" height="29" aria-hidden="true" focusable="false">'
            . '<path d="M16 2.5c-6.3 0-11 4.6-11 11.1C5 21.1 16 30 16 30s11-8.9 11-16.4C27 7.1 22.3 2.5 16 2.5z" fill="#fff"/>'
            . '<circle cx="16" cy="13.5" r="5.1" fill="#0984e3"/></svg>';
    }

    private function trendIcon(bool $isUp): string
    {
        return $isUp
            ? '<svg viewBox="0 0 12 12" width="12" height="12" aria-hidden="true"><path d="M6 1 11 10H1z" fill="currentColor"/></svg>'
            : '<svg viewBox="0 0 12 12" width="12" height="12" aria-hidden="true"><path d="M6 11 1 2h10z" fill="currentColor"/></svg>';
    }

    /**
     * The original approved artwork contains illustrative rate cards. They are
     * fully hidden behind one opaque stage (see .wa-market-grid CSS), and every
     * displayed card here is redrawn from fresh provider data. Never overlay
     * transparent cards on the baked-in sample numbers or symbols.
     */
    private function marketCards(array $rows): string
    {
        $html = '';
        foreach (array_slice($rows, 0, 15) as $index => $row) {
            $value = $row['value'] ?? null;
            $unit = (string) ($row['unit'] ?? 'تومان');
            $key = (string) ($row['key'] ?? '');
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') continue;

            $available = is_numeric($value) && (float) $value > 0;
            $number = $available
                ? $this->e($this->price($value, $unit)) : '—';
            $unitText = $available ? $this->e($unit) : 'نرخ موجود نیست';
            $indicator = '';
            $change = $row['change'] ?? null;
            if ($available && is_numeric($change) && abs((float) $change) > 0) {
                $previous = (float) $value - (float) $change;
                if ($previous > 0) {
                    $percent = abs((float) $change / $previous * 100);
                    if ($percent <= 100) {
                        $up = (float) $change > 0;
                        $indicator = '<span class="wa-market-trend ' . ($up ? 'up' : 'down') . '">'
                            . $this->trendIcon($up)
                            . '<span dir="ltr">' . $this->e($this->fa(number_format($percent, 2))) . '٪</span></span>';
                    }
                }
            }
            if ($indicator === '') {
                $indicator = '<span class="wa-market-no-change">—</span>';
            }

            $last = $index === 14 ? ' last-card' : '';
            $html .= '<article class="wa-market-card' . $last . '">'
                . '<div class="wa-market-icon">' . $this->marketIcon($key) . '</div>'
                . '<div class="wa-market-name">' . $this->e($name) . '</div>'
                . '<span class="wa-market-sep" aria-hidden="true"></span>'
                . '<div class="wa-market-value">'
                . '<div class="wa-market-amount"><b dir="ltr">' . $number . '</b>'
                . '<small>' . $unitText . '</small></div>'
                . '<div class="wa-market-trend-row">' . $indicator . '</div>'
                . '</div></article>';
        }
        return $html;
    }

    private function cityCards(array $rows, string $city, int $page, int $total): string
    {
        $html = '';
        foreach (array_slice($rows, 0, 12) as $row) {
            $label = trim((string) ($row['name'] ?? ''));
            if ($label === '' || !isset($row['value']) || (float) $row['value'] <= 0) continue;
            $title = $city ? $city . ' • ' . $label : $label;
            $number = $this->e($this->price($row['value'], 'تومان / مترمربع'));
            $html .= '<article class="wa-city-card"><span class="wa-pin">' . $this->pinIcon() . '</span>'
                . '<span class="wa-city-name">' . $this->e($title) . '</span>'
                . '<span class="wa-city-divider"></span>'
                . '<span class="wa-city-value"><small>میانگین قیمت هر متر</small>'
                . '<strong>' . $number . '</strong><em>میلیون تومان</em></span>'
                . '</article>';
        }
        return $html;
    }

    private function html(array $page): string
    {
        $kind = (string) ($page['kind'] ?? '');
        $image = base64_encode(file_get_contents(base_path(self::ART[$kind])));
        $body = $kind === 'market'
            ? '<div class="wa-market-grid">' . $this->marketCards($page['rows'] ?? []) . '</div>'
            : '<div class="wa-city-panel"><div class="wa-city-grid">'
                . $this->cityCards(
                    $page['rows'] ?? [],
                    (string) ($page['city'] ?? ''),
                    (int) ($page['page'] ?? 1),
                    (int) ($page['total'] ?? 1)
                ) . '</div></div>'
                . ((int) ($page['total'] ?? 1) > 1
                    ? '<div class="wa-city-pages">' . $this->fa('صفحه ' . ($page['page'] ?? 1) . ' از ' . ($page['total'] ?? 1)) . '</div>'
                    : '');
        $clock = $this->e($this->clock());
        $css = <<<'CSS'
*{box-sizing:border-box}
html,body{width:1122px;height:1402px;margin:0;overflow:hidden}
body{font-family:'SPAD-IRANSans',Tahoma,sans-serif;direction:rtl;color:#071b39;background:#062d50}
.wa-poster{position:relative;width:1122px;height:1402px;background-position:center;background-size:1122px 1402px;background-repeat:no-repeat;overflow:hidden}
.wa-time{position:absolute;z-index:5;display:flex;align-items:center;justify-content:center;white-space:nowrap;font-weight:500;color:white;font-size:26px;letter-spacing:0;border-radius:42px;background:linear-gradient(160deg,#0d649d 0%,#052c59 48%,#08213f 100%);border:2px solid #e4bb68;box-shadow:0 2px 14px rgba(255,214,133,.55),inset 0 1px 5px rgba(255,255,255,.22)}
.wa-time-market{top:292px;left:326px;width:454px;height:59px}
.wa-time-city{top:324px;left:359px;width:521px;height:61px}
.wa-market-grid{
 position:absolute;top:357px;left:47px;width:1028px;height:731px;
 display:grid;grid-template-columns:repeat(2,minmax(0,1fr));grid-template-rows:repeat(8,82px);
 gap:7px 12px;padding:10px;direction:rtl;overflow:hidden;
 border:1px solid rgba(255,211,132,.36);border-radius:26px;
 background:radial-gradient(ellipse 80% 58% at 50% 0%,#11508d 0%,#0a407c 43%,#062d60 100%);
 box-shadow:0 12px 30px rgba(0,22,60,.34),inset 0 1px 9px rgba(127,201,255,.16);
}
.wa-market-card{
 position:relative;z-index:1;min-width:0;min-height:0;width:100%;height:82px;
 display:flex;align-items:center;gap:9px;padding:8px 12px;
 border-radius:19px;border:1.5px solid #fff;
 background:linear-gradient(144deg,#f7fbff,#e2f0fb 62%,#cfdfef);
 box-shadow:0 3px 6px rgba(1,21,58,.19),inset 0 1px 3px #fff;
 overflow:hidden;
}
.wa-market-card.last-card{grid-column:1/-1;width:calc((100% - 12px)/2);height:82px;justify-self:center}
.wa-market-icon{
 flex:0 0 52px;width:52px;height:52px;display:flex;align-items:center;justify-content:center;
 border-radius:50%;background:#fff;box-shadow:0 2px 5px rgba(3,43,86,.24);overflow:hidden
}
.wa-market-icon svg{display:block;flex:0 0 52px;width:52px;height:52px}
.wa-market-name{
 flex:1 1 0;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
 text-align:right;direction:rtl;font-size:17px;font-weight:500;line-height:1.55;color:#0b2848;
}
.wa-market-sep{flex:0 0 1px;width:1px;height:50px;background:#9ab3c9}
.wa-market-value{
 flex:0 0 45%;min-width:0;height:100%;display:flex;flex-direction:column;
 align-items:center;justify-content:center;gap:1px;direction:rtl;
}
.wa-market-amount{
 display:flex;align-items:baseline;justify-content:center;gap:5px;
 width:100%;white-space:nowrap;min-width:0;
}
.wa-market-amount b{
 font-size:21px;font-weight:700;line-height:1.3;font-variant-numeric:tabular-nums;
 letter-spacing:-.5px;color:#082542;white-space:nowrap;
}
.wa-market-amount small{font-size:11px;font-weight:500;line-height:1.3;color:#31516e;white-space:nowrap}
.wa-market-trend-row{width:100%;height:22px;display:flex;align-items:center;justify-content:center}
.wa-market-trend{
 display:inline-flex;align-items:center;justify-content:center;gap:5px;
 min-width:84px;padding:1px 11px;border-radius:18px;
 font-size:12px;font-weight:500;line-height:20px;direction:ltr;white-space:nowrap;
}
.wa-market-trend svg{display:block;flex:0 0 11px;width:11px;height:11px}
.wa-market-trend.up{color:#147a37;background:#c7edd0}
.wa-market-trend.down{color:#ad2429;background:#f9d1d6}
.wa-market-no-change{font-size:13px;line-height:20px;color:#889bad}
.wa-city-panel{position:absolute;top:395px;left:264px;width:811px;height:804px;border-radius:38px;border:2px solid #f4fbff;box-shadow:0 4px 15px rgba(3,29,75,.25),inset 0 2px 8px #fff;background:linear-gradient(155deg,#f4faff,#d3e9f9);padding:15px 17px;overflow:hidden}
.wa-city-grid{width:100%;height:100%;display:flex;flex-direction:column;gap:5px}
.wa-city-card{flex:1 1 0;min-height:0;display:flex;align-items:center;gap:11px;padding:5px 10px;border-radius:20px;border:1.4px solid #fff;background:linear-gradient(115deg,#f2f9ff,#cee7f9);box-shadow:0 2px 5px rgba(28,90,148,.14);overflow:hidden}
.wa-pin{width:38px;height:38px;border-radius:12px;background:linear-gradient(155deg,#0984e3,#075aa3);display:flex;align-items:center;justify-content:center;flex:0 0 38px;box-shadow:0 2px 5px rgba(0,26,72,.18)}
.wa-pin svg{display:block;width:28px;height:28px}
.wa-city-name{min-width:0;flex:1 1 0;font-size:21px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-align:right;color:#0a2340}
.wa-city-divider{width:1px;height:63%;flex:0 0 1px;background:#8ca6bf}
.wa-city-value{flex:0 0 43%;height:100%;min-width:0;display:flex;align-items:center;justify-content:center;column-gap:5px;row-gap:0;flex-wrap:wrap;white-space:nowrap;border-radius:18px;background:linear-gradient(110deg,#d9eefc,#bcdff3);padding:2px 8px}
.wa-city-value small{font-size:10px;font-weight:400;line-height:1.3;color:#49647d}
.wa-city-value strong{font-size:26px;font-weight:700;line-height:1.3;color:#092542}
.wa-city-value em{font-size:12px;font-weight:500;font-style:normal;color:#254665}
.wa-city-pages{position:absolute;z-index:5;top:1204px;right:95px;padding:4px 17px;border-radius:18px;background:#063c6d;color:#fff;border:1px solid #d3b67c;font-size:15px;line-height:23px;box-shadow:0 3px 6px rgba(5,23,47,.2)}

CSS;
        return '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"/>'
            . '<style>' . $this->fontCss() . $css . '</style></head><body>'
            . '<main class="wa-poster" style="background-image:url(data:image/jpeg;base64,' . $image . ')">'
            . '<div class="wa-time wa-time-' . $kind . '">' . $clock . '</div>'
            . $body . '</main></body></html>';
    }
}
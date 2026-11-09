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

    private function marketCards(array $rows): string
    {
        $icons = [
            'usd'=>'$', 'eur'=>'€', 'gbp'=>'£', 'aed'=>'د.إ', 'try'=>'₺',
            'usdt'=>'₮', 'gold_18'=>'۱۸', 'gold_24'=>'۲۴', 'gold_ounce'=>'Au',
            'melted_gold'=>'Au', 'mesghal_gold'=>'Au',
            'new_coin'=>'◆', 'old_coin'=>'◆', 'half_coin'=>'◈', 'quarter_coin'=>'◇',
        ];
        $codes = [
            'usd'=>'USD', 'eur'=>'EUR', 'gbp'=>'GBP', 'aed'=>'AED', 'try'=>'TRY',
            'usdt'=>'USDT', 'gold_18'=>'GOLD', 'gold_24'=>'GOLD',
            'gold_ounce'=>'XAU', 'melted_gold'=>'GOLD', 'mesghal_gold'=>'GOLD',
            'new_coin'=>'سکه', 'old_coin'=>'سکه', 'half_coin'=>'سکه', 'quarter_coin'=>'سکه',
        ];
        $html = '';
        foreach (array_slice($rows, 0, 15) as $index => $row) {
            $value = $row['value'] ?? null;
            $unit = (string) ($row['unit'] ?? 'تومان');
            $key = (string) ($row['key'] ?? '');
            $icon = $icons[$key] ?? '◈';
            $code = $codes[$key] ?? '';
            $name = $this->e($row['name'] ?? '');
            $number = $this->e($this->price($value, $unit));
            $unitText = $value === null ? 'نرخ موجود نیست' : $this->e($unit);
            $change = $row['change'] ?? null;
            $indicator = '';
            if (is_numeric($change) && is_numeric($value) && $value > 0 && abs((float)$change) > 0) {
                $previous = (float)$value - (float)$change;
                if ($previous > 0) {
                    $percent = abs((float)$change / $previous * 100);
                    if ($percent <= 100) {
                        $direction = $change > 0 ? 'up' : 'down';
                        $glyph = $change > 0 ? '▲' : '▼';
                        $indicator = '<span class="indicator ' . $direction . '">'
                            . $glyph . ' ' . $this->e($this->fa(number_format($percent, 2))) . '٪</span>';
                    }
                }
            }
            $last = $index === 14 ? ' last-card' : '';
            $html .= '<article class="wa-market-card' . $last . '">'
                . '<div class="wa-market-icon">' . $this->e($icon) . '</div>'
                . '<div class="wa-market-name"><strong>' . $name . '</strong><small>' . $this->e($code) . '</small></div>'
                . '<i class="wa-market-sep"></i>'
                . '<div class="wa-market-value"><b>' . $number . '</b><small>' . $unitText . '</small>'
                . $indicator . '</div></article>';
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
            $html .= '<article class="wa-city-card"><span class="wa-pin">⌖</span>'
                . '<span class="wa-city-name">' . $this->e($title) . '</span>'
                . '<span class="wa-city-divider"></span>'
                . '<span class="wa-city-value"><small>میانگین قیمت هر متر</small>'
                . '<strong>' . $number . '</strong><em>میلیون تومان</em></span>'
                . '</article>';
        }
        if ($total > 1) {
            $html .= '<div class="wa-city-pages">' . $this->fa("صفحه $page از $total") . '</div>';
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
                ) . '</div></div>';
        $clock = $this->e($this->clock());
        $css = <<<'CSS'
*{box-sizing:border-box}
html,body{width:1122px;height:1402px;margin:0;overflow:hidden}
body{font-family:'SPAD-IRANSans',Tahoma,sans-serif;direction:rtl;color:#071b39;background:#062d50}
.wa-poster{position:relative;width:1122px;height:1402px;background-position:center;background-size:1122px 1402px;background-repeat:no-repeat;overflow:hidden}
.wa-time{position:absolute;z-index:5;display:flex;align-items:center;justify-content:center;white-space:nowrap;font-weight:500;color:white;font-size:26px;letter-spacing:0;border-radius:42px;background:linear-gradient(160deg,#0d649d 0%,#052c59 48%,#08213f 100%);border:2px solid #e4bb68;box-shadow:0 2px 14px rgba(255,214,133,.55),inset 0 1px 5px rgba(255,255,255,.22)}
.wa-time-market{top:292px;left:326px;width:454px;height:59px}
.wa-time-city{top:324px;left:359px;width:521px;height:61px}
.wa-market-grid{position:absolute;top:362px;left:54px;width:1016px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));grid-auto-rows:84px;gap:7px 14px;direction:rtl}
.wa-market-card{position:relative;width:100%;height:84px;display:flex;align-items:center;padding:8px 15px 8px 11px;gap:10px;border-radius:22px;border:1.5px solid rgba(255,255,255,.96);background:linear-gradient(145deg,#f5faff,#d5e8f9 75%,#c7dbec);box-shadow:0 5px 8px rgba(0,12,38,.18),inset 0 1px 4px #fff;overflow:hidden}
.wa-market-card.last-card{grid-column:1/-1;width:72%;height:65px;justify-self:center}
.wa-market-icon{flex:0 0 57px;height:57px;border-radius:50%;background:linear-gradient(140deg,#068dde,#06366c);border:2px solid #fff;box-shadow:0 2px 4px #7b95ac;color:#fff;display:flex;align-items:center;justify-content:center;font-family:Arial,sans-serif;font-weight:700;font-size:29px}
.wa-market-name{flex:1 1 0;min-width:0;display:flex;flex-direction:column;align-items:flex-start;justify-content:center}
.wa-market-name strong{font-size:19px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;line-height:1.45}
.wa-market-name small{font:17px Arial,sans-serif;color:#486889;direction:ltr;align-self:flex-start}
.wa-market-sep{height:60%;width:1px;background:#6686a4;flex:0 0 1px}
.wa-market-value{flex:0 0 44%;min-width:0;display:flex;flex-wrap:wrap;column-gap:5px;align-content:center;align-items:center;justify-content:flex-start;direction:rtl}
.wa-market-value b{font-size:25px;font-weight:700;line-height:1.2;white-space:nowrap;letter-spacing:-.3px;direction:ltr}
.wa-market-value small{font-size:13px;font-weight:500;white-space:nowrap}
.wa-market-value .indicator{display:block;flex-basis:100%;width:max-content;max-width:115px;margin-top:2px;text-align:center;border-radius:22px;font-size:13px;line-height:23px;font-weight:500;direction:ltr}
.wa-market-value .indicator.up{color:#008326;background:#bce8bd}
.wa-market-value .indicator.down{color:#ad2222;background:#facaca}
.wa-city-panel{position:absolute;top:395px;left:264px;width:811px;height:804px;border-radius:42px;border:2px solid #f4fbff;box-shadow:0 3px 13px rgba(3,29,75,.3),inset 0 2px 9px #fff;background:linear-gradient(155deg,#f4faff,#d3e9f9);padding:14px 17px 15px;overflow:hidden}
.wa-city-grid{width:100%;height:100%;display:flex;flex-direction:column;gap:5px}
.wa-city-card{flex:1 1 0;min-height:0;display:flex;align-items:center;gap:11px;padding:4px 10px;border-radius:24px;border:1.5px solid #fff;background:linear-gradient(115deg,#eef7ff,#c7e1f6);box-shadow:0 2px 6px rgba(28,90,148,.16);overflow:hidden}
.wa-pin{width:39px;height:39px;border-radius:13px;background:#087bb9;display:flex;align-items:center;justify-content:center;font:30px Arial,sans-serif;color:white;box-shadow:0 2px 4px rgba(0,26,72,.2)}
.wa-city-name{min-width:0;flex:1 1 0;font-size:23px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-align:right}
.wa-city-divider{width:1px;height:65%;flex:0 0 1px;background:#819ebd}
.wa-city-value{flex:0 0 42%;height:100%;min-width:0;display:flex;align-items:center;justify-content:center;gap:6px;white-space:nowrap;border-radius:21px;background:linear-gradient(110deg,#d5ebfa,#b4daef);padding:0 7px}
.wa-city-value small{font-size:10px;font-weight:400;white-space:normal;line-height:1.5;max-width:78px}
.wa-city-value strong{font-size:28px;font-weight:700;line-height:1}
.wa-city-value em{font-size:15px;font-weight:500;font-style:normal}
.wa-city-pages{position:absolute;bottom:3px;left:16px;font-size:12px;color:#315f90}
CSS;
        return '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"/>'
            . '<style>' . $this->fontCss() . $css . '</style></head><body>'
            . '<main class="wa-poster" style="background-image:url(data:image/jpeg;base64,' . $image . ')">'
            . '<div class="wa-time wa-time-' . $kind . '">' . $clock . '</div>'
            . $body . '</main></body></html>';
    }
}
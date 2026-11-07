<?php

namespace App\Services\VirtualTour;

/**
 * SPAD 360 ready-made tour: non-destructive presentation patch (v3).
 * Applies only to the recognized SPAD_TOUR package format; foreign ZIP tours
 * are left unchanged. Stored ZIP files remain intact for rollback.
 */
final class SpadTourTemplateV3
{
    public static function apply(string $asset, string $source): string
    {
        if ($asset === 'scenes.js' && str_contains($source, 'window.SPAD_TOUR')) {
            // Every room enters at the furthest permitted zoom-out.
            return preg_replace('/\bmaxFov\s*:\s*\d+(?:\.\d+)?/', 'maxFov: 120', $source, 1) ?: $source;
        }

        if ($asset === 'app.js' && str_contains($source, 'const config=window.SPAD_TOUR;')) {
            return self::patchPlayer($source);
        }

        if ($asset === 'index.html' && str_contains($source, 'id="sceneHeading"')) {
            if (!str_contains($source, 'id="sceneIcon"')) {
                $source = preg_replace(
                    '~<strong id="sceneHeading">([^<]*)</strong>~u',
                    '<span class="scene-heading-title"><img id="sceneIcon" class="scene-heading-image" src="assets/thumbs/0.webp" alt="تصویر فضای انتخاب‌شده"><strong id="sceneHeading">$1</strong></span>',
                    $source, 1
                ) ?: $source;
            }
            return $source;
        }

        if ($asset === 'styles.css' && str_contains($source, '.welcome-box')) {
            // The ready-made ZIP has no bundled site webfont. The sandboxed
            // player needs an explicit @font-face, including on iOS/Android.
            if (!str_contains($source, 'SPAD 360 template v3')) {
                $source .= "\n" . self::styles();
            }
            if (!str_contains($source, 'SPAD_IRANSANS_FONT_V4')) {
                $source .= "\n" . self::fontStyles();
            }
            return $source;
        }

        return $source;
    }

    private static function patchPlayer(string $js): string
    {
        $replacements = [
            'fov=s.fov||72;' => 'fov=config.maxFov;',
            'fov=scene.fov||72;' => 'fov=config.maxFov;',
            "$('sceneHeading').textContent=s.name;" => "$('sceneHeading').textContent=s.name;const icon=$('sceneIcon');if(icon){icon.src=`assets/thumbs/\${s.index}.webp`;icon.alt=`تصویر \${s.name}`}",
            '<span class="thumb-name">${s.name}</span>' => '<span class="thumb-name"><img class="thumb-room-icon" src="assets/thumbs/${s.index}.webp" alt="" loading="lazy">${s.name}</span>',
            '<span class="hotspot-circle">${svg(\'walk\')}</span>' => '<span class="hotspot-circle"><img class="hotspot-room-icon" src="assets/thumbs/${byId[link.to].index}.webp" alt="" loading="lazy"></span>',
        ];
        foreach ($replacements as $before => $after) {
            if ($before === "$('sceneHeading').textContent=s.name;" && str_contains($js, "const icon=$('sceneIcon')")) continue;
            if (str_contains($js, $before)) {
                $js = str_replace($before, $after, $js);
            }
        }
        return $js;
    }

    /**
     * Match the public site's IRANSans FaNum webfont. CSS-family fallbacks
     * alone never load fonts into an isolated iframe, especially on mobile.
     */
    private static function fontStyles(): string
    {
        return <<<'CSS'
/* SPAD_IRANSANS_FONT_V4 - unified mobile/tablet/desktop typography */
@font-face{font-family:"SPAD-IRANSans-FaNum";src:url("/api/virtual-tours/fonts/regular") format("woff");font-weight:400;font-style:normal;font-display:swap}
@font-face{font-family:"SPAD-IRANSans-FaNum";src:url("/api/virtual-tours/fonts/medium") format("woff");font-weight:500;font-style:normal;font-display:swap}
@font-face{font-family:"SPAD-IRANSans-FaNum";src:url("/api/virtual-tours/fonts/bold") format("woff");font-weight:700;font-style:normal;font-display:swap}
@font-face{font-family:"SPAD-IRANSans-FaNum";src:url("/api/virtual-tours/fonts/black") format("woff");font-weight:900;font-style:normal;font-display:swap}
@font-face{font-family:"SPAD-IRANSans-FaNum";src:url("/api/virtual-tours/fonts/light") format("woff");font-weight:300;font-style:normal;font-display:swap}
@font-face{font-family:"SPAD-IRANSans-FaNum";src:url("/api/virtual-tours/fonts/ultralight") format("woff");font-weight:100;font-style:normal;font-display:swap}
html,body,body *,button,input,textarea,select,option{
 font-family:"SPAD-IRANSans-FaNum","IRANSans",sans-serif!important;
}
CSS;
    }

    private static function styles(): string
    {
        return <<<'CSS'
/* SPAD 360 template v3 — compact welcome and scene-specific photographic icons. */
.welcome-box{max-width:430px;padding:23px 24px 19px;border-radius:21px}
.welcome-emblem{font-size:10px;padding:5px 11px}
.welcome-box h1{font-size:25px;line-height:1.55;margin:9px 0}
.welcome-box p{font-size:11px;line-height:1.85;max-width:340px;margin:0 auto 15px}
.welcome-instructions{gap:6px}
.welcome-instructions>div{padding:9px 5px 8px;font-size:9px;gap:6px;border-radius:10px}
.welcome-instructions svg{width:18px;height:18px}
.welcome-start{margin-top:15px;padding:12px 15px;font-size:12px;border-radius:11px}
.welcome-box small{font-size:9px;margin-top:10px}
.scene-heading-title{display:inline-flex;align-items:center;justify-content:center;gap:9px}
.scene-heading-image{width:34px;height:34px;object-fit:cover;border-radius:10px;border:1px solid rgba(255,255,255,.65);box-shadow:0 2px 12px #07162970}
.thumb-name{display:flex;align-items:center;gap:6px;max-width:calc(100% - 14px)}
.thumb-room-icon{width:23px!important;height:23px!important;object-fit:cover;border:1px solid rgba(255,255,255,.75);border-radius:7px;flex:0 0 23px;box-shadow:0 2px 7px #001a}
.hotspot-room-icon{width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;border:2px solid #d2f1ff}
@media(max-width:490px){
 .welcome-backdrop{padding:11px}
 .welcome-box{max-width:370px;padding:18px 15px 16px;border-radius:17px;max-height:calc(100dvh - 22px);overflow-y:auto}
 .welcome-box h1{font-size:22px;line-height:1.45;margin:8px 0}
 .welcome-box p{font-size:10px;line-height:1.7;margin-bottom:11px}
 .welcome-instructions>div{padding:8px 2px 7px;font-size:8px;gap:5px}
 .welcome-instructions svg{width:16px;height:16px}
 .welcome-start{margin-top:12px;padding:11px;font-size:12px}
 .welcome-box small{margin-top:7px}
 .scene-heading-image{width:27px;height:27px;border-radius:8px}
 .thumb-room-icon{width:19px!important;height:19px!important;flex-basis:19px}
}
@media(max-height:560px) and (orientation:landscape){
 .welcome-box{max-width:410px;max-height:calc(100dvh - 14px);overflow-y:auto;padding:13px 16px}
 .welcome-box h1{font-size:18px;margin:3px 0}
 .welcome-box p{font-size:9px;margin-bottom:6px}
 .welcome-instructions>div{padding:4px;gap:3px}
 .welcome-start{margin-top:7px;padding:8px}
 .welcome-box small{margin-top:4px}
}
CSS;
    }
}
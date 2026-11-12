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

        if ($asset === 'app.js' && (
            str_contains($source, 'const config=window.SPAD_TOUR;')
            || (str_contains($source, 'SPAD 360 reusable, independent and offline-ready engine')
                && str_contains($source, 'function uploadGpuTiles(im){'))
        )) {
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

        if ($asset === 'styles.css' && str_contains($source, '.sceneInfo') && str_contains($source, '.dpad')
            && str_contains($source, '#topbar') && !str_contains($source, 'SPAD_TOUR_FRAME_V5')) {
            // Player layout: chip, spaces button, rotation pad, scenes and
            // toolbar each get one fixed place. Inert until app.js marks the
            // page (html.spadFrame), so a failed script keeps the old layout.
            $source .= "\n" . self::frameStyles();
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
        $js = self::patchIosStability($js);
        if (str_contains($js, '$(\'sceneCount\')')) {
            if (!str_contains($js, 'SPAD_TOUR_FRAME_V5')) $js .= "\n" . self::frameScript();
        }
        return $js;
    }

    /**
     * This patch is applied only while serving recognized SPAD packages.
     * The uploaded ZIP is never modified; original image pixels stay on disk.
     *
     * Phones load the package's own mobile panorama (4096x2048 or 3072x1536
     * WebP, a few hundred KB) instead of the 6000x3000 HQ JPEG (7-10 MB),
     * falling back to the HQ file if it is missing. iPhone WebKit cannot
     * hold a canvas above 16.7 megapixels: an 18 MP HQ panorama copied
     * through one canvas became uninitialised GPU memory (dark streaks), so
     * iOS uploads one texture of at most 4096 px, straight from the image
     * when it fits. iPhone WebKit also cannot fullscreen a div.
     */
    private static function patchIosStability(string $js): string
    {
        if (!str_contains($js, 'function uploadGpuTiles(im){')
            || !str_contains($js, "document.documentElement.requestFullscreen()")
            || str_contains($js, 'SPAD_IOS_STABLE_RENDER_V2')) return $js;

        $js = str_replace('  function initGL(){',
            "  // SPAD_IOS_STABLE_RENDER_V2: capability-specific viewport and GPU protection.\n" .
            "  const SPAD_IOS_SAFE=/iPhone|iPad|iPod/i.test(navigator.userAgent)||(/Macintosh/i.test(navigator.userAgent)&&navigator.maxTouchPoints>1);\n" .
            "  function initGL(){", $js);
        // Preserve the WebGL drawing buffer on WebKit; its asynchronous
        // compositing of WebGL canvas over touch overlays can expose black tiles.
        $js = str_replace('preserveDrawingBuffer:false,depth:false',
            'preserveDrawingBuffer:SPAD_IOS_SAFE,depth:false', $js);
        $js = str_replace("powerPreference:'high-performance',desynchronized:true",
            "powerPreference:SPAD_IOS_SAFE?'default':'high-performance',desynchronized:!SPAD_IOS_SAFE", $js);
        $js = str_replace('const maxPixels=coarse?4400000:8500000;',
            'const maxPixels=coarse?(SPAD_IOS_SAFE?3000000:4400000):8500000;', $js);

        $fetchBefore = "  async function fetchSceneImage(s,mobile){\n    const preferred=s.src;";
        $fetchAfter = "  async function fetchSceneImage(s,mobile){\n" .
            "    if((mobile||SPAD_IOS_SAFE)&&s.mobileSrc&&s.mobileSrc!==s.src&&location.protocol!=='file:'&&!window.SPAD360_FORCE_EMBEDDED){\n" .
            "      try{return await getImage(s.mobileSrc)}catch(e){console.warn('SPAD 360 mobile panorama failed, using full size',e)}\n" .
            "    }\n" .
            "    const preferred=s.src;";
        $js = str_replace($fetchBefore, $fetchAfter, $js);

        $iosGpu = <<<'JS'
  function uploadGpuTiles(im){
    // iOS: one RGB texture of at most 4096 px. Uploaded straight from the
    // decoded image when it fits; otherwise scaled once through a canvas
    // that stays far below WebKit's 16.7 MP canvas limit.
    if(SPAD_IOS_SAFE&&gl){
      const maxEdge=Math.min(4096,Number(gl.getParameter(gl.MAX_TEXTURE_SIZE)||2048));
      const w=im.naturalWidth,h=im.naturalHeight;
      const scale=Math.min(1,maxEdge/w,maxEdge/h);
      let gpuCanvas=null,source=im;
      try{
        if(scale<1){
          gpuCanvas=document.createElement('canvas');
          gpuCanvas.width=Math.max(2,Math.round(w*scale));
          gpuCanvas.height=Math.max(2,Math.round(h*scale));
          const c=gpuCanvas.getContext('2d',{alpha:false});
          if(!c)throw Error('No panorama conversion context');
          c.imageSmoothingQuality='high';
          c.drawImage(im,0,0,gpuCanvas.width,gpuCanvas.height);
          source=gpuCanvas;
        }
        releaseGpuTiles();
        const tex=gl.createTexture();
        if(!tex)throw Error('No GPU texture');
        glTiles.push(tex);gl.activeTexture(gl.TEXTURE0);gl.bindTexture(gl.TEXTURE_2D,tex);
        gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL,false);
        gl.pixelStorei(gl.UNPACK_ALIGNMENT,1);
        gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_WRAP_S,gl.CLAMP_TO_EDGE);
        gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_WRAP_T,gl.CLAMP_TO_EDGE);
        gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_MIN_FILTER,gl.LINEAR);
        gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_MAG_FILTER,gl.LINEAR);
        gl.texImage2D(gl.TEXTURE_2D,0,gl.RGB,gl.RGB,gl.UNSIGNED_BYTE,source);
        if(gl.getError()!==gl.NO_ERROR)throw Error('iOS texture upload failed');
        // Every sampler unit is complete even when the GLSL compiler
        // does not fold away branches for the single texture.
        const maxUnits=Math.min(8,Number(gl.getParameter(gl.MAX_TEXTURE_IMAGE_UNITS)||8));
        for(let n=1;n<maxUnits;n++){gl.activeTexture(gl.TEXTURE0+n);gl.bindTexture(gl.TEXTURE_2D,tex);}
        gl.activeTexture(gl.TEXTURE0);
        glTileGrid=[1,1];glReady=true;dirty=true;
        return true;
      }catch(e){
        console.warn('SPAD iOS GPU fallback',e);
        releaseGpuTiles();
        return false;
      }finally{
        if(gpuCanvas){gpuCanvas.width=1;gpuCanvas.height=1;}
      }
    }
JS;
        $js = str_replace('  function uploadGpuTiles(im){', $iosGpu, $js);
        $oldFullscreen = "async function fullscreen(){try{if(document.fullscreenElement)await document.exitFullscreen();else await document.documentElement.requestFullscreen()}catch(_){toast('تمام‌صفحه در این مرورگر فعال نشد')}}";
        $newFullscreen = <<<'JS'
async function fullscreen(){
    if(window.parent!==window&&(SPAD_IOS_SAFE||!document.fullscreenEnabled)){
      // Parent route verifies event.source, then sends fixed event to
      // first-party property viewer. Do not request fullscreen of gallery.
      window.parent.postMessage({type:'SPAD_TOUR_REQUEST_IMMERSIVE'},'*');
      return;
    }
    try{
      if(document.fullscreenElement)await document.exitFullscreen();
      else if(document.documentElement.requestFullscreen)await document.documentElement.requestFullscreen();
      else if(SPAD_IOS_SAFE)document.documentElement.classList.toggle('spad-ios-tour-viewport');
    }catch(_){
      if(SPAD_IOS_SAFE)document.documentElement.classList.toggle('spad-ios-tour-viewport');
      else toast('تمام‌صفحه در این مرورگر فعال نشد');
    }
  }
JS;
        $js = str_replace($oldFullscreen, $newFullscreen, $js);
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

    /**
     * Player chrome, v5 (SPAD_TOUR_FRAME_V5): the room chip top right, the
     * spaces button top left, and one stack at the bottom: rotation pad and
     * room strip, then a single toolbar. Nothing overlaps; the property page's
     * own controls live outside the frame. Every rule is scoped to
     * html.spadFrame, which only the script below sets.
     */
    private static function frameStyles(): string
    {
        return <<<'CSS'
/* SPAD_TOUR_FRAME_V5 */
html.spadFrame #watermark,html.spadFrame .brand{display:none!important}
html.spadFrame #topbar{position:fixed!important;top:max(10px,env(safe-area-inset-top))!important;left:10px!important;right:10px!important;bottom:auto!important;display:flex!important;flex-direction:row!important;justify-content:space-between!important;align-items:flex-start!important;gap:8px!important;z-index:14!important;pointer-events:none!important}
html.spadFrame #topbar>*{pointer-events:auto}
html.spadFrame #sceneInfo{position:static!important;inset:auto!important;transform:none!important;margin:0!important;min-width:0!important;max-width:min(300px,62vw)!important;display:flex!important;align-items:center!important;gap:9px!important;padding:5px 5px 5px 13px!important;border-radius:13px!important;background:rgba(5,24,44,.72)!important;border:1px solid rgba(255,255,255,.14)!important;box-shadow:none!important;pointer-events:none!important}
html.spadFrame #sceneInfo.hidden{display:none!important}
html.spadFrame #sceneInfo .sceneCounter,html.spadFrame #sceneInfo .sceneHeadingThumb{display:none!important}
html.spadFrame #sceneInfo>div{min-width:0}
html.spadFrame #sceneInfo b{display:block!important;font-size:12.5px!important;font-weight:500!important;line-height:1.6!important;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
html.spadFrame #sceneInfo small{display:block!important;margin:0!important;font-size:10.5px!important;color:#a9c6e2!important;white-space:nowrap}
html.spadFrame .spadChipImg{flex:none;width:34px;height:34px;border-radius:9px;object-fit:cover;border:1px solid rgba(255,255,255,.35)}
html.spadFrame .topActions{display:flex!important;gap:6px!important;padding:0!important;border:0!important;background:none!important;box-shadow:none!important;-webkit-backdrop-filter:none!important;backdrop-filter:none!important}
html.spadFrame .topActions .iconBtn{width:44px!important;min-width:44px!important;height:44px!important;padding:0!important;border-radius:13px!important;border:1px solid rgba(255,255,255,.14)!important;background:rgba(5,24,44,.72)!important;color:#fff!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:7px!important}
html.spadFrame .topActions .spadSpaces{width:auto!important;padding:0 13px!important}
html.spadFrame .topActions .spadSpaces b{font-size:12px!important;font-weight:400!important}
html.spadFrame .topActions svg{width:16px!important;height:16px!important}
html.spadFrame.spadEmbedded #topFullscreen{display:none!important}
html.spadFrame .bottomBar{position:fixed!important;top:auto!important;left:10px!important;right:10px!important;bottom:max(10px,env(safe-area-inset-bottom))!important;display:flex!important;flex-direction:column!important;align-items:stretch!important;gap:8px!important;z-index:9!important;pointer-events:none!important}
html.spadFrame .bottomBar>*,html.spadFrame .spadUpper>*{pointer-events:auto}
html.spadFrame .spadUpper{display:flex;align-items:flex-end;gap:8px;width:100%;min-width:0;pointer-events:none}
html.spadFrame #filmstrip{flex:1 1 auto!important;min-width:0!important;width:auto!important;height:auto!important;margin:0!important;padding:6px!important;gap:6px!important;border-radius:13px!important;background:rgba(5,24,44,.72)!important;border:1px solid rgba(255,255,255,.14)!important}
html.spadFrame .sceneCard{width:112px!important;min-width:112px!important;height:auto!important;padding:0!important;gap:0!important;border-radius:9px!important;background:#0b2a48!important;border:1px solid rgba(255,255,255,.12)!important;overflow:hidden!important}
html.spadFrame .sceneCard.active{border-color:#38a8ff!important;box-shadow:0 0 0 1px #38a8ff!important}
html.spadFrame .sceneCard img{height:58px!important;border-radius:0!important}
html.spadFrame .sceneCard span{font-size:10.5px!important;line-height:1.5!important;padding:3px 7px!important;text-align:right!important;color:#d6e6f5!important}
html.spadFrame .dpad{position:relative!important;inset:auto!important;transform:none!important;flex:none!important;margin:0 auto 0 0!important;width:92px!important;height:92px!important;padding:7px!important;gap:2px!important;border-radius:50%!important;opacity:1!important;display:grid!important;grid-template:repeat(3,minmax(0,1fr))/repeat(3,minmax(0,1fr))!important;background:rgba(5,24,44,.72)!important;border:1px solid rgba(255,255,255,.14)!important;box-shadow:none!important}
html.spadFrame .dpad .iconBtn{width:100%!important;height:100%!important;min-width:0!important;min-height:0!important;padding:0!important;border:0!important;border-radius:50%!important;background:rgba(255,255,255,.08)!important}
html.spadFrame .dpad .iconBtn svg{width:14px!important;height:14px!important}
html.spadFrame .dpad .center{display:grid!important;place-items:center!important}
html.spadFrame .dpad .center svg{display:none!important}
html.spadFrame .dpad .center:after{content:"";width:12px;height:12px;border-radius:50%;background:#0984e3}
html.spadFrame .toolbar{position:relative!important;width:100%!important;margin:0!important;min-height:0!important;padding:5px!important;border-radius:14px!important;display:flex!important;flex-wrap:nowrap!important;justify-content:space-between!important;align-items:center!important;gap:6px!important;overflow:visible!important;background:rgba(5,24,44,.72)!important;border:1px solid rgba(255,255,255,.14)!important}
html.spadFrame .toolbar .toolGroup{display:flex!important;align-items:center!important;gap:4px!important;flex:none!important}
html.spadFrame .toolbar .iconBtn,html.spadFrame .toolbar .toolWithText{width:auto!important;min-width:36px!important;height:36px!important;padding:0 9px!important;border-radius:10px!important;border:0!important;background:rgba(255,255,255,.07)!important;color:#e6f1fb!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:6px!important}
html.spadFrame .toolbar .iconBtn.active{background:rgba(56,168,255,.24)!important;color:#9fd6ff!important}
html.spadFrame .toolbar #nextScene{background:linear-gradient(135deg,#0984e3,#0569c7)!important;color:#fff!important}
html.spadFrame .toolbar svg{width:16px!important;height:16px!important;flex:none}
html.spadFrame .toolbar b,html.spadFrame .spadLabelWide,html.spadFrame .spadLabelMenu{font-size:12px!important;font-weight:400!important;white-space:nowrap}
html.spadFrame .spadLabelMenu{display:none}
html.spadFrame .spadCount{padding:0 4px;color:#a9c6e2;font-size:11.5px;white-space:nowrap}
html.spadFrame .spadExtra{display:contents}
html.spadFrame .toolbar .spadMoreBtn{display:none!important}
@media(max-width:700px){
html.spadFrame #topbar{left:8px!important;right:8px!important;top:max(8px,env(safe-area-inset-top))!important}
html.spadFrame .bottomBar{left:8px!important;right:8px!important;bottom:max(8px,env(safe-area-inset-bottom))!important;gap:6px!important}
html.spadFrame #sceneInfo{max-width:calc(100vw - 132px)!important}
html.spadFrame .topActions .iconBtn{height:42px!important;width:42px!important;min-width:42px!important}
html.spadFrame .topActions .spadSpaces{width:auto!important;padding:0 12px!important}
html.spadFrame .sceneCard{width:88px!important;min-width:88px!important}
html.spadFrame .sceneCard img{height:44px!important}
html.spadFrame .dpad{width:80px!important;height:80px!important;padding:6px!important}
html.spadFrame .toolbar{padding:4px!important;gap:4px!important}
html.spadFrame .toolbar .toolGroup{gap:3px!important}
html.spadFrame .toolbar .iconBtn,html.spadFrame .toolbar .toolWithText{width:34px!important;min-width:34px!important;height:34px!important;padding:0!important}
html.spadFrame .toolbar b,html.spadFrame .spadLabelWide{display:none!important}
html.spadFrame .toolbar .spadMoreBtn{display:inline-flex!important}
html.spadFrame .spadExtra{display:none;position:absolute;left:4px;bottom:calc(100% + 8px);z-index:20;min-width:176px;flex-direction:column;gap:3px;padding:6px;border-radius:13px;background:rgba(5,24,44,.94);border:1px solid rgba(255,255,255,.14)}
html.spadFrame .spadExtra.open{display:flex}
html.spadFrame .spadExtra .iconBtn{width:100%!important;min-width:0!important;height:38px!important;padding:0 10px!important;justify-content:flex-start!important;gap:9px!important}
html.spadFrame .spadExtra .spadLabelMenu{display:inline!important}
}
@media(max-width:360px){html.spadFrame .spadCount{display:none}}
@media(max-height:519px){html.spadFrame #filmstrip{display:none!important}}
@media(max-height:329px){html.spadFrame .dpad{display:none!important}}
CSS;
    }

    private static function frameScript(): string
    {
        return <<<'JS'
/* SPAD_TOUR_FRAME_V5: one fixed place per control (see styles.css). */
(()=>{try{
  const $=id=>document.getElementById(id),root=document.documentElement;
  const top=$('topbar'),info=$('sceneInfo'),bottom=$('bottom'),strip=$('filmstrip'),counter=$('sceneCount');
  const pad=document.querySelector('.dpad'),bar=document.querySelector('#bottom .toolbar');
  if(!top||!info||!bottom||!strip||!counter||!pad||!bar)return;
  // Inside the property page (page > tour route > this player), the page
  // already offers full screen and sharing, so the player hides its copies.
  let embedded=false;try{embedded=window.parent!==window&&window.parent.parent!==window.parent}catch(_){}
  const icon=d=>`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${d}</svg>`;
  const el=(tag,cls)=>{const n=document.createElement(tag);if(cls)n.className=cls;return n};
  const chipImg=el('img','spadChipImg');chipImg.alt='';chipImg.decoding='async';
  info.insertBefore(chipImg,info.firstChild);
  top.insertBefore(info,top.firstChild);
  const spaces=$('openManager');
  if(spaces){spaces.classList.add('spadSpaces');spaces.title='فهرست فضاها';spaces.setAttribute('aria-label','فهرست فضاها');
    spaces.innerHTML=icon('<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>')+'<b>فضاها</b>'}
  const upper=el('div','spadUpper');upper.append(strip,pad);bottom.insertBefore(upper,bottom.firstChild);
  const label=(n,text,cls)=>{if(!n)return;const s=el('span',cls);s.textContent=text;n.appendChild(s)};
  const nav=el('div','toolGroup spadNav'),zoom=el('div','toolGroup spadZoom'),modes=el('div','toolGroup spadModes');
  const count=el('span','spadCount');count.setAttribute('aria-hidden','true');
  [$('previousScene'),count,$('nextScene')].forEach(n=>n&&nav.appendChild(n));
  [$('zoomOut'),$('zoomIn')].forEach(n=>n&&zoom.appendChild(n));
  label($('spinButton'),'چرخش خودکار','spadLabelWide');label($('tourAuto'),'بازدید خودکار','spadLabelWide');
  const extra=el('div','spadExtra');extra.id='spadExtra';
  [['resetView','نمای اولیه'],['musicButton','موسیقی ملایم'],['takePhoto','ثبت عکس'],['shareTour','اشتراک‌گذاری']].forEach(([id,text])=>{
    const n=$(id);if(!n)return;if(id==='shareTour'&&embedded)n.style.setProperty('display','none','important');label(n,text,'spadLabelMenu');extra.appendChild(n)});
  const more=el('button','iconBtn spadMoreBtn');more.type='button';more.title='ابزارهای بیشتر';
  more.setAttribute('aria-label','ابزارهای بیشتر');more.setAttribute('aria-expanded','false');more.setAttribute('aria-controls','spadExtra');
  more.innerHTML=icon('<circle cx="5" cy="12" r="1.3"/><circle cx="12" cy="12" r="1.3"/><circle cx="19" cy="12" r="1.3"/>');
  const closeMore=()=>{extra.classList.remove('open');more.setAttribute('aria-expanded','false')};
  more.onclick=e=>{e.stopPropagation();const open=!extra.classList.contains('open');extra.classList.toggle('open',open);more.setAttribute('aria-expanded',String(open))};
  extra.addEventListener('click',()=>setTimeout(closeMore,0));
  document.addEventListener('pointerdown',e=>{if(!extra.contains(e.target)&&!more.contains(e.target))closeMore()},true);
  [$('spinButton'),$('tourAuto'),extra,more,$('openEdit')].forEach(n=>n&&modes.appendChild(n));
  bar.replaceChildren(nav,zoom,modes);
  const small=info.querySelector('small');
  const sync=()=>{
    const parts=(counter.textContent||'').split('/').map(x=>x.trim());
    if(parts.length===2&&parts[0]){count.textContent=`${parts[0]} / ${parts[1]}`;if(small)small.textContent=`فضای ${parts[0]} از ${parts[1]}`}
    const active=strip.querySelector('.sceneCard.active img'),src=active&&active.getAttribute('src');
    if(src&&chipImg.getAttribute('src')!==src)chipImg.src=src;
    chipImg.style.display=chipImg.getAttribute('src')?'':'none';
  };
  const watch=new MutationObserver(()=>requestAnimationFrame(sync));
  watch.observe(counter,{childList:true,characterData:true,subtree:true});
  watch.observe(strip,{childList:true,subtree:true,attributes:true,attributeFilter:['class']});
  root.classList.add('spadFrame');if(embedded)root.classList.add('spadEmbedded');
  sync();
}catch(_){document.documentElement.classList.remove('spadFrame')}})();
JS;
    }
}

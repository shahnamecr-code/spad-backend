import React, { forwardRef, useEffect, useImperativeHandle, useRef } from "react";

const VERT = `
attribute vec2 aPosition;
varying vec2 vUv;
void main(){vUv=(aPosition+1.0)*0.5;gl_Position=vec4(aPosition,0.0,1.0);}
`;
const FRAG = `
precision highp float;
varying vec2 vUv;
uniform sampler2D uTexture;
uniform float uYaw;
uniform float uPitch;
uniform float uFov;
uniform float uAspect;
const float PI=3.141592653589793;
vec3 rotX(vec3 v,float a){float c=cos(a),s=sin(a);return vec3(v.x,c*v.y-s*v.z,s*v.y+c*v.z);}
vec3 rotY(vec3 v,float a){float c=cos(a),s=sin(a);return vec3(c*v.x+s*v.z,v.y,-s*v.x+c*v.z);}
void main(){
  vec2 n=vUv*2.0-1.0;
  float t=tan(uFov*0.5);
  vec3 ray=normalize(vec3(n.x*uAspect*t,n.y*t,-1.0));
  ray=rotX(ray,uPitch);
  ray=rotY(ray,uYaw);
  float lon=atan(ray.x,-ray.z);
  float lat=asin(clamp(ray.y,-1.0,1.0));
  vec2 uv=vec2(fract(lon/(2.0*PI)+0.5),0.5-lat/PI);
  gl_FragColor=texture2D(uTexture,uv);
}
`;

function rad(v){return v*Math.PI/180;}
function deg(v){return v*180/Math.PI;}
function clamp(v,a,b){return Math.max(a,Math.min(b,v));}
function rotateX(v,a){const c=Math.cos(a),s=Math.sin(a);return [v[0],c*v[1]-s*v[2],s*v[1]+c*v[2]];}
function rotateY(v,a){const c=Math.cos(a),s=Math.sin(a);return [c*v[0]+s*v[2],v[1],-s*v[0]+c*v[2]];}
function directionFromScreen(x,y,w,h,fov,yaw,pitch){
  const nx=(x/w)*2-1, ny=1-(y/h)*2, aspect=w/h, t=Math.tan(rad(fov)*0.5);
  let r=[nx*aspect*t,ny*t,-1]; const l=Math.hypot(...r)||1; r=r.map(x=>x/l);
  r=rotateX(r,rad(pitch)); r=rotateY(r,rad(yaw));
  return r;
}
function coordsFromDirection(r){return {yaw:deg(Math.atan2(r[0],-r[2])),pitch:deg(Math.asin(clamp(r[1],-1,1)))};}

const PanoramaEditor = forwardRef(function PanoramaEditor({src, initialYaw=0, initialPitch=0, initialFov=90, hotspots=[], onPanoramaClick, onHotspotClick, autoRotate=false}, ref){
  const wrapRef=useRef(null), canvasRef=useRef(null), stateRef=useRef({yaw:initialYaw,pitch:initialPitch,fov:initialFov,drag:false,moved:false,x:0,y:0,pointers:new Map(),pinch:0,autoRotate});
  const glRef=useRef(null), rafRef=useRef(null), orientationRef=useRef(null);

  useImperativeHandle(ref,()=>({
    getView:()=>({yaw:stateRef.current.yaw,pitch:stateRef.current.pitch,fov:stateRef.current.fov}),
    setView:(v={})=>{Object.assign(stateRef.current,v);draw();},
    toggleFullscreen:()=>wrapRef.current?.requestFullscreen?.(),
    startOrientation:async()=>{
      const D=window.DeviceOrientationEvent;
      if(!D) return false;
      if(typeof D.requestPermission==='function'){const p=await D.requestPermission();if(p!=='granted')return false;}
      if(orientationRef.current) window.removeEventListener('deviceorientation',orientationRef.current);
      orientationRef.current=(e)=>{if(e.alpha==null||e.beta==null)return;stateRef.current.yaw=e.alpha;stateRef.current.pitch=clamp(e.beta-90,-85,85);draw();};
      window.addEventListener('deviceorientation',orientationRef.current,true);return true;
    },
    stopOrientation:()=>{if(orientationRef.current)window.removeEventListener('deviceorientation',orientationRef.current);orientationRef.current=null;}
  }));

  const draw=()=>{
    const ctx=glRef.current;if(!ctx)return;const {gl,program,loc}=ctx,canvas=canvasRef.current,wrap=wrapRef.current;if(!canvas||!wrap)return;
    const dpr=Math.min(window.devicePixelRatio||1,2),w=Math.max(1,wrap.clientWidth),h=Math.max(1,wrap.clientHeight);
    if(canvas.width!==Math.round(w*dpr)||canvas.height!==Math.round(h*dpr)){canvas.width=Math.round(w*dpr);canvas.height=Math.round(h*dpr);canvas.style.width=w+'px';canvas.style.height=h+'px';gl.viewport(0,0,canvas.width,canvas.height);}
    gl.useProgram(program);gl.uniform1f(loc.yaw,rad(stateRef.current.yaw));gl.uniform1f(loc.pitch,rad(stateRef.current.pitch));gl.uniform1f(loc.fov,rad(stateRef.current.fov));gl.uniform1f(loc.aspect,w/h);gl.drawArrays(gl.TRIANGLE_STRIP,0,4);
  };

  useEffect(()=>{stateRef.current.yaw=initialYaw;stateRef.current.pitch=initialPitch;stateRef.current.fov=initialFov;draw();},[initialYaw,initialPitch,initialFov]);
  useEffect(()=>{stateRef.current.autoRotate=autoRotate;},[autoRotate]);

  useEffect(()=>{
    const canvas=canvasRef.current;const gl=canvas.getContext('webgl',{antialias:false,alpha:false,preserveDrawingBuffer:false});if(!gl)return;
    const shader=(type,source)=>{const s=gl.createShader(type);gl.shaderSource(s,source);gl.compileShader(s);if(!gl.getShaderParameter(s,gl.COMPILE_STATUS))throw new Error(gl.getShaderInfoLog(s));return s;};
    const program=gl.createProgram();gl.attachShader(program,shader(gl.VERTEX_SHADER,VERT));gl.attachShader(program,shader(gl.FRAGMENT_SHADER,FRAG));gl.linkProgram(program);
    const pos=gl.getAttribLocation(program,'aPosition'), buffer=gl.createBuffer();gl.bindBuffer(gl.ARRAY_BUFFER,buffer);gl.bufferData(gl.ARRAY_BUFFER,new Float32Array([-1,-1,1,-1,-1,1,1,1]),gl.STATIC_DRAW);gl.enableVertexAttribArray(pos);gl.vertexAttribPointer(pos,2,gl.FLOAT,false,0,0);
    const tex=gl.createTexture();gl.activeTexture(gl.TEXTURE0);gl.bindTexture(gl.TEXTURE_2D,tex);gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_WRAP_S,gl.REPEAT);gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_WRAP_T,gl.CLAMP_TO_EDGE);gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_MIN_FILTER,gl.LINEAR);gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_MAG_FILTER,gl.LINEAR);
    const pixel=new Uint8Array([22,35,50,255]);gl.texImage2D(gl.TEXTURE_2D,0,gl.RGBA,1,1,0,gl.RGBA,gl.UNSIGNED_BYTE,pixel);
    const loc={yaw:gl.getUniformLocation(program,'uYaw'),pitch:gl.getUniformLocation(program,'uPitch'),fov:gl.getUniformLocation(program,'uFov'),aspect:gl.getUniformLocation(program,'uAspect')};gl.useProgram(program);gl.uniform1i(gl.getUniformLocation(program,'uTexture'),0);
    glRef.current={gl,program,loc,tex};draw();
    const ro=new ResizeObserver(draw);ro.observe(wrapRef.current);
    let last=performance.now();const loop=(now)=>{if(stateRef.current.autoRotate&&!stateRef.current.drag){stateRef.current.yaw+=(now-last)*0.003;draw();}last=now;rafRef.current=requestAnimationFrame(loop);};rafRef.current=requestAnimationFrame(loop);
    return()=>{ro.disconnect();cancelAnimationFrame(rafRef.current);if(orientationRef.current)window.removeEventListener('deviceorientation',orientationRef.current);glRef.current=null;};
  },[]);

  useEffect(()=>{
    const ctx=glRef.current;if(!ctx||!src)return;const img=new Image();img.crossOrigin='anonymous';img.onload=()=>{const {gl,tex}=ctx;gl.bindTexture(gl.TEXTURE_2D,tex);gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL,false);gl.texImage2D(gl.TEXTURE_2D,0,gl.RGBA,gl.RGBA,gl.UNSIGNED_BYTE,img);draw();};img.src=src;
  },[src]);

  const point=(e)=>{const r=wrapRef.current.getBoundingClientRect();return {x:e.clientX-r.left,y:e.clientY-r.top};};
  const onDown=(e)=>{const s=stateRef.current,p=point(e);s.pointers.set(e.pointerId,p);s.drag=true;s.moved=false;s.x=p.x;s.y=p.y;if(s.pointers.size===2){const a=[...s.pointers.values()];s.pinch=Math.hypot(a[0].x-a[1].x,a[0].y-a[1].y);}e.currentTarget.setPointerCapture?.(e.pointerId);};
  const onMove=(e)=>{const s=stateRef.current;if(!s.pointers.has(e.pointerId))return;const p=point(e);if(Math.abs(p.x-s.x)>3||Math.abs(p.y-s.y)>3)s.moved=true;s.pointers.set(e.pointerId,p);if(s.pointers.size===2){const a=[...s.pointers.values()],d=Math.hypot(a[0].x-a[1].x,a[0].y-a[1].y);if(s.pinch){s.fov=clamp(s.fov-(d-s.pinch)*0.12,35,120);}s.pinch=d;draw();return;}s.yaw-=(p.x-s.x)*0.15;s.pitch=clamp(s.pitch+(p.y-s.y)*0.12,-85,85);s.x=p.x;s.y=p.y;draw();};
  const onUp=(e)=>{const s=stateRef.current;s.pointers.delete(e.pointerId);if(!s.pointers.size)s.drag=false;s.pinch=0;};
  const onClick=(e)=>{if(!onPanoramaClick||stateRef.current.moved)return;const r=wrapRef.current.getBoundingClientRect(),p=point(e),dir=directionFromScreen(p.x,p.y,r.width,r.height,stateRef.current.fov,stateRef.current.yaw,stateRef.current.pitch),c=coordsFromDirection(dir);onPanoramaClick(c);};
  const onWheel=(e)=>{e.preventDefault();stateRef.current.fov=clamp(stateRef.current.fov+Math.sign(e.deltaY)*4,35,120);draw();};

  const hotspotStyle=(h)=>{const r=wrapRef.current?.getBoundingClientRect();if(!r)return {display:'none'};let world=[Math.cos(rad(h.pitch))*Math.sin(rad(h.yaw)),Math.sin(rad(h.pitch)),-Math.cos(rad(h.pitch))*Math.cos(rad(h.yaw))];world=rotateY(world,rad(-stateRef.current.yaw));world=rotateX(world,rad(-stateRef.current.pitch));if(world[2]>=-0.03)return {display:'none'};const t=Math.tan(rad(stateRef.current.fov)*.5),nx=world[0]/(-world[2]*t*(r.width/r.height)),ny=world[1]/(-world[2]*t);if(Math.abs(nx)>1.2||Math.abs(ny)>1.2)return {display:'none'};return {left:`${(nx+1)*50}%`,top:`${(1-ny)*50}%`};};

  return <div className="spad-pano-editor" ref={wrapRef}>
    <canvas ref={canvasRef} onPointerDown={onDown} onPointerMove={onMove} onPointerUp={onUp} onPointerCancel={onUp} onClick={onClick} onWheel={onWheel}/>
    <div className="spad-pano-hotspots">{hotspots.map(h=><button key={h.id} type="button" style={hotspotStyle(h)} className={`spad-hotspot spad-hotspot-${h.type}`} onClick={(e)=>{e.stopPropagation();onHotspotClick&&onHotspotClick(h);}} title={h.title||h.type}><span>{iconGlyph(h)}</span><small>{h.title}</small></button>)}</div>
    <div className="spad-pano-help">Drag: چرخش • Scroll/Pinch: زوم • Click: افزودن Hotspot</div>
  </div>;
});

export default PanoramaEditor;

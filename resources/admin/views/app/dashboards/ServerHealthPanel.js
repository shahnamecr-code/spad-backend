import React, { useState } from 'react';

export default function ServerHealthPanel({info,resources,resourceError,loadingResources,loading,error,onRefresh,period,onPeriod}) {
 const [details,setDetails]=useState(false);
 const samples=Array.isArray(info?.samples)?info.samples:[];
 const recent=samples.slice(-40),last=recent.length?recent[recent.length-1]:null;
 const stale=!last||info?.latest_age_seconds===null||Number(info?.latest_age_seconds)>900;
 const frontOk=!stale&&Boolean(last?.app_ok);
 const apiOk=resources?.api_ok===true;
 const dbOk=resources?.database_ok===true;
 const fmt=v=>v===null||v===undefined?'نامشخص':Number(v).toLocaleString('fa-IR');
 const unit=bytes=>bytes===null||bytes===undefined?'نامشخص':(Number(bytes)/(1024**3)).toLocaleString('fa-IR',{maximumFractionDigits:1})+' گیگابایت';
 const ratio=v=>v===null||v===undefined?'—':Number(v).toLocaleString('fa-IR',{maximumFractionDigits:1})+'٪';
 const status=(value,notKnown=false)=>notKnown?'نامشخص':value?'در دسترس':'نیازمند بررسی';
 const observed=info?.observed_success_percent===null||info?.observed_success_percent===undefined?'—':ratio(info.observed_success_percent);
 const maxLatency=Math.max(100,...recent.map(x=>Number(x.app_ms)||0));
 const dots=recent.map((p,i)=>({x:8+(i/Math.max(1,recent.length-1))*404,
  y:84-Math.min(74,Math.max(0,(Number(p.app_ms)||0)/maxLatency*70)),
  ok:p.app_ok&&p.db_ok,time:p.at,ms:p.app_ms}));
 const points=dots.filter(x=>x.ok&&x.ms!==null);
 const tile=(key,title,value,sub,kind='normal',pct=null)=><div className={'spad-v13-ops-tile '+kind} key={key}>
  <span className="spad-v13-ops-tile__label">{title}</span>
  <strong className="spad-v13-ops-tile__value">{value}</strong>
  <small>{sub}</small>
  {pct!==null&&<div className="spad-v13-ops-tile__rail" role="meter" aria-label={title} aria-valuemin="0" aria-valuemax="100" aria-valuenow={Math.max(0,Math.min(100,Number(pct)))}>
    <span style={{width:Math.max(0,Math.min(100,Number(pct)))+'%'}}/></div>}
 </div>;
 const probeStatus=loading?'در حال بررسی':status(frontOk,stale);
 const time=resources?.uptime_seconds===null||resources?.uptime_seconds===undefined?'نامشخص':fmt(Math.floor(resources.uptime_seconds/86400))+' روز';
 return <section className="spad-v11-health spad-v13-ops" aria-label="پایش فشرده سلامت و منابع میزبان">
  <div className="spad-v13-ops-head">
   <div className="spad-v13-ops-head__title"><span className="spad-v11-health__pulse" aria-hidden="true"/>
    <strong>پایش سلامت و پایداری سامانه</strong></div>
   <div className="spad-v13-ops-head__buttons">
    <button type="button" disabled={loading||loadingResources} onClick={onRefresh} title="به‌روزرسانی اطلاعات"><i className="simple-icon-refresh"/> <span>به‌روزرسانی</span></button>
    <button type="button" onClick={()=>setDetails(v=>!v)} aria-expanded={details} aria-controls="spad-ops-history">
     <i className={details?'simple-icon-arrow-up':'simple-icon-graph'}/> {details?'بستن جزئیات':'نمودار و جزئیات'}
    </button>
   </div>
  </div>
  <div className="spad-v13-ops-grid">
   {tile('frontend','فرانت‌اند سایت',probeStatus,stale?'آخرین نمونه تازه نیست':'بررسی HTTPS دامنه اصلی',stale?'neutral':frontOk?'good':'warning')}
   {tile('backend','API و پایگاه داده',!resources?'نامشخص':apiOk&&dbOk?'پایدار':!apiOk?'نامشخص':'نیازمند بررسی',
    'API: '+(resources?status(apiOk):'نامشخص')+' | DB: '+(resources?.database_ok===null||!resources?'نامشخص':status(dbOk)),
    !resources?'neutral':apiOk&&dbOk?'good':'warning')}
   {tile('active','کاربران ورودکرده فعال',fmt(resources?.active_authenticated_users_15m),
    'حساب‌های فعال API در ۱۵ دقیقه',resources?.active_authenticated_users_15m===null||resources?.active_authenticated_users_15m===undefined?'neutral':'good')}
   {tile('disk','مصرف دیسک میزبان',ratio(resources?.disk?.used_percent),
    resources?.disk?'مصرف '+unit(resources.disk.used_bytes)+' از '+unit(resources.disk.total_bytes):'آمار دیسک در دسترس نیست',
    resources?.disk?.used_percent>=90?'warning':'normal',resources?.disk?.used_percent??null)}
   {tile('memory','مصرف رم میزبان',ratio(resources?.memory?.used_percent),
    resources?.memory?'مصرف '+unit(resources.memory.used_bytes)+' از '+unit(resources.memory.total_bytes):'آمار رم در دسترس نیست',
    resources?.memory?.used_percent>=90?'warning':'normal',resources?.memory?.used_percent??null)}
   {tile('cpu','بار پردازشی / کارکرد میزبان',resources?.cpu_load_1m===null||resources?.cpu_load_1m===undefined?'نامشخص':fmt(resources.cpu_load_1m),
    'بار میانگین ۱ دقیقه | کارکرد: '+time)}
  </div>
  <div className="spad-v13-ops-foot">
   <span><i className="simple-icon-clock"/> {info?.last_at?'آخرین پایش سایت: '+info.last_at:'نمونه پایش هنوز ثبت نشده است'}</span>
   <span>دیسک و رم: منابع میزبان، نه سهمیه حساب cPanel؛ کاربران: فقط حساب‌های احرازشده API</span>
   {(error||resourceError)&&<span className="spad-v13-ops-error">{error||resourceError}</span>}
  </div>
  {details&&<div className="spad-v13-ops-details" id="spad-ops-history">
   <div className="spad-v11-health__period" role="group" aria-label="بازه پایش">
    <button type="button" className={period==='24h'?'active':''} onClick={()=>onPeriod('24h')}>۲۴ ساعت</button>
    <button type="button" className={period==='7d'?'active':''} onClick={()=>onPeriod('7d')}>۷ روز</button>
    <span className="spad-v11-health__state">موفقیت نمونه‌ها: {observed}</span>
   </div>
   <div className="spad-v11-health__plot">
    <div className="spad-v11-health__plot-label"><b>زمان پاسخ واقعی سایت</b><small>{fmt(recent.length)} نمونه اخیر</small></div>
    {points.length>1?<svg viewBox="0 0 420 94" preserveAspectRatio="none" role="img" aria-label="نمودار تأخیر پاسخ سرویس فرانت">
     <line x1="5" y1="84" x2="415" y2="84" stroke="#ffffff22"/>
     <polyline fill="none" stroke="#7ce7f7" strokeWidth="2.3" strokeLinejoin="round"
      points={points.map(x=>x.x+','+x.y).join(' ')}/>
     {dots.map((p,i)=><circle key={p.time||i} cx={p.x} cy={p.y} r={p.ok?3:4} fill={p.ok?'#aafbec':'#ffc38b'}/>)}
    </svg>:<div className="spad-v11-health__empty">داده کافی برای رسم نمودار موجود نیست.</div>}
   </div>
   <div className="spad-v11-health__samples">
    <div><span>نمونه‌های ثبت‌شده</span><b>{fmt(info?.sample_count||0)}</b></div>
    <div><span>نمونه‌های ناموفق</span><b>{fmt(info?.failed_count||0)}</b></div>
    <div><span>پوشش نمونه‌برداری</span><b>{ratio(info?.coverage_percent)}</b></div>
   </div>
   <p className="spad-v13-ops-disclaimer">مقادیر از داده‌های واقعی سرور تهیه می‌شوند. بازه‌های بدون نمونه، قطعی محسوب نمی‌شوند. آمار CPU نشان‌دهنده بار میزبان است و درصد مصرف CPU نیست.</p>
  </div>}
 </section>;
}

import React,{useCallback,useEffect,useMemo,useState} from 'react';
import {Link} from 'react-router-dom';
import {useSelector} from 'react-redux';
import axios from 'axios';
import {adminPathApi,adminRoot} from '../../../constants/defaultValues';
const n=v=>Number(v||0).toLocaleString('fa-IR');
const list=v=>Array.isArray(v)?v:[];
const pct=(v,t)=>t>0?Math.min(100,Math.max(0,Number(v||0)/t*100)):0;
const today=()=>new Date().toLocaleDateString('fa-IR',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
const paths={
 ads:adminRoot+'/advertisements/adver-list',
 crm:adminRoot+'/crm/all-requests',
 expert:adminRoot+'/expert-requests/list',
 approval:adminRoot+'/advertisements/unapproved',
 services:adminRoot+'/web-services',
 property:adminRoot+'/building-management'
};
const Stat=({title,value,icon,link,color})=><Link to={link} className={'spad-v8-metric '+(color||'')}>
 <div className="spad-v8-metric-top"><span className="spad-v8-metric-icon"><i className={icon}/></span><i className="simple-icon-arrow-left"/></div>
 <div className="spad-v8-metric-value">{n(value)}</div><div className="spad-v8-metric-title">{title}</div>
 </Link>;
const Pane=({title,sub,children,action,klass=''})=><section className={'spad-v8-pane '+klass}>
 <div className="spad-v8-pane-head"><div><h2>{title}</h2>{sub&&<p>{sub}</p>}</div>{action}</div>{children}
 </section>;
const ServerHealthPanel=({info,loading,error,onRefresh})=>{
 const samples=info&&Array.isArray(info.samples)?info.samples:[];
 const recent=samples.slice(-32);
 const ok=recent.length?recent[recent.length-1].app_ok&&recent[recent.length-1].db_ok:false;
 const observed=info&&info.observed_success_percent!==null&&info.observed_success_percent!==undefined?Number(info.observed_success_percent).toLocaleString('fa-IR',{maximumFractionDigits:1})+'٪':null;
 const last=recent.length?recent[recent.length-1]:null;
 const ms=last&&Number.isFinite(Number(last.app_ms))?Number(last.app_ms).toLocaleString('fa-IR')+' میلی‌ثانیه':'نامشخص';
 return <div className="spad-v10-health" role="region" aria-label="پایش واقعی سلامت سایت و پایگاه داده">
   <div className="spad-v10-health__heading"><span><i className="simple-icon-heart"/> پایش پایداری سرور</span>
    <button type="button" onClick={onRefresh} disabled={loading} title="تازه‌سازی وضعیت پایداری"><i className="simple-icon-refresh"/></button></div>
   <div className="spad-v10-health__primary">
    <div><strong>{observed||'در حال گردآوری'}</strong><small>{observed?'درصد موفقیت نمونه‌های ثبت‌شده':'نیازمند حداقل ۱۲ نمونه'}</small></div>
    <span className={'spad-v10-health__badge '+(last?(ok?'good':'bad'):'unknown')}>
      <i className="simple-icon-check"/> {last?(ok?'آخرین بررسی سالم':'نیازمند بررسی'):'هنوز داده‌ای موجود نیست'}</span>
   </div>
   {recent.length?
    <div className="spad-v10-health__plot" role="img" aria-label={'آخرین '+recent.length.toLocaleString('fa-IR')+' نمونه ثبت‌شده پایش'}>
     {recent.map((p,i)=><span key={p.at||i} className={'spad-v10-health__bar '+(p.app_ok&&p.db_ok?'good':'bad')}
      style={{height:(p.app_ms===null?25:Math.max(20,Math.min(92,25+Number(p.app_ms)/30)))+'%'}}
      title={(p.at||'')+' | '+(p.app_ok&&p.db_ok?'پاسخ موفق':'پاسخ ناموفق')+' | '+(p.app_ms||'—')+' میلی‌ثانیه'}/>)}
    </div>:
    <div className="spad-v10-health__no-data">داده تاریخی موجود نیست؛ نخستین بررسی از سرور درخواست شده است.</div>}
   <div className="spad-v10-health__foot"><span>زمان پاسخ آخرین نمونه: {ms}</span><span>{info?.sample_count?Number(info.sample_count).toLocaleString('fa-IR')+' نمونه واقعی':'در انتظار ثبت نمونه'}</span></div>
   <small className="spad-v10-health__caveat">{error||'این نمودار نتایج بررسی‌های ثبت‌شده را نشان می‌دهد، نه تضمین آپتایم شبانه‌روزی.'}</small>
  </div>;
};
const DashboardDefault=()=>{
 const [dashboard,setDashboard]=useState(null),[loading,setLoading]=useState(true),[error,setError]=useState('');
 const [services,setServices]=useState(null);
 const [health,setHealth]=useState(null);
 const [healthLoading,setHealthLoading]=useState(true);
 const [healthError,setHealthError]=useState('');
 const loadHealth=useCallback(async()=>{
  setHealthLoading(true);
  try{
   const response=await axios.get(adminPathApi+'/dashboard/server-health');
   setHealth(response?.data?.data||null);setHealthError('');
  }catch(e){setHealthError('ارتباط با سامانه پایش برقرار نشد.');}
  finally{setHealthLoading(false);}
 },[]);
 useEffect(()=>{loadHealth();const timer=setInterval(loadHealth,300000);return()=>clearInterval(timer);},[loadHealth]);
 const user=useSelector(s=>s.authUser.currentUser);
 const superAdmin=list(user?.roles).includes('Super Admin');
 const load=useCallback(async()=>{
  setLoading(true);setError('');
  try{const {data}=await axios.get(adminPathApi+'/dashboard');setDashboard(data||{});}
  catch(e){setError('آمار فعلی دریافت نشد؛ اطلاعات پیشین در صورت وجود نمایش داده می‌شود.');}
  finally{setLoading(false);}
 },[]);
 useEffect(()=>{load();},[load]);
 useEffect(()=>{if(!superAdmin)return;let active=true;
  axios.get(adminPathApi+'/web-services').then(({data})=>{if(active)setServices({total:list(data.data).length,registered:list(data.data).filter(x=>x.id).length,configured:list(data.data).filter(x=>x.id&&x.mode==='configured').length,env:data.environment||{}});}).catch(()=>{if(active)setServices(null);});
  return ()=>{active=false;};
 },[superAdmin]);
 const states=list(dashboard?.ads_by_state?.value);
 const days=list(dashboard?.ads_by_time?.label),volumes=list(dashboard?.ads_by_time?.value);
 const trend=days.map((name,i)=>({name,value:Number(volumes[i]||0)}));
 const max=Math.max(1,...trend.map(x=>x.value));
 const categories=list(dashboard?.ads_by_category?.label).map((name,i)=>({name,value:Number(list(dashboard?.ads_by_category?.value)[i]||0)})).sort((a,b)=>b.value-a.value).slice(0,6);
 const maxCat=Math.max(1,...categories.map(x=>x.value));
 const recent=list(dashboard?.last_ads);
 // All destinations below are existing admin routes. Super-admin access is
 // preserved; operational shortcuts never imply API secrets are publicly exposed.
 const settingsLinks=[
  {name:'تنظیمات اصلی سایت',sub:'تنظیمات عمومی',icon:'simple-icon-settings',url:adminRoot+'/settings/main'},
  {name:'مدیریت وب‌سرویس‌ها',sub:'API، شاهکار و پیامک',icon:'simple-icon-puzzle',url:paths.services},
  {name:'تنظیمات استعلامات',sub:'سرویس‌های استعلام',icon:'simple-icon-magnifier',url:adminRoot+'/inquiries/settings'},
  {name:'خدمات استعلام',sub:'فهرست سرویس‌ها',icon:'simple-icon-layers',url:adminRoot+'/inquiries/services'},
  {name:'مدیریت ساختمان',sub:'ساختمان و امکانات',icon:'iconsminds-building',url:paths.property},
  {name:'سازندگان برتر',sub:'سازندگان و رزومه',icon:'iconsminds-business-man',url:adminRoot+'/top-builders'},
  {name:'مزایده املاک',sub:'مزایده‌ها',icon:'iconsminds-gavel',url:adminRoot+'/property-auctions'},
  {name:'امور ملکی',sub:'خدمات و درخواست‌ها',icon:'simple-icon-docs',url:adminRoot+'/property-affairs'},
  {name:'بازسازی آنلاین',sub:'پروژه‌ها و ابزارها',icon:'simple-icon-layers',url:adminRoot+'/online-renovation'},
  {name:'وام مسکن',sub:'وام و درخواست‌ها',icon:'simple-icon-wallet',url:adminRoot+'/mortgage'},
  {name:'قیمت مصالح',sub:'نرخ‌ها و منابع',icon:'iconsminds-coins',url:adminRoot+'/material-prices'},
  {name:'تنظیمات کیف پول',sub:'پرداخت و اعتبار',icon:'simple-icon-wallet',url:adminRoot+'/wallets/settings'},
  {name:'دسته‌بندی ملک‌ها',sub:'نوع ملک و گروه‌ها',icon:'simple-icon-grid',url:adminRoot+'/advertisements/categories/list'},
  {name:'ویژگی‌های املاک',sub:'فیلدها و مشخصات',icon:'simple-icon-check',url:adminRoot+'/advertisements/properties/list'},
  {name:'تنظیمات مناطق',sub:'محله‌ها و محدوده‌ها',icon:'simple-icon-location-pin',url:adminRoot+'/settings/regions'},
  {name:'تنظیمات شهرها',sub:'مکان‌ها',icon:'simple-icon-direction',url:adminRoot+'/settings/cities'},
  {name:'پنل همکاران',sub:'کارگزاری و همکاران',icon:'iconsminds-handshake',url:adminRoot+'/partner-panels'},
  {name:'مدیریت تور مجازی',sub:'تورهای ملکی',icon:'simple-icon-picture',url:adminRoot+'/virtual-tours/list'}
 ];
 const actions=[
  {title:'مدیریت ملک‌ها',sub:'فهرست و وضعیت آگهی‌ها',icon:'iconsminds-building',url:paths.ads},
  {title:'درخواست‌ها',sub:'پیگیری CRM و متقاضیان',icon:'simple-icon-people',url:paths.crm},
  {title:'تور مجازی',sub:'مدیریت تورهای ثبت‌شده',icon:'simple-icon-picture',url:adminRoot+'/virtual-tours/list'},
  {title:'استعلامات',sub:'درخواست‌ها و سرویس‌ها',icon:'simple-icon-shield',url:adminRoot+'/inquiries/requests'},
  ...(superAdmin?[
   {title:'وب‌سرویس‌ها',sub:'درگاه API و امنیت اتصال',icon:'simple-icon-puzzle',url:paths.services},
   {title:'مدیریت ساختمان',sub:'ساختمان‌ها و ساکنان',icon:'iconsminds-building',url:paths.property},
   {title:'تنظیمات اصلی',sub:'پیکربندی سامانه',icon:'simple-icon-settings',url:adminRoot+'/settings/main'},
   {title:'بازسازی آنلاین',sub:'پروژه‌ها و درخواست‌ها',icon:'simple-icon-layers',url:adminRoot+'/online-renovation'}
  ]:[])
 ];
 return <div className="spad-v8-dashboard" dir="rtl">
  <header className="spad-v8-dash-hero">
   <div className="spad-v8-dash-content">
    <span className="spad-v8-kicker spad-v10-hero-kicker"><span className="spad-v8-live-dot"/> پنل مدیریت و نظارت اسپاد</span>
    <h1>مرکز فرماندهی هوشمند اسپاد</h1>
    <p>نمای یکپارچه عملکرد مجموعه، پرونده‌های ملکی و خدمات دیجیتال</p>
    <div className="spad-v8-dash-meta"><span><i className="simple-icon-calendar"/> {today()}</span><span><i className="simple-icon-chart"/> داده‌های واقعی سامانه</span></div>
   </div>
   <ServerHealthPanel info={health} loading={healthLoading} error={healthError} onRefresh={loadHealth}/>
  </header>
  {superAdmin&&<section className="spad-v8-settings-launcher" aria-labelledby="spad-v8-settings-title">
   <div className="spad-v8-settings-launcher__heading">
    <div><span className="spad-v8-settings-launcher__kicker"><i className="simple-icon-settings"/> SPAD SETTINGS HUB</span>
     <h2 id="spad-v8-settings-title">دسترسی سریع به تمام تنظیمات اسپاد</h2>
     <p>تمام بخش‌های مدیریتی و تنظیمات تخصصی، مستقیم و بدون جستجو در منو</p>
    </div><span className="spad-v8-settings-launcher__total">{n(settingsLinks.length)} بخش مدیریتی</span>
   </div>
   <div className="spad-v8-settings-launcher__grid">
    {settingsLinks.map(item=><Link className="spad-v8-settings-launcher__link" key={item.url} to={item.url} title={item.name}>
     <span className="spad-v8-settings-launcher__icon"><i className={item.icon}/></span>
     <span className="spad-v8-settings-launcher__name"><strong>{item.name}</strong><small>{item.sub}</small></span>
     <i className="simple-icon-arrow-left spad-v8-settings-launcher__arrow" aria-hidden="true"/>
    </Link>)}
   </div>
  </section>}
  {error&&<div className="spad-v8-alert" role="alert">{error} <button onClick={load} type="button">تلاش مجدد</button></div>}
  <div className="spad-v8-section-heading"><div><span>OVERVIEW / مرور عملکرد</span><h2>وضعیت امروز کسب‌وکار</h2></div><button type="button" onClick={load} disabled={loading}><i className="simple-icon-refresh"/> {loading?'در حال دریافت':'به‌روزرسانی داده‌ها'}</button></div>
  <div className="spad-v8-metrics" aria-busy={loading}>
   <Stat title="کل ملک‌های ثبت‌شده" value={dashboard?.all_ads_count} icon="iconsminds-building" link={paths.ads} color="blue"/>
   <Stat title="درخواست‌های CRM" value={dashboard?.crm_requests_count} icon="simple-icon-people" link={paths.crm} color="violet"/>
   <Stat title="درخواست‌های کارشناسی" value={dashboard?.expert_requests_count} icon="simple-icon-user-following" link={paths.expert} color="cyan"/>
   <Stat title="در انتظار بررسی" value={states[2]} icon="simple-icon-clock" link={paths.approval} color="amber"/>
  </div>
  <div className="spad-v8-dash-grid">
   <Pane title="ضربان ثبت ملک‌ها" sub="روند تعداد آگهی‌ها در بازه ثبت‌شده روی سرور" action={<span className="spad-v8-small-tag">SPAD LIVE DATA</span>}>
    {trend.length?<div className="spad-v8-trend" role="img" aria-label="نمودار تعداد ثبت آگهی‌ها">{trend.map((point,i)=><div key={i} className="spad-v8-trend-column"><small>{n(point.value)}</small><div className="spad-v8-trend-rail"><span style={{height:(Math.max(2,pct(point.value,max)))+'%'}}/></div><b>{point.name}</b></div>)}</div>:<div className="spad-v8-placeholder">{loading?'در حال دریافت نمودار…':'داده‌ای برای این بازه ثبت نشده است'}</div>}
   </Pane>
   <Pane title="وضعیت بررسی آگهی‌ها" sub="کنترل فرایند انتشار و کیفیت آگهی">
    <div className="spad-v8-status-bars">{['تأییدشده','ردشده','در انتظار تأیید'].map((name,i)=><div key={name}><div><span>{name}</span><strong>{n(states[i])}</strong></div><div className="spad-v8-meter"><span className={'s'+i} style={{width:pct(states[i],Number(dashboard?.all_ads_count||0))+'%'}}/></div></div>)}</div>
    {superAdmin&&<div className="spad-v8-connection-summary"><div><i className="simple-icon-puzzle"/><strong>زیرساخت وب‌سرویس‌ها</strong></div><p>{services? n(services.registered)+' مورد ثبت‌شده از '+n(services.total)+' مورد فهرست‌شده':'وضعیت اتصال هنوز دریافت نشده است'}</p><Link to={paths.services}>مدیریت APIها <i className="simple-icon-arrow-left"/></Link></div>}
   </Pane>
  </div>
  <div className="spad-v8-dash-grid">
   <Pane title="تازه‌ترین ملک‌های ثبت‌شده" sub="آخرین فعالیت‌های ملکی سامانه" action={<Link to={paths.ads}>مشاهده همه</Link>}>
    {recent.length?<div className="spad-v8-latest">{recent.slice(0,6).map((item,i)=><Link key={item.id||i} to={adminRoot+'/advertisements/edit/'+item.id}>
     <span className="spad-v8-latest-icon">{item.image?<img src={item.image} alt="" loading="lazy"/>:<i className="iconsminds-building"/>}</span><div><strong>{item.title||'ملک بدون عنوان'}</strong><small>{item.date||'تاریخ نامشخص'}</small></div><em>{item.status_text||'ثبت‌شده'}</em><i className="simple-icon-arrow-left"/></Link>)}</div>:<div className="spad-v8-placeholder">{loading?'در حال دریافت املاک…':'آگهی جدیدی برای نمایش وجود ندارد'}</div>}
   </Pane>
   <Pane title="سهم دسته‌های ملکی" sub="پراکندگی آگهی‌های فعلی بر اساس نوع ملک">
    {categories.length?<div className="spad-v8-category-bars">{categories.map((item,i)=><div key={i}><div><span>{item.name}</span><b>{n(item.value)}</b></div><div className="spad-v8-meter"><span style={{width:pct(item.value,maxCat)+'%'}}/></div></div>)}</div>:<div className="spad-v8-placeholder">هنوز داده‌ای ثبت نشده است.</div>}
   </Pane>
  </div>
  <Pane title="میز کار مدیر اسپاد" sub="دسترسی مستقیم به خدمات کلیدی و بخش‌های مدیریتی" klass="spad-v8-workspace">
   <div className="spad-v8-action-grid">{actions.map((action,i)=><Link to={action.url} key={action.url}><span className="spad-v8-action-icon"><i className={action.icon}/></span><div><strong>{action.title}</strong><small>{action.sub}</small></div><i className="simple-icon-arrow-left"/></Link>)}</div>
  </Pane>
 </div>;
};
export default DashboardDefault;

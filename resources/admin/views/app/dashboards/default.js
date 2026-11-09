import React,{useCallback,useEffect,useMemo,useState} from 'react';
import {Link} from 'react-router-dom';
import {useSelector} from 'react-redux';
import axios from 'axios';
import {adminPathApi,adminRoot} from '../../../constants/defaultValues';
import menuItems from '../../../constants/menu';
import IntlMessages from '../../../helpers/IntlMessages';
import '../../../assets/css/sass/spad-admin-workspace-all-links.scss';
const n=v=>Number(v||0).toLocaleString('fa-IR');
const list=v=>Array.isArray(v)?v:[];
const pct=(v,t)=>t>0?Math.min(100,Math.max(0,Number(v||0)/t*100)):0;
// Localized live clock for SPAD's operational timezone, independent of device timezone.
const iranDate=timestamp=>new Date(timestamp).toLocaleDateString('fa-IR-u-ca-persian',{
 timeZone:'Asia/Tehran',weekday:'long',year:'numeric',month:'long',day:'numeric'
});
const iranTime=timestamp=>new Date(timestamp).toLocaleTimeString('fa-IR',{
 timeZone:'Asia/Tehran',hour:'2-digit',minute:'2-digit',hour12:false
});
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
const ServerHealthPanel=({info,resources,resourceError,loadingResources,loading,error,onRefresh,period,onPeriod})=>{
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
};
const DashboardDefault=()=>{
 const [clock,setClock]=useState(()=>Date.now());
 useEffect(()=>{
  const timer=setInterval(()=>setClock(Date.now()),30000);
  return ()=>clearInterval(timer);
 },[]);
 const [dashboard,setDashboard]=useState(null),[loading,setLoading]=useState(true),[error,setError]=useState('');
 const [services,setServices]=useState(null);
 const [health,setHealth]=useState(null);
 const [healthPeriod,setHealthPeriod]=useState('24h');
 const [healthLoading,setHealthLoading]=useState(true);
 const [healthError,setHealthError]=useState('');
 const loadHealth=useCallback(async()=>{
  setHealthLoading(true);
  try{
   const response=await axios.get(adminPathApi+'/dashboard/server-health',{params:{period:healthPeriod}});
   setHealth(response?.data?.data||null);setHealthError('');
  }catch(e){setHealthError('ارتباط با سامانه پایش برقرار نشد.');}
  finally{setHealthLoading(false);}
 },[healthPeriod]);
 useEffect(()=>{loadHealth();const timer=setInterval(loadHealth,300000);return()=>clearInterval(timer);},[loadHealth]);
 const user=useSelector(s=>s.authUser.currentUser);
 const superAdmin=list(user?.roles).includes('Super Admin');
 const [resources,setResources]=useState(null);
 const [resourceError,setResourceError]=useState('');
 const [resourcesLoading,setResourcesLoading]=useState(false);
 const loadResources=useCallback(async()=>{
  if(!superAdmin)return;
  setResourcesLoading(true);
  try{
   const res=await axios.get(adminPathApi+'/dashboard/server-resources');
   setResources(res?.data?.data||null);setResourceError('');
  }catch(e){setResourceError('دریافت اطلاعات منابع میزبان ممکن نشد.');}
  finally{setResourcesLoading(false);}
 },[superAdmin]);
 useEffect(()=>{
  if(!superAdmin)return;
  loadResources();
  const timer=setInterval(loadResources,120000);
  return ()=>clearInterval(timer);
 },[superAdmin,loadResources]);
 const reloadOperational=useCallback(()=>{loadHealth();loadResources();},[loadHealth,loadResources]);
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
  {name:'درگاه و کیف پول',sub:'درگاه پرداخت، کیف پول و تراکنش‌ها',icon:'simple-icon-wallet',url:adminRoot+'/wallets/center'},
  {name:'دسته‌بندی ملک‌ها',sub:'نوع ملک و گروه‌ها',icon:'simple-icon-grid',url:adminRoot+'/advertisements/categories/list'},
  {name:'ویژگی‌های املاک',sub:'فیلدها و مشخصات',icon:'simple-icon-check',url:adminRoot+'/advertisements/properties/list'},
  {name:'تنظیمات مناطق',sub:'محله‌ها و محدوده‌ها',icon:'simple-icon-location-pin',url:adminRoot+'/settings/regions'},
  {name:'تنظیمات شهرها',sub:'مکان‌ها',icon:'simple-icon-direction',url:adminRoot+'/settings/cities'},
  {name:'پنل همکاران',sub:'کارگزاری و همکاران',icon:'iconsminds-handshake',url:adminRoot+'/partner-panels'},
  {name:'مدیریت تور مجازی',sub:'تورهای ملکی',icon:'simple-icon-picture',url:adminRoot+'/virtual-tours/list'}
 ];
 // Show every destination from the actual sidebar definition, not a manually
 // maintained subset. Keep the same role/permission visibility as the sidebar.
 const canSee=item=>superAdmin||(
  (!item.roles||item.roles.some(role=>list(user?.roles).includes(role))) &&
  (!item.permission||item.permission.some(permission=>list(user?.permissions).includes(permission)))
 );
 const usedWorkspaceRoutes=new Set();
 const workspaceSections=menuItems.filter(canSee).map(section=>{
  const links=[];
  const collect=items=>items.forEach(item=>{
   if(!canSee(item))return;
   if(item.subs&&item.subs.length){collect(item.subs);return;}
   if(!item.to||usedWorkspaceRoutes.has(item.to))return;
   usedWorkspaceRoutes.add(item.to);
   links.push({url:item.to,title:item.label,icon:item.icon||section.icon||'simple-icon-grid',external:Boolean(item.newWindow)});
  });
  collect(section.subs&&section.subs.length?section.subs:[section]);
  return {id:section.id,label:section.label,links};
 }).filter(section=>section.links.length);
 // These standalone settings shortcuts are valid app routes, but some are not
 // listed as sidebar leaf nodes. Do not duplicate a destination.
 const extraWorkspaceLinks=superAdmin?settingsLinks.filter(item=>{
  if(usedWorkspaceRoutes.has(item.url))return false;
  usedWorkspaceRoutes.add(item.url);
  return true;
 }):[];
 const workspaceLinks=workspaceSections.reduce((all,section)=>all.concat(section.links),[]).concat(
  extraWorkspaceLinks.map(item=>({url:item.url,title:item.name,icon:item.icon,external:false,plainTitle:true}))
 );
 const workspaceCount=workspaceLinks.length;
 return <div className="spad-v8-dashboard" dir="rtl">
  <header className="spad-v8-dash-hero spad-v12-command-hero" aria-label="مرکز فرماندهی هوشمند اسپاد">
   <div className="spad-v12-command-hero__heading">
    <span className="spad-v12-command-hero__label"><span className="spad-v8-live-dot" aria-hidden="true"/> مرکز فرماندهی هوشمند اسپاد</span>
    <div className="spad-v12-command-hero__datetime" aria-label="تاریخ و ساعت به وقت ایران">
     <span><i className="simple-icon-calendar" aria-hidden="true"/> {iranDate(clock)}</span>
     <span><i className="simple-icon-clock" aria-hidden="true"/> {iranTime(clock)} <small>به وقت ایران</small></span>
    </div>
   </div>
   <ServerHealthPanel info={health} resources={resources} resourceError={resourceError} loadingResources={resourcesLoading} loading={healthLoading} error={healthError} onRefresh={reloadOperational} period={healthPeriod} onPeriod={setHealthPeriod}/>
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
  <Pane title="میز کار مدیر اسپاد" sub="دسترسی مستقیم به تمام صفحات مجاز پنل مدیریت" klass="spad-v8-workspace spad-workspace-all">
   <div className="spad-workspace-all__total">{n(workspaceCount)} مسیر مدیریتی در دسترس</div>
   <div className="spad-v8-action-grid">
    {workspaceLinks.map(item=>item.external?
     <a key={item.url} href={item.url} target="_blank" rel="noopener noreferrer" className="spad-workspace-all__button"><span className="spad-v8-action-icon"><i className={item.icon}/></span><span className="spad-workspace-all__label">{item.plainTitle?item.title:<IntlMessages id={item.title}/>}</span><i className="simple-icon-arrow-left" aria-hidden="true"/></a>:
     <Link key={item.url} to={item.url} className="spad-workspace-all__button"><span className="spad-v8-action-icon"><i className={item.icon}/></span><span className="spad-workspace-all__label">{item.plainTitle?item.title:<IntlMessages id={item.title}/>}</span><i className="simple-icon-arrow-left" aria-hidden="true"/></Link>)}
   </div>
  </Pane>
 </div>;
};
export default DashboardDefault;

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
const DashboardDefault=()=>{
 const [dashboard,setDashboard]=useState(null),[loading,setLoading]=useState(true),[error,setError]=useState('');
 const [services,setServices]=useState(null);
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
    <span className="spad-v8-kicker"><span className="spad-v8-live-dot"/> SPAD ESTATE / COMMAND CENTER</span>
    <h1>مرکز فرماندهی هوشمند اسپاد</h1>
    <p>نمای یکپارچه عملکرد مجموعه، پرونده‌های ملکی و خدمات دیجیتال</p>
    <div className="spad-v8-dash-meta"><span><i className="simple-icon-calendar"/> {today()}</span><span><i className="simple-icon-chart"/> داده‌های واقعی سامانه</span></div>
   </div>
   <div className="spad-v8-hero-panel"><div><i className="iconsminds-building"/><span>SPAD</span></div><small>ADMINISTRATIVE INTELLIGENCE</small>
    <Link to={paths.ads}>مدیریت املاک <i className="simple-icon-arrow-left"/></Link>
   </div>
  </header>
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

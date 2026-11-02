import React, {useEffect,useMemo,useState} from 'react';
import {useSelector} from 'react-redux';
import axios from 'axios';
import {adminPathApi} from '../../../constants/defaultValues';

const BASE=adminPathApi+'/web-services';
const categories=[
 ['all','همه وب‌سرویس‌ها','simple-icon-grid'],
 ['sms','پیامک و تأییدیه','simple-icon-envelope'],
 ['identity','احراز هویت','simple-icon-shield'],
 ['bills','قبوض و پرداخت','simple-icon-wallet'],
 ['inquiries','استعلامات','simple-icon-magnifier'],
 ['market','قیمت‌های بازار','simple-icon-chart'],
 ['property','ملک و موقعیت','simple-icon-home'],
 ['other','سایر سرویس‌ها','simple-icon-layers']
];
const empty={code:'',title:'',category:'other',provider:'',endpoint:'',description:'',mode:'planned',enabled:false,secret:'',config_public:{}};
const errorText=(e)=>e?.response?.data?.message||'ارتباط با سرور برقرار نشد؛ تغییرات ثبت نشدند.';
const Icon=({name})=><i className={name||'simple-icon-puzzle'}/>;
const count=(n)=>Number(n||0).toLocaleString('fa-IR');
const integrationMap={sms:['sms_login','sms_kyc','sms_handover'],identity:['shahkar']};
const STATUS={planned:'برنامه‌ریزی‌شده',configured:'پیکربندی اولیه'};
const checkLabel={not_tested:'هنوز بررسی نشده',configuration_ready:'تنظیمات اولیه تکمیل',incomplete:'تنظیمات ناقص'};
const Detail=({item,environment})=>{
 const ids=integrationMap[item.category]||[];
 const labels={sms_login:'الگوی پیامک ورود',sms_kyc:'الگوی احراز هویت',sms_handover:'الگوی تأیید تحویل',shahkar:'کلید شاهکار'};
 return ids.length?<div className="spad-v8-env-row">{ids.map(id=><span key={id} className={environment[id]?'is-ready':'is-missing'}><i className={environment[id]?'simple-icon-check':'simple-icon-clock'}/> {labels[id]}: {environment[id]?'ثبت‌شده روی سرور':'فاقد تنظیمات'}</span>)}</div>:null;
};
export default function WebServices(){
 const user=useSelector(s=>s.authUser.currentUser);
 const isSuper=Boolean(user&&Array.isArray(user.roles)&&user.roles.includes('Super Admin'));
 const [list,setList]=useState([]),[environment,setEnvironment]=useState({});
 const [filter,setFilter]=useState('all'),[query,setQuery]=useState('');
 const [edit,setEdit]=useState(null),[busy,setBusy]=useState(false),[loading,setLoading]=useState(true);
 const [failure,setFailure]=useState(''),[notice,setNotice]=useState(''),[confirmDelete,setConfirmDelete]=useState(null);
 const [secretVisible,setSecretVisible]=useState(false);
 const load=async()=>{
  setLoading(true);setFailure('');
  try{const {data}=await axios.get(BASE);setList(Array.isArray(data.data)?data.data:[]);setEnvironment(data.environment||{});}
  catch(e){setFailure(errorText(e));}
  finally{setLoading(false);}
 };
 useEffect(()=>{if(isSuper)load();else setLoading(false);},[isSuper]);
 const filtered=useMemo(()=>list.filter(item=>(filter==='all'||item.category===filter)
  &&[item.title,item.provider,item.description,item.code].filter(Boolean).join(' ').toLowerCase().includes(query.trim().toLowerCase())),[list,filter,query]);
 const catalogCount=list.filter(x=>x.id===null).length;
 const configured=list.filter(x=>x.id!==null&&x.mode==='configured').length;
 const start=(item)=>{
  setFailure('');setNotice('');setSecretVisible(false);
  setEdit(item?{...item,secret:'',provider:item.provider||'',endpoint:item.endpoint||'',description:item.description||'',config_public:item.config_public||{}}:{...empty});
 };
 const set=(key,val)=>setEdit(s=>({...s,[key]:val}));
 const save=async(e)=>{
  e.preventDefault();if(!edit)return;
  if(!edit.title.trim()||!edit.code.trim()){setFailure('عنوان و شناسه سرویس الزامی هستند.');return;}
  setBusy(true);setFailure('');setNotice('');
  try{
   const payload={code:edit.code.trim().toLowerCase(),title:edit.title.trim(),category:edit.category,
    provider:edit.provider.trim(),endpoint:edit.endpoint.trim(),description:edit.description.trim(),
    enabled:Boolean(edit.enabled),mode:edit.mode,secret:edit.secret||'',config_public:edit.config_public||{}};
   if(edit.id){await axios.put(BASE+'/'+edit.id,payload);}
   else{await axios.post(BASE+'/',payload);}
   setNotice('تنظیمات در بایگانی امن اسپاد ثبت شد. فعال‌شدن واقعی وب‌سرویس مستلزم آداپتر اختصاصی است.');
   setEdit(null);await load();
  }catch(err){setFailure(errorText(err));}finally{setBusy(false);}
 };
 const inspect=async(item)=>{
  if(!item.id)return;
  setBusy(true);setFailure('');
  try{const {data}=await axios.post(BASE+'/'+item.id+'/check',{});setNotice(data.data.last_note||'پیکربندی بررسی شد.');await load();}
  catch(e){setFailure(errorText(e));}finally{setBusy(false);}
 };
 const erase=async()=>{
  if(!confirmDelete)return;
  setBusy(true);
  try{await axios.delete(BASE+'/'+confirmDelete.id);setConfirmDelete(null);setEdit(null);setNotice('تنظیمات این سرویس از دفتر اتصال‌ها حذف شد. پیکربندی زنده سرور تغییر نکرد.');await load();}
  catch(e){setFailure(errorText(e));}finally{setBusy(false);}
 };
 if(!isSuper)return <div className="spad-v8-services"><div className="spad-v8-alert">مدیریت کلیدهای وب‌سرویس فقط برای مدیر اصلی اسپاد مجاز است.</div></div>;
 return <div className="spad-v8-services" dir="rtl">
  <header className="spad-v8-service-hero">
   <div><span className="spad-v8-kicker">SPAD API & INTEGRATION CONTROL CENTER</span>
    <h1>مرکز مدیریت وب‌سرویس‌های اسپاد</h1>
    <p>مدیریت ایمن سرویس‌های فعلی و آینده؛ از پیامک و شاهکار تا استعلامات، قبوض و داده‌های بازار</p>
    <div className="spad-v8-service-hero-tags"><span><i className="simple-icon-lock"/> کلیدها رمزنگاری می‌شوند</span><span><i className="simple-icon-layers"/> توسعه‌پذیر برای سرویس‌های آینده</span></div>
   </div>
   <div className="spad-v8-service-hero-icon"><i className="simple-icon-puzzle"/><b>SPAD</b><small>API HUB</small></div>
  </header>
  <div className="spad-v8-service-metrics">
   <article><span>سرویس‌های فهرست‌شده</span><strong>{count(list.length)}</strong><small>شامل سرویس‌های پیشنهادی آینده</small></article>
   <article><span>دارای پیکربندی مدیریتی</span><strong>{count(configured)}</strong><small>به معنی تأیید اتصال زنده نیست</small></article>
   <article><span>در انتظار راه‌اندازی</span><strong>{count(catalogCount)}</strong><small>بدون اطلاعات محرمانه ذخیره‌شده</small></article>
  </div>
  <div className="spad-v8-service-info"><i className="simple-icon-info"/><p>بررسی تنظیمات، فقط کامل‌بودن فیلدها را کنترل می‌کند؛ تست واقعی API، تغییر منبع داده یا ارسال پیامک، تنها پس از اتصال آداپتر بررسی‌شده انجام می‌شود. کلیدهای موجود در تنظیمات سرور بدون تغییر باقی می‌مانند.</p></div>
  {failure&&<div className="spad-v8-alert" role="alert">{failure}</div>}
  {notice&&<div className="spad-v8-notice" role="status">{notice}</div>}
  <div className="spad-v8-toolbar">
   <div><h2>دایرکتوری یکپارچه سرویس‌ها</h2><p>جستجو، فیلتر، ذخیره تنظیمات و مدیریت منابع آینده</p></div>
   <div className="spad-v8-tool-controls"><input aria-label="جستجوی وب‌سرویس" placeholder="جستجوی نام، ارائه‌دهنده یا شناسه…" value={query} onChange={e=>setQuery(e.target.value)}/><button type="button" className="spad-v8-action" onClick={()=>start(null)}><i className="simple-icon-plus"/> افزودن وب‌سرویس</button><button type="button" className="spad-v8-ghost" disabled={loading} onClick={load}><i className="simple-icon-refresh"/> تازه‌سازی</button></div>
  </div>
  <div className="spad-v8-category-pills">{categories.map(([id,title,icon])=><button type="button" key={id} className={filter===id?'active':''} aria-pressed={filter===id} onClick={()=>setFilter(id)}><Icon name={icon}/>{title}<b>{count(id==='all'?list.length:list.filter(s=>s.category===id).length)}</b></button>)}</div>
  {loading?<div className="spad-v8-loading">در حال دریافت وضعیت مرکز وب‌سرویس‌ها…</div>:filtered.length?<div className="spad-v8-service-grid">{filtered.map(item=>{
   const cat=categories.find(c=>c[0]===item.category)||categories[categories.length-1];
   return <article className="spad-v8-service-card" key={item.code}>
    <div className="spad-v8-service-top"><div className="spad-v8-service-icon"><Icon name={cat[2]}/></div><span className="spad-v8-service-tag">{cat[1]}</span><span className={'spad-v8-service-state '+(item.id&&item.mode==='configured'?'setup':'planned')}>{item.id?STATUS[item.mode]||'ثبت‌شده':'پیشنهادی'}</span></div>
    <h3>{item.title}</h3><p>{item.description||'وب‌سرویس قابل توسعه در اسپاد'}</p>
    <div className="spad-v8-service-meta"><span><i className="simple-icon-layers"/> ارائه‌دهنده: <b>{item.provider||'انتخاب نشده'}</b></span><span><i className="simple-icon-lock"/> کلید امن: <b>{item.has_secret?'ذخیره‌شده':'هنوز ثبت نشده'}</b></span></div>
    <Detail item={item} environment={environment}/>
    <div className="spad-v8-service-bottom"><small>{item.id?checkLabel[item.health_status]||'بررسی نشده':'آماده تعریف تنظیمات'}</small><div><button type="button" disabled={busy||!item.id} onClick={()=>inspect(item)}>بررسی تنظیمات</button><button type="button" className="primary" onClick={()=>start(item)}>{item.id?'مدیریت':'راه‌اندازی'}</button></div></div>
   </article>;
  })}</div>:<div className="spad-v8-empty">سرویسی با این مشخصات پیدا نشد.</div>}
  {edit&&<div className="spad-v8-modal-backdrop" onMouseDown={e=>{if(e.target===e.currentTarget&&!busy)setEdit(null);}}><form className="spad-v8-modal" onSubmit={save} role="dialog" aria-modal="true" aria-label="تنظیمات وب‌سرویس">
   <header><div><span>SPAD / SERVICE SETTINGS</span><h2>{edit.id?'ویرایش اتصال':'افزودن اتصال جدید'}</h2><p>مقادیر محرمانه پس از ذخیره مجدداً قابل مشاهده نیستند.</p></div><button type="button" disabled={busy} aria-label="بستن" onClick={()=>setEdit(null)}>×</button></header>
   <div className="spad-v8-form-grid">
    <label>عنوان سرویس<input required minLength={3} maxLength={160} value={edit.title} onChange={e=>set('title',e.target.value)}/></label>
    <label>شناسه فنی<input required pattern="[a-zA-Z0-9_-]+" maxLength={80} disabled={Boolean(edit.id)} dir="ltr" value={edit.code} onChange={e=>set('code',e.target.value.replace(/[^a-zA-Z0-9_-]/g,'').toLowerCase())}/></label>
    <label>گروه<select value={edit.category} onChange={e=>set('category',e.target.value)}>{categories.filter(c=>c[0]!=='all').map(c=><option value={c[0]} key={c[0]}>{c[1]}</option>)}</select></label>
    <label>نام ارائه‌دهنده<input maxLength={120} value={edit.provider} onChange={e=>set('provider',e.target.value)} placeholder="مثلاً SMS.ir"/></label>
    <label className="wide">آدرس HTTPS سرویس (در صورت وجود)<input type="url" dir="ltr" pattern="https://.*" value={edit.endpoint} onChange={e=>set('endpoint',e.target.value)} placeholder="https://api.example.com/v1"/></label>
    <label className="wide">کلید API / توکن محرمانه (اختیاری)<div className="spad-v8-secret"><input autoComplete="new-password" maxLength={5000} type={secretVisible?'text':'password'} dir="ltr" value={edit.secret} onChange={e=>set('secret',e.target.value)} placeholder={edit.has_secret?'کلید قبلی ذخیره شده؛ فقط برای جایگزینی وارد کنید':'کلید یا توکن اتصال'}/><button type="button" onClick={()=>setSecretVisible(v=>!v)}>{secretVisible?'پنهان':'نمایش موقت'}</button></div><small>کلید قبلی از سرور بازخوانی نمی‌شود. هیچ رمز یا توکنی در گزارش‌های وضعیت نمایش داده نمی‌شود.</small></label>
    <label className="wide">توضیحات / کاربرد<textarea rows={3} maxLength={1200} value={edit.description} onChange={e=>set('description',e.target.value)}/></label>
    <label>مرحله راه‌اندازی<select value={edit.mode} onChange={e=>set('mode',e.target.value)}><option value="planned">در برنامه اتصال</option><option value="configured">پیکربندی اولیه ثبت‌شده</option></select></label>
    <label className="spad-v8-toggle"><input type="checkbox" checked={Boolean(edit.enabled)} onChange={e=>set('enabled',e.target.checked)}/><span>فعال در دفتر تنظیمات (نه اتصال خودکار سایت)</span></label>
   </div>
   <footer>{edit.id&&<button type="button" className="spad-v8-danger" disabled={busy} onClick={()=>setConfirmDelete(edit)}>حذف تنظیمات</button>}<button type="button" disabled={busy} onClick={()=>setEdit(null)}>انصراف</button><button type="submit" className="spad-v8-action" disabled={busy}>{busy?'در حال ثبت…':'ذخیره ایمن تنظیمات'}</button></footer>
  </form></div>}
  {confirmDelete&&<div className="spad-v8-modal-backdrop"><div role="alertdialog" aria-label="تأیید حذف" className="spad-v8-confirm"><h3>حذف تنظیمات «{confirmDelete.title}»؟</h3><p>این عمل کلید ذخیره‌شده را از دفتر اتصال‌ها حذف می‌کند و قابل برگشت نیست. سرویس‌های فعلی سرور تغییر نمی‌کنند.</p><div><button type="button" disabled={busy} onClick={()=>setConfirmDelete(null)}>انصراف</button><button type="button" disabled={busy} className="spad-v8-danger" onClick={erase}>تأیید حذف</button></div></div></div>}
 </div>;
}

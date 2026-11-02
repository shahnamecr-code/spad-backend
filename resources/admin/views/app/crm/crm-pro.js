import React,{useCallback,useEffect,useState} from 'react';
import axios from 'axios';
import {useSelector} from 'react-redux';
import {Link} from 'react-router-dom';
import {adminPathApi,adminRoot} from '../../../constants/defaultValues';

const API=adminPathApi+'/crm-pro';
const STAGES=[['new','سرنخ جدید'],['contacted','تماس اولیه'],['qualified','احراز نیاز'],['visit','بازدید ملک'],['negotiation','مذاکره'],['won','معامله نهایی'],['lost','مختومه']];
const INTERESTS=[['buy','خرید ملک'],['sell','فروش ملک'],['rent','رهن و اجاره'],['invest','سرمایه‌گذاری'],['other','سایر']];
const blank={full_name:'',mobile:'',email:'',stage:'new',interest:'buy',source:'manual',city:'',budget:'',preferred_location:'',note:'',priority:2,sms_consent:false,voice_consent:false,consent_source:''};
const label=(array,key)=>array.find(x=>x[0]===key)?.[1]||key;
const num=v=>Number(v||0).toLocaleString('fa-IR');
const err=e=>e?.response?.data?.message||'ارتباط با سرور برقرار نشد. مجدداً بررسی کنید.';
const date=s=>s?new Date(String(s).replace(' ','T')).toLocaleString('fa-IR'):'—';
const newId=()=>typeof crypto!=='undefined'&&crypto.randomUUID?crypto.randomUUID():'10000000-1000-4000-8000-'+String(Date.now()%1e12).padStart(12,'0');
const Icon=({name})=><i className={name}/>;
const Field=({label:caption,children,full})=><label className={full?'wide':''}><span>{caption}</span>{children}</label>;

export default function CrmPro(){
 const user=useSelector(x=>x.authUser.currentUser);
 const superAdmin=Boolean(user&&Array.isArray(user.roles)&&user.roles.includes('Super Admin'));
 const [summary,setSummary]=useState(null),[contacts,setContacts]=useState([]),[total,setTotal]=useState(0);
 const [page,setPage]=useState(1),[stage,setStage]=useState(''),[search,setSearch]=useState(''),[query,setQuery]=useState('');
 const [selected,setSelected]=useState(null),[detail,setDetail]=useState(null);
 const [form,setForm]=useState(null),[note,setNote]=useState(''),[noteType,setNoteType]=useState('note');
 const [task,setTask]=useState({title:'',kind:'call',due_at:'',description:''});
 const [outbound,setOutbound]=useState({channel:'sms',message:''});
 const [saving,setSaving]=useState(false),[loading,setLoading]=useState(true),[error,setError]=useState(''),[info,setInfo]=useState('');
 const reload=useCallback(async()=>{
  try{
   const [a,b]=await Promise.all([
    axios.get(API+'/overview'),axios.get(API+'/contacts',{params:{page,per_page:15,stage:stage||undefined,q:query||undefined}})
   ]);
   setSummary(a.data.data||null);setContacts(b.data.data||[]);setTotal(b.data.total||0);setError('');
  }catch(e){setError(err(e));}
  finally{setLoading(false);}
 },[page,stage,query]);
 const loadDetail=useCallback(async id=>{
  try{const {data}=await axios.get(API+'/contacts/'+id);setDetail(data);setError('');}
  catch(e){setError(err(e));}
 },[]);
 useEffect(()=>{if(superAdmin)reload();else setLoading(false);},[superAdmin,reload]);
 useEffect(()=>{if(selected)loadDetail(selected);else setDetail(null);},[selected,loadDetail]);
 const submit=async(event)=>{
  event.preventDefault();if(!form)return;
  setSaving(true);setError('');setInfo('');
  try{
   const payload={...form};
   const {data}=form.id?await axios.put(API+'/contacts/'+form.id,payload):await axios.post(API+'/contacts',payload);
   setInfo(form.id?'پرونده مشتری به‌روزرسانی شد.':'مشتری جدید به CRM اضافه شد.');
   setForm(null);setSelected(data.data.id);await reload();await loadDetail(data.data.id);
  }catch(e){setError(err(e));}finally{setSaving(false);}
 };
 const post=async(url,body,success)=>{
  setSaving(true);setError('');setInfo('');
  try{const {data}=await axios.post(url,body);setInfo(data.message||success||'ثبت شد.');
   if(selected)await loadDetail(selected);await reload();return true;}
  catch(e){setError(err(e));return false;}finally{setSaving(false);}
 };
 const send=async(event)=>{
  event.preventDefault();
  if(!detail)return;
  const contact=detail.data;
  if(!window.confirm('این ارتباط برای '+contact.full_name+' ثبت شود؟ فقط پس از رضایت مشتری و در ساعات مجاز درخواست به ارائه‌دهنده ارسال می‌شود.'))return;
  const ok=await post(API+'/contacts/'+selected+'/outbound',{...outbound,request_id:newId()},'درخواست ثبت شد.');
  if(ok)setOutbound(v=>({...v,message:''}));
 };
 const addNote=async(e)=>{e.preventDefault();const ok=await post(API+'/contacts/'+selected+'/notes',{content:note,type:noteType});if(ok)setNote('');};
 const addTask=async(e)=>{e.preventDefault();const ok=await post(API+'/contacts/'+selected+'/followups',task);if(ok)setTask({title:'',kind:'call',due_at:'',description:''});};
 if(!superAdmin)return <div className="spad-crm-pro"><p className="spad-crm-pro__alert">پرونده‌های CRM تخصصی فقط برای مدیر اصلی اسپاد قابل دسترسی هستند.</p></div>;
 return <div className="spad-crm-pro" dir="rtl">
  <header className="spad-crm-pro__hero"><div><span><i className="simple-icon-people"/> مرکز ارتباط و فروش املاک</span><h1>مدیریت هوشمند مشتریان اسپاد</h1>
   <p>سرنخ‌های ملکی، مذاکرات، بازدیدها، پیگیری‌ها و ارتباطات مشتری در یک میز کار یکپارچه</p>
   <div className="spad-crm-pro__hero-tags"><span>پرونده اختصاصی مشتری</span><span>تاریخچه تعاملات</span><span>ارتباط با رضایت مخاطب</span></div></div>
   <div className="spad-crm-pro__hero-actions"><button type="button" onClick={()=>{setForm({...blank});setError('');}}>+ ثبت مشتری جدید</button><Link to={adminRoot+'/crm/all-requests'}>درخواست‌های ملکی</Link></div>
  </header>
  <div className="spad-crm-pro__metrics">
   <article><small>کل پرونده‌های CRM</small><strong>{num(summary?.total)}</strong><i className="simple-icon-people"/></article>
   <article><small>سرنخ‌های جدید</small><strong>{num(summary?.stage?.new)}</strong><i className="simple-icon-star"/></article>
   <article><small>موارد در مذاکره</small><strong>{num(summary?.stage?.negotiation)}</strong><i className="simple-icon-bubbles"/></article>
   <article><small>پیگیری‌های سررسیدشده</small><strong>{num(summary?.overdue)}</strong><i className="simple-icon-clock"/></article>
   <article><small>درخواست‌های ارتباط امروز</small><strong>{num(summary?.outbound_today)}</strong><i className="simple-icon-envelope"/></article>
  </div>
  <div className="spad-crm-pro__provider-strip">
   <div><span className={summary?.providers?.sms?'ready':'waiting'}/><b>ارسال پیامک SMS.ir</b><small>{summary?.providers?.sms?'آماده ثبت درخواست':'نیازمند کلید و شماره خط در سرور'}</small></div>
   <div><span className={summary?.providers?.voice?'ready':'waiting'}/><b>تماس صوتی متن‌به‌گفتار</b><small>{summary?.providers?.voice?'آماده ثبت درخواست':'نیازمند فعال‌سازی ارائه‌دهنده خودگو'}</small></div>
   <p>هر ارسال فقط برای یک مخاطب دارای رضایت، پس از تأیید مدیر انجام می‌شود. پذیرش درخواست توسط اپراتور به معنای تحویل یا پاسخ‌گویی نیست.</p>
  </div>
  {error&&<div className="spad-crm-pro__alert" role="alert">{error}</div>}
  {info&&<div className="spad-crm-pro__success" role="status">{info}</div>}
  <div className="spad-crm-pro__workspace">
   <section className="spad-crm-pro__directory">
    <div className="spad-crm-pro__section-head"><div><h2>دفتر مشتریان</h2><p>مشاهده، دسته‌بندی و مدیریت دقیق سرنخ‌ها</p></div><button type="button" onClick={reload} disabled={saving}>تازه‌سازی <i className="simple-icon-refresh"/></button></div>
    <form className="spad-crm-pro__search" onSubmit={e=>{e.preventDefault();setPage(1);setQuery(search);}}>
     <input aria-label="جستجوی مشتری" placeholder="جستجوی نام، موبایل یا شهر" value={search} onChange={e=>setSearch(e.target.value)}/>
     <select aria-label="مرحله معامله" value={stage} onChange={e=>{setStage(e.target.value);setPage(1);}}><option value="">همه مراحل</option>{STAGES.map(([value,title])=><option key={value} value={value}>{title}</option>)}</select>
     <button type="submit"><i className="simple-icon-magnifier"/> جستجو</button>
    </form>
    <div className="spad-crm-pro__columns"><span>مشتری / شماره</span><span>نیاز و وضعیت</span><span>شهر / اولویت</span></div>
    <div className="spad-crm-pro__contacts">{contacts.length?contacts.map(item=><button type="button" key={item.id} className={'spad-crm-pro__contact '+(selected===item.id?'selected':'')} onClick={()=>{setSelected(item.id);setForm(null);setInfo('');}}>
      <span className="spad-crm-pro__identity"><span className="avatar">{item.full_name.trim().charAt(0)}</span><span><b>{item.full_name}</b><small dir="ltr">{item.mobile}</small></span></span>
      <span className="spad-crm-pro__stage"><b>{label(INTERESTS,item.interest)}</b><small>{label(STAGES,item.stage)}</small></span>
      <span className="spad-crm-pro__location"><b>{item.city||'—'}</b><small>{item.priority===1?'اولویت بالا':item.priority===3?'اولویت پایین':'اولویت معمولی'}</small></span>
     </button>):<div className="spad-crm-pro__empty">{loading?'در حال دریافت…':'مشتری مطابق این فیلتر پیدا نشد.'}</div>}</div>
    <div className="spad-crm-pro__pages"><span>{num(total)} پرونده</span><div><button type="button" disabled={page<=1} onClick={()=>setPage(p=>p-1)}>قبلی</button><span>صفحه {num(page)}</span><button type="button" disabled={page*15>=total} onClick={()=>setPage(p=>p+1)}>بعدی</button></div></div>
   </section>
   <section className="spad-crm-pro__record">
    {!detail?<div className="spad-crm-pro__placeholder"><i className="simple-icon-user-following"/><h2>پرونده مشتری</h2><p>یکی از مشتریان را انتخاب کنید یا پرونده جدیدی بسازید تا اطلاعات، پیگیری‌ها و ارتباطات اینجا نمایش داده شوند.</p></div>:
    <><div className="spad-crm-pro__profile-head"><div><span>پرونده شماره {num(detail.data.id)}</span><h2>{detail.data.full_name}</h2><p>{label(INTERESTS,detail.data.interest)} · {detail.data.city||'شهر نامشخص'} · {label(STAGES,detail.data.stage)}</p></div>
     <button type="button" onClick={()=>setForm({...detail.data})}><i className="simple-icon-pencil"/> ویرایش پرونده</button></div>
     <div className="spad-crm-pro__facts"><div><small>شماره همراه</small><b dir="ltr">{detail.data.mobile}</b></div><div><small>بودجه تقریبی</small><b>{detail.data.budget||'ثبت نشده'}</b></div><div><small>محدوده مدنظر</small><b>{detail.data.preferred_location||'ثبت نشده'}</b></div></div>
     {detail.data.note&&<p className="spad-crm-pro__note">{detail.data.note}</p>}
     <div className="spad-crm-pro__consents"><span className={detail.data.sms_consent&&!detail.data.opted_out_at?'yes':'no'}>پیامک: {detail.data.sms_consent?'رضایت ثبت شده':'بدون رضایت'}</span><span className={detail.data.voice_consent&&!detail.data.opted_out_at?'yes':'no'}>تماس صوتی: {detail.data.voice_consent?'رضایت ثبت شده':'بدون رضایت'}</span>
      <button type="button" disabled={saving||Boolean(detail.data.opted_out_at)} onClick={()=>{if(window.confirm('عدم تمایل مشتری برای تمام ارتباطات خودکار ثبت شود؟'))post(API+'/contacts/'+selected+'/opt-out',{});}}>ثبت عدم تمایل</button></div>
     <div className="spad-crm-pro__tabs">
      <div className="spad-crm-pro__half"><h3><i className="simple-icon-calendar"/> برنامه پیگیری</h3>
       <form onSubmit={addTask} className="spad-crm-pro__mini-form">
        <input placeholder="عنوان پیگیری" required minLength={3} maxLength={170} value={task.title} onChange={e=>setTask({...task,title:e.target.value})}/>
        <div><select value={task.kind} onChange={e=>setTask({...task,kind:e.target.value})}><option value="call">تماس کارشناسی</option><option value="visit">بازدید ملک</option><option value="meeting">جلسه</option><option value="document">مدارک</option><option value="other">سایر</option></select><input type="datetime-local" required value={task.due_at} onChange={e=>setTask({...task,due_at:e.target.value})}/></div>
        <button type="submit" disabled={saving}>ثبت پیگیری</button>
       </form>
       <div className="spad-crm-pro__mini-list">{detail.tasks?.length?detail.tasks.slice(0,7).map(t=><div key={t.id}><div><b>{t.title}</b><small>{date(t.due_at)}</small></div>{t.status==='pending'?<button type="button" disabled={saving} onClick={()=>post(API+'/contacts/'+selected+'/followups/'+t.id+'/complete',{})}>انجام شد</button>:<span className="done">تکمیل شده</span>}</div>):<p>پیگیری ثبت نشده است.</p>}</div>
      </div>
      <div className="spad-crm-pro__half"><h3><i className="simple-icon-envelope"/> پیام و تماس صوتی</h3>
       <form onSubmit={send} className="spad-crm-pro__mini-form">
        <select value={outbound.channel} onChange={e=>setOutbound({...outbound,channel:e.target.value})}><option value="sms">ارسال پیامک اختصاصی</option><option value="voice">تماس صوتی خودکار از متن</option></select>
        <textarea required minLength={10} maxLength={650} rows={4} value={outbound.message} onChange={e=>setOutbound({...outbound,message:e.target.value})} placeholder="متن اختصاصی قابل ارسال یا خواندن با صدای مصنوعی برای مشتری…"/>
        <button type="submit" disabled={saving||Boolean(detail.data.opted_out_at)||!detail.data[outbound.channel+'_consent']||!summary?.providers?.[outbound.channel]}>تأیید و درخواست {outbound.channel==='sms'?'ارسال پیامک':'تماس صوتی'}</button>
       </form>
       <div className="spad-crm-pro__mini-list">{detail.outbound?.length?detail.outbound.slice(0,5).map(item=><div key={item.id}><div><b>{item.channel==='sms'?'پیامک':'تماس صوتی'} · {item.status==='accepted'?'پذیرفته‌شده، نه تحویل قطعی':item.status==='failed'?'ناموفق':item.status==='unknown'?'وضعیت نامشخص':'در حال پردازش'}</b><small>{date(item.created_at)} — {item.message.slice(0,100)}</small></div></div>):<p>ارتباطی ثبت نشده است.</p>}</div>
      </div>
     </div>
     <div className="spad-crm-pro__activity"><h3>تاریخچه ارتباط و اقدامات</h3>
      <form onSubmit={addNote}><select value={noteType} onChange={e=>setNoteType(e.target.value)}><option value="note">یادداشت</option><option value="meeting">جلسه</option><option value="call_log">نتیجه تماس</option><option value="visit">بازدید ملک</option><option value="offer">پیشنهاد معامله</option></select><input required minLength={2} maxLength={3000} value={note} placeholder="ثبت گزارش و نکته مهم مشتری…" onChange={e=>setNote(e.target.value)}/><button disabled={saving}>ثبت</button></form>
      {detail.activities?.length?detail.activities.slice(0,10).map(a=><div className="spad-crm-pro__activity-row" key={a.id}><i className="simple-icon-check"/><div><b>{a.type.replaceAll('_',' ')}</b><p>{a.content||'وضعیت عملیات ثبت شد.'}</p><small>{date(a.created_at)}</small></div></div>):<p className="spad-crm-pro__empty">هنوز رویدادی ثبت نشده است.</p>}
     </div>
    </>}
   </section>
  </div>
  {form&&<div className="spad-crm-pro__overlay" role="presentation" onMouseDown={e=>{if(e.target===e.currentTarget&&!saving)setForm(null);}}>
   <form onSubmit={submit} className="spad-crm-pro__modal" role="dialog" aria-modal="true" aria-label="پرونده مشتری">
    <header><div><span>پرونده CRM</span><h2>{form.id?'ویرایش مشتری':'افزودن مشتری جدید'}</h2><p>اطلاعات و رضایت‌های ارتباطی را مطابق مدارک واقعی وارد کنید.</p></div><button type="button" disabled={saving} onClick={()=>setForm(null)}>×</button></header>
    <div className="spad-crm-pro__fields">
     <Field label="نام و نام خانوادگی"><input required minLength={2} maxLength={150} value={form.full_name} onChange={e=>setForm({...form,full_name:e.target.value})}/></Field>
     <Field label="شماره همراه"><input required inputMode="tel" dir="ltr" placeholder="09…" value={form.mobile} onChange={e=>setForm({...form,mobile:e.target.value})}/></Field>
     <Field label="نوع درخواست"><select value={form.interest} onChange={e=>setForm({...form,interest:e.target.value})}>{INTERESTS.map(([id,text])=><option key={id} value={id}>{text}</option>)}</select></Field>
     <Field label="مرحله پرونده"><select value={form.stage} onChange={e=>setForm({...form,stage:e.target.value})}>{STAGES.map(([id,text])=><option key={id} value={id}>{text}</option>)}</select></Field>
     <Field label="شهر"><input maxLength={90} value={form.city||''} onChange={e=>setForm({...form,city:e.target.value})}/></Field>
     <Field label="محدوده مدنظر"><input maxLength={170} value={form.preferred_location||''} onChange={e=>setForm({...form,preferred_location:e.target.value})}/></Field>
     <Field label="بودجه تقریبی"><input maxLength={90} value={form.budget||''} onChange={e=>setForm({...form,budget:e.target.value})}/></Field>
     <Field label="اولویت"><select value={form.priority} onChange={e=>setForm({...form,priority:Number(e.target.value)})}><option value={1}>بالا</option><option value={2}>معمولی</option><option value={3}>پایین</option></select></Field>
     <Field label="پست الکترونیک"><input type="email" maxLength={190} value={form.email||''} onChange={e=>setForm({...form,email:e.target.value})}/></Field>
     <Field label="منبع آشنایی"><input maxLength={50} value={form.source||''} onChange={e=>setForm({...form,source:e.target.value})}/></Field>
     <Field label="توضیحات پرونده" full><textarea rows={3} maxLength={3000} value={form.note||''} onChange={e=>setForm({...form,note:e.target.value})}/></Field>
     <Field label="مجوزهای ارتباط" full><div className="spad-crm-pro__checkboxes"><label><input type="checkbox" checked={Boolean(form.sms_consent)} onChange={e=>setForm({...form,sms_consent:e.target.checked})}/> رضایت صریح برای پیامک</label><label><input type="checkbox" checked={Boolean(form.voice_consent)} onChange={e=>setForm({...form,voice_consent:e.target.checked})}/> رضایت صریح برای تماس صوتی خودکار</label></div></Field>
     <Field label="منبع یا مدرک رضایت مخاطب (در صورت فعال‌کردن ارسال)" full><input maxLength={120} required={Boolean(form.sms_consent||form.voice_consent)} placeholder="تاریخ، فرم رضایت، جلسه یا مدرک ثبت‌شده…" value={form.consent_source||''} onChange={e=>setForm({...form,consent_source:e.target.value})}/></Field>
    </div><footer><button type="button" onClick={()=>setForm(null)} disabled={saving}>انصراف</button><button type="submit" disabled={saving}>{saving?'در حال ثبت…':'ذخیره پرونده مشتری'}</button></footer>
   </form>
  </div>}
 </div>;
}

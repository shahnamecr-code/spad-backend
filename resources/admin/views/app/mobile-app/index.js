import React, {useEffect, useState} from "react";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import "./mobile-app.scss";

const base=adminPathApi+"/mobile-app";
const initial={
 maintenance_enabled:false,maintenance_message:"",latest_version:"",min_version:"",update_url:"",update_message:"",
 announcement_enabled:false,announcement_text:"",announcement_link:"",show_market_ticker:true,show_intro:true,
 support_phone:"",notifications_enabled:true,notification_check_minutes:30
};
const emptyMessage={title:"",body:"",link:"",audience:"all",mobile:""};
const fa=n=>Number(n||0).toLocaleString("fa-IR");
const date=v=>{try{return v?new Date(v).toLocaleString("fa-IR",{dateStyle:"medium",timeStyle:"short"}):"";}catch(e){return "";}};
const mobileNumber=value=>value
 .replace(/[۰-۹]/g,digit=>String("۰۱۲۳۴۵۶۷۸۹".indexOf(digit)))
 .replace(/[٠-٩]/g,digit=>String("٠١٢٣٤٥٦٧٨٩".indexOf(digit)))
 .replace(/[^0-9+]/g,"");
const errorText=(e,fallback)=>{
 const d=e?.response?.data;
 if(d?.errors){const first=Object.values(d.errors)[0];if(Array.isArray(first)&&first[0])return first[0];}
 return d?.message||fallback;
};
const Card=({title,children})=><section className="spad-app-admin-card"><h2>{title}</h2>{children}</section>;
const Toggle=({checked,onChange,children})=><label className="spad-app-admin-toggle">
 <input type="checkbox" checked={Boolean(checked)} onChange={e=>onChange(e.target.checked)}/><span>{children}</span></label>;

export default function MobileAppAdmin(){
 const [tab,setTab]=useState("notifications");
 const [form,setForm]=useState(initial);
 const [stats,setStats]=useState(null);
 const [message,setMessage]=useState(emptyMessage);
 const [list,setList]=useState({data:[],current_page:1,last_page:1,total:0});
 const [loading,setLoading]=useState(true),[busy,setBusy]=useState(false);
 const [loaded,setLoaded]=useState(false);
 const [inboxEnabled,setInboxEnabled]=useState(null);
 const [error,setError]=useState(""),[ok,setOk]=useState("");
 const set=(k,v)=>setForm(f=>({...f,[k]:v}));
 const setMsg=(k,v)=>setMessage(m=>({...m,[k]:v}));

 const loadSettings=async()=>{
  const {data}=await axios.get(base+"/settings");
  setForm({...initial,...data.data});setStats(data.stats);setInboxEnabled(data.data.notifications_enabled);
 };
 const loadList=async(page=1)=>{
  const {data}=await axios.get(base+"/notifications",{params:{page}});
  setList(data);setStats(data.stats);
  if(typeof data.notifications_enabled==="boolean")setInboxEnabled(data.notifications_enabled);
 };
 const load=async()=>{
  setLoading(true);setError("");
  try{await Promise.all([loadSettings(),loadList(1)]);setLoaded(true);}
  catch(e){
   if([401,403].includes(e?.response?.status))setLoaded(false);
   setError(errorText(e,"خطا در دریافت اطلاعات اپلیکیشن"));
  }
  finally{setLoading(false);}
 };
 useEffect(()=>{load();},[]);

 const saveSettings=async e=>{
  e.preventDefault();setBusy(true);setError("");setOk("");
  try{
   const {data}=await axios.post(base+"/settings",{...form,notification_check_minutes:Number(form.notification_check_minutes)});
   setForm({...initial,...data.data});setStats(data.stats);setInboxEnabled(data.data.notifications_enabled);
   setOk("تنظیمات ذخیره شد. اپ آن‌ها را در بررسی بعدی دریافت می‌کند.");
  }catch(e){setError(errorText(e,"تنظیمات ذخیره نشد. اطلاعات را بررسی کنید."));}
  finally{setBusy(false);}
 };

 const send=async e=>{
  e.preventDefault();
  if(!inboxEnabled||busy)return;
  const who=message.audience==="all"?"همه کاربران اپلیکیشن":"کاربر "+message.mobile;
  if(!window.confirm("این اعلان برای "+who+" فرستاده شود؟"))return;
  setBusy(true);setError("");setOk("");
  try{
   await axios.post(base+"/notifications",{
    title:message.title,body:message.body,link:message.link||null,audience:message.audience,
    ...(message.audience==="user"?{mobile:message.mobile}:{})
   });
   setMessage(emptyMessage);setOk("اعلان ثبت شد و در صندوق اعلان‌های اپ قرار گرفت.");
   await loadList(1).catch(()=>setError("اعلان ثبت شد. برای تازه‌سازی فهرست از دکمه به‌روزرسانی استفاده کنید."));
  }catch(e){setError(errorText(e,"اعلان فرستاده نشد."));}
  finally{setBusy(false);}
 };

 const revoke=async item=>{
  if(!window.confirm("نمایش «"+item.title+"» در صندوق اپ لغو شود؟ سابقه در پنل می‌ماند."))return;
  setBusy(true);setError("");setOk("");
  try{
   await axios.delete(base+"/notifications/"+item.id);
   setOk("نمایش اعلان لغو شد.");
   await loadList(list.current_page).catch(()=>setError("نمایش اعلان لغو شد. فهرست را دوباره به‌روزرسانی کنید."));
  }
  catch(e){setError(errorText(e,"نمایش اعلان لغو نشد."));}
  finally{setBusy(false);}
 };

 const loadPage=async page=>{
  setBusy(true);setError("");
  try{await loadList(page);}
  catch(e){setError(errorText(e,"دریافت صفحه اعلان‌ها انجام نشد."));}
  finally{setBusy(false);}
 };

 return <div dir="rtl" className="spad-app-admin">
  <header className="spad-app-admin-hero">
   <div><span>SPAD MOBILE APP</span><h1>تنظیمات اپلیکیشن</h1>
    <p>ارسال اعلان به کاربران اپ اندروید و آیفون، اطلاعیه بالای صفحه، به‌روزرسانی، حالت تعمیر و بخش‌های قابل نمایش اپ.</p></div>
   <button type="button" onClick={load} disabled={busy||loading}>به‌روزرسانی</button>
  </header>

  {stats?<div className="spad-app-admin-stats">
   <div><b>{fa(stats.devices)}</b><span>نصب ثبت‌شده</span></div>
   <div><b>{fa(stats.active_30_days)}</b><span>فعال در ۳۰ روز اخیر</span></div>
   <div><b>{fa(stats.android)}</b><span>اندروید</span></div>
   <div><b>{fa(stats.ios)}</b><span>آیفون</span></div>
   <div><b>{fa(stats.signed_in)}</b><span>وارد حساب شده</span></div>
   <div><b>{fa(stats.notifications_on)}</b><span>اعلان روشن</span></div>
  </div>:null}

  {loaded?<nav className="spad-app-admin-tabs" aria-label="بخش‌های تنظیمات اپلیکیشن">
   <button type="button" className={tab==="notifications"?"active":""} onClick={()=>setTab("notifications")}>اعلان‌ها</button>
   <button type="button" className={tab==="settings"?"active":""} onClick={()=>setTab("settings")}>تنظیمات اپ</button>
  </nav>:null}

  {error?<div className="spad-app-admin-alert" role="alert">{error}</div>:null}
  {ok?<div className="spad-app-admin-ok" role="status">{ok}</div>:null}

  {loading?<p className="spad-app-admin-loading">در حال بارگذاری...</p>:!loaded?null:tab==="notifications"?<div className="spad-app-admin-grid">
   <Card title="ارسال اعلان تازه">
    {!inboxEnabled?<p className="spad-app-admin-alert" role="status">دریافت اعلان در اپ خاموش است. آن را از بخش تنظیمات اپ فعال کنید.</p>:null}
    <form onSubmit={send}>
     <div className="spad-app-admin-audience">
      <label><input type="radio" checked={message.audience==="all"} onChange={()=>setMsg("audience","all")}/> همه کاربران اپ</label>
      <label><input type="radio" checked={message.audience==="user"} onChange={()=>setMsg("audience","user")}/> یک کاربر مشخص</label>
     </div>
     {message.audience==="user"?<label>شماره موبایل کاربر<input dir="ltr" inputMode="tel" maxLength={14} required placeholder="09xxxxxxxxx" value={message.mobile} onChange={e=>setMsg("mobile",mobileNumber(e.target.value))}/></label>:null}
     <label>عنوان<input maxLength={120} required value={message.title} onChange={e=>setMsg("title",e.target.value)}/></label>
     <label>متن اعلان<textarea rows={4} maxLength={600} required value={message.body} onChange={e=>setMsg("body",e.target.value)}/></label>
     <label>با لمس اعلان باز شود (اختیاری)<input dir="ltr" maxLength={300} placeholder="/search  یا  /property/165  یا  https://spad.estate/..." value={message.link} onChange={e=>setMsg("link",e.target.value)}/></label>
     <p>صفحه‌های داخل اپ با «/» شروع می‌شوند؛ مثلاً <code>/search</code>، <code>/live-price</code>، <code>/inquiries</code>، <code>/property/165</code> یا <code>/service?slug=gold-currency</code>.</p>
     <div className="spad-app-admin-actions"><button type="submit" disabled={busy||!inboxEnabled}>{busy?"در حال انجام...":"ارسال اعلان"}</button></div>
    </form>
   </Card>
   <Card title={"سابقه اعلان‌ها ("+fa(list.total)+")"}>
    {list.data.length===0?<p>هنوز اعلانی فرستاده نشده است.</p>:<ul className="spad-app-admin-list">
     {list.data.map(item=><li key={item.id}>
      <div className="head"><b>{item.title}</b><span className={item.audience==="all"?"tag all":"tag user"}>{item.audience==="all"?"همگانی":(item.user?.name||item.user?.mobile||"شخصی")}</span></div>
      <p>{item.body}</p>
      <div className="meta"><span>{date(item.created_at)}{item.admin?" · "+item.admin:""}</span>{item.link?<code dir="ltr">{item.link}</code>:null}
       {item.revoked_at?<span className="tag revoked">لغو شده · {date(item.revoked_at)}{item.revoked_by?" · "+item.revoked_by:""}</span>:
        <button type="button" onClick={()=>revoke(item)} disabled={busy}>لغو نمایش</button>}</div>
     </li>)}
    </ul>}
    {list.last_page>1?<div className="spad-app-admin-pager">
     <button type="button" disabled={busy||list.current_page<=1} onClick={()=>loadPage(list.current_page-1)}>قبلی</button>
     <span>{fa(list.current_page)} از {fa(list.last_page)}</span>
     <button type="button" disabled={busy||list.current_page>=list.last_page} onClick={()=>loadPage(list.current_page+1)}>بعدی</button>
    </div>:null}
   </Card>
  </div>:<form onSubmit={saveSettings}>
   <div className="spad-app-admin-grid">
    <Card title="اعلان‌ها">
     <Toggle checked={form.notifications_enabled} onChange={v=>set("notifications_enabled",v)}>دریافت اعلان در اپ فعال باشد</Toggle>
     <label>فاصله بررسی اعلان تازه در پس‌زمینه (دقیقه)<input type="number" min="15" max="1440" required value={form.notification_check_minutes} onChange={e=>set("notification_check_minutes",e.target.value)}/></label>
     <p>اپ هنگام باز شدن و با اجازهٔ گوشی در پس‌زمینه اعلان‌های تازه را بررسی می‌کند. زمان اجرای بررسی پس‌زمینه را سیستم‌عامل تعیین می‌کند.</p>
    </Card>
    <Card title="اطلاعیه بالای صفحه اصلی">
     <Toggle checked={form.announcement_enabled} onChange={v=>set("announcement_enabled",v)}>نمایش اطلاعیه</Toggle>
     <label>متن اطلاعیه<input maxLength={200} value={form.announcement_text} onChange={e=>set("announcement_text",e.target.value)}/></label>
     <label>با لمس اطلاعیه باز شود (اختیاری)<input dir="ltr" maxLength={300} placeholder="/services" value={form.announcement_link} onChange={e=>set("announcement_link",e.target.value)}/></label>
    </Card>
    <Card title="نسخه و به‌روزرسانی">
     <label>آخرین نسخه منتشرشده<input dir="ltr" placeholder="1.0.0" value={form.latest_version} onChange={e=>set("latest_version",e.target.value.trim())}/></label>
     <label>حداقل نسخه مجاز (به‌روزرسانی اجباری)<input dir="ltr" placeholder="خالی = اجباری نیست" value={form.min_version} onChange={e=>set("min_version",e.target.value.trim())}/></label>
     <label>نشانی دریافت نسخه تازه<input dir="ltr" placeholder="https://..." value={form.update_url} onChange={e=>set("update_url",e.target.value.trim())}/></label>
     <label>پیام به‌روزرسانی<input maxLength={300} value={form.update_message} onChange={e=>set("update_message",e.target.value)}/></label>
     <p>نسخه‌های پایین‌تر از «آخرین نسخه» پیشنهاد به‌روزرسانی می‌بینند؛ نسخه‌های پایین‌تر از «حداقل نسخه» تا به‌روزرسانی نکنند وارد اپ نمی‌شوند.</p>
    </Card>
    <Card title="حالت تعمیر و بخش‌های اپ">
     <Toggle checked={form.maintenance_enabled} onChange={v=>set("maintenance_enabled",v)}>حالت تعمیر (اپ فقط پیام زیر را نشان می‌دهد)</Toggle>
     <label>پیام حالت تعمیر<textarea rows={2} maxLength={300} required value={form.maintenance_message} onChange={e=>set("maintenance_message",e.target.value)}/></label>
     <Toggle checked={form.show_market_ticker} onChange={v=>set("show_market_ticker",v)}>نوار قیمت لحظه‌ای طلا و ارز در بالای اپ</Toggle>
     <Toggle checked={form.show_intro} onChange={v=>set("show_intro",v)}>معرفی چندصفحه‌ای در اولین اجرا</Toggle>
     <label>شماره پشتیبانی در اپ (اختیاری)<input dir="ltr" maxLength={20} value={form.support_phone} onChange={e=>set("support_phone",e.target.value)}/></label>
    </Card>
   </div>
   <footer className="spad-app-admin-actions"><button type="submit" disabled={busy}>{busy?"در حال ذخیره...":"ذخیره تنظیمات اپلیکیشن"}</button><span>فقط مدیر اصلی اسپاد می‌تواند این بخش را تغییر دهد.</span></footer>
  </form>}
 </div>;
}


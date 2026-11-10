import React, {useCallback, useEffect, useRef, useState} from "react";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import "./whatsapp-market.scss";

const endpoint = adminPathApi + "/whatsapp-market";
const defaults = {
  enabled:false, schedule:{markets:["10:00","18:00"],cities:["11:00"],hourly_markets:false},
  cities:[{province:"تهران",city:"تهران"},{province:"فارس",city:"شیراز"}],
  groups:[], phone_number_id:"", access_token:"", token_configured:false,
  official_groups_acknowledged:false,render_available:false
};
const emptyPersonal={
 connection:{state:"loading",ready:false,service_online:false,number_masked:"",pairing_available:false},
 preferences:{enabled:false,schema_ready:false,uses_market_schedule:true,max_messages_per_day:12},
 jobs:[]
};
const rowsToText = a => (a || []).map(x => x.province+"|"+x.city).join("\n");
const readCities = s => s.split(/\n+/).map(x => x.trim()).filter(Boolean).map(x => {
 const [province, ...rest] = x.split("|");
 return {province:(province||"").trim(),city:rest.join("|").trim()};
}).filter(x=>x.province&&x.city);
const normalizeTimes = s => [...new Set(s.split(/[,،\n]+/).map(t=>t.trim()).filter(Boolean))];
const formatDate = value => {
 if(!value)return "—";
 try {return new Date(value).toLocaleString("fa-IR",{timeZone:"Asia/Tehran"});}
 catch(_){return value;}
};
const decodePreviewError = async error => {
 const status=error?.response?.status;
 if(status===429){
  const seconds=Number(error?.response?.headers?.["retry-after"]||0);
  const retry=Number.isFinite(seconds)&&seconds>0&&seconds<=3600
   ? " حدود "+Math.ceil(seconds)+" ثانیه دیگر"
   : " چند لحظه دیگر";
  return "برای جلوگیری از درخواست‌های بیش از حد، پیش‌نمایش موقتاً محدود شده است؛"+retry+" دوباره امتحان کنید.";
 }
 if(status===401||status===403)return "نشست مدیریت معتبر نیست یا مجوز مشاهده بنر را ندارید. دوباره وارد پنل مدیر اصلی شوید.";
 const payload=error?.response?.data;
 if(payload instanceof Blob){
  try {
   const text=await payload.text();
   const data=JSON.parse(text);
   if(data?.message)return data.message;
  }catch(_){}
 }
 if(payload?.message)return payload.message;
 if(!error?.response)return "اتصال با سرور پیش‌نمایش برقرار نشد. دسترسی شبکه را بررسی کنید.";
 return "پیش‌نمایش در پاسخ سرور خطا دارد (کد "+status+"). گزارش علت برای بررسی ثبت شد.";
};
const Box = ({title,children})=><section className="spad-wa-card"><h2>{title}</h2>{children}</section>;

export default function WhatsAppMarketAdmin(){
 const [form,setForm]=useState(defaults);
 const [marketTimes,setMarketTimes]=useState("10:00, 18:00");
 const [cityTimes,setCityTimes]=useState("11:00");
 const [citiesText,setCitiesText]=useState(rowsToText(defaults.cities));
 const [groupsText,setGroupsText]=useState("");
 const [logs,setLogs]=useState([]);
 const [loaded,setLoaded]=useState(false),[busy,setBusy]=useState(false),[previewBusy,setPreviewBusy]=useState(false);
 const [error,setError]=useState(""),[success,setSuccess]=useState("");
 const [preview,setPreview]=useState(""),[previewKind,setPreviewKind]=useState("market"),[previewCity,setPreviewCity]=useState(0);
 const [personal,setPersonal]=useState(emptyPersonal);
 const [personalBusy,setPersonalBusy]=useState(false);
 const [personalQr,setPersonalQr]=useState("");
 const [personalError,setPersonalError]=useState("");
 const [personalMessage,setPersonalMessage]=useState("");
 const currentPreview=useRef("");
 const previewInFlight=useRef(false);
 const set=(key,value)=>setForm(p=>({...p,[key]:value}));
 const load=useCallback(async()=>{
  setError("");
  try {
   const res=await axios.get(endpoint);
   const data={...defaults,...res.data.data,access_token:"",official_groups_acknowledged:false};
   setForm(data);setLogs(res.data.logs||[]);
   setMarketTimes((data.schedule?.markets||[]).join(", "));
   setCityTimes((data.schedule?.cities||[]).join(", "));
   setCitiesText(rowsToText(data.cities));setGroupsText((data.groups||[]).join("\n"));
  }catch(e){setError(e?.response?.data?.message||"دریافت تنظیمات انتشار امکان‌پذیر نیست.");}
  finally{setLoaded(true);}
 },[]);
 useEffect(()=>{load();return()=>{if(currentPreview.current)URL.revokeObjectURL(currentPreview.current);};},[load]);
 const refreshPersonal=useCallback(async()=>{
  try{
   const {data}=await axios.get(endpoint+"/personal/status");
   setPersonal(data||emptyPersonal);
   return data;
  }catch(e){
   setPersonal(emptyPersonal);
   setPersonalError(e?.response?.data?.message||"ارتباط با بخش واتساپ شخصی برقرار نشد.");
   return null;
  }
 },[]);
 useEffect(()=>{refreshPersonal();},[refreshPersonal]);
 const showPairingQr=async()=>{
  setPersonalBusy(true);setPersonalError("");setPersonalMessage("");
  try{
   const {data}=await axios.get(endpoint+"/personal/qr");
   if(data?.image?.startsWith("data:image/png;base64,")){
    setPersonalQr(data.image);
    setPersonalMessage("کد QR را با واتساپ خودت از بخش دستگاه‌های متصل اسکن کن.");
   }else{
    setPersonalQr("");
    setPersonalMessage(data?.state==="ready"?"حساب شخصی قبلاً متصل است.":"کد QR هنوز آماده نیست؛ چند لحظه دیگر دوباره تلاش کن.");
   }
  }catch(e){
   setPersonalError(e?.response?.data?.message||"دریافت QR فعلاً ممکن نیست.");
  }finally{setPersonalBusy(false);}
 };
 const togglePersonalSchedule=async()=>{
  const enabled=!personal.preferences?.enabled;
  if(enabled&&!window.confirm("پس از فعال‌سازی، بنرها در زمان‌های انتخابی فقط به گفت‌وگوی شخصی خودت ارسال می‌شوند؛ این اتصال از روش غیررسمی واتساپ وب استفاده می‌کند و ریسک محدودیت حساب دارد. تأیید می‌کنی؟"))return;
  setPersonalBusy(true);setPersonalError("");setPersonalMessage("");
  try{
   await axios.post(endpoint+"/personal/settings",{enabled});
   await refreshPersonal();
   setPersonalMessage(enabled?"ارسال زمان‌بندی‌شده به گفت‌وگوی شخصی فعال شد.":"ارسال زمان‌بندی‌شده به گفت‌وگوی شخصی خاموش شد.");
  }catch(e){setPersonalError(e?.response?.data?.message||"ثبت تنظیمات واتساپ شخصی انجام نشد.");}
  finally{setPersonalBusy(false);}
 };
 const sendPersonalTest=async()=>{
  if(!personal.connection?.ready)return;
  if(!window.confirm("فقط یک بنر به گفت‌وگوی «پیام به خودم» در واتساپ متصل‌شده ارسال شود؟"))return;
  setPersonalBusy(true);setPersonalError("");setPersonalMessage("");
  const place=cities[previewCity]||cities[0];
  try{
   const {data}=await axios.post(endpoint+"/personal/send",{
    kind:previewKind,...(previewKind==="city"?place:{}),confirmed:true
   });
   setPersonalMessage(data?.message||"درخواست ارسال آزمایشی پذیرفته شد.");
   await refreshPersonal();
  }catch(e){setPersonalError(e?.response?.data?.message||"ارسال آزمایشی انجام نشد. وضعیت اتصال را بررسی کن.");}
  finally{setPersonalBusy(false);}
 };

 const cities=readCities(citiesText);
 const save=async e=>{
  e.preventDefault();setError("");setSuccess("");
  const schedule={markets:normalizeTimes(marketTimes),cities:normalizeTimes(cityTimes),hourly_markets:!!form.schedule?.hourly_markets};
  const groups=[...new Set(groupsText.split(/\n+/).map(x=>x.trim()).filter(Boolean))];
  if(!schedule.markets.length||!schedule.cities.length||!cities.length){setError("ساعت‌ها و حداقل یک شهر باید تنظیم شوند.");return;}
  setBusy(true);
  try{
   const res=await axios.post(endpoint,{
    enabled:Boolean(form.enabled),schedule,cities,groups,
    phone_number_id:form.phone_number_id,
    access_token:form.access_token||undefined,
    official_groups_acknowledged:Boolean(form.official_groups_acknowledged)
   });
   setForm({...form,...res.data.data,access_token:"",official_groups_acknowledged:false});
   setSuccess("تنظیمات ذخیره شد. زمان‌بندی بر اساس ساعت ایران خواهد بود.");
   load();
  }catch(e){
   setError(e?.response?.data?.message||"ذخیره تنظیمات انجام نشد. فرمت زمان و شناسه‌ها را بررسی کنید.");
  }finally{setBusy(false);}
 };
 const showPreview=async()=>{
  if(previewInFlight.current)return;
  previewInFlight.current=true;
  setPreviewBusy(true);setError("");setSuccess("");
  const place=cities[previewCity]||cities[0];
  if(previewKind==="city"&&!place){previewInFlight.current=false;setPreviewBusy(false);setError("ابتدا یک شهر اضافه کنید.");return;}
  const params={kind:previewKind,...(previewKind==="city"?place:{})};
  try {
   let loaded=false;
   for(let attempt=0;attempt<24;attempt++){
    const res=await axios.get(endpoint+"/preview",{
     params,responseType:"blob",timeout:18000
    });
    const mime=String(res.headers?.["content-type"]||res.data?.type||"").toLowerCase();
    if(res.status===202 && mime.includes("application/json")){
     let body={};
     try{body=JSON.parse(await res.data.text());}catch(_){}
     if(body.status==="temporarily_unavailable"){
      throw new Error(body.message||"ساخت تصویر موقتاً ناموفق بوده است؛ چند دقیقه دیگر امتحان کنید.");
     }
     setSuccess("تصویر با نرخ واقعی در صف آماده‌سازی سرور است؛ پس از ساخته‌شدن نمایش داده می‌شود.");
     const next=Number(body.retry_after||5);
     await new Promise(resolve=>setTimeout(resolve,Math.max(3000,Math.min(10000,next*1000))));
     continue;
    }
    if(mime.includes("text/html")){
     throw new Error("نشست مدیریت منقضی شده است؛ لطفاً دوباره وارد پنل شوید.");
    }
    if(!mime.includes("image/png")||!(res.data instanceof Blob)||res.data.size<40000){
     throw new Error("پاسخ سرور تصویر PNG معتبر نیست. کد وضعیت: "+res.status);
    }
    if(currentPreview.current)URL.revokeObjectURL(currentPreview.current);
    const blob=new Blob([res.data],{type:"image/png"});
    currentPreview.current=URL.createObjectURL(blob);
    setPreview(currentPreview.current);
    setSuccess("");
    loaded=true;
    break;
   }
   if(!loaded)throw new Error("ساخت تصویر در صف سرور طولانی شده است؛ کمی بعد دوباره پیش‌نمایش را باز کنید.");
  }catch(e){
   setSuccess("");
   setError(e?.message&&!e.response?e.message:await decodePreviewError(e));
  }finally{previewInFlight.current=false;setPreviewBusy(false);}
 };
 const sendNow=async()=>{
  if(!window.confirm("این اقدام واقعاً بنر را به همه گروه‌های فعال Meta ارسال می‌کند. ادامه می‌دهید؟"))return;
  const place=cities[previewCity]||cities[0];
  if(previewKind==="city"&&!place){setError("شهر انتخاب نشده است.");return;}
  setBusy(true);setError("");setSuccess("");
  try{
   const res=await axios.post(endpoint+"/run",{kind:previewKind,...(previewKind==="city"?place:{}),confirmed:true});
   setSuccess(res.data.message||"درخواست ارسال ثبت شد. وضعیت را در گزارش‌ها بررسی کنید.");
   await load();
  }catch(e){setError(e?.response?.data?.message||"تلاش برای انتشار ناموفق بود.");}
  finally{setBusy(false);}
 };
 return <div className="spad-wa-panel" dir="rtl">
  <header className="spad-wa-hero">
   <span>دستیار تمام هوشمند ملکی اسپاد</span>
   <h1>انتشار هوشمند قیمت‌ها در واتساپ</h1>
   <p>استفاده از دو بنر تصویری تأییدشده اسپاد با فونت اصلی IRANSans و قیمت‌های واقعی بازار و محله‌ها؛ ثبت خودکار روز، تاریخ شمسی و ساعت تهران روی هر بنر.</p>
   <a href="https://spad.estate" target="_blank" rel="noreferrer">spad.estate</a>
  </header>
  <div className="spad-wa-warning" role="note">
   محدودیت سرویس رسمی: اتصال خودکار فقط برای گروه‌های کوچکِ واجد شرایط Meta Groups API قابل پیکربندی است (حساب تجاری رسمی و حداکثر ۸ عضو).
   API رسمی عمومی برای انتشار خودکار در کانال واتساپ وجود ندارد. فعال‌سازی این قسمت بدون مجوز و مقصد معتبر ممکن نیست.
  </div>
  {error?<div className="spad-wa-alert" role="alert">{error}</div>:null}
  {success?<div className="spad-wa-success" role="status">{success}</div>:null}
  {!loaded?<p>در حال دریافت تنظیمات...</p>:<form onSubmit={save}>
   <div className="spad-wa-grid">
    <Box title="وضعیت و زمان انتشار">
     <label className="spad-wa-toggle"><input type="checkbox" checked={!!form.enabled} onChange={e=>set("enabled",e.target.checked)}/> فعال‌سازی انتشار زمان‌بندی‌شده</label>
     <p>منطقه زمانی: تهران. تا قبل از ثبت و فعال‌سازی، هیچ پیامی فرستاده نمی‌شود.</p>
     <label>ساعت‌های روزانه ارز، طلا و دلار
      <input dir="ltr" value={marketTimes} onChange={e=>setMarketTimes(e.target.value)} placeholder="10:00, 18:00"/>
     </label>
     <label>ساعت‌های روزانه بنرهای ملکی
      <input dir="ltr" value={cityTimes} onChange={e=>setCityTimes(e.target.value)} placeholder="11:00"/>
     </label>
     <label className="spad-wa-toggle"><input type="checkbox" checked={!!form.schedule?.hourly_markets} onChange={e=>set("schedule",{...form.schedule,hourly_markets:e.target.checked})}/> انتشار خودکار نرخ ارز، طلا و دلار در ابتدای هر ساعت</label>
     <p>برای چند ساعت، زمان‌ها را با ویرگول جدا کنید. قالب ۲۴ساعته است.</p>
    </Box>
    <Box title="شهرها و محله‌های منتشرشونده">
     <p>هر سطر: نام استان | نام شهر. همه محله‌های دارای قیمت معتبر شهر در بنرهای چندصفحه‌ای قرار می‌گیرند.</p>
     <textarea dir="rtl" rows={6} value={citiesText} onChange={e=>{setCitiesText(e.target.value);setPreviewCity(0);}} placeholder={"فارس|شیراز\nتهران|تهران"}/>
     <p>اطلاعات از موتور بازار نمودا در اسپاد دریافت می‌شود؛ محله‌های بدون قیمت منتشر نمی‌شوند. شهر دلخواه را می‌توان افزود.</p>
    </Box>
    <Box title="اتصال واتساپ شخصی من">
     <p>با اسکن QR، واتساپ شخصی خودت به یک دستگاه متصل‌شده اضافه می‌شود. بنرها فقط به گفت‌وگوی «پیام به خودم» ارسال می‌شوند و انتشار در گروه‌ها دستی باقی می‌ماند.</p>
     <p className="spad-wa-personal-warning">این اتصال از روش غیررسمی واتساپ وب استفاده می‌کند؛ ممکن است با تغییرات واتساپ قطع شود یا برای حساب محدودیت ایجاد کند. فقط با آگاهی از این ریسک متصل شو.</p>
     <p className="spad-wa-personal-status">وضعیت اتصال: <strong>{personal.connection?.ready?"متصل به "+(personal.connection.number_masked||"واتساپ شخصی"):personal.connection?.service_online?"آماده اتصال، هنوز متصل نیست":"سرویس اتصال روی سرور در دسترس نیست"}</strong></p>
     <p>اتصال با QR: در گوشی وارد واتساپ ← دستگاه‌های متصل ← اتصال دستگاه شو، سپس کد را از پنل روی رایانه اسکن کن.</p>
     <div className="spad-wa-actions">
      <button type="button" disabled={personalBusy||personal.connection?.ready} onClick={showPairingQr}>نمایش QR اتصال شخصی</button>
      <button type="button" className="spad-wa-secondary" disabled={personalBusy} onClick={async()=>{setPersonalQr("");await refreshPersonal();}}>بررسی وضعیت اتصال</button>
     </div>
     {personalQr&&!personal.connection?.ready?<div className="spad-wa-personal-qr"><img src={personalQr} alt="QR اتصال واتساپ شخصی؛ فقط مدیر اصلی باید آن را اسکن کند"/><small>کد اتصال محرمانه است؛ از آن اسکرین‌شات عمومی منتشر نکن.</small></div>:null}
     <label className="spad-wa-toggle"><input type="checkbox" checked={!!personal.preferences?.enabled}
      disabled={personalBusy||!personal.connection?.ready}
      onChange={togglePersonalSchedule}/> ارسال خودکار به پیام‌های خودم طبق زمان‌بندی بالای صفحه</label>
     <p>ارسال با قالب‌های تأییدشده اسپاد؛ برای جلوگیری از ارسال زیاد، حداکثر ۱۲ تصویر در روز و یک صفحه از هر شهر در هر نوبت.</p>
     <button type="button" className="spad-wa-secondary" onClick={sendPersonalTest} disabled={!personal.connection?.ready||personalBusy}>ارسال آزمایشی همین بنر به واتساپ خودم</button>
     {personalError?<p className="spad-wa-personal-error" role="alert">{personalError}</p>:null}
     {personalMessage?<p className="spad-wa-personal-ok" role="status">{personalMessage}</p>:null}
     {(personal.jobs||[]).length?<div className="spad-wa-personal-jobs"><strong>آخرین تلاش‌های ارسال شخصی</strong>
      {personal.jobs.slice(0,5).map(j=><div key={j.id}>{j.subject}: {j.status==="accepted_local"?"پذیرش توسط اتصال":j.status==="failed"?"ناموفق":"در انتظار"}</div>)}
     </div>:null}
    </Box>
    <Box title="اتصال رسمی گروه‌های واتساپ">
     <label>شناسه شماره تجاری متا
      <input dir="ltr" value={form.phone_number_id} onChange={e=>set("phone_number_id",e.target.value)} placeholder="Phone Number ID"/>
     </label>
     <label>توکن دسترسی متا
      <input dir="ltr" type="password" autoComplete="new-password" value={form.access_token} onChange={e=>set("access_token",e.target.value)} placeholder={form.token_configured?"توکن ذخیره شده؛ برای حفظ آن خالی بگذارید":"Access Token"}/>
     </label>
     <p>وضعیت کلید: {form.token_configured?"ذخیره شده و مخفی":"هنوز ثبت نشده"}</p>
     <label>شناسه گروه‌های مجاز (هر سطر یک گروه)
      <textarea dir="ltr" rows={5} value={groupsText} onChange={e=>setGroupsText(e.target.value)} placeholder="Meta group ID"/>
     </label>
     <label className="spad-wa-toggle"><input type="checkbox" checked={!!form.official_groups_acknowledged}
       onChange={e=>set("official_groups_acknowledged",e.target.checked)}/>
       حساب تجاری رسمی و مجوز ارسال به گروه‌های معرفی‌شده را تأیید می‌کنم.
     </label>
    </Box>
    <Box title="بنر و انتشار آزمایشی">
     <label>نوع گزارش
      <select value={previewKind} onChange={e=>setPreviewKind(e.target.value)}>
       <option value="market">نرخ ارز، طلا و سکه</option><option value="city">قیمت محله‌های شهر</option>
      </select>
     </label>
     {previewKind==="city"?<label>شهر
      <select value={Math.min(previewCity,cities.length-1)} onChange={e=>setPreviewCity(Number(e.target.value))}>
       {cities.map((x,i)=><option key={x.province+x.city+i} value={i}>{x.city} - {x.province}</option>)}
      </select>
     </label>:null}
     <div className="spad-wa-actions">
      <button type="button" onClick={showPreview} disabled={previewBusy}>{previewBusy?"در حال ساخت...":"نمایش پیش‌نمایش زنده"}</button>
      <button type="button" className="spad-wa-secondary" onClick={sendNow} disabled={!form.enabled||busy}>ارسال واقعی به گروه‌های تنظیم‌شده</button>
     </div>
     {preview?<img className="spad-wa-preview" src={preview} alt="پیش‌نمایش بنر اختصاصی قیمت‌ها برای اسپاد"/>:<p>برای بررسی بنر قبل از انتشار، پیش‌نمایش را اجرا کنید.</p>}
     <p>خروجی واقعی نیاز به تبدیل PNG در سرور دارد. وضعیت موتور تصویر: {form.render_available?"موتور ساخت تصاویر اسپاد آماده است":"موتور ساخت تصویر نیازمند بررسی سرور است"}</p>
    </Box>
   </div>
   <div className="spad-wa-footer"><button type="submit" disabled={busy}>{busy?"در حال ثبت...":"ذخیره تنظیمات انتشار"}</button>
   <span>اعتبار توکن در پاسخ API نمایش داده نمی‌شود.</span></div>
  </form>}
  <Box title="تاریخچه ساخت و ارسال">
   <button type="button" onClick={load} disabled={busy}>به‌روزرسانی گزارش</button>
   {logs.length?<div className="spad-wa-table-wrap"><table><thead><tr><th>تاریخ</th><th>گزارش</th><th>وضعیت</th><th>نتیجه گروه‌ها</th></tr></thead><tbody>
    {logs.map(item=><tr key={item.id}><td>{formatDate(item.created_at)}</td><td>{item.subject}</td>
     <td>{({accepted:"پذیرش توسط متا",partial:"ارسال ناقص",failed:"ناموفق",generating:"ساخت تصویر",sending:"در حال ارسال"})[item.status]||item.status}
      {item.error_message?<small>{item.error_message}</small>:null}</td>
     <td>{(item.delivery_json||[]).map((d,i)=><div key={i}>{d.group}: {d.status==="accepted_by_meta"?"پذیرش درخواست":"رد درخواست"}</div>)}</td></tr>)}
   </tbody></table></div>:<p>هنوز گزارشی ثبت نشده است.</p>}
  </Box>
 </div>;
}

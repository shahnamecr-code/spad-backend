import React, {useCallback, useEffect, useRef, useState} from "react";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import "./whatsapp-market.scss";

const endpoint = adminPathApi + "/whatsapp-market";
const defaults = {
  enabled:false, schedule:{markets:["10:00","18:00"],cities:["11:00"]},
  cities:[{province:"تهران",city:"تهران"},{province:"فارس",city:"شیراز"}],
  groups:[], phone_number_id:"", access_token:"", token_configured:false,
  official_groups_acknowledged:false,render_available:false
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
 const currentPreview=useRef("");
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
 const cities=readCities(citiesText);
 const save=async e=>{
  e.preventDefault();setError("");setSuccess("");
  const schedule={markets:normalizeTimes(marketTimes),cities:normalizeTimes(cityTimes)};
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
  setPreviewBusy(true);setError("");setSuccess("");
  const place=cities[previewCity]||cities[0];
  if(previewKind==="city"&&!place){setPreviewBusy(false);setError("ابتدا یک شهر اضافه کنید.");return;}
  try{
   const res=await axios.get(endpoint+"/preview",{
    params:{kind:previewKind,...(previewKind==="city"?place:{})},responseType:"blob"
   });
   if(currentPreview.current)URL.revokeObjectURL(currentPreview.current);
   const blob=new Blob([res.data],{type:"image/svg+xml"});
   currentPreview.current=URL.createObjectURL(blob);
   setPreview(currentPreview.current);
  }catch(_){setError("پیش‌نمایش از داده‌های معتبر در حال حاضر ممکن نیست.");}
  finally{setPreviewBusy(false);}
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
   <p>ساخت بنرهای اختصاصی قیمت ارز، طلا و محله‌های شهرها با داده‌های واقعی اسپاد و زمان‌بندی خودکار، همراه با پیش‌نمایش و گزارش انتشار.</p>
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
     <p>برای چند ساعت، زمان‌ها را با ویرگول جدا کنید. قالب ۲۴ساعته است.</p>
    </Box>
    <Box title="شهرها و محله‌های منتشرشونده">
     <p>هر سطر: نام استان | نام شهر. همه محله‌های دارای قیمت معتبر شهر در بنرهای چندصفحه‌ای قرار می‌گیرند.</p>
     <textarea dir="rtl" rows={6} value={citiesText} onChange={e=>{setCitiesText(e.target.value);setPreviewCity(0);}} placeholder={"فارس|شیراز\nتهران|تهران"}/>
     <p>اطلاعات از موتور بازار نمودا در اسپاد دریافت می‌شود؛ محله‌های بدون قیمت منتشر نمی‌شوند. شهر دلخواه را می‌توان افزود.</p>
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
     <p>خروجی واقعی نیاز به تبدیل PNG در سرور دارد. وضعیت موتور تصویر: {form.render_available?"افزونه تصویر نصب شده":"نیازمند نصب Imagick و پشتیبانی SVG"}</p>
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

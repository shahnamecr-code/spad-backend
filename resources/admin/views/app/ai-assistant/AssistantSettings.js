import React, {useEffect, useState} from "react";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import "./assistant-settings.scss";

const endpoint=adminPathApi+"/ai-assistant/settings";
const initial={enabled:false,title:"دستیار هوشمند ملکی اسپاد",model:"gpt-4.1-mini",daily_limit:30,max_output_tokens:450,custom_instructions:"",api_key:"",api_key_configured:false};
const Card=({title,children})=><section className="spad-ai-admin-card"><h2>{title}</h2>{children}</section>;

export default function AssistantSettings(){
 const [form,setForm]=useState(initial);
 const [busy,setBusy]=useState(false),[loading,setLoading]=useState(true);
 const [error,setError]=useState(""),[ok,setOk]=useState("");
 const set=(key,value)=>setForm(f=>({...f,[key]:value}));
 const load=async()=>{
  setLoading(true);setError("");
  try{const res=await axios.get(endpoint);setForm({...initial,...res.data.data,api_key:""});}
  catch(e){setError(e?.response?.data?.message||"خطا در دریافت تنظیمات دستیار");}
  finally{setLoading(false);}
 };
 useEffect(()=>{load();},[]);
 const save=async e=>{
  e.preventDefault();setBusy(true);setError("");setOk("");
  try{
   const {data}=await axios.post(endpoint,{
    enabled:Boolean(form.enabled),
    title:form.title,
    model:form.model,
    daily_limit:Number(form.daily_limit),
    max_output_tokens:Number(form.max_output_tokens),
    custom_instructions:form.custom_instructions,
    ...(form.api_key?{api_key:form.api_key}:{})
   });
   setForm({...initial,...data.data,api_key:""});
   setOk("تنظیمات دستیار با موفقیت ذخیره شد.");
  }catch(e){setError(e?.response?.data?.message||"تنظیمات ذخیره نشد. اطلاعات را بررسی کنید.");}
  finally{setBusy(false);}
 };
 return <div dir="rtl" className="spad-ai-admin">
  <header className="spad-ai-admin-hero">
   <div><span>SPAD INTELLIGENCE</span><h1>دستیار هوشمند ملکی اسپاد</h1>
   <p>مدیریت پاسخ‌گویی خودکار، مدل هوش مصنوعی، مصرف و دستورالعمل پاسخ‌ها در تمام صفحات عمومی سایت.</p></div>
   <button type="button" onClick={load} disabled={busy||loading}>به‌روزرسانی تنظیمات</button>
  </header>
  {error?<div className="spad-ai-admin-alert" role="alert">{error}</div>:null}
  {ok?<div className="spad-ai-admin-ok" role="status">{ok}</div>:null}
  {loading?<p>در حال بارگذاری تنظیمات...</p>:<form onSubmit={save}>
   <div className="spad-ai-admin-grid">
    <Card title="وضعیت و هویت دستیار">
     <label className="spad-ai-admin-toggle"><input type="checkbox" checked={Boolean(form.enabled)} onChange={e=>set("enabled",e.target.checked)}/>
      <span>فعال‌سازی چت‌بات در سایت</span></label>
     <p>تا زمانی که کلید API معتبر ثبت نشده باشد، امکان فعال‌سازی وجود ندارد. در حالت غیرفعال، دکمه چت برای کاربران نمایش داده نمی‌شود.</p>
     <label>نام نمایشی دستیار<input maxLength={100} required value={form.title} onChange={e=>set("title",e.target.value)}/></label>
     <div className="spad-ai-admin-status">وضعیت کلید API: <b>{form.api_key_configured?"ثبت شده (مخفی)":"ثبت نشده"}</b></div>
    </Card>
    <Card title="اتصال امن هوش مصنوعی">
     <label>مدل پاسخ‌گو
      <select value={form.model} onChange={e=>set("model",e.target.value)}>
       <option value="gpt-4.1-mini">GPT-4.1 mini — اقتصادی</option>
       <option value="gpt-4o-mini">GPT-4o mini — اقتصادی</option>
       <option value="gpt-4.1">GPT-4.1 — پیشرفته</option>
      </select>
     </label>
     <label>کلید API (فقط در صورت ثبت یا جایگزینی)<input type="password" autoComplete="new-password" value={form.api_key} onChange={e=>set("api_key",e.target.value)} placeholder={form.api_key_configured?"برای حفظ کلید قبلی خالی بگذارید":"کلید خصوصی OpenAI"} /></label>
     <p>کلید فقط به سرور ارسال و رمزنگاری می‌شود و هیچ‌وقت به کاربران یا API عمومی برنمی‌گردد. برای سرویس OpenAI باید اعتبار API جداگانه فعال باشد.</p>
    </Card>
    <Card title="کنترل هزینه و سرعت پاسخ‌گویی">
     <label>حداکثر پیام روزانه برای هر IP<input type="number" min="5" max="100" required value={form.daily_limit} onChange={e=>set("daily_limit",Number(e.target.value))}/></label>
     <label>سقف توکن خروجی هر پاسخ<input type="number" min="180" max="1000" required value={form.max_output_tokens} onChange={e=>set("max_output_tokens",Number(e.target.value))}/></label>
     <p>برای جلوگیری از استفاده نامحدود، علاوه بر سقف روزانه، برای درخواست‌های عمومی محدودیت سرعت فعال است. محتوای گفت‌وگوها به‌صورت پیش‌فرض در دیتابیس اسپاد ثبت نمی‌شود.</p>
    </Card>
    <Card title="شخصیت و دانش اختصاصی اسپاد">
     <label>دستورالعمل‌های تکمیلی پاسخ‌گویی<textarea rows={8} maxLength={3500} value={form.custom_instructions} onChange={e=>set("custom_instructions",e.target.value)} placeholder="مثلاً: خدمات فعلی اسپاد، نحوه دریافت کارشناسی، راهنمای انتخاب ملک و مسیرهای رسمی سایت..."/></label>
     <p>دستیار از جعل نرخ روز، موجودی آگهی‌ها و ادعای قطعی حقوقی جلوگیری می‌کند. این نسخه بدون اتصال مستقیم به موتور جست‌وجوی آگهی‌ها، اطلاعات زنده ملک‌ها را حدس نمی‌زند.</p>
    </Card>
   </div>
   <footer className="spad-ai-admin-actions"><button type="submit" disabled={busy}>{busy?"در حال ذخیره...":"ذخیره تنظیمات دستیار"}</button><span>تنها مدیر اصلی اسپاد می‌تواند تنظیمات و کلید API را تغییر دهد.</span></footer>
  </form>}
 </div>;
}

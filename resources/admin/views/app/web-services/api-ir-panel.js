import React, {useEffect, useState} from 'react';
import axios from 'axios';
import {adminPathApi} from '../../../constants/defaultValues';

const URL=adminPathApi+'/web-services/api-ir';
const t=(v)=>Number(v||0).toLocaleString('fa-IR');
export default function ApiIrPanel(){
 const [state,setState]=useState(null),[token,setToken]=useState(''),[enabled,setEnabled]=useState(false);
 const [drafts,setDrafts]=useState({}),[busy,setBusy]=useState(false),[note,setNote]=useState('');
 const [smsOtp,setSmsOtp]=useState(false),[personInfo,setPersonInfo]=useState(false),[otpTemplate,setOtpTemplate]=useState(1);
 const refresh=async()=>{
  try{
   const {data}=await axios.get(URL);
   const x=data.data;setState(x);setEnabled(Boolean(x.enabled));
   setDrafts(Object.fromEntries((x.services||[]).map(s=>[s.slug,String(s.price||0)])));
   setSmsOtp(Boolean(x.account_features?.sms_otp));setPersonInfo(Boolean(x.account_features?.person_info));setOtpTemplate(Number(x.account_features?.otp_template??1));
  }catch(e){setNote(e.response?.data?.message||'مرکز api.ir در بک‌اند نصب نشده است.');}
 };
 useEffect(()=>{refresh();},[]);
 const save=async(e)=>{
  e.preventDefault();setBusy(true);setNote('');
  try{
   await axios.post(URL,{token:token.trim(),enabled});
   setToken('');await refresh();
   setNote('تنظیمات و کلید امن ذخیره شدند. برای نمایش خدمات، تعرفه و وضعیت هر سرویس را نیز تعیین کنید.');
  }catch(e){setNote(e.response?.data?.message||'ذخیره تنظیمات ناموفق بود.');}
  finally{setBusy(false);}
 };
 const update=async(s,on)=>{
  setBusy(true);setNote('');
  try{
   const price=Number(drafts[s.slug]||0);
   if(!Number.isSafeInteger(price)||price<0)throw Error('تعرفه باید عدد صحیح و معتبر باشد.');
   await axios.post(URL+'/services/'+encodeURIComponent(s.slug),{price,enabled:on});
   await refresh();setNote('تنظیمات '+s.title+' ثبت شد.');
  }catch(e){setNote(e.response?.data?.message||e.message||'ذخیره سرویس ناموفق بود.');}
  finally{setBusy(false);}
 };
 return <section dir="rtl" style={{background:'#f5faff',border:'1px solid #d6e6ff',borderRadius:18,padding:20,margin:'22px 0',color:'#173458'}}>
  <h2 style={{fontWeight:700,fontSize:20,marginBottom:8}}>مدیریت اختصاصی سرویس‌های api.ir</h2>
  <p style={{fontSize:13}}>یک کلید برای هفت سرویس. کلید رمزنگاری‌شده می‌ماند و در این صفحه بازیابی یا نمایش داده نمی‌شود. ثبت کلید بدون تست تراکنش هزینه‌دار انجام می‌شود.</p>
  <form onSubmit={save} style={{display:'flex',flexWrap:'wrap',gap:12,alignItems:'flex-end',margin:'18px 0'}}>
   <label style={{flex:'1 1 280px'}}>کلید API (Bearer Token)
    <input dir="ltr" type="password" autoComplete="new-password" value={token} onChange={e=>setToken(e.target.value)} placeholder={state?.has_key?'کلید ثبت شده؛ برای تغییر، کلید جدید بگذارید':'کلید api.ir را وارد کنید'} style={{display:'block',width:'100%',padding:12,borderRadius:9,border:'1px solid #bfcee1'}}/>
   </label>
   <label style={{padding:12,whiteSpace:'nowrap'}}><input type="checkbox" checked={enabled} onChange={e=>setEnabled(e.target.checked)}/> اتصال اصلی فعال باشد</label>
   <button type="submit" disabled={busy} style={{padding:'12px 22px',background:'#087bd7',color:'white',border:0,borderRadius:9}}>ذخیره اتصال امن</button>
  </form>
  <p style={{fontSize:12}}>{state?.has_key?'کلید ذخیره شده است':'کلید هنوز ثبت نشده است'} · {state?.enabled?'اتصال اصلی روشن':'اتصال اصلی خاموش'} · درخواست واقعی فقط پس از فعال‌سازی هر خدمت اجرا می‌شود.</p>
  {note&&<p role="status" style={{padding:10,background:'#e8f2ff',borderRadius:9}}>{note}</p>}
  <div style={{background:'#fff',border:'1px solid #cfe0f3',padding:16,borderRadius:12,margin:'18px 0'}}>
   <strong style={{display:'block',marginBottom:10}}>ورود پیامکی و احراز هویت کاربران</strong>
   <p style={{fontSize:12}}>در صورت غیرفعال بودن OTP api.ir، درگاه قبلی حفظ می‌شود. بررسی ثبت‌احوال پس از تأیید موبایل، شاهکار و رضایت کاربر انجام می‌شود.</p>
   <label style={{display:'block',margin:'10px 0'}}><input type="checkbox" checked={smsOtp} onChange={e=>setSmsOtp(e.target.checked)}/> ورود و تأیید موبایل با SmsOTP</label>
   <label style={{display:'block',margin:'10px 0'}}><input type="checkbox" checked={personInfo} onChange={e=>setPersonInfo(e.target.checked)}/> مشخصات هویتی از PersonInfo</label>
   <label style={{display:'block',margin:'10px 0'}}>قالب پیامک <select value={otpTemplate} onChange={e=>setOtpTemplate(Number(e.target.value))} style={{marginRight:8,padding:7,borderRadius:7}}>
    <option value={0}>کد</option><option value={1}>کد ورود</option><option value={2}>کد تأیید</option><option value={3}>رمز</option><option value={4}>رمز ورود</option>
   </select></label>
   <button type="button" disabled={busy||!state} style={{padding:'10px 18px',borderRadius:8,color:'white',border:0,background:'#087bd7'}} onClick={async()=>{
    setBusy(true);setNote('');
    try{await axios.post(URL+'/account-features',{sms_otp:smsOtp,person_info:personInfo,otp_template:otpTemplate});await refresh();setNote('تنظیمات OTP و مشخصات هویتی ذخیره شد.');}
    catch(e){setNote(e.response?.data?.message||'ذخیره موفق نبود.');}finally{setBusy(false);}
   }}>ذخیره تنظیمات ورود و احراز هویت</button>
   <p style={{fontSize:12,marginTop:9}}>فعال‌سازی نهایی به کلید معتبر و مجوز ارائه‌دهنده نیاز دارد؛ هیچ درخواست هزینه‌دار آزمایشی ارسال نمی‌شود.</p>
  </div>
  <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(235px,1fr))',gap:12,marginTop:16}}>
   {(state?.services||[]).map(s=><article key={s.slug} style={{background:'white',border:'1px solid #dce8f8',borderRadius:12,padding:14}}>
    <strong style={{display:'block',marginBottom:8}}>{s.title}</strong>
    <small style={{display:'block',marginBottom:7}}>{s.operation}{s.restricted?' · احراز هویت الزامی':''}</small>
    <label>تعرفه فروش (تومان)
     <input type="number" min="0" step="1" value={drafts[s.slug]??'0'} onChange={e=>setDrafts(old=>({...old,[s.slug]:e.target.value}))} style={{display:'block',width:'100%',padding:8,border:'1px solid #ccd8e6',borderRadius:6}}/>
    </label>
    <button disabled={busy||!s.installed} type="button" onClick={()=>update(s,!s.enabled)} style={{marginTop:12,width:'100%',padding:9,border:0,borderRadius:7,background:s.enabled?'#eef2f7':'#087bd7',color:s.enabled?'#345':'white'}}>
     {s.enabled?'غیرفعال‌سازی':'ذخیره تعرفه و فعال‌سازی'}
    </button>
    <small style={{display:'block',marginTop:7}}>{s.enabled?'فعال برای ثبت درخواست':'غیرفعال برای پرداخت'} · {t(s.price)} تومان</small>
   </article>)}
  </div>
  <p style={{fontSize:12,marginTop:13}}>استعلام‌های چک و تسهیلات صرفاً برای کد ملی حساب تأییدشده کاربر فعال هستند. بررسی بدون مصرف اعتبار در این صفحه فقط وضعیت تنظیمات را نشان می‌دهد.</p>
 </section>;
}

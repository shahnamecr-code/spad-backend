import React, {useEffect, useState} from 'react';
import axios from 'axios';
import {adminPathApi} from '../../../constants/defaultValues';

const URL=adminPathApi+'/web-services/api-ir';
const format=value=>Number(value||0).toLocaleString('fa-IR');
const palette={
 navy:'#143e62',blue:'#087dbb',muted:'#7895a9',line:'#dce9f4',
 pale:'#f4f9fd',surface:'#ffffff',green:'#187a55',amber:'#aa7227'
};
const card={background:'#fff',border:'1px solid '+palette.line,borderRadius:17,padding:21,boxShadow:'0 7px 24px #173f5a08'};
const field={padding:'12px 13px',minHeight:44,width:'100%',border:'1px solid #cedfeb',borderRadius:10,background:'#fcfeff',color:'#1f5274',fontSize:12};
const primary={background:'linear-gradient(100deg,#066caa,#129ac6)',color:'#fff',border:0,borderRadius:10,minHeight:43,padding:'11px 19px',cursor:'pointer',fontSize:12};
const secondary={background:'#f1f8fc',color:'#0b7caf',border:'1px solid #cce5f3',borderRadius:10,minHeight:42,padding:'10px 16px',cursor:'pointer',fontSize:12};
const Badge=({ok,children})=><span style={{display:'inline-flex',alignItems:'center',gap:6,color:ok?palette.green:palette.amber,background:ok?'#e9f8ef':'#fff5e6',padding:'6px 10px',fontSize:10,borderRadius:30,whiteSpace:'nowrap'}}><span style={{width:6,height:6,background:ok?'#1ca375':'#d89b3c',borderRadius:'50%'}}/>{children}</span>;
const Stat=({value,label})=><div style={{...card,padding:18,minWidth:0}}><div style={{fontSize:23,color:palette.navy,fontWeight:650}}>{format(value)}</div><div style={{fontSize:11,color:palette.muted,marginTop:5}}>{label}</div></div>;
const Toggle=({checked,onChange,disabled,label,description})=><label style={{display:'flex',alignItems:'center',justifyContent:'space-between',gap:12,padding:'15px 0',borderBottom:'1px solid #edf2f7',cursor:'pointer'}}>
 <span><strong style={{display:'block',fontWeight:600,fontSize:12,color:palette.navy}}>{label}</strong><small style={{display:'block',fontSize:10,color:palette.muted,lineHeight:2,marginTop:4}}>{description}</small></span>
 <input type="checkbox" disabled={disabled} checked={checked} onChange={e=>onChange(e.target.checked)} style={{width:18,height:18,accentColor:palette.blue,flex:'none'}} />
</label>;

export default function ApiIrPanel(){
 const [state,setState]=useState(null),[token,setToken]=useState(''),[enabled,setEnabled]=useState(false);
 const [smsOtp,setSmsOtp]=useState(false),[shahkarPro,setShahkarPro]=useState(false),[otpTemplate,setOtpTemplate]=useState(1);
 const [videoLive,setVideoVerify]=useState(false),[specialServices,setSpecialServices]=useState([]);
 const [drafts,setDrafts]=useState({}),[busy,setBusy]=useState(false),[loading,setLoading]=useState(true);
 const [tab,setTab]=useState('identity'),[notice,setNotice]=useState(''),[isError,setIsError]=useState(false);
 const notify=(message,error=false)=>{setNotice(message);setIsError(error);};
 const refresh=async()=>{
  try {
   const {data}=await axios.get(URL);
   const d=data.data;setState(d);setEnabled(Boolean(d.enabled));
   setDrafts(Object.fromEntries((d.services||[]).map(s=>[s.slug,String(s.price||0)])));
   setSmsOtp(Boolean(d.account_features?.sms_otp));
   setShahkarPro(Boolean(d.account_features?.shahkar_pro));
   setVideoLive(Boolean(d.account_features?.video_live));
   setSpecialServices(Array.isArray(d.biometric_policy?.special_services)?d.biometric_policy.special_services:[]);
   setOtpTemplate(Number(d.account_features?.otp_template??1));
   return d;
  }catch(e){notify(e.response?.data?.message||'خطا در دریافت وضعیت سرویس‌های API.ir.',true);throw e;}
  finally{setLoading(false);}
 };
 useEffect(()=>{refresh().catch(()=>{});},[]);
 const run=async(work,success)=>{
  if(busy)return;
  setBusy(true);setNotice('');setIsError(false);
  try{await work();await refresh();notify(success);}
  catch(e){notify(e.response?.data?.message||e.message||'درخواست با خطا مواجه شد.',true);}
  finally{setBusy(false);}
 };
 const saveMaster=e=>{
  e.preventDefault();
  run(()=>axios.post(URL,{token:token.trim(),enabled}), 'اتصال اصلی API.ir با موفقیت ذخیره شد.');
  setToken('');
 };
 const saveFeatures=()=>run(()=>axios.post(URL+'/account-features',{
  sms_otp:smsOtp,shahkar_pro:shahkarPro,otp_template:otpTemplate,
 }),'تنظیمات موتور جدید شاهکار پرو ذخیره شد.');
 const saveBiometric=()=>run(()=>axios.post(URL+'/biometric-policy',{
  video_live:videoLive,special_services:specialServices,
 }), 'تنظیمات سطح ویژه و VideoLive ذخیره شد.');
 const toggleSpecial=code=>setSpecialServices(previous=>
  previous.includes(code)?previous.filter(item=>item!==code):[...previous,code]
 );
 const changeService=(service,next)=>run(async()=>{
  const price=Number(drafts[service.slug]||0);
  if(!Number.isSafeInteger(price)||price<0)throw Error('تعرفه باید عدد صحیح معتبر باشد.');
  await axios.post(URL+'/services/'+encodeURIComponent(service.slug),{price,enabled:next});
 },'تنظیمات '+service.title+' ذخیره شد.');
 const ready=Boolean(state?.identity_ready);
 const stats=state?.identity_stats||{};
 const checks=state?.identity_checks||[];
 const providerRow=(check)=><div key={check.code} style={{display:'flex',gap:14,justifyContent:'space-between',alignItems:'center',border:'1px solid #e3edf5',background:'#f9fcfe',borderRadius:13,padding:16,flexWrap:'wrap'}}>
  <div style={{minWidth:0}}><div style={{display:'flex',alignItems:'center',gap:9,flexWrap:'wrap'}}><strong style={{color:palette.navy,fontSize:14}}>{check.operation}</strong><span style={{fontSize:10,color:palette.muted}}>{check.name}</span></div>
   <p style={{fontSize:10,color:palette.muted,margin:'6px 0 0',lineHeight:2}}>{check.code==='sms_otp'?'تأیید دسترسی کاربر به شماره همراه حساب':'تطبیق مالکیت شماره همراه با کد ملی از API.ir'}</p>
  </div>
  <Badge ok={check.enabled}>{check.enabled?'فعال در تنظیمات':'غیرفعال در تنظیمات'}</Badge>
 </div>;
 return <div dir="rtl" style={{background:palette.pale,borderRadius:22,padding:'clamp(15px,2.6vw,28px)',margin:'20px 0',color:palette.navy,border:'1px solid #d9e8f4',fontFamily:'inherit'}}>
  <header style={{background:'linear-gradient(115deg,#073e69,#0877b2 75%,#159dcc)',borderRadius:20,padding:'25px 28px',color:'white',display:'flex',alignItems:'center',justifyContent:'space-between',gap:20,flexWrap:'wrap'}}>
   <div style={{flex:'1 1 270px'}}><div style={{fontSize:10,letterSpacing:1,color:'#c7efff',direction:'ltr',textAlign:'right'}}>SPAD · API.IR CONTROL</div>
    <h2 style={{fontSize:22,fontWeight:650,color:'white',margin:'12px 0 8px'}}>مدیریت یکپارچه وب‌سرویس‌ها و احراز هویت اسپاد</h2>
    <p style={{fontSize:12,color:'#dfecfa',lineHeight:2,margin:0}}>مدیریت امن اتصال، احراز هویت کاربران و استعلام‌های ملکی از یک نقطه.</p></div>
   <div style={{border:'1px solid #ffffff50',borderRadius:15,background:'#ffffff20',padding:'13px 18px',minWidth:175}}>
    <div style={{fontSize:10,color:'#d5effe'}}>اتصال API.ir</div>
    <strong style={{fontSize:16,display:'block',marginTop:5}}>{state?.enabled&&state?.has_key?'پیکربندی فعال':'نیازمند بررسی'}</strong>
    <small style={{fontSize:10,color:'#d7f0fa',display:'block',marginTop:5}}>وضعیت پیکربندی، نه تست تراکنش</small>
   </div>
  </header>

  <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(140px,1fr))',gap:12,margin:'18px 0'}}>
   <Stat value={stats.total} label="کل پرونده‌های هویتی"/>
   <Stat value={stats.approved} label="تأییدشده با API.ir"/>
   <Stat value={stats.draft} label="در حال احراز هویت"/>
   <Stat value={stats.legacy} label="تأییدهای قدیمی"/>
   <Stat value={state?.biometric_verified_count} label="احراز ویدئویی معتبر"/>
  </div>

  <nav aria-label="بخش مدیریت وب‌سرویس" style={{display:'flex',gap:8,flexWrap:'wrap',padding:5,borderRadius:13,background:'#e9f3f9',marginBottom:17}}>
   {[['identity','احراز هویت پایه'],['biometric','سطح ویژه بایومتریک'],['connection','اتصال و کلید امن'],['inquiries','مدیریت استعلام‌های ملکی']].map(([id,label])=><button key={id} type="button" onClick={()=>setTab(id)}
    style={{border:'none',padding:'11px 17px',borderRadius:10,background:tab===id?'white':'transparent',color:tab===id?'#086f9f':'#6c8da4',fontWeight:tab===id?600:400,boxShadow:tab===id?'0 4px 12px #123f5c0d':'none',cursor:'pointer',fontSize:12}}>{label}</button>)}
  </nav>
  {notice&&<div role={isError?'alert':'status'} style={{borderRadius:11,padding:14,background:isError?'#fff1ed':'#eaf9f0',color:isError?'#a44d42':'#176e50',marginBottom:17,fontSize:12}}>{notice}</div>}
  {loading&&<p style={{color:palette.muted,fontSize:12,padding:15}}>در حال دریافت وضعیت وب‌سرویس‌ها...</p>}

  {tab==='identity'&&<div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(280px,1fr))',gap:17,alignItems:'start'}}>
   <div style={card}>
    <div style={{display:'flex',gap:11,alignItems:'center',justifyContent:'space-between',flexWrap:'wrap'}}>
     <div><small style={{fontSize:10,color:palette.blue}}>IDENTITY ENGINE V4</small><h3 style={{fontSize:16,fontWeight:630,margin:'7px 0 0'}}>موتور احراز هویت خودکار</h3></div>
     <Badge ok={ready}>{ready?'هر دو سرویس پیکربندی شده':'پیکربندی ناقص'}</Badge>
    </div>
    <p style={{color:palette.muted,fontSize:11,lineHeight:2.2,margin:'14px 0'}}>نسخه جدید از ابتدا با SmsOTP و ShahkarPro پیاده‌سازی شده است. تنها پس از تأیید شماره همراه و تطبیق مثبت کد ملی با مالک سیم‌کارت، سطح یک صادر می‌شود. نتیجه PersonInfo یا تاییدهای قدیمی معتبر این نسخه نیست.</p>
    <div style={{display:'grid',gap:10}}>{checks.length?checks.map(providerRow):<p style={{fontSize:11,color:palette.amber}}>داده سرویس‌ها هنوز دریافت نشده است.</p>}</div>
    <div style={{display:'flex',gap:7,alignItems:'flex-start',background:'#fff8ec',color:'#976f2c',fontSize:10,lineHeight:2,marginTop:15,padding:12,borderRadius:10}}>
     این وضعیت فقط تنظیمات سرویس‌هاست؛ صحت پاسخ ارائه‌دهنده بدون مجوز کاربر و استعلام واقعی قابل تأیید نیست.
    </div>
   </div>
   <div style={card}>
    <small style={{color:palette.blue,fontSize:10}}>SERVICE CONFIGURATION</small><h3 style={{fontSize:16,margin:'7px 0 4px',fontWeight:630}}>تنظیمات احراز هویت</h3>
    <Toggle checked={smsOtp} onChange={setSmsOtp} disabled={busy||!state} label="تأیید موبایل با SmsOTP" description="ارسال کد شش‌رقمی به شماره همراه حساب کاربر"/>
    <Toggle checked={shahkarPro} onChange={setShahkarPro} disabled={busy||!state} label="تطبیق با ShahkarPro" description="استعلام مالکیت شماره موبایل نسبت به کد ملی صاحب حساب"/>
    <label style={{display:'block',margin:'17px 0 12px',fontSize:11,color:palette.muted}}>قالب پیامک
     <select value={otpTemplate} disabled={busy} onChange={e=>setOtpTemplate(Number(e.target.value))} style={{...field,marginTop:8}}>
      <option value={0}>کد</option><option value={1}>کد ورود</option><option value={2}>کد تأیید</option><option value={3}>رمز</option><option value={4}>رمز ورود</option>
     </select>
    </label>
    <button type="button" disabled={busy||!state} style={primary} onClick={saveFeatures}>{busy?'در حال ذخیره...':'ذخیره تنظیمات احراز هویت'}</button>
    <p style={{fontSize:10,lineHeight:2.2,color:palette.muted,marginTop:17}}>سطح یک با SmsOTP و ShahkarPro برای خدمات عادی است؛ سطح دو با VideoLive برای خدمات ویژه‌ای است که از تب مستقل مدیریت می‌کنید.</p>
   </div>
  </div>}


  {tab==='biometric'&&<div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(280px,1fr))',gap:17,alignItems:'start'}}>
   <div style={card}>
    <small style={{fontSize:10,color:palette.blue}}>SPAD IDENTITY · LEVEL 2</small>
    <h3 style={{fontSize:17,fontWeight:650,margin:'9px 0'}}>احراز ویدئویی بایومتریک با VideoLive</h3>
    <p style={{fontSize:11,lineHeight:2.3,color:palette.muted}}>پس از احراز هویت پایه، کاربر با ویدئوی کوتاه، تاریخ تولد شمسی و سریال کارت ملی از طریق API.ir ارزیابی می‌شود. هر دو نتیجه تطبیق چهره و زنده‌سنجی و تطبیق گفتار باید مثبت باشند؛ اعتبار تأیید سطح دو ۱۸۰ روز است.</p>
    <Toggle checked={videoLive} onChange={setVideoLive} disabled={busy||!state} label="فعال‌سازی اتصال VideoLive" description="ابتدا مجوز و اعتبار این سرویس را در API.ir تأیید کنید؛ روشن کردن تنظیمات جای تست واقعی را نمی‌گیرد."/>
    <div style={{padding:13,borderRadius:12,marginTop:14,background:'#f0f8fe',color:'#557d95',fontSize:11,lineHeight:2.1}}>ویدئو و سریال کارت ملی در دیتابیس اسپاد نگهداری نمی‌شوند. فقط وضعیت موفقیت و تاریخ انقضا در پرونده کاربر ثبت می‌شود.</div>
    <button type="button" disabled={busy||!state} onClick={saveBiometric} style={{...primary,marginTop:18}}>{busy?'در حال ذخیره...':'ذخیره سطح ویژه و فهرست خدمات'}</button>
   </div>
   <div style={card}>
    <small style={{fontSize:10,color:palette.blue}}>DYNAMIC ACCESS POLICY</small>
    <h3 style={{fontSize:17,fontWeight:650,margin:'9px 0'}}>خدمات نیازمند احراز ویدئویی</h3>
    <p style={{fontSize:11,color:palette.muted,lineHeight:2.2}}>خدمات زیر از روی بخش‌های دارای کنترل واقعی سرور ساخته می‌شوند؛ هر استعلام جدید نیز پس از نصب به‌صورت خودکار به فهرست انتخاب اضافه می‌شود.</p>
    <div style={{display:'grid',gap:8,maxHeight:425,overflowY:'auto',padding:'4px 0'}}>
     {(state?.biometric_policy?.available_services||[]).map(item=>
      <label key={item.code} style={{display:'flex',alignItems:'center',gap:10,border:'1px solid #e1edf5',borderRadius:11,padding:'12px 14px',background:specialServices.includes(item.code)?'#eff8ff':'#fff',cursor:'pointer'}}>
       <input type="checkbox" checked={specialServices.includes(item.code)} disabled={busy} onChange={()=>toggleSpecial(item.code)} style={{width:18,height:18,accentColor:palette.blue}}/>
       <span style={{flex:1,fontSize:12,color:palette.navy}}>{item.title}</span><small style={{fontSize:10,color:palette.muted}}>{item.group}</small>
      </label>)}
    </div>
    <div style={{display:'flex',justifyContent:'space-between',gap:8,alignItems:'center',marginTop:15,fontSize:11,color:palette.muted}}>
     <span>خدمت ویژه انتخاب‌شده: {format(specialServices.length)}</span>
     <button type="button" disabled={busy} onClick={saveBiometric} style={primary}>ذخیره سیاست دسترسی</button>
    </div>
    <p style={{fontSize:10,lineHeight:2,color:palette.amber,marginTop:12}}>به‌صورت پیش‌فرض مزایده ملکی و قرعه‌کشی ملک نیازمند سطح ویژه هستند. تغییر تنظیمات پیش از پرداخت و ثبت پیشنهاد اعمال می‌شود.</p>
   </div>
  </div>}

  {tab==='connection'&&<div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(265px,1fr))',gap:16,alignItems:'start'}}>
   <form style={card} onSubmit={saveMaster}>
    <small style={{color:palette.blue,fontSize:10}}>SECURE CONNECTION</small><h3 style={{fontSize:16,fontWeight:630,margin:'7px 0 12px'}}>اتصال یکپارچه و کلید محرمانه</h3>
    <label style={{display:'block',color:palette.muted,fontSize:11}}>کلید API.ir (Bearer Token)
     <input type="password" dir="ltr" autoComplete="new-password" value={token} onChange={e=>setToken(e.target.value)} style={{...field,marginTop:10}} placeholder={state?.has_key?'کلید قبلی محفوظ است؛ در صورت نیاز کلید جدید وارد کنید':'کلید سرویس را وارد کنید'}/>
    </label>
    <Toggle checked={enabled} onChange={setEnabled} disabled={busy} label="فعال بودن اتصال اصلی API.ir" description="خاموش‌کردن اتصال، تمام سرویس‌های وابسته را غیرفعال می‌کند"/>
    <button type="submit" disabled={busy} style={{...primary,marginTop:17}}>{busy?'در حال ذخیره...':'ذخیره اتصال امن'}</button>
   </form>
   <div style={card}><h3 style={{fontSize:14,fontWeight:630,margin:'0 0 14px'}}>امنیت اتصال</h3>
    <div style={{marginBottom:12}}><Badge ok={Boolean(state?.has_key)}>{state?.has_key?'کلید رمزنگاری‌شده موجود است':'کلید ثبت نشده است'}</Badge></div>
    <div style={{marginBottom:12}}><Badge ok={Boolean(state?.enabled&&state?.configured)}>{state?.enabled&&state?.configured?'اتصال فعال در تنظیمات':'اتصال غیرفعال'}</Badge></div>
    <p style={{fontSize:11,lineHeight:2.1,color:palette.muted}}>کلید در پاسخ API یا پنل نمایش داده نمی‌شود. ذخیره تنظیمات، هیچ استعلام هزینه‌داری ارسال نمی‌کند.</p>
    <p style={{fontSize:10,color:palette.muted}}>آخرین تغییر تنظیمات: {state?.last_config_change?new Date(state.last_config_change).toLocaleString('fa-IR'):'—'}</p>
   </div>
  </div>}

  {tab==='inquiries'&&<div style={card}>
   <header style={{display:'flex',justifyContent:'space-between',flexWrap:'wrap',gap:15,alignItems:'center',marginBottom:17}}>
    <div><small style={{color:palette.blue,fontSize:10}}>PROPERTY INQUIRIES</small><h3 style={{fontSize:16,fontWeight:630,margin:'6px 0'}}>وب‌سرویس‌های استعلام ملکی</h3><p style={{fontSize:11,color:palette.muted,margin:0,lineHeight:2}}>تعرفه و وضعیت هر سرویس را مستقل مدیریت کنید.</p></div>
    <Badge ok={Boolean(state?.enabled&&state?.has_key)}>{state?.enabled&&state?.has_key?'اتصال اصلی فعال':'اتصال اصلی غیرفعال'}</Badge>
   </header>
   <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fit,minmax(250px,1fr))',gap:13}}>
    {(state?.services||[]).map(service=><article key={service.slug} style={{background:'#fafdff',border:'1px solid #dce8f1',borderRadius:14,padding:17}}>
     <div style={{display:'flex',alignItems:'start',justifyContent:'space-between',gap:7,marginBottom:11}}>
      <strong style={{fontSize:12,color:palette.navy,fontWeight:600}}>{service.title}</strong>
      <Badge ok={service.enabled}>{service.enabled?'فعال':'غیرفعال'}</Badge>
     </div>
     <small style={{display:'block',direction:'ltr',textAlign:'right',fontSize:10,color:palette.muted,marginBottom:12}}>{service.operation}{service.restricted?' · Restricted':''}</small>
     <div style={{margin:'0 0 14px',padding:12,borderRadius:10,background:'#eef7fd',border:'1px solid #d5e9f5'}}>
      <strong style={{display:'block',fontSize:10,color:'#24658d',marginBottom:8}}>فیلدهای الزامی فرم کاربر (طبق API.ir)</strong>
      <div style={{display:'flex',gap:6,flexWrap:'wrap'}}>
       {(service.customer_fields||[]).map(field=><span key={field.key} title={field.key} style={{fontSize:10,background:'#fff',border:'1px solid #d7e8f1',borderRadius:8,padding:'5px 8px',color:'#43738e'}}>{field.label}</span>)}
      </div>
      <small style={{display:'block',fontSize:9,color:'#7894a7',marginTop:8,direction:'ltr',textAlign:'right'}}>API keys: {(service.required_fields||[]).join(' · ')}</small>
     </div>
     <div style={{background:'#f0f7fc',border:'1px solid #dfecf5',borderRadius:11,padding:'11px 12px',marginBottom:12}}>
      <strong style={{display:'block',color:palette.navy,fontSize:11,marginBottom:8,fontWeight:600}}>اطلاعات لازم از کاربر</strong>
      {(service.required_fields||[]).map(f=><div key={f.key} style={{display:'flex',justifyContent:'space-between',gap:9,fontSize:10,color:'#597e95',marginBottom:6}}>
       <span>{f.label}{f.required?' *':''}</span><code dir="ltr" style={{fontSize:10,color:'#0975ad',background:'transparent'}}>{f.key}</code>
      </div>)}
      <small style={{display:'block',fontSize:9,lineHeight:1.9,color:palette.muted,marginTop:8}}>
       ساختار JSON ارائه‌دهنده: {(service.provider_json_keys||[]).join(' · ')}. تأیید رضایت داخلی اسپاد جزو اطلاعات ارسال‌شده به ارائه‌دهنده نیست.
      </small>
     </div>
     <label style={{fontSize:11,color:palette.muted}}>تعرفه فروش (تومان)
      <input type="number" min="0" step="1" style={{...field,marginTop:8}} value={drafts[service.slug]??'0'} onChange={e=>setDrafts(old=>({...old,[service.slug]:e.target.value}))}/>
     </label>
     <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',gap:9,marginTop:15}}>
      <small style={{color:palette.muted,fontSize:10}}>فعلی: {format(service.price)} تومان</small>
      <button type="button" style={service.enabled?secondary:primary} disabled={busy||!service.installed} onClick={()=>changeService(service,!service.enabled)}>
       {service.enabled?'غیرفعال‌سازی':'ثبت تعرفه و فعال‌سازی'}
      </button>
     </div>
    </article>)}
   </div>
   <p style={{fontSize:10,lineHeight:2,color:palette.muted,marginTop:18}}>استعلام‌های مالی با رضایت کاربر و تنها برای هویت تأییدشده او در دسترس‌اند. هیچ تست هزینه‌دار از این صفحه ارسال نمی‌شود.</p>
  </div>}
 </div>;
}

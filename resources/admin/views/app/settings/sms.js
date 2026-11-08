import React,{useEffect,useState} from "react";
import axios from "axios";
import {Button,Card,CardBody,Col,Input,Label,Row} from "reactstrap";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";

const api = adminPathApi + "/settings/sms";
const card = {borderRadius:16,border:"1px solid #dceaf5",boxShadow:"0 8px 24px rgba(16,77,127,.07)",marginBottom:18};
const hint = {fontSize:11,color:"#7091a9",lineHeight:1.9};
const label = {fontSize:12,fontWeight:600,color:"#315c7c"};

export default function SmsSettings(){
 const [data,setData]=useState(null),[key,setKey]=useState("");
 const [logs,setLogs]=useState([]),[loading,setLoading]=useState(true);
 const [busy,setBusy]=useState(false),[testing,setTesting]=useState(false);
 const [phone,setPhone]=useState(""),[error,setError]=useState("");
 const load=async()=>{
  setLoading(true);setError("");
  try{const r=await axios.get(api);setData(r.data.data);setLogs(r.data.logs||[]);setKey("");}
  catch(e){setError(e.response?.data?.message||"دریافت تنظیمات ممکن نشد.");}
  finally{setLoading(false);}
 };
 useEffect(()=>{load();},[]);
 const field=(name,value)=>setData(old=>({...old,[name]:value}));
 const event=(name,name2,value)=>setData(old=>({...old,events:{...old.events,[name]:{...old.events[name],[name2]:value}}}));
 const toggleAll=(enabled)=>setData(old=>({...old,events:Object.fromEntries(Object.entries(old.events||{}).map(([name,rule])=>[name,{...rule,enabled}]))}));
 const save=async e=>{
  e.preventDefault();setBusy(true);
  try{
   const events={};
   Object.keys(data.events||{}).forEach(k=>events[k]={enabled:!!data.events[k].enabled,pattern_code:String(data.events[k].pattern_code||"").trim()});
   await axios.post(api,{enabled:!!data.enabled,api_key:key||undefined,
    sender:data.sender||"",daily_limit:Number(data.daily_limit||300),admin_mobile:data.admin_mobile||"",admin_notify_enabled:!!data.admin_notify_enabled,events});
   NotificationManager.success("تنظیمات پیامکی ذخیره شد.");await load();
  }catch(e){NotificationManager.error(e.response?.data?.message||"ذخیره تنظیمات ناموفق بود.");}
  finally{setBusy(false);}
 };
 const test=async()=>{
  if(!/^09[0-9]{9}$/.test(phone)){NotificationManager.error("شماره همراه معتبر وارد کنید.");return;}
  setTesting(true);
  try{const r=await axios.post(api+"/test",{mobile:phone});
   if(r.data.status)NotificationManager.success(r.data.message);
   else NotificationManager.error(r.data.message);
   await load();
  }catch(e){NotificationManager.error(e.response?.data?.message||"آزمایش پیامک ناموفق بود.");}
  finally{setTesting(false);}
 };
 return <section dir="rtl" style={{maxWidth:1280,margin:"0 auto",padding:"14px 16px 75px"}}>
  <header style={{borderRadius:20,background:"linear-gradient(120deg,#0c355a,#1287ca)",padding:24,color:"white",marginBottom:19}}>
   <div style={{fontSize:11,opacity:.8}}>تنظیمات اسپاد / مرکز پیام‌رسانی</div>
   <h2 style={{fontSize:22,color:"white",margin:"8px 0"}}>مرکز مدیریت ایران پیامک</h2>
   <p style={{fontSize:12,margin:0,opacity:.87}}>وب‌سرویس اختصاصی، کدهای یک‌بارمصرف، اطلاع‌رسانی‌های رویدادی و سوابق ارسال</p>
  </header>
  {loading?<div className="loading"/>:error?<Card style={card}><CardBody><p>{error}</p><Button color="primary" onClick={load}>تلاش مجدد</Button></CardBody></Card>:
  <form onSubmit={save}>
   <Row>
    <Col md="7"><Card style={card}><CardBody>
     <h3 style={{fontSize:16,marginBottom:14}}>اتصال ایران پیامک</h3>
     <label style={{display:"flex",gap:12,alignItems:"center",background:"#eff8ff",padding:14,borderRadius:12,marginBottom:17,cursor:"pointer"}}>
      <input type="checkbox" checked={!!data.enabled} onChange={e=>field("enabled",e.target.checked)}/>
      <strong style={{fontSize:13}}>فعال‌کردن پیامک ایران پیامک</strong>
     </label>
     <Row>
      <Col md="6"><Label style={label}>API Key ایران پیامک</Label>
       <Input type="password" autoComplete="new-password" placeholder={data.key_saved?"کلید ذخیره شده؛ برای تغییر وارد کنید":"کلید API را وارد کنید"} value={key} onChange={e=>setKey(e.target.value)}/>
       <small style={hint}>{data.key_saved?"کلید رمزگذاری شده است و دوباره نمایش داده نمی‌شود.":"هنوز کلید ثبت نشده است."}</small></Col>
      <Col md="6" style={{marginTop:15}}><Label style={label}>خط فرستنده</Label>
       <Input dir="ltr" placeholder="5000..." value={data.sender||""} onChange={e=>field("sender",e.target.value)}/>
       <small style={hint}>شماره خط فعال؛ در ارسال ساده و الگویی استفاده می‌شود.</small></Col>
      <Col md="6" style={{marginTop:15}}><Label style={label}>سقف روزانه</Label>
       <Input type="number" min={10} max={10000} value={data.daily_limit} onChange={e=>field("daily_limit",e.target.value)}/></Col>
     </Row>
     <div style={{marginTop:14,padding:12,background:"#f5faff",borderRadius:10}}>
      <Label style={label}>شماره همراه اطلاع‌رسانی مدیر</Label>
      <Input dir="ltr" type="tel" maxLength={11} placeholder="09xxxxxxxxx" value={data.admin_mobile||""} onChange={e=>field("admin_mobile",e.target.value.replace(/[^0-9]/g,""))}/>
      <label style={{display:"flex",gap:9,marginTop:9,alignItems:"center"}}><input type="checkbox" checked={!!data.admin_notify_enabled} onChange={e=>field("admin_notify_enabled",e.target.checked)}/> فعال‌سازی اعلان رویدادها برای مدیر</label>
      <small style={hint}>اعلان‌های مدیریتی بدون کد ورود کاربران ارسال می‌شوند.</small>
     </div>
     <p style={{...hint,marginTop:12}}>برای کدهای ورود و تأیید، الگوی ایران پیامک با متغیر %code% بسازید؛ برای رویدادهای دارای شناسه از %id% استفاده کنید. کلید API رمزگذاری و محرمانه نگهداری می‌شود.</p>
    </CardBody></Card></Col>
    <Col md="5"><Card style={card}><CardBody>
     <h3 style={{fontSize:16}}>تست اتصال</h3>
     <p style={hint}>برای آزمایش باید «ثبت‌نام موفق» فعال باشد. این اقدام فقط با کلیک شما انجام می‌شود و حداکثر دو بار در ۱۵ دقیقه امکان‌پذیر است.</p>
     <Label style={label}>شماره دریافت‌کننده آزمایش</Label>
     <Input dir="ltr" type="tel" maxLength={11} placeholder="09123456789" value={phone} onChange={e=>setPhone(e.target.value.replace(/[^0-9]/g,""))}/>
     <Button type="button" color="primary" outline style={{marginTop:14,borderRadius:9}} onClick={test} disabled={busy||testing||!data.enabled}>{testing?"در حال ارسال...":"ارسال آزمایشی"}</Button>
    </CardBody></Card></Col>
   </Row>
   <Card style={card}><CardBody>
    <div style={{display:"flex",alignItems:"center",justifyContent:"space-between",flexWrap:"wrap",gap:10}}>
     <h3 style={{fontSize:16,margin:0}}>تنظیمات رویدادهای پیامکی</h3>
     <div style={{display:"flex",gap:8,flexWrap:"wrap"}}>
      <Button type="button" color="primary" outline size="sm" onClick={()=>toggleAll(true)}>فعال‌سازی همه رویدادها</Button>
      <Button type="button" color="secondary" outline size="sm" onClick={()=>toggleAll(false)}>غیرفعال‌سازی همه</Button>
     </div>
    </div>
    <p style={hint}>برای هر عملیات می‌توانید ارسال پیامک را جداگانه فعال کنید. با «فعال‌سازی همه» تمام رویدادها انتخاب می‌شوند؛ پیش از ذخیره، شناسه الگوهای ایران پیامک و شماره خط فرستنده را تنظیم کنید. پیام‌های تأیید یک‌بارمصرف و هشدارهای امنیتی برای عملکرد صحیح باید فعال بمانند.</p>
    <Row style={{marginTop:15}}>
     {Object.entries(data.events||{}).map(([name,rule])=><Col md="6" xl="4" key={name} style={{marginBottom:13}}>
      <div style={{padding:14,background:rule.enabled?"#f2faff":"white",border:"1px solid #dbeaf5",borderRadius:13,height:"100%"}}>
       <label style={{display:"flex",alignItems:"center",gap:10,cursor:"pointer"}}>
        <input type="checkbox" checked={!!rule.enabled} onChange={e=>event(name,"enabled",e.target.checked)}/>
        <strong style={{fontSize:12,color:"#31516b"}}>{rule.label}</strong>
       </label>
       <Label style={{...label,fontSize:10,marginTop:11}}>کد الگوی ایران پیامک</Label>
       <Input dir="ltr" type="text" maxLength={100} placeholder="شناسه الگو (برای کد تأیید الزامی)" style={{height:36}} value={rule.pattern_code||""} onChange={e=>event(name,"pattern_code",e.target.value)}/>
      </div>
     </Col>)}
    </Row>
   </CardBody></Card>
   <Card style={card}><CardBody>
    <h3 style={{fontSize:15}}>آخرین گزارش‌های ارسال</h3>
    <p style={hint}>شماره دریافت‌کننده پوشیده است. کلید API، کد تأیید و متن پیام ذخیره نمی‌شوند.</p>
    <div style={{overflowX:"auto"}}><table className="table table-striped" style={{fontSize:11}}>
     <thead><tr><th>زمان</th><th>رویداد</th><th>گیرنده</th><th>وضعیت</th></tr></thead>
     <tbody>{logs.length?logs.map(item=><tr key={item.id}>
      <td>{item.created_at}</td><td>{data.events?.[item.event]?.label||item.event}</td>
      <td dir="ltr">{item.mobile_masked}</td><td>{item.status==="sent"?"پذیرفته‌شده":item.status==="sending"?"در حال ارسال":item.status==="unknown"?"نامشخص":"ناموفق"}</td>
     </tr>):<tr><td colSpan={4} style={{textAlign:"center"}}>هنوز پیامی ثبت نشده است.</td></tr>}</tbody>
    </table></div>
   </CardBody></Card>
   <div style={{position:"sticky",bottom:10,background:"#eaf6ff",borderRadius:13,border:"1px solid #cce4f4",padding:13,display:"flex",alignItems:"center",justifyContent:"space-between",gap:12}}>
    <span style={{fontSize:11,color:"#47738f"}}>تا زمان واردکردن کلید و شماره خط ایران پیامک، ارسال غیرفعال است.</span>
    <Button type="submit" color="primary" disabled={busy} style={{borderRadius:10,minWidth:150}}>{busy?"در حال ذخیره...":"ذخیره تنظیمات"}</Button>
   </div>
  </form>}
 </section>;
}

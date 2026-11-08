import React,{useCallback,useEffect,useState} from "react";
import axios from "axios";
import {Link} from "react-router-dom";
import {adminPathApi,adminRoot} from "../../../constants/defaultValues";

const API=adminPathApi+"/settings/sms/campaign";
const card={border:"1px solid #d9e9f6",borderRadius:17,background:"#fff",padding:21,boxShadow:"0 5px 23px rgba(24,80,115,.06)"};
const input={border:"1px solid #cbdff0",borderRadius:10,padding:"11px 13px",width:"100%",background:"#fff",fontSize:13};
const errorText=e=>e?.response?.data?.message||"ارسال درخواست ممکن نشد. اتصال سرویس را بررسی کنید.";
const uuid=()=>typeof crypto!=="undefined"&&crypto.randomUUID?crypto.randomUUID():"10000000-1000-4000-8000-"+String(Date.now()%1e12).padStart(12,"0");
const status=s=>({accepted:"پذیرفته‌شده",pending:"در صف",sending:"در حال ارسال",unknown:"نامشخص",failed:"ناموفق",skipped:"لغوشده"}[s]||s);

export default function SmsSendCenter(){
 const [data,setData]=useState(null),[error,setError]=useState(""),[info,setInfo]=useState("");
 const [q,setQ]=useState(""),[search,setSearch]=useState(""),[selected,setSelected]=useState([]);
 const [text,setText]=useState(""),[busy,setBusy]=useState(false),[loading,setLoading]=useState(true);
 const load=useCallback(async()=>{setLoading(true);try{
  const r=await axios.get(API,{params:{q:search}});setData(r.data);setError("");
 }catch(e){setError(errorText(e));}finally{setLoading(false);}},[search]);
 useEffect(()=>{load();},[load]);
 const send=async e=>{
  e.preventDefault();if(!selected.length){setError("حداقل یک مشتری دارای رضایت را انتخاب کنید.");return;}
  if(!window.confirm("ارسال پیامک به "+selected.length+" مشتری ثبت شود؟ این پیامک‌ها برای پردازش در صف قرار می‌گیرند."))return;
  setBusy(true);setError("");setInfo("");
  try{const r=await axios.post(API,{contact_ids:selected,text,request_id:uuid()});
   setInfo(r.data.message+" تعداد ثبت‌شده: "+Number(r.data.queued||0).toLocaleString("fa-IR"));
   setSelected([]);setText("");await load();
  }catch(e){setError(errorText(e));}finally{setBusy(false);}
 };
 const change=(id,checked)=>setSelected(a=>checked?[...a,id].slice(0,20):a.filter(x=>x!==id));
 return <section dir="rtl" style={{maxWidth:1300,margin:"auto",padding:"12px 18px 70px"}}>
  <header style={{background:"linear-gradient(110deg,#0b365d,#0585ce)",padding:"24px 27px",borderRadius:20,color:"#fff",marginBottom:20}}>
   <small style={{opacity:.82}}>مرکز پیام‌رسانی و ارتباط با مشتریان اسپاد</small>
   <h1 style={{fontSize:24,color:"white",margin:"9px 0"}}>ارسال پیامک ایران پیامک</h1>
   <p style={{fontSize:12,opacity:.9,margin:"0 0 13px"}}>انتخاب مشتریان دارای رضایت، ارسال دستی و مشاهده سوابق پردازش</p>
   <div style={{display:"flex",gap:9,flexWrap:"wrap"}}>
    <Link style={{color:"white",padding:"8px 13px",border:"1px solid #b4dff6",borderRadius:10}} to={adminRoot+"/settings/sms"}>تنظیمات و کلید وب‌سرویس</Link>
    <Link style={{color:"white",padding:"8px 13px",border:"1px solid #b4dff6",borderRadius:10}} to={adminRoot+"/customers"}>مدیریت مشتریان</Link>
   </div>
  </header>
  {error&&<p role="alert" style={{background:"#fff0f0",color:"#ad3847",borderRadius:10,padding:13}}>{error}</p>}
  {info&&<p role="status" style={{background:"#effaf5",color:"#087653",borderRadius:10,padding:13}}>{info}</p>}
  <div style={{display:"grid",gridTemplateColumns:"repeat(auto-fit,minmax(310px,1fr))",gap:17,alignItems:"start"}}>
   <div style={card}>
    <h2 style={{fontSize:17,margin:"0 0 12px"}}>انتخاب گیرندگان</h2>
    <p style={{fontSize:12,color:"#6a8ca5",lineHeight:2}}>فقط مشتریانی که رضایت معتبر پیامکی دارند نمایش داده می‌شوند. در هر درخواست حداکثر ۲۰ نفر قابل انتخاب‌اند.</p>
    <form onSubmit={e=>{e.preventDefault();setSearch(q);}}>
     <div style={{display:"flex",gap:8}}>
      <input style={input} placeholder="جستجوی مشتری یا موبایل" value={q} onChange={e=>setQ(e.target.value)}/>
      <button type="submit" style={{background:"#007fcc",color:"white",border:0,borderRadius:10,padding:"8px 18px"}}>جستجو</button>
     </div>
    </form>
    <p style={{fontSize:12,color:"#416b89",marginTop:13}}>{selected.length.toLocaleString("fa-IR")} نفر انتخاب شده‌اند</p>
    <div style={{maxHeight:370,overflowY:"auto",border:"1px solid #e2edf5",borderRadius:12}}>
     {(data?.contacts||[]).map(c=><label key={c.id} style={{display:"flex",alignItems:"center",gap:10,padding:"12px 13px",borderBottom:"1px solid #eff5f9",cursor:"pointer"}}>
      <input type="checkbox" checked={selected.includes(c.id)} disabled={!selected.includes(c.id)&&selected.length>=20} onChange={e=>change(c.id,e.target.checked)}/>
      <span style={{flex:1,fontSize:12}}><strong>{c.full_name}</strong><small style={{display:"block",color:"#7695ab"}} dir="ltr">{c.mobile_masked}</small></span>
      <span style={{color:"#567992",fontSize:11}}>{c.budget_toman?Number(c.budget_toman).toLocaleString("fa-IR")+" تومان":"بودجه ثبت نشده"}</span>
     </label>)}
     {(!data?.contacts?.length)&&<p style={{padding:22,textAlign:"center",fontSize:12,color:"#7895ad"}}>{loading?"در حال بارگذاری…":"مخاطب دارای رضایت پیدا نشد."}</p>}
    </div>
   </div>
   <div style={card}>
    <h2 style={{fontSize:17,margin:"0 0 12px"}}>متن پیامک</h2>
    <p style={{fontSize:12,color:"#6a8ca5",lineHeight:2}}>ارسال از همان کلید و خط ایران پیامک در تنظیمات ادمین استفاده می‌کند؛ ارسال گروهی بدون رضایت مشتری ممکن نیست.</p>
    <form onSubmit={send}>
     <textarea style={{...input,minHeight:170,resize:"vertical"}} rows={7} required minLength={10} maxLength={400} value={text} placeholder="متن پیامک را بنویسید…" onChange={e=>setText(e.target.value)}/>
     <div style={{display:"flex",justifyContent:"space-between",gap:8,alignItems:"center",marginTop:9}}>
      <small style={{fontSize:11,color:"#7292a7"}}>{text.length} از ۴۰۰ نویسه</small>
      <button type="submit" disabled={busy||!data?.enabled||!selected.length} style={{background:"#087bc6",color:"white",border:0,borderRadius:10,padding:"11px 17px",opacity:busy||!data?.enabled?.6:1}}>
       {busy?"در حال ثبت…":"تأیید و قرار دادن در صف"}
      </button>
     </div>
    </form>
    <div style={{marginTop:16,padding:13,background:"#f0f8ff",borderRadius:12,fontSize:12,lineHeight:2,color:"#3c6785"}}>
     <div><strong>اتصال ارسال دستی:</strong> {data?.enabled?"فعال":"غیرفعال / نیازمند تکمیل تنظیمات"}</div>
     <div><strong>اعلان ملک جدید:</strong> {data?.property_match_enabled?"فعال":"غیرفعال"}</div>
     <div><strong>در انتظار پردازش:</strong> {Number(data?.queued||0).toLocaleString("fa-IR")}</div>
     <div>ارسال‌ها تنها در ساعت ۹ تا ۱۹ به وقت ایران و با سقف ایمنی انجام می‌شوند.</div>
    </div>
   </div>
  </div>
  <div style={{...card,marginTop:18}}>
   <div style={{display:"flex",justifyContent:"space-between",alignItems:"center",marginBottom:12,flexWrap:"wrap",gap:8}}>
    <h2 style={{fontSize:17,margin:0}}>گزارش آخرین پیامک‌ها</h2>
    <button type="button" onClick={load} disabled={loading} style={{border:"1px solid #c4e0f2",background:"#f3faff",borderRadius:9,padding:"8px 14px",color:"#0072b8"}}>تازه‌سازی گزارش</button>
   </div>
   <div style={{overflowX:"auto"}}><table style={{width:"100%",borderCollapse:"collapse",fontSize:12}}>
    <thead><tr>{["زمان","گیرنده","نوع","وضعیت","خطای فنی"].map(h=><th key={h} style={{textAlign:"right",borderBottom:"1px solid #deedf7",padding:"12px 10px",color:"#6d8da6"}}>{h}</th>)}</tr></thead>
    <tbody>{(data?.logs||[]).map(x=><tr key={x.id}>
     <td style={{padding:11,borderBottom:"1px solid #eff4f8"}}>{x.created_at}</td><td>{x.full_name||"—"}</td>
     <td>{x.kind==="property_match"?"معرفی ملک":"ارسال دستی"}</td><td>{status(x.status)}</td><td>{x.error_code||"—"}</td>
    </tr>)}</tbody>
   </table>
   {!data?.logs?.length&&<p style={{textAlign:"center",color:"#8198aa",padding:19}}>هنوز گزارشی ثبت نشده است.</p>}
   </div>
  </div>
 </section>;
}
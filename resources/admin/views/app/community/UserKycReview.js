import React,{useEffect,useState} from "react";
import axios from "axios";
import {Card,CardBody,Button,Badge,Input} from "reactstrap";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";

const URL=adminPathApi+"/identity/requests";
const err=e=>Object.values(e?.response?.data?.errors||{}).flat()[0]||e?.response?.data?.message||"درخواست ناموفق بود";
const fa=v=>new Intl.NumberFormat("fa-IR").format(v||0);
const date=value=>value?new Date(value).toLocaleString("fa-IR"):"—";
const label=status=>status==="approved"?"تأییدشده":status==="pending"?"نیازمند احراز مجدد":status==="draft"?"در حال تکمیل":status==="rejected"?"ردشده":status||"در انتظار";
export default function UserKycReview(){
 const [rows,setRows]=useState([]),[loading,setLoading]=useState(true),[busy,setBusy]=useState(false),[filter,setFilter]=useState("all"),[reason,setReason]=useState({});
 const load=async()=>{setLoading(true);try{const r=await axios.get(URL);setRows(r.data.data||[]);}catch(e){NotificationManager.error(err(e));}finally{setLoading(false);}};
 useEffect(()=>{load();},[]);
 const reject=async row=>{
  const note=(reason[row.id]||"").trim();
  if(note.length<5){NotificationManager.error("دلیل رد پرونده را با حداقل پنج حرف وارد کنید.");return;}
  if(!window.confirm("این اقدام اعتبار احراز هویت کاربر را لغو می‌کند. ادامه می‌دهید؟"))return;
  setBusy(true);
  try{await axios.patch(URL+"/"+row.id,{decision:"reject",note});NotificationManager.success("وضعیت پرونده ثبت شد.");await load();}
  catch(e){NotificationManager.error(err(e));}
  finally{setBusy(false);}
 };
 const verified=rows.filter(x=>x.status==="approved"&&x.verification_provider==="api_ir").length;
 const pending=rows.filter(x=>x.status==="draft"||x.status==="pending").length;
 const legacy=rows.filter(x=>x.status==="approved"&&x.verification_provider!=="api_ir").length;
 const visible=rows.filter(x=>filter==="all"||(filter==="verified"?x.status==="approved"&&x.verification_provider==="api_ir":filter==="legacy"?x.status==="approved"&&x.verification_provider!=="api_ir":filter==="pending"?(x.status==="draft"||x.status==="pending"):x.status==="rejected"));
 return <Card style={{border:"1px solid #d3e5f4",borderRadius:18,marginBottom:22,background:"#fafdff"}}><CardBody dir="rtl" style={{padding:22}}>
  <header style={{display:"flex",justifyContent:"space-between",gap:15,flexWrap:"wrap",alignItems:"center"}}>
   <div><small style={{color:"#0685c8",letterSpacing:1}}>SPAD IDENTITY CENTER</small><h3 style={{color:"#174b73",fontSize:20,margin:"9px 0"}}>مرکز احراز هویت یکپارچه اسپاد</h3>
    <p style={{fontSize:12,color:"#62839b",margin:0,lineHeight:2}}>احراز هویت سطح پایه فقط با SmsOTP، Shahkar و PersonInfo شرکت api.ir انجام می‌شود. هیچ دکمه تأیید دستی در این پنل وجود ندارد.</p></div>
   <Button color="info" outline disabled={loading} onClick={load}>تازه‌سازی</Button>
  </header>
  <div style={{display:"grid",gridTemplateColumns:"repeat(auto-fit,minmax(150px,1fr))",gap:11,margin:"22px 0"}}>
   {[[fa(rows.length),"کل پرونده‌ها"],[fa(verified),"تأییدشده با api.ir"],[fa(pending),"در حال بررسی"],[fa(legacy),"نیازمند احراز مجدد"]].map(([value,title])=><div key={title} style={{border:"1px solid #e0eaf4",borderRadius:12,background:"#fff",padding:15}}><strong style={{fontSize:23,color:"#126b9e"}}>{value}</strong><small style={{display:"block",fontSize:11,color:"#6c8a9f"}}>{title}</small></div>)}
  </div>
  <div style={{display:"flex",gap:8,flexWrap:"wrap",marginBottom:17}}>
   {[["all","همه"],["verified","تأییدشده"],["pending","در حال بررسی"],["legacy","قدیمی"],["rejected","رد شده"]].map(([id,title])=><button type="button" key={id} onClick={()=>setFilter(id)} style={{border:"1px solid #cde1ed",borderRadius:9,background:filter===id?"#087ebc":"#fff",color:filter===id?"#fff":"#336581",padding:"8px 13px",fontSize:11}}>{title}</button>)}
  </div>
  <div style={{display:"grid",gridTemplateColumns:"repeat(auto-fit,minmax(270px,1fr))",gap:12}}>
   {visible.map(v=>{
    const newVerified=v.status==="approved"&&v.verification_provider==="api_ir";
    return <article key={v.id} style={{background:"#fff",border:"1px solid #dfeaf3",borderRadius:14,padding:16}}>
     <div style={{display:"flex",alignItems:"center",justifyContent:"space-between",gap:8}}>
      <strong style={{fontSize:13,color:"#185c88"}}>{v.full_name||"پرونده بدون نام"}</strong><Badge color={newVerified?"success":v.status==="rejected"?"danger":"secondary"}>{v.status==="approved"&&!newVerified?"تأیید قدیمی":label(v.status)}</Badge>
     </div>
     <p style={{fontSize:11,color:"#708ca0",margin:"10px 0"}}>شناسه کاربر: {fa(v.user_id)} · {v.mobile||"—"}</p>
     <div style={{background:"#f8fbff",borderRadius:10,padding:10,lineHeight:2,fontSize:11,color:"#567e98"}}>
      <div>تأیید موبایل: <strong>{v.phone_verified?"انجام شده":"انجام نشده"}</strong></div>
      <div>شاهکار: <strong>{v.shahkar_status==="matched"?"تطبیق مثبت":v.shahkar_status==="mismatch"?"عدم تطبیق":"انجام نشده"}</strong></div>
      <div>ثبت‌احوال: <strong>{v.person_info_status==="matched"?"تطبیق مثبت":v.person_info_status==="mismatch"?"عدم تطبیق":"انجام نشده"}</strong></div>
      <div>منبع احراز: <strong>{newVerified?"api.ir · خودکار":"تأیید با api.ir ثبت نشده"}</strong></div>
      <div>آخرین استعلام شاهکار: {date(v.shahkar_checked_at)}</div>
      <div>آخرین استعلام ثبت‌احوال: {date(v.person_info_checked_at)}</div>
     </div>
     {v.status!=="rejected"&&<div style={{marginTop:12}}>
      <Input type="textarea" rows="2" disabled={busy} placeholder="دلیل لغو اعتبار هویتی (الزامی)" value={reason[v.id]||""} onChange={e=>setReason(prev=>({...prev,[v.id]:e.target.value}))} style={{fontSize:11}}/>
      <Button size="sm" outline color="danger" disabled={busy} onClick={()=>reject(v)} style={{marginTop:8}}>لغو اعتبار / رد پرونده</Button>
     </div>}
    </article>;
   })}
  </div>
  {!loading&&!visible.length&&<p style={{padding:20,textAlign:"center",color:"#7894a6"}}>پرونده‌ای در این دسته وجود ندارد.</p>}
  <p style={{marginTop:18,fontSize:11,color:"#7595a8",lineHeight:2}}>پرونده‌های قدیمی برای سوابق حذف نشده‌اند، ولی در سیستم جدید تأیید سطح پایه فقط با تطبیق موفق هر سه سرویس api.ir صادر می‌شود.</p>
 </CardBody></Card>;
}

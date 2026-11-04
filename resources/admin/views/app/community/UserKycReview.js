import React, {useEffect, useMemo, useState} from "react";
import axios from "axios";
import {Card, CardBody, Button, Badge, Input} from "reactstrap";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";

const URL = adminPathApi + "/identity/requests";
const fa = v => new Intl.NumberFormat("fa-IR").format(Number(v || 0));
const fmtDate = v => v ? new Date(v).toLocaleString("fa-IR") : "—";
const safePhone = v => {
 const s = String(v || "");
 return s.length >= 8 ? s.slice(0, 4) + "***" + s.slice(-4) : "—";
};
const error = e => Object.values(e?.response?.data?.errors || {}).flat()[0] ||
 e?.response?.data?.message || "درخواست انجام نشد.";
const label = v => {
 if (v.status === "approved") return v.verification_provider === "api_ir" ? "تأیید خودکار" : "تأیید قدیمی";
 if (v.status === "draft") return "در حال احراز هویت";
 if (v.status === "pending") return "نیازمند احراز مجدد";
 if (v.status === "rejected") return "ردشده";
 return "ثبت‌نشده";
};
const base = {background:"#fff",border:"1px solid #e0ecf4",borderRadius:15,boxShadow:"0 4px 20px #113f5707"};
const small = {fontSize:11,color:"#7290a5",lineHeight:2};
const chip = (active) => ({
 border:"1px solid "+(active?"#087db9":"#d9e8f3"),background:active?"#087db9":"#fff",
 color:active?"#fff":"#5d819b",borderRadius:30,padding:"8px 14px",
 fontSize:11,cursor:"pointer"
});

export default function UserKycReview() {
 const [rows,setRows] = useState([]);
 const [loading,setLoading] = useState(true);
 const [busy,setBusy] = useState(false);
 const [filter,setFilter] = useState("all");
 const [query,setQuery] = useState("");
 const [reason,setReason] = useState({});
 const load = async() => {
  setLoading(true);
  try { const res = await axios.get(URL); setRows(res.data.data || []); }
  catch(e) { NotificationManager.error(error(e)); }
  finally { setLoading(false); }
 };
 useEffect(() => { load(); }, []);
 const counts = useMemo(() => ({
  all:rows.length,
  verified:rows.filter(v=>v.status==="approved"&&v.verification_provider==="api_ir").length,
  pending:rows.filter(v=>v.status==="draft"||v.status==="pending").length,
  legacy:rows.filter(v=>v.status==="approved"&&v.verification_provider!=="api_ir").length,
  rejected:rows.filter(v=>v.status==="rejected").length,
 }), [rows]);
 const visible = useMemo(() => rows.filter(v => {
  if (filter==="verified" && !(v.status==="approved"&&v.verification_provider==="api_ir")) return false;
  if (filter==="pending" && !(v.status==="draft"||v.status==="pending")) return false;
  if (filter==="legacy" && !(v.status==="approved"&&v.verification_provider!=="api_ir")) return false;
  if (filter==="rejected" && v.status!=="rejected") return false;
  const q = query.trim();
  return !q || String(v.full_name||"").includes(q) || String(v.user_id||"").includes(q) ||
    String(v.mobile||"").includes(q);
 }), [rows,filter,query]);
 const revoke = async v => {
  const note = String(reason[v.id]||"").trim();
  if (note.length < 5) { NotificationManager.error("دلیل رد یا لغو اعتبار را کامل وارد کنید."); return; }
  if (!window.confirm("اعتبار این پرونده لغو می‌شود؛ تأیید دستی یا بازگشت خودکار وجود ندارد. ادامه می‌دهید؟")) return;
  setBusy(true);
  try {
   await axios.patch(URL+"/"+v.id,{decision:"reject",note});
   NotificationManager.success("اعتبار پرونده با ثبت دلیل لغو شد.");
   setReason(old=>({...old,[v.id]:""}));
   await load();
  } catch(e) { NotificationManager.error(error(e)); }
  finally { setBusy(false); }
 };
 return <Card style={{border:"1px solid #dae9f3",borderRadius:20,background:"#f7fbfe",marginBottom:23,overflow:"hidden"}}>
  <CardBody dir="rtl" style={{padding:22}}>
   <section style={{background:"linear-gradient(115deg,#084973,#087bb8)",borderRadius:16,color:"#fff",padding:23,display:"flex",alignItems:"center",justifyContent:"space-between",gap:15,flexWrap:"wrap"}}>
    <div><small style={{fontSize:10,letterSpacing:1,color:"#c7ecff",direction:"ltr",display:"block",textAlign:"right"}}>SPAD VERIFIED ID · V3</small>
     <h3 style={{fontSize:20,color:"#fff",margin:"9px 0",fontWeight:650}}>مرکز مدیریت احراز هویت کاربران</h3>
     <p style={{margin:0,fontSize:11,color:"#e4f4fb",lineHeight:2}}>احراز هویت خودکار با SmsOTP و PersonInfo شرکت API.ir؛ بدون تأیید دستی و بدون وابستگی به شاهکار.</p>
    </div>
    <Button outline color="light" disabled={loading||busy} onClick={load} style={{borderRadius:9,fontSize:11}}>به‌روزرسانی پرونده‌ها</Button>
   </section>

   <div style={{display:"grid",gridTemplateColumns:"repeat(auto-fit,minmax(145px,1fr))",gap:11,margin:"18px 0"}}>
    {[
     ["all","کل پرونده‌ها"],
     ["verified","تأیید خودکار API.ir"],
     ["pending","در حال تکمیل"],
     ["legacy","پرونده‌های قدیمی"],
     ["rejected","ردشده"],
    ].map(([key,title])=><div key={key} style={{...base,padding:16}}>
     <strong style={{display:"block",fontSize:23,fontWeight:650,color:"#125985"}}>{fa(counts[key])}</strong>
     <small style={small}>{title}</small>
    </div>)}
   </div>

   <section style={{...base,padding:17,marginBottom:16,display:"flex",justifyContent:"space-between",gap:12,flexWrap:"wrap",alignItems:"center"}}>
    <div style={{display:"flex",gap:7,flexWrap:"wrap"}}>
     {[["all","همه"],["verified","تأییدشده"],["pending","در جریان"],["legacy","قدیمی"],["rejected","ردشده"]].map(([id,title])=>
      <button type="button" key={id} style={chip(filter===id)} onClick={()=>setFilter(id)}>{title}</button>
     )}
    </div>
    <input aria-label="جست‌وجوی پرونده" type="search" value={query} onChange={e=>setQuery(e.target.value)}
     placeholder="جست‌وجوی نام یا شناسه کاربر" style={{width:260,maxWidth:"100%",minHeight:39,border:"1px solid #d6e7f1",borderRadius:10,padding:"7px 12px",fontSize:11}}/>
   </section>

   <div style={{display:"grid",gridTemplateColumns:"repeat(auto-fit,minmax(265px,1fr))",gap:14}}>
    {visible.map(v=>{
     const approved=v.status==="approved"&&v.verification_provider==="api_ir";
     const color=approved?"#168a61":v.status==="rejected"?"#c65c55":"#a17528";
     return <article key={v.id} style={{...base,padding:17}}>
      <div style={{display:"flex",alignItems:"start",gap:10,justifyContent:"space-between"}}>
       <div><strong style={{display:"block",fontSize:13,color:"#16587e",fontWeight:650}}>{v.full_name||"پرونده بدون نام"}</strong>
        <small style={{...small,marginTop:6,display:"block"}}>کاربر #{fa(v.user_id)} · {safePhone(v.mobile)}</small></div>
       <Badge color={approved?"success":v.status==="rejected"?"danger":"secondary"} style={{fontSize:10,fontWeight:400,whiteSpace:"nowrap"}}>{label(v)}</Badge>
      </div>
      <div style={{background:"#f5f9fd",border:"1px solid #e8f0f6",borderRadius:11,margin:"13px 0",padding:12,fontSize:11,color:"#557a94",lineHeight:2.4}}>
       <div style={{display:"flex",justifyContent:"space-between",gap:8}}><span>تأیید شماره همراه</span><strong style={{color:v.phone_verified?"#178a62":"#9e7b44"}}>{v.phone_verified?"تأییدشده":"در انتظار"}</strong></div>
       <div style={{display:"flex",justifyContent:"space-between",gap:8}}><span>استعلام PersonInfo</span><strong style={{color:v.person_info_status==="matched"?"#168965":"#987a43"}}>{v.person_info_status==="matched"?"تطبیق مثبت":v.person_info_status==="mismatch"?"عدم تطبیق":"انجام نشده"}</strong></div>
       <div style={{display:"flex",justifyContent:"space-between",gap:8}}><span>تأیید خودکار سطح ۱</span><strong style={{color}}>{approved?"ثبت‌شده":"ثبت نشده"}</strong></div>
       <div style={{color:"#7896a9",fontSize:10,marginTop:7}}>آخرین استعلام: {fmtDate(v.person_info_checked_at)}</div>
      </div>
      {v.status!=="rejected"&&<div>
       <label style={{...small,display:"block",marginBottom:7}}>لغو اعتبار پرونده (فقط با دلیل مکتوب)</label>
       <Input type="textarea" rows="2" disabled={busy} placeholder="دلیل لغو اعتبار / رد پرونده را وارد کنید" value={reason[v.id]||""}
        onChange={e=>setReason(old=>({...old,[v.id]:e.target.value}))} style={{fontSize:11,borderRadius:9}}/>
       <Button color="danger" outline size="sm" disabled={busy} onClick={()=>revoke(v)} style={{fontSize:11,marginTop:10,borderRadius:8}}>لغو اعتبار پرونده</Button>
      </div>}
     </article>;
    })}
   </div>
   {!loading&&!visible.length&&<p style={{...small,textAlign:"center",padding:26}}>پرونده‌ای با این فیلتر پیدا نشد.</p>}
   <p style={{...small,fontSize:10,margin:"20px 0 0"}}>تأییدهای قدیمی برای ثبت سوابق باقی می‌مانند. در نسخه جدید سطح پایه تنها پس از کد پیامکی و تطبیق موفق مشخصات هویتی API.ir فعال می‌شود. این نتیجه مالکیت سیم‌کارت به کد ملی را اثبات نمی‌کند.</p>
  </CardBody>
 </Card>;
}

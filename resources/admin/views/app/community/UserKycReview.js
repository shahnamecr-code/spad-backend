import React,{useEffect,useState} from "react";
import axios from "axios";
import {Card,CardBody,Button,Input,Badge} from "reactstrap";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";
const API=adminPathApi+"/identity/requests";
const text=(e)=>Object.values(e?.response?.data?.errors||{}).flat()[0]||e?.response?.data?.message||"عملیات ناموفق بود";
export default function UserKycReview(){
 const [list,setList]=useState([]),[notes,setNotes]=useState({}),[levels,setLevels]=useState({}),[busy,setBusy]=useState(false);
 const load=async()=>{try{const r=await axios.get(API);setList(r.data.data||[])}catch(e){NotificationManager.error(text(e))}};
 useEffect(()=>{load()},[]);
 const file=async(v,kind)=>{
  try{const r=await axios.get(API+"/"+v.id+"/documents/"+kind,{responseType:"blob"});
   const u=URL.createObjectURL(r.data),a=document.createElement("a");a.href=u;a.download="spad-identity-"+v.id+"-"+kind;a.click();setTimeout(()=>URL.revokeObjectURL(u),10000);
  }catch(e){NotificationManager.error(text(e))}
 };
 const review=async(v,decision)=>{
  const level=Number(levels[v.id]||v.requested_level||1),note=(notes[v.id]||"").trim();
  if(decision==="reject"&&note.length<5){NotificationManager.error("دلیل رد را با حداقل پنج حرف ثبت کنید.");return;}
  if(!window.confirm("آیا پس از بررسی شخصی همه مدارک، نتیجه احراز هویت کاربر ثبت شود؟"))return;
  setBusy(true);try{await axios.patch(API+"/"+v.id,{decision,level:decision==="approve"?level:null,note:note||null});
   NotificationManager.success("نتیجه بررسی احراز هویت ثبت شد.");await load();
  }catch(e){NotificationManager.error(text(e))}finally{setBusy(false)}
 };
 return <Card style={{borderRadius:17,marginBottom:20,border:"1px solid #c9e5f6",background:"linear-gradient(140deg,#eff9ff,#fff)"}}><CardBody>
  <div style={{display:"flex",justifyContent:"space-between",alignItems:"center",flexWrap:"wrap",gap:12}}>
   <div><small style={{fontSize:10,color:"#0887c7"}}>SPAD TRUST & IDENTITY</small><h3 style={{color:"#0e6091",fontSize:18,margin:"7px 0"}}>احراز هویت کاربران · سطح یک، دو و سه</h3>
    <p style={{fontSize:12,color:"#6d8da2",margin:0}}>تأیید دستی پس از شاهکار و بررسی واقعی مدارک. فایل‌ها فقط از API محافظت‌شده دانلود می‌شوند.</p></div>
   <Button color="info" outline onClick={load}>تازه‌سازی درخواست‌ها</Button>
  </div>
  <div style={{display:"grid",gridTemplateColumns:"repeat(auto-fit,minmax(265px,1fr))",gap:12,marginTop:18}}>
  {list.map(v=><div key={v.id} style={{border:"1px solid #d9e9f3",borderRadius:14,padding:15,background:"#fff"}}>
   <div style={{display:"flex",justifyContent:"space-between",gap:9}}><strong style={{fontSize:13,color:"#195c86"}}>{v.full_name}</strong><Badge color={v.status==="approved"?"success":v.status==="pending"?"warning":"secondary"}>{v.status==="pending"?"در انتظار بررسی":v.status==="approved"?"تأیید شده":v.status}</Badge></div>
   <p style={{fontSize:11,color:"#7592a6",margin:"10px 0"}}>کاربر #{v.user_id} · {v.mobile||"—"} · سطح درخواستی {v.requested_level}</p>
   <p style={{fontSize:11,color:"#3a7297"}}>موبایل: {v.phone_verified?"تأییدشده":"تأییدنشده"} · شاهکار: {v.shahkar_status==="matched"?"تطبیق مثبت":v.shahkar_status} · ثبت‌احوال: {v.person_info_status==="matched"?"تأییدشده":v.person_info_status==="mismatch"?"مغایرت":"بررسی‌نشده"}</p>
   <div style={{display:"flex",gap:6,flexWrap:"wrap",marginBottom:10}}>
    <Button size="sm" color="primary" outline onClick={()=>file(v,"national_card")}>کارت ملی</Button>
    {v.requested_level>=2&&<Button size="sm" color="primary" outline onClick={()=>file(v,"face_photo")}>تصویر تطبیقی</Button>}
    {v.requested_level>=3&&<Button size="sm" color="primary" outline onClick={()=>file(v,"supporting")}>مدرک تکمیلی</Button>}
   </div>
   {v.status==="pending"&&<>
    <label style={{fontSize:11,color:"#4a7896"}}>سطح مصوب
     <Input type="select" value={levels[v.id]||v.requested_level} onChange={e=>setLevels(x=>({...x,[v.id]:e.target.value}))}>
      {[1,2,3].filter(n=>n<=v.requested_level).map(n=><option value={n} key={n}>سطح {n}</option>)}
     </Input></label>
    <Input type="textarea" rows={2} placeholder="یادداشت بررسی / دلیل رد" value={notes[v.id]||""} onChange={e=>setNotes(x=>({...x,[v.id]:e.target.value}))}/>
    <div style={{display:"flex",gap:8,marginTop:8}}><Button disabled={busy||v.shahkar_status!=="matched"||!v.phone_verified} color="success" size="sm" onClick={()=>review(v,"approve")}>تأیید و تعیین سطح</Button><Button color="danger" outline size="sm" disabled={busy} onClick={()=>review(v,"reject")}>رد درخواست</Button></div>
   </>}
  </div>)}
  </div>
  {!list.length&&<p style={{fontSize:12,color:"#7297b1",marginTop:18}}>فعلاً پرونده‌ای برای این بخش ثبت نشده است.</p>}
 </CardBody></Card>;
}

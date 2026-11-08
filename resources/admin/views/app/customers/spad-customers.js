import React,{useCallback,useEffect,useState} from "react";
import axios from "axios";
import {useSelector} from "react-redux";
import {adminPathApi,adminRoot} from "../../../constants/defaultValues";
import {Link} from "react-router-dom";

const API=adminPathApi+"/crm-pro";
const empty={first_name:"",last_name:"",mobile:"",budget_toman:"",city:"شیراز",
 preferred_location:"",note:"",source:"manual",stage:"new",interest:"buy",
 priority:2,sms_consent:false,voice_consent:false,consent_source:"",email:""};
const format=v=>v?Number(v).toLocaleString("fa-IR")+" تومان":"—";
const errorText=e=>e?.response?.data?.message||"ارتباط با سرور برقرار نشد. تنظیمات و اتصال را بررسی کنید.";
const panel={background:"#fff",border:"1px solid #dceaf5",borderRadius:18,padding:20,boxShadow:"0 6px 23px rgba(13,72,118,.04)"};
const fieldStyle={border:"1px solid #cbdff0",borderRadius:10,padding:"11px 13px",width:"100%",background:"#fff",fontSize:13,outline:"none"};
const Btn=({children,...props})=><button type="button" {...props} style={{background:"#edf7ff",color:"#006eb8",border:"1px solid #c8e4f7",borderRadius:10,padding:"9px 14px",cursor:"pointer",...props.style}}>{children}</button>;
const digits=s=>String(s||"").replace(/[۰-۹٠-٩]/g,c=>"۰۱۲۳۴۵۶۷۸۹".indexOf(c)>-1?String("۰۱۲۳۴۵۶۷۸۹".indexOf(c)):String("٠١٢٣٤٥٦٧٨٩".indexOf(c)));

export default function SpadCustomers(){
 const user=useSelector(x=>x.authUser.currentUser);
 const access=Boolean(user&&Array.isArray(user.roles)&&user.roles.includes("Super Admin"));
 const [rows,setRows]=useState([]),[page,setPage]=useState(1),[total,setTotal]=useState(0);
 const [search,setSearch]=useState(""),[query,setQuery]=useState("");
 const [form,setForm]=useState(null),[saving,setSaving]=useState(false),[loading,setLoading]=useState(true);
 const [message,setMessage]=useState(""),[error,setError]=useState("");
 const load=useCallback(async()=>{
  setLoading(true);
  try{const r=await axios.get(API+"/contacts",{params:{page,per_page:15,q:query||undefined}});
   setRows(r.data.data||[]);setTotal(r.data.total||0);setError("");
  }catch(e){setError(errorText(e));}finally{setLoading(false);}
 },[page,query]);
 useEffect(()=>{if(access)load();else setLoading(false);},[access,load]);
 const edit=r=>{
  const name=String(r.full_name||"").trim().split(/\s+/);
  setForm({...empty,...r,first_name:name.shift()||"",last_name:name.join(" "),budget_toman:r.budget_toman||""});
  setError("");setMessage("");
 };
 const save=async e=>{
  e.preventDefault();if(!form)return;
  const full_name=(form.first_name.trim()+" "+form.last_name.trim()).trim();
  const budget=Number(digits(form.budget_toman).replace(/[,٬\s]/g,""));
  if(!Number.isSafeInteger(budget)||budget<1000000||budget>100000000000000){setError("بودجه خرید را به تومان و به‌صورت عدد معتبر وارد کنید.");return;}
  if(!/^09\d{9}$/.test(digits(form.mobile))){setError("شماره موبایل معتبر وارد کنید.");return;}
  if(form.sms_consent && form.consent_source.trim().length<8){setError("برای ارسال پیامک، منبع رضایت مشتری را ثبت کنید.");return;}
  setSaving(true);setError("");setMessage("");
  const payload={...form,full_name,mobile:digits(form.mobile),budget_toman:budget,
   budget:budget.toLocaleString("fa-IR")+" تومان",voice_consent:!!form.voice_consent,sms_consent:!!form.sms_consent};
  delete payload.first_name;delete payload.last_name;delete payload.id;
  try{if(form.id)await axios.put(API+"/contacts/"+form.id,payload);
   else await axios.post(API+"/contacts",payload);
   setMessage(form.id?"اطلاعات مشتری ویرایش شد.":"مشتری جدید ثبت شد.");setForm(null);await load();
  }catch(err){setError(errorText(err));}finally{setSaving(false);}
 };
 const remove=async r=>{
  if(!window.confirm("مشتری «"+r.full_name+"» از فهرست فعال حذف شود؟ سوابق قبلی حفظ می‌شوند."))return;
  setSaving(true);setError("");
  try{await axios.delete(API+"/contacts/"+r.id);setMessage("مشتری حذف شد.");await load();}
  catch(e){setError(errorText(e));}finally{setSaving(false);}
 };
 const cell={padding:"13px 12px",borderBottom:"1px solid #ebf3f8",fontSize:12,whiteSpace:"nowrap"};
 const head={...cell,color:"#527c98",fontWeight:600,background:"#f2f8fe"};
 if(!access)return <div dir="rtl" style={panel}>دسترسی به مدیریت مشتریان فقط برای مدیر اصلی اسپاد مجاز است.</div>;
 return <section dir="rtl" style={{padding:"12px 18px 75px",maxWidth:1450,margin:"auto",fontFamily:"inherit"}}>
  <header style={{background:"linear-gradient(110deg,#0c345a,#0687d4)",borderRadius:21,padding:"24px 26px",color:"#fff",marginBottom:20,display:"flex",justifyContent:"space-between",alignItems:"center",flexWrap:"wrap",gap:14}}>
   <div><small style={{opacity:.8}}>سامانه هوشمند ارتباط با خریداران اسپاد</small>
    <h1 style={{fontSize:24,color:"white",margin:"8px 0"}}>مدیریت مشتریان اسپاد</h1>
    <p style={{fontSize:12,opacity:.9,margin:0}}>ثبت، ویرایش و حذف مشتریان، مدیریت بودجه خرید و رضایت دریافت پیامک</p></div>
   <div style={{display:"flex",gap:8,flexWrap:"wrap"}}>
    <Link to={adminRoot+"/settings/sms/send"} style={{color:"#fff",border:"1px solid #b5e6ff",padding:"10px 14px",borderRadius:10}}>مرکز ارسال پیامک</Link>
    <button type="button" onClick={()=>edit(empty)} style={{color:"#086cb6",background:"#fff",border:0,borderRadius:10,padding:"10px 18px",cursor:"pointer"}}>+ افزودن مشتری</button>
   </div>
  </header>
  {error&&<p role="alert" style={{background:"#fff0ef",color:"#ab3440",padding:12,borderRadius:10}}>{error}</p>}
  {message&&<p role="status" style={{background:"#edf9f4",color:"#137b57",padding:12,borderRadius:10}}>{message}</p>}
  <div style={panel}>
   <div style={{display:"flex",justifyContent:"space-between",gap:15,flexWrap:"wrap",alignItems:"center",marginBottom:16}}>
    <h2 style={{fontSize:17,margin:0}}>فهرست مشتریان <small style={{fontSize:12,color:"#6e90aa"}}>({Number(total).toLocaleString("fa-IR")} پرونده)</small></h2>
    <form onSubmit={e=>{e.preventDefault();setPage(1);setQuery(search);}} style={{display:"flex",gap:8}}>
     <input aria-label="جستجوی مشتری" value={search} onChange={e=>setSearch(e.target.value)} placeholder="نام یا شماره موبایل" style={{...fieldStyle,width:260,maxWidth:"56vw"}}/>
     <Btn style={{background:"#007ccb",color:"#fff"}}>جستجو</Btn>
    </form>
   </div>
   <div style={{overflowX:"auto"}}>
    <table style={{width:"100%",borderCollapse:"collapse",textAlign:"right"}}>
     <thead><tr><th style={head}>نام و نام خانوادگی</th><th style={head}>شماره موبایل</th><th style={head}>بودجه خرید</th><th style={head}>محدوده</th><th style={head}>اجازه پیامک</th><th style={head}>عملیات</th></tr></thead>
     <tbody>{rows.map(r=><tr key={r.id}>
      <td style={cell}>{r.full_name}</td><td style={cell} dir="ltr">{r.mobile}</td><td style={cell}>{format(r.budget_toman)}</td>
      <td style={cell}>{r.preferred_location||r.city||"—"}</td>
      <td style={cell}>{r.sms_consent&&!r.opted_out_at?"ثبت شده":"ندارد"}</td>
      <td style={cell}><div style={{display:"flex",gap:6}}><Btn onClick={()=>edit(r)}>ویرایش</Btn><Btn disabled={saving} onClick={()=>remove(r)} style={{background:"#fff2f2",color:"#aa3851",borderColor:"#ffe2e2"}}>حذف</Btn></div></td>
     </tr>)}</tbody>
    </table>
    {!rows.length&&<p style={{padding:25,textAlign:"center",color:"#718eab"}}>{loading?"در حال دریافت اطلاعات…":"هنوز مشتری مطابق این جستجو ثبت نشده است."}</p>}
   </div>
   <div style={{display:"flex",justifyContent:"space-between",alignItems:"center",paddingTop:16}}>
    <span style={{fontSize:12,color:"#718eab"}}>هر مشتری فقط یک پرونده فعال دارد.</span>
    <div style={{display:"flex",gap:7,alignItems:"center"}}><Btn disabled={page<=1||saving} onClick={()=>setPage(p=>p-1)}>قبلی</Btn><small>صفحه {page.toLocaleString("fa-IR")}</small><Btn disabled={page*15>=total||saving} onClick={()=>setPage(p=>p+1)}>بعدی</Btn></div>
   </div>
  </div>
  {form&&<div role="presentation" onMouseDown={e=>{if(e.target===e.currentTarget&&!saving)setForm(null);}} style={{position:"fixed",inset:0,background:"rgba(8,36,67,.56)",zIndex:99999,display:"flex",alignItems:"center",justifyContent:"center",padding:16}}>
   <form onSubmit={save} style={{...panel,width:690,maxHeight:"90vh",overflowY:"auto"}} aria-label="فرم اطلاعات مشتری">
    <div style={{display:"flex",justifyContent:"space-between",alignItems:"center",marginBottom:15}}>
     <h2 style={{fontSize:19,margin:0}}>{form.id?"ویرایش اطلاعات مشتری":"افزودن مشتری جدید"}</h2><Btn disabled={saving} onClick={()=>setForm(null)}>بستن</Btn>
    </div>
    <div style={{display:"grid",gridTemplateColumns:"repeat(auto-fit,minmax(210px,1fr))",gap:14}}>
     {[
      ["first_name","نام","text"],["last_name","نام خانوادگی","text"],["mobile","شماره موبایل","tel"],
      ["budget_toman","بودجه خرید (تومان)","text"],["city","شهر","text"],
      ["preferred_location","محله یا محدوده مدنظر","text"]
     ].map(([name,title,type])=><label key={name} style={{fontSize:12,color:"#406b89"}}>{title}
      <input required={["first_name","last_name","mobile","budget_toman"].includes(name)} type={type} dir={name==="mobile"||name==="budget_toman"?"ltr":"rtl"}
       value={form[name]||""} onChange={e=>setForm({...form,[name]:e.target.value})} style={{...fieldStyle,marginTop:6}}/>
     </label>)}
    </div>
    <label style={{display:"block",fontSize:12,color:"#406b89",marginTop:14}}>توضیحات مشتری
     <textarea rows={3} maxLength={3000} style={{...fieldStyle,marginTop:6}} value={form.note||""} onChange={e=>setForm({...form,note:e.target.value})}/>
    </label>
    <div style={{background:"#f1f8fe",borderRadius:12,padding:13,marginTop:15}}>
     <label style={{fontSize:13,display:"flex",gap:10,alignItems:"center"}}>
      <input type="checkbox" checked={!!form.sms_consent} onChange={e=>setForm({...form,sms_consent:e.target.checked})}/> مشتری رضایت صریح خود را برای پیامک معرفی ملک اعلام کرده است.
     </label>
     {form.sms_consent&&<label style={{display:"block",fontSize:12,marginTop:10}}>منبع یا مدرک ثبت رضایت
      <input required minLength={8} maxLength={120} value={form.consent_source||""} placeholder="فرم، تاریخ و نحوه اعلام رضایت" onChange={e=>setForm({...form,consent_source:e.target.value})} style={{...fieldStyle,marginTop:6}}/>
     </label>}
     <p style={{fontSize:11,color:"#688aa3",margin:"9px 0 0"}}>بدون رضایت ثبت‌شده هیچ پیامک تبلیغاتی یا معرفی ملک ارسال نمی‌شود. اختلاف مجاز با بودجه مشتری: یک میلیارد تومان کمتر یا بیشتر.</p>
    </div>
    <div style={{display:"flex",justifyContent:"flex-end",gap:10,marginTop:18}}>
     <Btn onClick={()=>setForm(null)} disabled={saving}>انصراف</Btn>
     <button type="submit" disabled={saving} style={{background:"#0077c6",color:"#fff",border:0,borderRadius:10,padding:"11px 25px",cursor:"pointer"}}>{saving?"در حال ذخیره…":"ذخیره اطلاعات مشتری"}</button>
    </div>
   </form>
  </div>}
 </section>;
}
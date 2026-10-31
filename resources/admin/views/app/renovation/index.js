import React,{useEffect,useMemo,useState} from "react";
import axios from "axios";
import {Button,Badge,Card,CardBody,Input,Label,Table,Row,Col} from "reactstrap";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";
const base=adminPathApi+"/online-renovation";
const fmt=n=>Number(n||0).toLocaleString("fa-IR");
const formStyle={border:"1px solid #d5e8f7",background:"#f8fbff",borderRadius:10,fontSize:12,minHeight:39};
const blockStyle={border:"1px solid #d3e6f4",borderRadius:16,boxShadow:"0 8px 23px rgba(4,87,155,.045)"};
const states={draft:"پیش‌نویس",completed:"دارای فاکتور",reviewed:"بررسی کارشناسی"};
export default function RenovationAdmin(){
 const [view,setView]=useState("projects"),[items,setItems]=useState([]),[rates,setRates]=useState([]);
 const [selected,setSelected]=useState(null),[detail,setDetail]=useState(null);
 const [edit,setEdit]=useState({admin_quote_toman:"",admin_days:"",admin_note:"",status:"completed"});
 const [loading,setLoading]=useState(true),[busy,setBusy]=useState(false),[query,setQuery]=useState("");
 const [notice,setNotice]=useState("");
 const load=async()=>{
  setLoading(true);
  const results=await Promise.allSettled([axios.get(base+"/projects"),axios.get(base+"/rates")]);
  if(results[0].status==="fulfilled")setItems(results[0].value.data.data||[]);
  if(results[1].status==="fulfilled")setRates(results[1].value.data.data||[]);
  if(results.some(x=>x.status==="rejected"))setNotice("دریافت بخشی از اطلاعات ممکن نشد.");
  setLoading(false);
 };
 useEffect(()=>{load()},[]);
 const detailLoad=async p=>{
  setSelected(p.id);setDetail(null);
  try{const r=await axios.get(base+"/projects/"+p.id);setDetail(r.data.data);
    setEdit({admin_quote_toman:r.data.data.admin_quote_toman??"",
      admin_days:r.data.data.admin_days??"",admin_note:r.data.data.admin_note||"",
      status:r.data.data.status||"draft"});
  }catch(_){NotificationManager.error("جزئیات پروژه در دسترس نیست.");}
 };
 const save=async()=>{
  if(!selected)return;setBusy(true);
  try{await axios.patch(base+"/projects/"+selected,{
    status:edit.status,
    admin_quote_toman:edit.admin_quote_toman===""?null:Number(edit.admin_quote_toman),
    admin_days:edit.admin_days===""?null:Number(edit.admin_days),
    admin_note:edit.admin_note||null
  });NotificationManager.success("بررسی و برآورد پروژه ذخیره شد.");await load();await detailLoad({id:selected});}
  catch(e){NotificationManager.error(e?.response?.data?.message||"ذخیره انجام نشد.");}finally{setBusy(false);}
 };
 const rateUpdate=(code,key,val)=>setRates(a=>a.map(x=>x.code===code?{...x,[key]:val}:x));
 const saveRate=async rate=>{
  setBusy(true);try{await axios.put(base+"/rates/"+rate.code,{
   price_toman:Number(rate.price_toman),days:Number(rate.days),enabled:!!rate.enabled
  });NotificationManager.success("تعرفه بازسازی ذخیره شد.");await load();
  }catch(e){NotificationManager.error(e?.response?.data?.message||"ثبت تعرفه ناموفق بود.");}
  finally{setBusy(false);}
 };
 const download=async m=>{
  try{
   const response=await axios.get(base+"/projects/"+selected+"/media/"+m.id,{responseType:"blob"});
   const url=URL.createObjectURL(response.data);const a=document.createElement("a");
   a.href=url;a.download="SPAD-renovation-"+selected+"-"+m.id+".jpg";document.body.appendChild(a);a.click();a.remove();
   setTimeout(()=>URL.revokeObjectURL(url),3000);
  }catch(_){NotificationManager.error("دریافت فایل مجاز نبود.");}
 };
 const filtered=useMemo(()=>items.filter(x=>(x.title+" "+x.id+" "+x.user_id).includes(query.trim())),[items,query]);
 return <div dir="rtl" style={{fontFamily:"IRANSans,sans-serif",padding:4}}>
  <section style={{padding:"26px 30px",background:"linear-gradient(110deg,#07549f,#0984e3,#12a2df)",borderRadius:20,color:"#fff",marginBottom:17}}>
    <small style={{letterSpacing:1}}>SPAD ONLINE RENOVATION · CONTROL CENTER</small>
    <h2 style={{fontSize:23,color:"#fff",margin:"10px 0 5px"}}>مدیریت بازسازی آنلاین اسپاد</h2>
    <p style={{margin:0,color:"#d6f1ff",fontSize:12}}>پروژه‌ها، نرخ‌ها، تاریخچه خروجی‌های کاربر و فاکتورهای بازسازی در یک صفحه</p>
  </section>
  <Row style={{marginBottom:12}}>{[["پروژه‌ها",items.length],["فاکتور صادرشده",items.filter(x=>x.invoice).length],["نیازمند بررسی",items.filter(x=>x.status==="completed").length],["آیتم‌های تعرفه",rates.length]].map(([label,num])=>
    <Col key={label} xs="6" md="3"><Card style={{...blockStyle,marginBottom:8}}><CardBody><small style={{fontSize:11,color:"#7c99ab"}}>{label}</small>
      <strong style={{display:"block",fontSize:24,color:"#086fb8"}}>{fmt(num)}</strong></CardBody></Card></Col>)}</Row>
  <div style={{display:"flex",flexWrap:"wrap",gap:8,marginBottom:15}}>
   <Button color={view==="projects"?"primary":"light"} onClick={()=>setView("projects")}>پروژه‌ها و فاکتورها</Button>
   <Button color={view==="rates"?"primary":"light"} onClick={()=>setView("rates")}>قیمت‌گذاری و زمان‌بندی</Button>
   <Button color="info" outline onClick={load} disabled={loading}>به‌روزرسانی</Button>
  </div>
  {notice&&<p style={{fontSize:12,color:"#bd6929"}}>{notice}</p>}
  {view==="rates"?<Card style={blockStyle}><CardBody>
    <h3 style={{fontSize:17,color:"#164c7a"}}>تعرفه اولیه بازسازی</h3>
    <p style={{fontSize:11,color:"#718da2",lineHeight:2}}>نرخ‌های ثبت‌شده نمونه اولیه برآورد هستند. پیش از ارائه نرخ بازار، قیمت هر آیتم را با تعرفه کارشناسی به‌روز کنید. تغییرات روی فاکتورهای صادرشده قبلی اعمال نمی‌شوند.</p>
    <div style={{display:"grid",gridTemplateColumns:"repeat(auto-fit,minmax(260px,1fr))",gap:11}}>
    {rates.map(r=><div key={r.code} style={{border:"1px solid #dceaf4",borderRadius:13,padding:15,background:"#faffff"}}>
      <strong style={{display:"block",fontSize:13,color:"#21597e",marginBottom:12}}>{r.title}</strong>
      <Label style={{fontSize:11}}>قیمت به تومان / {r.unit}</Label><Input type="number" min={0} style={formStyle} value={r.price_toman} onChange={e=>rateUpdate(r.code,"price_toman",e.target.value)}/>
      <Label style={{fontSize:11,marginTop:12}}>مدت تقریبی (روز)</Label><Input type="number" min={0} max={120} style={formStyle} value={r.days} onChange={e=>rateUpdate(r.code,"days",e.target.value)}/>
      <label style={{display:"flex",alignItems:"center",gap:8,margin:"12px 0",fontSize:11}}>
       <Input type="checkbox" checked={!!r.enabled} onChange={e=>rateUpdate(r.code,"enabled",e.target.checked)}/> فعال</label>
      <Button size="sm" color="primary" disabled={busy} onClick={()=>saveRate(r)}>ذخیره نرخ</Button>
    </div>)}</div>
  </CardBody></Card>:<div>
   <Card style={blockStyle}><CardBody>
     <div style={{display:"flex",justifyContent:"space-between",gap:12,alignItems:"center",flexWrap:"wrap",marginBottom:12}}>
      <div><h3 style={{fontSize:17,color:"#164d76"}}>پروژه‌های بازسازی کاربران</h3><small>برای مشاهده جزئیات، خروجی و فاکتور روی هر پروژه کلیک کنید.</small></div>
      <Input style={{...formStyle,width:260}} placeholder="جستجوی نام، کد پروژه یا کاربر" value={query} onChange={e=>setQuery(e.target.value)}/>
     </div>
     <div style={{overflowX:"auto"}}><Table responsive hover size="sm" style={{fontSize:12}}>
      <thead><tr><th>کد</th><th>عنوان پروژه</th><th>شناسه کاربر</th><th>روش ورود</th><th>وضعیت</th><th>برآورد تومان</th><th></th></tr></thead>
      <tbody>{filtered.map(p=><tr key={p.id}><td>{fmt(p.id)}</td><td>{p.title}</td><td>{fmt(p.user_id)}</td>
       <td>{p.source_type==="site_property"?"ملک اسپاد":"آپلود تصاویر"}</td><td><Badge color={p.status==="reviewed"?"success":p.status==="draft"?"secondary":"info"}>{states[p.status]||p.status}</Badge></td>
       <td>{p.invoice?fmt(p.invoice.total_toman):"—"}</td><td><Button size="sm" outline color="primary" onClick={()=>detailLoad(p)}>بررسی</Button></td></tr>)}</tbody>
     </Table></div>
   </CardBody></Card>
   {selected&&<Card style={{...blockStyle,marginTop:16}}><CardBody>
    {detail?<><div style={{display:"flex",justifyContent:"space-between",gap:9,alignItems:"center",flexWrap:"wrap"}}><h3 style={{fontSize:17,color:"#164d76"}}>جزئیات پروژه {fmt(selected)} · {detail.title}</h3><Button size="sm" onClick={()=>setSelected(null)}>بستن</Button></div>
     <p style={{fontSize:11,color:"#8a9eae"}}>متراژ: {fmt(detail.area)} متر | سبک: {detail.design_style} | زمان ثبت: {detail.created_at}</p>
     {detail.invoice?<div style={{overflowX:"auto"}}><Table responsive size="sm" style={{fontSize:11}}><thead><tr><th>آیتم</th><th>مقدار</th><th>نرخ واحد</th><th>جمع</th></tr></thead><tbody>{detail.invoice.lines.map(l=><tr key={l.code}><td>{l.title}</td><td>{fmt(l.qty)} {l.unit}</td><td>{fmt(l.unit_price_toman)}</td><td>{fmt(l.total_toman)}</td></tr>)}</tbody></Table>
      <strong style={{fontSize:15,color:"#076fb2"}}>جمع برآورد: {fmt(detail.invoice.total_toman)} تومان | {fmt(detail.invoice.estimated_days)} روز</strong></div>
     :<p>هنوز فاکتور نهایی صادر نشده است.</p>}
     <h4 style={{fontSize:14,marginTop:18}}>تصاویر و خروجی‌های پروژه</h4>
     <div style={{display:"flex",flexWrap:"wrap",gap:8}}>{(detail.media||[]).map(m=><Button key={m.id} color={m.kind==="output"?"success":"secondary"} outline size="sm" onClick={()=>download(m)}>
      {m.kind==="output"?"خروجی":"تصویر اولیه"} · {m.room} #{fmt(m.id)}</Button>)}</div>
     <Row style={{marginTop:18}}><Col md="3"><Label style={{fontSize:11}}>وضعیت پروژه</Label><Input type="select" style={formStyle} value={edit.status} onChange={e=>setEdit(v=>({...v,status:e.target.value}))}>
      <option value="draft">پیش‌نویس</option><option value="completed">دارای فاکتور</option><option value="reviewed">بررسی کارشناسی</option></Input></Col>
      <Col md="4"><Label style={{fontSize:11}}>مبلغ کارشناسی نهایی (تومان)</Label><Input type="number" min={0} style={formStyle} value={edit.admin_quote_toman} onChange={e=>setEdit(v=>({...v,admin_quote_toman:e.target.value}))}/></Col>
      <Col md="3"><Label style={{fontSize:11}}>مدت پیشنهادی (روز)</Label><Input type="number" min={1} style={formStyle} value={edit.admin_days} onChange={e=>setEdit(v=>({...v,admin_days:e.target.value}))}/></Col></Row>
     <Label style={{fontSize:11,marginTop:10}}>یادداشت کارشناس</Label><Input type="textarea" style={{...formStyle,minHeight:90}} value={edit.admin_note} onChange={e=>setEdit(v=>({...v,admin_note:e.target.value}))}/>
     <Button color="primary" style={{marginTop:13}} disabled={busy} onClick={save}>ذخیره بررسی پروژه</Button>
    </>:<p style={{fontSize:12}}>دریافت جزئیات پروژه...</p>}
   </CardBody></Card>}
  </div>}
 </div>;
}

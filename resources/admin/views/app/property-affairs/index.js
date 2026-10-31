import React,{useEffect,useMemo,useState} from "react";
import axios from "axios";
import {Button,Badge,Card,CardBody,Input,Label,FormGroup,Row,Col,Table} from "reactstrap";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";

const api=adminPathApi+"/property-affairs";
const money=n=>Number(n||0).toLocaleString("fa-IR")+" تومان";
const statusLabels={submitted:"ثبت‌شده",in_review:"در حال بررسی",waiting_user:"نیازمند مدرک",completed:"تکمیل‌شده",rejected:"ردشده"};
const statusColors={submitted:"info",in_review:"primary",waiting_user:"warning",completed:"success",rejected:"danger"};
const initialFields=[
 {key:"city",label:"شهر موردنظر",type:"text",required:true,placeholder:""},
 {key:"description",label:"شرح درخواست",type:"textarea",required:true,placeholder:""}
];
const blank=()=>({slug:"",title:"",subtitle:"",category:"ثبت‌نام و خدمات",icon:"home",description:"",requirements:"",official_url:"",
 price_toman:0,enabled:true,accepting_requests:true,featured:false,sort_order:100,form_schema:initialFields.map(x=>({...x}))});
const inputStyle={borderRadius:10,minHeight:41,background:"#f8fbff",borderColor:"#d8e9f4",fontSize:12};
const panelStyle={borderRadius:17,border:"1px solid #d9eaf5",boxShadow:"0 9px 26px rgba(12,80,128,.05)"};
const labelStyle={color:"#377392",fontSize:12,fontWeight:600};
const field=(title,node)=><FormGroup><Label style={labelStyle}>{title}</Label>{node}</FormGroup>;
const formInput=(key,value,change,type="text",props={})=><Input style={inputStyle} type={type} value={value??""} onChange={e=>change(key,e.target.value)} {...props}/>;
const safeError=e=>Object.values(e?.response?.data?.errors||{}).flat()[0]||e?.response?.data?.message||"عملیات انجام نشد.";

export default function PropertyAffairsAdmin(){
 const [view,setView]=useState("requests");
 const [services,setServices]=useState([]);
 const [requests,setRequests]=useState([]);
 const [filter,setFilter]=useState("all");
 const [search,setSearch]=useState("");
 const [busy,setBusy]=useState(false);
 const [editing,setEditing]=useState(null);
 const [form,setForm]=useState(blank());
 const [formOpen,setFormOpen]=useState(false);
 const [selected,setSelected]=useState(null);
 const [review,setReview]=useState({status:"in_review",public_note:"",admin_note:"",quoted_price_toman:""});
 const [detail,setDetail]=useState(null);
 const [loading,setLoading]=useState(true);
 const load=async()=>{
  const results=await Promise.allSettled([axios.get(api+"/services"),axios.get(api+"/requests")]);
  if(results[0].status==="fulfilled")setServices(results[0].value.data.data||[]);
  if(results[1].status==="fulfilled")setRequests(results[1].value.data.data||[]);
  if(results.some(x=>x.status==="rejected"))NotificationManager.error("دریافت بخشی از اطلاعات مرکز امور ملکی انجام نشد.");
  setLoading(false);
 };
 useEffect(()=>{load()},[]);
 const change=(key,value)=>setForm(p=>({...p,[key]:value}));
 const openService=item=>{
  setEditing(item?.id||null);
  setForm(item?{...item,form_schema:(item.form_schema||[]).map(x=>({...x,options:x.options||[]}))}:blank());
  setFormOpen(true);setView("services");
 };
 const changeField=(i,key,value)=>setForm(p=>({...p,form_schema:p.form_schema.map((f,index)=>index===i?{...f,[key]:value}:f)}));
 const saveService=async()=>{
  if(!form.title.trim()||!form.slug.trim()){NotificationManager.error("عنوان و شناسه خدمت الزامی است.");return;}
  setBusy(true);
  try{
   const payload={...form,price_toman:Number(form.price_toman||0),sort_order:Number(form.sort_order||0),
    enabled:!!form.enabled,accepting_requests:!!form.accepting_requests,featured:!!form.featured,
    official_url:form.official_url||null,
    form_schema:form.form_schema.map(f=>({...f,required:!!f.required,
     ...(f.type==="select"?{options:Array.isArray(f.options)?f.options:[]}:{} )}))};
   if(editing)await axios.put(api+"/services/"+editing,payload);
   else await axios.post(api+"/services",payload);
   NotificationManager.success(editing?"تنظیمات خدمت به‌روزرسانی شد.":"خدمت جدید اضافه شد.");
   setFormOpen(false);setEditing(null);await load();
  }catch(e){NotificationManager.error(String(safeError(e)))}finally{setBusy(false)}
 };
 const openRequest=async item=>{
  setSelected(item.id);setDetail(null);
  setReview({status:item.status,public_note:item.public_note||"",admin_note:item.admin_note||"",
   quoted_price_toman:item.quoted_price_toman??""});
  try{const response=await axios.get(api+"/requests/"+item.id);setDetail(response.data.data)}
  catch(e){NotificationManager.error(String(safeError(e)))}
 };
 const updateRequest=async()=>{
  if(!selected)return;
  setBusy(true);
  try{
   await axios.patch(api+"/requests/"+selected,{
    status:review.status,public_note:review.public_note||null,
    admin_note:review.admin_note||null,
    quoted_price_toman:review.quoted_price_toman===""?null:Number(review.quoted_price_toman)
   });
   NotificationManager.success("وضعیت درخواست و یادداشت کارشناس ذخیره شد.");
   await load();await openRequest({...requests.find(x=>x.id===selected),...review,id:selected});
  }catch(e){NotificationManager.error(String(safeError(e)))}finally{setBusy(false)}
 };
 const download=async d=>{
  try{
   const r=await axios.get(api+"/requests/"+selected+"/documents/"+d.id,{responseType:"blob"});
   const url=window.URL.createObjectURL(r.data);
   const anchor=document.createElement("a");anchor.href=url;anchor.download=d.original_name||"property-affair-document";
   document.body.appendChild(anchor);anchor.click();anchor.remove();
   window.setTimeout(()=>window.URL.revokeObjectURL(url),40000);
  }catch(e){NotificationManager.error(String(safeError(e)))}
 };
 const filtered=useMemo(()=>requests.filter(x=>(filter==="all"||x.status===filter)&&
  (!search.trim()||[x.service_title,x.tracking_code,x.mobile,x.first_name,x.last_name,String(x.id)].join(" ").toLowerCase().includes(search.trim().toLowerCase()))),
  [requests,filter,search]);
 const stats=[["درخواست‌ها",requests.length],["در حال بررسی",requests.filter(x=>x.status==="in_review").length],
  ["منتظر تکمیل مدارک",requests.filter(x=>x.status==="waiting_user").length],["خدمات فعال",services.filter(x=>x.enabled).length]];
 return <div dir="rtl" style={{padding:"0 3px 35px",fontFamily:"IRANSans,IRANSans_FaNum,sans-serif"}}>
  <section style={{padding:"24px 27px",background:"linear-gradient(115deg,#07395f,#0a8dbb)",color:"#fff",borderRadius:19,marginBottom:18}}>
   <div style={{fontSize:10,letterSpacing:1.5}}>SPAD PROPERTY SERVICES ADMIN</div>
   <h2 style={{fontSize:22,color:"#fff",margin:"9px 0"}}>مرکز مدیریت امور ملکی اسپاد</h2>
   <p style={{fontSize:12,color:"#d9f3ff",margin:0}}>مدیریت درخواست‌های املاک و اسکان، تسهیلات ودیعه، ثبت‌نام‌های حمایتی، فرم‌ها و مدارک کاربران</p>
  </section>
  <Row style={{marginBottom:13}}>{stats.map(([title,n])=><Col xs="6" md="3" key={title}><Card style={{...panelStyle,marginBottom:10}}><CardBody style={{padding:16}}>
   <small style={{fontSize:11,color:"#7a9bab"}}>{title}</small><strong style={{display:"block",fontSize:23,color:"#116b9b"}}>{Number(n).toLocaleString("fa-IR")}</strong>
  </CardBody></Card></Col>)}</Row>
  <div style={{display:"flex",gap:8,flexWrap:"wrap",marginBottom:16}}>
   <Button color={view==="requests"?"primary":"light"} onClick={()=>setView("requests")}>درخواست‌ها و رسیدگی</Button>
   <Button color={view==="services"?"primary":"light"} onClick={()=>setView("services")}>تعریف و تنظیم خدمات</Button>
   <Button color="info" outline onClick={load} disabled={loading}>به‌روزرسانی</Button>
  </div>
  {view==="services"&&<Card style={panelStyle}><CardBody>
   <div style={{display:"flex",alignItems:"center",justifyContent:"space-between",flexWrap:"wrap",gap:12,marginBottom:13}}>
    <div><h3 style={{fontSize:17,color:"#125e86"}}>کتابخانه خدمات امور ملکی</h3><small style={{color:"#7797ab"}}>افزودن، ویرایش، فعال‌سازی سرویس و شخصی‌سازی فرم و اولویت نمایش</small></div>
    <Button color="primary" onClick={()=>openService(null)}>+ افزودن خدمت جدید</Button>
   </div>
   <div style={{display:"grid",gridTemplateColumns:"repeat(auto-fit,minmax(260px,1fr))",gap:12}}>
    {services.map(x=><div key={x.id} style={{border:"1px solid #dceaf3",borderRadius:13,padding:15,background:"#f9fcff"}}>
     <div style={{display:"flex",justifyContent:"space-between",gap:8}}><strong style={{fontSize:13,color:"#156789"}}>{x.title}</strong><Badge color={x.enabled?"success":"secondary"}>{x.enabled?"منتشرشده":"غیرفعال"}</Badge></div>
     <p style={{fontSize:11,lineHeight:2,color:"#7896a8"}}>{x.subtitle}</p>
     <div style={{fontSize:10,color:"#7e99a8"}}>{x.category} · {x.form_schema?.length||0} فیلد · {x.accepting_requests?"پذیرش باز":"پذیرش بسته"}</div>
     <Button color="info" outline size="sm" style={{marginTop:12}} onClick={()=>openService(x)}>ویرایش تنظیمات و فرم</Button>
    </div>)}
   </div>
   {formOpen&&<section style={{border:"1px solid #bddfed",borderRadius:16,background:"linear-gradient(135deg,#f1faff,#fff)",padding:19,marginTop:20}}>
    <h3 style={{color:"#116b93",fontSize:17}}>{editing?"ویرایش خدمت":"ایجاد خدمت جدید"}</h3>
    <Row>
     <Col md="6">{field("عنوان خدمت",formInput("title",form.title,change))}</Col>
     <Col md="6">{field("شناسه انگلیسی برای لینک",formInput("slug",form.slug,change,"text",{placeholder:"example-service"}))}</Col>
     <Col md="6">{field("زیرعنوان",formInput("subtitle",form.subtitle,change))}</Col>
     <Col md="6">{field("دسته‌بندی",formInput("category",form.category,change))}</Col>
     <Col md="6">{field("آیکون",<Input style={inputStyle} type="select" value={form.icon} onChange={e=>change("icon",e.target.value)}>
      {["home","wallet","building","file","users","map","credit"].map(v=><option key={v} value={v}>{v}</option>)}</Input>)}</Col>
     <Col md="6">{field("ترتیب نمایش",formInput("sort_order",form.sort_order,change,"number",{min:0,max:999}))}</Col>
     <Col md="12">{field("توضیحات صفحه",formInput("description",form.description,change,"textarea",{rows:3}))}</Col>
     <Col md="12">{field("شرایط و مدارک موردنیاز",formInput("requirements",form.requirements,change,"textarea",{rows:2}))}</Col>
     <Col md="12">{field("لینک سامانه رسمی مرتبط (HTTPS)",formInput("official_url",form.official_url,change,"url"))}</Col>
     <Col md="6">{field("هزینه اعلامی خدمات (تومان)؛ بدون دریافت آنلاین",formInput("price_toman",form.price_toman,change,"number",{min:0}))}</Col>
    </Row>
    <div style={{display:"flex",flexWrap:"wrap",gap:16,margin:"8px 0 18px"}}>
     {[["enabled","نمایش عمومی سرویس"],["accepting_requests","پذیرش درخواست جدید"],["featured","نمایش به‌عنوان خدمت ویژه"]].map(([k,title])=><Label key={k} check style={{fontSize:12}}>
      <Input type="checkbox" checked={!!form[k]} onChange={e=>change(k,e.target.checked)}/> {title}
     </Label>)}
    </div>
    <div style={{borderTop:"1px solid #d2e7f5",paddingTop:15}}>
     <h4 style={{fontSize:15,color:"#126a93"}}>سازنده فرم اختصاصی درخواست</h4>
     <p style={{fontSize:11,color:"#7697a7"}}>ترتیب، عنوان، نوع و الزامی‌بودن فیلدها را تعریف کنید. هیچ فیلدی برای رمز عبور، پیامک ورود یا رمز پویا ایجاد نکنید.</p>
     {form.form_schema.map((f,i)=><div key={i} style={{display:"grid",gridTemplateColumns:"1fr 1fr 120px 95px auto",gap:9,alignItems:"end",background:"#fff",padding:10,borderRadius:11,border:"1px solid #dfedf5",marginBottom:9}}>
      {field("عنوان فیلد",<Input style={inputStyle} value={f.label} onChange={e=>changeField(i,"label",e.target.value)}/>)}
      {field("کلید انگلیسی",<Input style={inputStyle} value={f.key} onChange={e=>changeField(i,"key",e.target.value)}/>)}
      {field("نوع",<Input type="select" style={inputStyle} value={f.type} onChange={e=>changeField(i,"type",e.target.value)}>
       {["text","textarea","select","date","number"].map(v=><option value={v} key={v}>{v}</option>)}</Input>)}
      <div style={{fontSize:11,paddingBottom:13}}><Label check><Input type="checkbox" checked={!!f.required} onChange={e=>changeField(i,"required",e.target.checked)}/> الزامی</Label></div>
      <Button size="sm" color="danger" outline style={{marginBottom:14}} disabled={form.form_schema.length===1} onClick={()=>change("form_schema",form.form_schema.filter((_,j)=>j!==i))}>حذف</Button>
      {f.type==="select"&&<div style={{gridColumn:"1/-1"}}>{field("گزینه‌های انتخابی (با | جدا کنید)",<Input style={inputStyle} value={(f.options||[]).join("|")} onChange={e=>changeField(i,"options",e.target.value.split("|").map(x=>x.trim()).filter(Boolean))}/>)}</div>}
     </div>)}
     <Button color="info" outline size="sm" disabled={form.form_schema.length>=15} onClick={()=>change("form_schema",[...form.form_schema,{key:"field_"+(form.form_schema.length+1),label:"فیلد جدید",type:"text",required:false,placeholder:""}])}>+ افزودن فیلد جدید</Button>
    </div>
    <div style={{display:"flex",gap:10,marginTop:19}}>
     <Button color="primary" disabled={busy} onClick={saveService}>{busy?"در حال ذخیره":"ذخیره تنظیمات خدمت"}</Button>
     <Button color="light" onClick={()=>setFormOpen(false)}>انصراف</Button>
    </div>
   </section>}
  </CardBody></Card>}
  {view==="requests"&&<Card style={panelStyle}><CardBody>
   <div style={{display:"flex",justifyContent:"space-between",gap:12,flexWrap:"wrap",marginBottom:15}}>
    <h3 style={{color:"#14698e",fontSize:17}}>میز رسیدگی به پرونده‌های امور ملکی</h3>
    <div style={{display:"flex",gap:8,flexWrap:"wrap"}}>
     <Input style={{...inputStyle,minWidth:220}} placeholder="جستجو: نام، موبایل، کد پیگیری" value={search} onChange={e=>setSearch(e.target.value)}/>
     <Input style={{...inputStyle,minWidth:165}} type="select" value={filter} onChange={e=>setFilter(e.target.value)}>
      <option value="all">همه وضعیت‌ها</option>{Object.entries(statusLabels).map(([k,v])=><option key={k} value={k}>{v}</option>)}
     </Input>
    </div>
   </div>
   <Table responsive hover style={{fontSize:12}}><thead><tr><th>شماره / پیگیری</th><th>خدمت</th><th>متقاضی</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th></tr></thead>
    <tbody>{filtered.map(q=><tr key={q.id}>
     <td>#{q.id}<div style={{fontSize:10,color:"#89a2b0"}}>{q.tracking_code}</div></td>
     <td>{q.service_title}</td><td>{[q.first_name,q.last_name].filter(Boolean).join(" ")||"کاربر"}<div style={{fontSize:10}}>{q.mobile||"—"}</div></td>
     <td><Badge color={statusColors[q.status]||"secondary"}>{statusLabels[q.status]||q.status}</Badge></td>
     <td>{q.created_at?String(q.created_at).replace("T"," ").slice(0,16):"—"}</td>
     <td><Button color="info" outline size="sm" onClick={()=>openRequest(q)}>مشاهده و رسیدگی</Button></td>
    </tr>)}</tbody></Table>
   {!filtered.length&&<p style={{fontSize:12,color:"#7c9bab"}}>درخواستی با شرایط انتخاب‌شده پیدا نشد.</p>}
   {selected&&<div style={{border:"1px solid #bedfee",borderRadius:14,padding:18,background:"#f5fbff",marginTop:16}}>
    <h4 style={{fontSize:16,color:"#116890"}}>پرونده #{selected} · جزئیات و پاسخ کارشناس</h4>
    {!detail?<p>در حال دریافت پرونده...</p>:<>
     <Row><Col md="6"><strong style={{fontSize:12}}>مشخصات ثبت‌شده</strong>
      <div style={{marginTop:10,fontSize:12}}>{Object.entries(detail.form_data||{}).map(([key,value])=><div key={key} style={{padding:"8px 0",borderBottom:"1px solid #dcebf5",overflowWrap:"anywhere"}}>{key}: <strong>{String(value)}</strong></div>)}</div>
     </Col><Col md="6"><strong style={{fontSize:12}}>مدارک خصوصی پرونده</strong><div style={{marginTop:10}}>
      {(detail.documents||[]).length?detail.documents.map(d=><div key={d.id} style={{display:"flex",alignItems:"center",justifyContent:"space-between",gap:8,padding:8,borderBottom:"1px solid #dcebf5",fontSize:11}}>
       <span>{d.original_name}</span><Button color="info" outline size="sm" onClick={()=>download(d)}>دریافت امن</Button>
      </div>):<p style={{fontSize:11}}>مدرکی بارگذاری نشده است.</p>}
     </div></Col></Row>
     <Row style={{marginTop:15}}><Col md="6">{field("وضعیت رسیدگی",<Input style={inputStyle} type="select" value={review.status} onChange={e=>setReview(p=>({...p,status:e.target.value}))}>
      {Object.entries(statusLabels).map(([k,v])=><option value={k} key={k}>{v}</option>)}</Input>)}</Col>
      <Col md="6">{field("برآورد هزینه خدمات (تومان، صرفاً اطلاع‌رسانی)",<Input style={inputStyle} type="number" min={0} value={review.quoted_price_toman} onChange={e=>setReview(p=>({...p,quoted_price_toman:e.target.value}))}/>)}</Col>
      <Col md="6">{field("پاسخ عمومی قابل مشاهده برای کاربر",<Input style={inputStyle} type="textarea" rows={3} value={review.public_note} onChange={e=>setReview(p=>({...p,public_note:e.target.value}))}/>)}</Col>
      <Col md="6">{field("یادداشت داخلی فقط مدیریت",<Input style={inputStyle} type="textarea" rows={3} value={review.admin_note} onChange={e=>setReview(p=>({...p,admin_note:e.target.value}))}/>)}</Col>
     </Row>
     <p style={{fontSize:11,color:"#7796a9"}}>ثبت و تکمیل پرونده در اسپاد، تأیید سامانه رسمی یا پرداخت بانکی محسوب نمی‌شود. مدارک فقط در پنل مجاز قابل دانلود هستند.</p>
     <Button color="primary" disabled={busy} onClick={updateRequest}>ذخیره وضعیت و پاسخ کارشناس</Button>{" "}
     <Button color="light" onClick={()=>{setSelected(null);setDetail(null)}}>بستن</Button>
    </>}
   </div>}
  </CardBody></Card>}
 </div>;
}

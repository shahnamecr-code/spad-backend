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
 const [view,setView]=useState("projects"),[items,setItems]=useState([]),[rates,setRates]=useState([]),[assets,setAssets]=useState([]);
 const [assetCategory,setAssetCategory]=useState("all");
 const [newAsset,setNewAsset]=useState({title:"",category:"cabinet",category_title:"کابینت",room_scope:"kitchen",
  color_hex:"#e8eff5",image_url:"",photo_source_url:"",photo_credit:"",material_spec:"",unit_price_toman:"",enabled:true,sort_order:100});
 const [selected,setSelected]=useState(null),[detail,setDetail]=useState(null);
 const [edit,setEdit]=useState({admin_quote_toman:"",admin_days:"",admin_note:"",status:"completed"});
 const [loading,setLoading]=useState(true),[busy,setBusy]=useState(false),[query,setQuery]=useState("");
 const [notice,setNotice]=useState("");
 const load=async()=>{
  setLoading(true);
  const results=await Promise.allSettled([axios.get(base+"/projects"),axios.get(base+"/rates"),axios.get(base+"/assets")]);
  if(results[0].status==="fulfilled")setItems(results[0].value.data.data||[]);
  if(results[1].status==="fulfilled")setRates(results[1].value.data.data||[]);
  if(results[2].status==="fulfilled")setAssets(results[2].value.data.data||[]);
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
 const assetCategories=[["cabinet","کابینت","kitchen"],["hob","گاز صفحه‌ای","kitchen"],["hood","هود","kitchen"],["sink","سینک","kitchen"],
   ["chandelier","لوستر","living"],["ceiling","کناف","living"],["led","لاین نوری","living"],
   ["paint","رنگ دیوار","all"],["wallpaper","کاغذ دیواری","all"],["curtain","پرده","living"]];
 const assetInput=(id,key,value)=>setAssets(a=>a.map(x=>x.id===id?{...x,[key]:value}:x));
 const assetPayload=x=>({
   title:x.title,category:x.category,category_title:x.category_title,room_scope:x.room_scope,
   color_hex:x.color_hex||"#edf5fb",image_url:x.image_url||null,photo_source_url:x.photo_source_url||null,
   photo_credit:x.photo_credit||null,material_spec:x.material_spec||null,
   unit_price_toman:x.unit_price_toman===""||x.unit_price_toman===null?null:Number(x.unit_price_toman),
   enabled:!!x.enabled,sort_order:Number(x.sort_order||0)
 });
 const saveAsset=async asset=>{setBusy(true);try{
   await axios.put(base+"/assets/"+asset.id,assetPayload(asset));NotificationManager.success("نمونه ذخیره شد.");await load();
  }catch(e){NotificationManager.error(e?.response?.data?.message||"ذخیره نمونه ناموفق بود.");}finally{setBusy(false);}};
 const createAsset=async()=>{setBusy(true);try{
   await axios.post(base+"/assets",assetPayload(newAsset));NotificationManager.success("نمونه جدید ثبت شد.");await load();
   setNewAsset(x=>({...x,title:"",image_url:"",photo_source_url:"",photo_credit:"",material_spec:"",unit_price_toman:""}));
  }catch(e){NotificationManager.error(e?.response?.data?.message||"ثبت نمونه ناموفق بود.");}finally{setBusy(false);}};
 const uploadReference=async(assetId,file)=>{
  if(!file)return;
  if(!/^image\/(jpeg|png|webp)$/.test(file.type)||file.size>5*1024*1024){
   NotificationManager.error("فرمت تصویر باید JPG، PNG یا WebP و حجم حداکثر ۵ مگابایت باشد.");return;
  }
  setBusy(true);
  try{
   const form=new FormData();form.append("image",file);
   await axios.post(base+"/assets/"+assetId+"/reference",form,{headers:{"Content-Type":"multipart/form-data"}});
   NotificationManager.success("عکس مرجع اختصاصی این مدل ذخیره شد.");await load();
  }catch(e){NotificationManager.error(e?.response?.data?.message||"بارگذاری تصویر مرجع ناموفق بود.");}
  finally{setBusy(false);}
 };
 const disableAsset=async asset=>{
  if(!window.confirm("این نمونه غیرفعال شود؟ تاریخچه کاربران حذف نمی‌شود."))return;
  setBusy(true);try{await axios.delete(base+"/assets/"+asset.id);await load();}
  catch(e){NotificationManager.error(e?.response?.data?.message||"عملیات ناموفق بود.");}
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
   <Button color={view==="assets"?"primary":"light"} onClick={()=>setView("assets")}>کاتالوگ متریال و تصاویر</Button>
   <Button color="info" outline onClick={load} disabled={loading}>به‌روزرسانی</Button>
  </div>
  {notice&&<p style={{fontSize:12,color:"#bd6929"}}>{notice}</p>}
  {view==="assets"?<Card style={blockStyle}><CardBody>
   <h3 style={{fontSize:18,color:"#0a77c4",marginBottom:8}}>کتابخانه نمونه‌های تصویری و متریال</h3>
   <p style={{fontSize:11,lineHeight:2,color:"#6a8398"}}>عکس‌های انتخابی، نمونه الهام طراحی هستند؛ برای تعرفه تأییدنشده مبلغ درج نکنید. افزودن عکس فقط از دامنه مجاز و نشانی HTTPS انجام می‌شود.</p>
   <div style={{display:"flex",flexWrap:"wrap",gap:7,margin:"12px 0 16px"}}>
    {[["all","همه"],...assetCategories.map(x=>[x[0],x[1]])].map(([code,title])=><Button
     key={code} size="sm" color={assetCategory===code?"primary":"light"} onClick={()=>setAssetCategory(code)}>{title}</Button>)}
   </div>
   <div style={{display:"grid",gridTemplateColumns:"repeat(auto-fill,minmax(250px,1fr))",gap:12}}>
    {assets.filter(x=>assetCategory==="all"||x.category===assetCategory).map(asset=>
     <article key={asset.id} style={{padding:12,border:"1px solid #d7e7f5",borderRadius:15,background:"#f9fcff"}}>
      {(asset.reference_image_url||asset.image_url)?<img src={asset.reference_image_url||asset.image_url} alt={asset.title} loading="lazy" style={{width:"100%",height:132,objectFit:"cover",borderRadius:10,marginBottom:9}}/>:
       <div style={{height:55,background:asset.color_hex,borderRadius:10,marginBottom:8}}/>}
      <Label style={{fontSize:11}}>عنوان نمونه</Label><Input style={formStyle} value={asset.title} onChange={e=>assetInput(asset.id,"title",e.target.value)}/>
      <Label style={{fontSize:11,marginTop:7}}>مشخصات دقیق طراحی برای هوش مصنوعی</Label>
      <Input type="textarea" style={{...formStyle,minHeight:70}} maxLength={1000} value={asset.material_spec||""}
       placeholder="جنس، نوع درب، طرح دستگیره، رنگ، رگه‌ها، سطح مات یا براق، اتصالات و جزئیات ساخت"
       onChange={e=>assetInput(asset.id,"material_spec",e.target.value)}/>
      <Label style={{fontSize:11,marginTop:7}}>رنگ نمایشی</Label><Input type="color" style={{width:"100%",height:35}} value={asset.color_hex} onChange={e=>assetInput(asset.id,"color_hex",e.target.value)}/>
      <Label style={{fontSize:11,marginTop:7}}>نشانی تصویر نمونه (HTTPS)</Label><Input style={formStyle} value={asset.image_url||""} onChange={e=>assetInput(asset.id,"image_url",e.target.value)}/>
      <Label style={{fontSize:11,marginTop:7}}>بارگذاری عکس مرجع محصول با مجوز استفاده</Label>
      <Input type="file" accept="image/jpeg,image/png,image/webp" disabled={busy}
       onChange={e=>{const file=e.target.files?.[0];if(file)uploadReference(asset.id,file);}}/>
      <small style={{display:"block",fontSize:10,color:"#7190a4",marginTop:5}}>تصویر اختصاصی مرجع در دیتابیس ثبت می‌شود و هنگام رندر AI به مدل ارسال خواهد شد. حقوق انتشار تصویر را رعایت کنید.</small>
      <Label style={{fontSize:11,marginTop:7}}>لینک منبع و صاحب عکس</Label>
      <Input style={formStyle} value={asset.photo_source_url||""} onChange={e=>assetInput(asset.id,"photo_source_url",e.target.value)}/>
      <Input style={{...formStyle,marginTop:5}} value={asset.photo_credit||""} onChange={e=>assetInput(asset.id,"photo_credit",e.target.value)} placeholder="نام عکاس یا منبع"/>
      <Label style={{fontSize:11,marginTop:7}}>قیمت تخمینی نمونه (تومان)</Label>
      <Input type="number" min="0" style={formStyle} value={asset.unit_price_toman??""} placeholder="قیمت تعیین نشده" onChange={e=>assetInput(asset.id,"unit_price_toman",e.target.value)}/>
      <label style={{display:"flex",gap:10,alignItems:"center",padding:"9px 0",fontSize:11}}>
       <Input type="checkbox" checked={!!asset.enabled} onChange={e=>assetInput(asset.id,"enabled",e.target.checked)}/> فعال
      </label>
      <div style={{display:"flex",gap:8}}>
       <Button color="primary" size="sm" disabled={busy} onClick={()=>saveAsset(asset)}>ذخیره نمونه</Button>
       <Button color="danger" outline size="sm" disabled={busy} onClick={()=>disableAsset(asset)}>غیرفعال‌سازی</Button>
      </div>
     </article>)}
   </div>
   <div style={{padding:15,background:"#eaf7ff",border:"1px solid #c6e5f8",borderRadius:13,marginTop:20}}>
    <strong style={{color:"#136b9f",fontSize:13}}>افزودن نمونه جدید به کاتالوگ</strong>
    <Row style={{marginTop:12}}>
     <Col md="4"><Label>دسته‌بندی</Label><Input type="select" value={newAsset.category} onChange={e=>{
      const match=assetCategories.find(x=>x[0]===e.target.value);
      setNewAsset(v=>({...v,category:match[0],category_title:match[1],room_scope:match[2]}));
     }}>{assetCategories.map(x=><option key={x[0]} value={x[0]}>{x[1]}</option>)}</Input></Col>
     <Col md="4"><Label>عنوان نمونه</Label><Input value={newAsset.title} onChange={e=>setNewAsset(v=>({...v,title:e.target.value}))}/></Col>
     <Col md="12"><Label>مشخصات محصول و جزئیات طرح برای هوش مصنوعی</Label>
      <Input type="textarea" value={newAsset.material_spec} maxLength={1000} onChange={e=>setNewAsset(v=>({...v,material_spec:e.target.value}))} placeholder="جنس بدنه، سبک، نوع دستگیره، نوع سطح، رنگ و سایر جزئیات واقعی"/></Col>
     <Col md="4"><Label>رنگ پیش‌نمایش</Label><Input type="color" value={newAsset.color_hex} onChange={e=>setNewAsset(v=>({...v,color_hex:e.target.value}))}/></Col>
     <Col md="6"><Label>لینک تصویر</Label><Input value={newAsset.image_url} onChange={e=>setNewAsset(v=>({...v,image_url:e.target.value}))} placeholder="https://images.unsplash.com/..."/></Col>
     <Col md="6"><Label>لینک منبع تصویر</Label><Input value={newAsset.photo_source_url} onChange={e=>setNewAsset(v=>({...v,photo_source_url:e.target.value}))}/></Col>
     <Col md="6"><Label>نام عکاس</Label><Input value={newAsset.photo_credit} onChange={e=>setNewAsset(v=>({...v,photo_credit:e.target.value}))}/></Col>
     <Col md="6"><Label>قیمت تخمینی (تومان)</Label><Input type="number" min={0} value={newAsset.unit_price_toman} onChange={e=>setNewAsset(v=>({...v,unit_price_toman:e.target.value}))}/></Col>
    </Row>
    <Button color="primary" disabled={busy||!newAsset.title.trim()} onClick={createAsset} style={{marginTop:12}}>ثبت نمونه جدید</Button>
   </div>
  </CardBody></Card>:
  view==="rates"?<Card style={blockStyle}><CardBody>
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

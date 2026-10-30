import React,{useEffect,useMemo,useState} from "react";
import axios from "axios";
import {Button,Card,CardBody,FormGroup,Input,Label,Table,Row,Col} from "reactstrap";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";
const API=adminPathApi+"/materials/prices";
const defaults={enabled:true,sync_enabled:false,provider:"persianapi",api_connected:false,refresh_minutes:30,stale_minutes:120,provider_price_divisor:1,title:"قیمت روز مصالح ساختمانی",subtitle:"",notice:"",api_token:"",clear_api_token:false};
const blank={name:"",sku:"",category:"آهن‌آلات و فولاد",brand:"",unit:"کیلوگرم",price_toman:"",external_id:"",sort_order:100,featured:false,enabled:true};
const fmt=(v)=>Number(v||0).toLocaleString("fa-IR");
const date=(v)=>v?new Date(v).toLocaleString("fa-IR"):"—";
const MaterialPricesAdmin=()=>{
 const [settings,setSettings]=useState(defaults),[items,setItems]=useState([]),[cats,setCats]=useState([]),[form,setForm]=useState(blank),[id,setId]=useState(null),[q,setQ]=useState(""),[filter,setFilter]=useState("all"),[history,setHistory]=useState([]),[busy,setBusy]=useState(false),[syncing,setSyncing]=useState(false);
 const notice=(msg,fail=false)=>(fail?NotificationManager.error:NotificationManager.success)(msg);
 const reload=async()=>{try{const r=await axios.get(API);setItems(r.data.data.materials||[]);setCats(r.data.data.categories||[]);setSettings({...defaults,...r.data.data.settings,api_token:""});}catch(e){notice(e.response?.data?.message||"دریافت مدیریت مصالح ناموفق بود",true)}};
 useEffect(()=>{reload()},[]);
 const set=(k,v)=>setSettings(s=>({...s,[k]:v}));
 const saveSettings=async()=>{setBusy(true);try{const {api_connected,last_sync_at,last_sync_status,provider,...payload}=settings;const r=await axios.post(API+"/settings",payload);setSettings({...defaults,...r.data.data});notice("تنظیمات ذخیره شد");}catch(e){notice(e.response?.data?.message||"ذخیره تنظیمات ناموفق بود",true)}finally{setBusy(false)}};
 const save=async()=>{setBusy(true);try{const p={...form,price_toman:form.price_toman===""?null:Number(form.price_toman),sort_order:Number(form.sort_order||100),enabled:!!form.enabled,featured:!!form.featured};if(id)await axios.put(API+"/items/"+id,p);else await axios.post(API+"/items",p);notice("کالا و نرخ ذخیره شد");setId(null);setForm(blank);setHistory([]);await reload();}catch(e){notice(e.response?.data?.message||"ثبت کالا ناموفق بود",true)}finally{setBusy(false)}};
 const edit=(x)=>{setId(x.id);setForm({...blank,...x,price_toman:x.price_toman??""});setHistory([]);axios.get(API+"/items/"+x.id+"/history").then(r=>setHistory(r.data.data||[])).catch(()=>{});document.getElementById("materials-edit")?.scrollIntoView({behavior:"smooth"})};
 const sync=async()=>{setSyncing(true);try{const r=await axios.post(API+"/sync");notice(r.data.data?.message||"دریافت قیمت کامل شد");await reload()}catch(e){notice(e.response?.data?.data?.message||e.response?.data?.message||"دریافت API انجام نشد",true)}finally{setSyncing(false)}};
 const visible=useMemo(()=>items.filter(x=>(filter==="all"||x.category===filter)&&(!q||[x.name,x.sku,x.brand,x.category].join(" ").includes(q))),[items,q,filter]);
 return <div className="container-fluid" dir="rtl">
  <div style={{background:"linear-gradient(120deg,#075399,#0b9ddd)",borderRadius:20,padding:24,color:"#fff",marginBottom:20}}><small>SPAD MATERIALS COMMAND CENTER</small><h2 style={{color:"#fff"}}>مدیریت قیمت مصالح</h2><p>تنظیمات نمایش، اتصال امن به API، ویرایش نرخ و مشاهده تاریخچه مصالح</p></div>
  <Row className="mb-4"><Col md="4"><Card><CardBody>کل مصالح: <b>{fmt(items.length)}</b></CardBody></Card></Col><Col md="4"><Card><CardBody>دارای نرخ: <b>{fmt(items.filter(x=>x.price_toman!==null).length)}</b></CardBody></Card></Col><Col md="4"><Card><CardBody>وضعیت API: <b>{settings.api_connected?"کلید تنظیم شده":"بدون کلید"}</b></CardBody></Card></Col></Row>
  <Card className="mb-4"><CardBody><h3>تنظیمات صفحه و همگام‌سازی</h3><Row>
   <Col md="6"><FormGroup><Label>عنوان صفحه</Label><Input value={settings.title} onChange={e=>set("title",e.target.value)}/></FormGroup><FormGroup><Label>زیرعنوان</Label><Input type="textarea" value={settings.subtitle||""} onChange={e=>set("subtitle",e.target.value)}/></FormGroup><FormGroup><Label>متن هشدار قیمت</Label><Input type="textarea" value={settings.notice||""} onChange={e=>set("notice",e.target.value)}/></FormGroup></Col>
   <Col md="6"><FormGroup><Label>توکن PersianAPI (پس از ذخیره قابل نمایش نیست)</Label><Input type="password" autoComplete="new-password" value={settings.api_token||""} placeholder={settings.api_connected?"کلید ذخیره شده است؛ برای تغییر وارد کنید":"توکن API را وارد کنید"} onChange={e=>set("api_token",e.target.value)}/></FormGroup>
    <Row><Col md="4"><FormGroup><Label>فاصله دریافت (دقیقه)</Label><Input type="number" min={15} max={1440} value={settings.refresh_minutes} onChange={e=>set("refresh_minutes",Number(e.target.value))}/></FormGroup></Col>
    <Col md="4"><FormGroup><Label>اعتبار نرخ (دقیقه)</Label><Input type="number" min={15} max={10080} value={settings.stale_minutes} onChange={e=>set("stale_minutes",Number(e.target.value))}/></FormGroup></Col>
    <Col md="4"><FormGroup><Label>ارز ورودی API</Label><Input type="select" value={settings.provider_price_divisor} onChange={e=>set("provider_price_divisor",Number(e.target.value))}><option value={1}>تومان</option><option value={10}>ریال</option></Input></FormGroup></Col></Row>
    <p style={{fontSize:12}}>آخرین بررسی: {date(settings.last_sync_at)} — {settings.last_sync_status||"هنوز اجرا نشده"}</p>
   </Col></Row>
   <div style={{display:"flex",gap:22,flexWrap:"wrap",marginBottom:16}}>
    <Label check><Input type="checkbox" checked={!!settings.enabled} onChange={e=>set("enabled",e.target.checked)}/> فعال بودن صفحه </Label>
    <Label check><Input type="checkbox" checked={!!settings.sync_enabled} onChange={e=>set("sync_enabled",e.target.checked)}/> اتصال خودکار </Label>
    <Label check><Input type="checkbox" checked={!!settings.clear_api_token} onChange={e=>set("clear_api_token",e.target.checked)}/> حذف کلید API </Label>
   </div>
   <Button color="primary" disabled={busy} onClick={saveSettings}>ذخیره تنظیمات</Button>{" "}
   <Button color="info" outline disabled={syncing||!settings.sync_enabled||!settings.api_connected} onClick={sync}>{syncing?"دریافت...":"همگام‌سازی اکنون"}</Button>
  </CardBody></Card>
  <Card className="mb-4" id="materials-edit"><CardBody><h3>{id?"ویرایش مصالح و نرخ":"ثبت مصالح جدید"}</h3><Row>
    {[["name","نام کالا"],["sku","کد یکتا"],["brand","برند / کارخانه"],["unit","واحد"],["external_id","شناسه منبع API"]].map(([k,label])=><Col md="4" key={k}><FormGroup><Label>{label}</Label><Input value={form[k]||""} onChange={e=>setForm(s=>({...s,[k]:e.target.value}))}/></FormGroup></Col>)}
    <Col md="4"><FormGroup><Label>دسته</Label><Input type="select" value={form.category} onChange={e=>setForm(s=>({...s,category:e.target.value}))}>{cats.map(c=><option key={c}>{c}</option>)}</Input></FormGroup></Col>
    <Col md="4"><FormGroup><Label>قیمت (تومان)</Label><Input type="number" min={1} value={form.price_toman??""} placeholder="خالی = بدون نرخ" onChange={e=>setForm(s=>({...s,price_toman:e.target.value}))}/></FormGroup></Col>
    <Col md="4"><FormGroup><Label>ترتیب</Label><Input type="number" value={form.sort_order} onChange={e=>setForm(s=>({...s,sort_order:e.target.value}))}/></FormGroup></Col>
   </Row>
   <Label check style={{marginLeft:20}}><Input type="checkbox" checked={!!form.enabled} onChange={e=>setForm(s=>({...s,enabled:e.target.checked}))}/> نمایش محصول</Label>
   <Label check><Input type="checkbox" checked={!!form.featured} onChange={e=>setForm(s=>({...s,featured:e.target.checked}))}/> محصول منتخب</Label>
   <div style={{marginTop:18}}><Button color="primary" disabled={busy} onClick={save}>{busy?"ذخیره...":"ذخیره کالا"}</Button>{" "}<Button outline onClick={()=>{setId(null);setForm(blank);setHistory([])}}>کالای جدید</Button></div>
   {history.length>0&&<div className="mt-4"><h4>تاریخچه آخرین نرخ‌ها</h4><Table responsive size="sm"><thead><tr><th>زمان</th><th>قیمت (تومان)</th><th>منبع</th></tr></thead><tbody>{history.slice(0,20).map(x=><tr key={x.id}><td>{date(x.quoted_at)}</td><td>{fmt(x.price_toman)}</td><td>{x.source_type}</td></tr>)}</tbody></Table></div>}
  </CardBody></Card>
  <Card><CardBody><h3>فهرست کالاها ({fmt(visible.length)})</h3><Row className="mb-3"><Col md="7"><Input value={q} onChange={e=>setQ(e.target.value)} placeholder="جست‌وجو بر اساس نام، کد یا برند"/></Col><Col md="5"><Input type="select" value={filter} onChange={e=>setFilter(e.target.value)}><option value="all">همه گروه‌ها</option>{cats.map(c=><option key={c}>{c}</option>)}</Input></Col></Row>
   <div style={{maxHeight:580,overflow:"auto"}}><Table responsive striped size="sm"><thead><tr><th>کالا</th><th>دسته</th><th>نرخ</th><th>تغییر</th><th>زمان</th><th>عملیات</th></tr></thead><tbody>{visible.slice(0,1000).map(x=><tr key={x.id}><td><b>{x.name}</b><small style={{display:"block"}}>{x.sku} · {x.brand||"—"} · هر {x.unit}</small></td><td>{x.category}</td><td>{x.price_toman===null?"—":fmt(x.price_toman)}</td><td>{x.change_percent===null?"—":x.change_percent+"٪"}</td><td>{date(x.source_updated_at)}</td><td><Button size="sm" color="info" outline onClick={()=>edit(x)}>ویرایش</Button></td></tr>)}</tbody></Table></div>
  </CardBody></Card>
 </div>;
};
export default MaterialPricesAdmin;

import React,{useEffect,useState} from "react";
import axios from "axios";
import {Card,CardBody,Button,Input,Label,FormGroup,Row,Col,Table,Badge} from "reactstrap";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";

const api=adminPathApi+"/community";
const fmt=(v)=>Number(v||0).toLocaleString("fa-IR");
const dtime=(v)=>v?String(v).replace("T"," ").slice(0,19):"—";
const statuses={pending:"در انتظار بررسی",approved:"فعال",rejected:"رد شده",suspended:"تعلیق",review:"نیازمند بررسی",published:"منتشر شده",paused:"متوقف",closed:"پایان یافته"};
const money=(v)=>fmt(v)+" تومان";
const initial={buildings_enabled:true,builders_enabled:true,auctions_enabled:true,minimum_bid_increment:10000000,auction_notice:"ثبت پیشنهاد صرفاً اعلام علاقه‌مندی است و انتقال مالکیت نیازمند قرارداد قانونی جداگانه است."};
const error=(e)=>NotificationManager.error(e.response?.data?.message||"خطا در ارتباط با سرور");
const ok=(msg)=>NotificationManager.success(msg);
const timeInput=(x)=>x?String(x).slice(0,19).replace("T"," "):"";
const CommonHeading=({title,desc})=><div style={{background:"linear-gradient(110deg,#075995,#0d9bcf)",borderRadius:18,padding:"20px 24px",color:"#fff",marginBottom:20}}>
 <small style={{opacity:.8}}>SPAD OPERATIONS COMMAND CENTER</small>
 <h2 style={{color:"#fff",fontSize:22,margin:"8px 0"}}>{title}</h2>
 <p style={{margin:0,fontSize:12,opacity:.87}}>{desc}</p></div>;
const Kpi=({title,value})=><Col sm="4"><Card style={{marginBottom:12,border:"1px solid #dbebf5",borderRadius:13}}><CardBody><small style={{color:"#7292aa"}}>{title}</small><h3 style={{color:"#087bbc",fontWeight:500}}>{fmt(value)}</h3></CardBody></Card></Col>;
export default function CommunityAdmin({mode="buildings"}){
 const [section,setSection]=useState(mode);
 const [settings,setSettings]=useState(initial);
 const [buildings,setBuildings]=useState([]);
 const [builders,setBuilders]=useState([]);
 const [auctions,setAuctions]=useState([]);
 const [bids,setBids]=useState({});
 const [auctionDrafts,setAuctionDrafts]=useState({});
 const [builderDrafts,setBuilderDrafts]=useState({});
 const [busy,setBusy]=useState(false);
 const [search,setSearch]=useState("");
 const reload=async()=>{
  try{
   const [cfg,b,br,a]=await Promise.all([
    axios.get(api+"/settings"),axios.get(api+"/buildings"),
    axios.get(api+"/builders"),axios.get(api+"/auctions")
   ]);
   setSettings({...initial,...(cfg.data.data||{})});
   setBuildings(b.data.data||[]);setBuilders(br.data.data||[]);setAuctions(a.data.data||[]);
  }catch(e){error(e)}
 };
 useEffect(()=>{reload()},[]);
 useEffect(()=>setSection(mode),[mode]);
 const saveSettings=async()=>{
  setBusy(true);
  try{
   const payload={...settings,minimum_bid_increment:Number(settings.minimum_bid_increment)};
   await axios.post(api+"/settings",payload);ok("تنظیمات خدمات اسپاد ذخیره شد");await reload();
  }catch(e){error(e)}finally{setBusy(false)}
 };
 const updateBuilding=async(item,status)=>{
  if(!window.confirm("وضعیت این ساختمان تغییر کند؟"))return;
  try{await axios.patch(api+"/buildings/"+item.id,{status});ok("وضعیت ساختمان ثبت شد");await reload()}catch(e){error(e)}
 };
 const updateBuilder=async(item)=>{
  const draft=builderDrafts[item.id]||item;
  setBusy(true);
  try{
   await axios.patch(api+"/builders/"+item.id,{
    published:!!draft.published,verified:!!draft.verified,
    rank_priority:Number(draft.rank_priority)
   });
   ok("وضعیت و اولویت نمایش سازنده ذخیره شد");await reload();
  }catch(e){error(e)}finally{setBusy(false)}
 };
 const updateAuction=async(item)=>{
  const draft=auctionDrafts[item.id]||item;
  setBusy(true);
  try{
   await axios.patch(api+"/auctions/"+item.id,{
    status:draft.status,starts_at:timeInput(draft.starts_at)||null,
    ends_at:timeInput(draft.ends_at)||null,
    minimum_increment_toman:Number(draft.minimum_increment_toman)
   });
   ok("وضعیت مزایده ذخیره شد");await reload();
  }catch(e){error(e)}finally{setBusy(false)}
 };
 const fetchBids=async(id)=>{
  try{const r=await axios.get(api+"/auctions/"+id+"/bids");setBids(o=>({...o,[id]:r.data.data||[]}))}
  catch(e){error(e)}
 };
 const filtered=(arr)=>arr.filter(x=>!search||[x.id,x.name,x.title,x.city,x.status,x.user_id].join(" ").includes(search));
 const updBuilder=(item,key,value)=>setBuilderDrafts(old=>({...old,[item.id]:{...item,...old[item.id],[key]:value}}));
 const updAuction=(item,key,value)=>setAuctionDrafts(old=>({...old,[item.id]:{...item,...old[item.id],[key]:value}}));
 return <div className="container-fluid" dir="rtl" style={{paddingBottom:60}}>
  <CommonHeading title={section==="buildings"?"مدیریت ساختمان‌های اسپاد":section==="builders"?"باشگاه سازندگان برتر اسپاد":"مرکز مدیریت مزایده ملک"}
   desc="تأیید محتوا، مدیریت فعالیت‌ها و کنترل تنظیمات از سامانه یکپارچه اسپاد"/>
  <div style={{display:"flex",gap:9,flexWrap:"wrap",marginBottom:18}}>
    {[["buildings","مدیریت ساختمان"],["builders","سازندگان برتر"],["auctions","مزایده ملک"],["settings","تنظیمات سرویس‌ها"]].map(([key,title])=>
      <Button key={key} color={section===key?"primary":"light"} outline={section!==key} onClick={()=>setSection(key)} style={{fontWeight:500,borderRadius:11}}>{title}</Button>)}
  </div>
  <Row><Kpi title="ساختمان‌های ثبت‌شده" value={buildings.length}/><Kpi title="رزومه‌های سازندگان" value={builders.length}/><Kpi title="مزایده‌های ثبت‌شده" value={auctions.length}/></Row>
  {section==="settings"&&<Card style={{borderRadius:16}}><CardBody>
   <h3 style={{fontSize:17}}>تنظیمات ماژول‌های اختصاصی</h3>
   {[
    ["buildings_enabled","فعال‌بودن سامانه مدیریت ساختمان"],
    ["builders_enabled","نمایش سازندگان برتر و پذیرش رزومه"],
    ["auctions_enabled","نمایش مزایده‌ها و پذیرش پیشنهاد"]
   ].map(([key,label])=><FormGroup check key={key} style={{marginBottom:14}}>
    <Label check><Input type="checkbox" checked={!!settings[key]} onChange={e=>setSettings(v=>({...v,[key]:e.target.checked}))}/>{label}</Label>
   </FormGroup>)}
   <FormGroup><Label>حداقل گام مجاز مزایده (تومان)</Label><Input type="number" min={1000000} value={settings.minimum_bid_increment} onChange={e=>setSettings(v=>({...v,minimum_bid_increment:e.target.value}))}/></FormGroup>
   <FormGroup><Label>اطلاعیه و شرایط شرکت در مزایده (نمایش عمومی)</Label><Input type="textarea" rows={3} maxLength={1500} value={settings.auction_notice||""} onChange={e=>setSettings(v=>({...v,auction_notice:e.target.value}))}/></FormGroup>
   <p style={{fontSize:12,color:"#7b8998"}}>ثبت پیشنهاد مزایده به معنی دریافت وجه، ضمانت معامله یا انتقال سند نیست. دریافت وجه تضمین تا زمان اتصال درگاه و تأیید مقررات فعال نشده است.</p>
   <Button color="primary" onClick={saveSettings} disabled={busy}>ذخیره تنظیمات</Button>
  </CardBody></Card>}
  {section!=="settings"&&<FormGroup><Input style={{maxWidth:440,borderRadius:11}} placeholder="جستجو بر اساس عنوان، شهر، شماره یا وضعیت" value={search} onChange={e=>setSearch(e.target.value)}/></FormGroup>}
  {section==="buildings"&&<Card><CardBody><h4>بررسی درخواست‌های ساختمان</h4><Table responsive hover>
   <thead><tr><th>شناسه</th><th>ساختمان و شهر</th><th>مدیر کاربر</th><th>تعداد واحد</th><th>وضعیت</th><th>تغییر وضعیت</th></tr></thead>
   <tbody>{filtered(buildings).map(x=><tr key={x.id}>
    <td>{x.id}</td><td><strong>{x.name}</strong><div style={{fontSize:11,color:"#779"}}>{x.city} - {x.address}</div></td>
    <td>{x.owner_user_id}</td><td>{fmt(x.unit_count)}</td><td>{statuses[x.status]||x.status}</td>
    <td><div style={{display:"flex",gap:6,flexWrap:"wrap"}}>
     {["approved","suspended","rejected"].map(st=><Button key={st} size="sm" color={st==="approved"?"primary":"secondary"} outline={x.status!==st} onClick={()=>updateBuilding(x,st)}>{statuses[st]}</Button>)}
    </div></td>
   </tr>)}</tbody></Table>
   {!buildings.length&&<p>هنوز ساختمانی ثبت نشده است.</p>}
  </CardBody></Card>}
  {section==="builders"&&<Card><CardBody><h4>رزومه‌ها، تأیید هویت و اولویت نمایش</h4>
   <p style={{fontSize:12,color:"#789"}}>رتبه‌بندی فعلاً اولویت سردبیری است؛ ادعای رتبه آماری یا امتیاز کاربران نمایش داده نمی‌شود.</p>
   <Table responsive hover><thead><tr><th>سازنده</th><th>سابقه</th><th>پروژه‌ها</th><th>تأیید مدیریت</th><th>انتشار عمومی</th><th>اولویت</th><th>ثبت</th></tr></thead><tbody>
   {filtered(builders).map(x=>{const d={...x,...builderDrafts[x.id]};return <tr key={x.id}>
    <td><strong>{x.name}</strong><div style={{fontSize:11}}>{x.city} | کاربر {x.user_id}</div><p style={{fontSize:11,maxWidth:260}}>{x.headline}</p></td>
    <td>{fmt(x.experience_years)} سال</td><td>{fmt(x.completed_projects)}</td>
    <td><Input type="select" bsSize="sm" value={d.verified?"1":"0"} onChange={e=>updBuilder(x,"verified",e.target.value==="1")}><option value="0">تأیید نشده</option><option value="1">تأیید شده</option></Input></td>
    <td><Input type="select" bsSize="sm" value={d.published?"1":"0"} onChange={e=>updBuilder(x,"published",e.target.value==="1")}><option value="0">مخفی / بررسی</option><option value="1">منتشر شود</option></Input></td>
    <td><Input bsSize="sm" style={{width:90}} type="number" min={1} max={10000} value={d.rank_priority} onChange={e=>updBuilder(x,"rank_priority",e.target.value)}/></td>
    <td><Button size="sm" color="primary" disabled={busy} onClick={()=>updateBuilder(x)}>ذخیره</Button></td>
   </tr>})}
   </tbody></Table>{!builders.length&&<p>هنوز رزومه‌ای ثبت نشده است.</p>}
  </CardBody></Card>}
  {section==="auctions"&&<Card><CardBody><h4>بازبینی و مدیریت مزایده‌ها</h4>
   <p style={{fontSize:12,color:"#778ca1"}}>پیشنهادها با قفل تراکنش ثبت می‌شوند. شروع و پایان پس از ثبت اولین پیشنهاد قابل تغییر نیست. برنده صرفاً موقت اعلام می‌شود.</p>
   <Table responsive hover><thead><tr><th>ملک</th><th>قیمت آغازین</th><th>زمان‌بندی</th><th>وضعیت و گام</th><th>عملیات</th></tr></thead><tbody>
   {filtered(auctions).map(x=>{const d={...x,...auctionDrafts[x.id]};return <tr key={x.id}>
    <td style={{minWidth:185}}><strong>{x.title}</strong><div style={{fontSize:11,color:"#789"}}>{x.city} / {x.district} | کاربر {x.owner_user_id}</div>
     <p style={{fontSize:10,maxWidth:260,whiteSpace:"normal"}}>{x.description}</p></td>
    <td style={{minWidth:100}}>{money(x.opening_bid_toman)}</td>
    <td style={{minWidth:190}}>
     <Label style={{fontSize:10}}>شروع (میلادی، ساعت سرور)</Label><Input bsSize="sm" type="text" placeholder="2026-11-01 12:00:00" value={timeInput(d.starts_at)} onChange={e=>updAuction(x,"starts_at",e.target.value)}/>
     <Label style={{fontSize:10,marginTop:6}}>پایان (میلادی، ساعت سرور)</Label><Input bsSize="sm" type="text" placeholder="2026-11-05 12:00:00" value={timeInput(d.ends_at)} onChange={e=>updAuction(x,"ends_at",e.target.value)}/>
    </td>
    <td style={{minWidth:170}}><Input type="select" bsSize="sm" value={d.status} onChange={e=>updAuction(x,"status",e.target.value)}>
      {["review","published","paused","closed","rejected"].map(k=><option value={k} key={k}>{statuses[k]}</option>)}
     </Input><Label style={{fontSize:10,marginTop:7}}>گام پیشنهاد (تومان)</Label>
     <Input bsSize="sm" type="number" min={1000000} value={d.minimum_increment_toman} onChange={e=>updAuction(x,"minimum_increment_toman",e.target.value)}/>
    </td>
    <td><Button color="primary" size="sm" disabled={busy} onClick={()=>updateAuction(x)}>ثبت</Button>
      <Button size="sm" color="info" outline className="mt-2" onClick={()=>fetchBids(x.id)}>پیشنهادها</Button>
      {bids[x.id]&&<div style={{marginTop:8,minWidth:180,maxHeight:190,overflowY:"auto",fontSize:11}}>{bids[x.id].length?bids[x.id].map(b=><div key={b.id}>{money(b.amount_toman)} | کاربر {b.user_id} | {dtime(b.created_at)}</div>):"پیشنهادی ثبت نشده"}</div>}
    </td>
   </tr>})}
   </tbody></Table>{!auctions.length&&<p>هنوز مزایده‌ای ثبت نشده است.</p>}
  </CardBody></Card>}
 </div>;
}

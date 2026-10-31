import React,{useEffect,useState} from "react";
import axios from "axios";
import {Card,CardBody,Button,Input,Label,FormGroup,Row,Col,Table,Badge,Modal,ModalHeader,ModalBody,ModalFooter} from "reactstrap";
import AuctionDateTimeFields,{normalizeAuctionTime,persianAuctionTime,validAuctionTimeRange} from "./AuctionDateTimeFields";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";

const api=adminPathApi+"/community";
const fmt=(v)=>Number(v||0).toLocaleString("fa-IR");
const dtime=(v)=>v?String(v).replace("T"," ").slice(0,19):"—";
const statuses={pending:"در انتظار بررسی",approved:"فعال",rejected:"رد شده",suspended:"تعلیق",review:"نیازمند بررسی",published:"منتشر شده",paused:"متوقف",closed:"پایان یافته"};
const money=(v)=>fmt(v)+" تومان";
const unitPaymentFields=[
 ["water_bill_id","شناسه قبض آب"],["water_subscription_no","شماره اشتراک آب"],
 ["electricity_bill_id","شناسه قبض برق"],["electricity_subscription_no","شماره اشتراک برق"],
 ["gas_bill_id","شناسه قبض گاز"],["gas_subscription_no","شماره اشتراک گاز"],
 ["landline_number","شماره تلفن ثابت با کد شهر"],["landline_bill_id","شناسه قبض تلفن"],
 ["municipal_renovation_code","کد نوسازی شهرداری"],["municipal_property_code","شناسه ملک در شهرداری"]
];
const unitDeedFields=[
 ["deed_main_no","پلاک ثبتی اصلی"],["deed_sub_no","پلاک ثبتی فرعی"],
 ["deed_registration_section","بخش ثبتی"],["deed_area_sqm","مساحت مندرج در سند (متر مربع)"],
 ["deed_unique_id","شناسه یکتای سند (در صورت نیاز)"],["tax_file_reference","شماره پرونده / ارجاع مالیاتی"]
];

const initial={buildings_enabled:true,builders_enabled:true,auctions_enabled:true,minimum_bid_increment:10000000,auction_deposit_enabled:false,auction_deposit_toman:0,auction_deposit_terms:"ودیعه مشارکت از کیف پول کسر می‌شود و بازپرداخت آن با ثبت سند مالی و تأیید مدیریت انجام می‌شود.",auction_notice:"ثبت پیشنهاد صرفاً اعلام علاقه‌مندی است و انتقال مالکیت نیازمند قرارداد قانونی جداگانه است."};
const error=(e)=>{const details=e.response?.data?.errors;const first=details&&Object.values(details).flat()[0];NotificationManager.error(first||e.response?.data?.message||"خطا در ارتباط با سرور");};
const ok=(msg)=>NotificationManager.success(msg);
const timeInput=normalizeAuctionTime;
const CommonHeading=({title,desc})=><div style={{background:"linear-gradient(110deg,#075995,#0d9bcf)",borderRadius:18,padding:"20px 24px",color:"#fff",marginBottom:20}}>
 <small style={{opacity:.8}}>SPAD OPERATIONS COMMAND CENTER</small>
 <h2 style={{color:"#fff",fontSize:22,margin:"8px 0"}}>{title}</h2>
 <p style={{margin:0,fontSize:12,opacity:.87}}>{desc}</p></div>;
const Kpi=({title,value})=><Col sm="4"><Card style={{marginBottom:12,border:"1px solid #dbebf5",borderRadius:13}}><CardBody><small style={{color:"#7292aa"}}>{title}</small><h3 style={{color:"#087bbc",fontWeight:500}}>{fmt(value)}</h3></CardBody></Card></Col>;
export default function CommunityAdmin({mode="buildings"}){
 const section=mode;
 const [settings,setSettings]=useState(initial);
 const [buildings,setBuildings]=useState([]);
 const [buildingManagers,setBuildingManagers]=useState([]);
 const [buildingEditing,setBuildingEditing]=useState(null);
 const [buildingFormOpen,setBuildingFormOpen]=useState(false);
 const emptyBuilding={name:"",city:"شیراز",address:"",unit_count:1,default_charge_toman:0,status:"pending",partner_panel_id:""};
 const [buildingForm,setBuildingForm]=useState(emptyBuilding);
 const [unitsBuilding,setUnitsBuilding]=useState(null);
 const [buildingUnits,setBuildingUnits]=useState([]);
 const [unitEditing,setUnitEditing]=useState(null);
 const emptyUnit={floor_number:0,unit_number:"",postal_code:"",owner_name:"",owner_phone:"",is_rented:false,tenant_first_name:"",tenant_last_name:"",tenant_phone:"",tenant_move_out_date:"",...Object.fromEntries([...unitPaymentFields,...unitDeedFields].map(([key])=>[key,""]))};
 const [unitForm,setUnitForm]=useState(emptyUnit);
 const [builders,setBuilders]=useState([]);
 const [auctions,setAuctions]=useState([]);
 const [bids,setBids]=useState({});
 const [auctionParticipants,setAuctionParticipants]=useState({});
 const [auctionHeldTotals,setAuctionHeldTotals]=useState({});
 const [auctionDrafts,setAuctionDrafts]=useState({});
 const [builderDrafts,setBuilderDrafts]=useState({});
 const [busy,setBusy]=useState(false);
 const [uploading,setUploading]=useState(false);
 const [editBuilder,setEditBuilder]=useState(null);
 const [newBuilder,setNewBuilder]=useState(false);
 const [projectFor,setProjectFor]=useState(null);
 const [projects,setProjects]=useState([]);
 const [projectDraft,setProjectDraft]=useState({title:"",city:"",description:"",image_url:"",completed_year:""});
 const [newAuction,setNewAuction]=useState(false);
 const blankBuilder={name:"",city:"شیراز",headline:"",biography:"",activity_area:"",building_names:"",experience_years:0,completed_projects:0,logo_url:"",published:false,verified:false,rank_priority:1000};
 const blankAuction={title:"",city:"",district:"",description:"",image_url:"",opening_bid_toman:"",minimum_increment_toman:10000000,advertisement_id:"",starts_at:"",ends_at:""};
 const [auctionForm,setAuctionForm]=useState(blankAuction);
 const [scheduleFor,setScheduleFor]=useState(null);
 const [scheduleValues,setScheduleValues]=useState({starts_at:"",ends_at:""});
 const [auctionAdSearch,setAuctionAdSearch]=useState("");
 const [eligibleAuctionAds,setEligibleAuctionAds]=useState([]);
 const [auctionLinkedAd,setAuctionLinkedAd]=useState(null);
 const [auctionAdLookupError,setAuctionAdLookupError]=useState("");
 const [auctionAdLoading,setAuctionAdLoading]=useState(false);
 const [builderForm,setBuilderForm]=useState(blankBuilder);
 const [search,setSearch]=useState("");
 const reload=async()=>{
  const sections=await Promise.allSettled([
   axios.get(api+"/settings"),
   section==="buildings"?axios.get(api+"/buildings"):Promise.resolve(null),
   section==="builders"?axios.get(api+"/builders"):Promise.resolve(null),
   section==="auctions"?axios.get(api+"/auctions"):Promise.resolve(null)
  ]);
  if(sections[0].status==="fulfilled")setSettings({...initial,...(sections[0].value.data.data||{})});
  if(section==="buildings"&&sections[1].status==="fulfilled")setBuildings(sections[1].value.data.data||[]);
  if(section==="builders"&&sections[2].status==="fulfilled")setBuilders(sections[2].value.data.data||[]);
  if(section==="auctions"&&sections[3].status==="fulfilled")setAuctions(sections[3].value.data.data||[]);
  if(sections.some(x=>x.status==="rejected"))NotificationManager.error("بخشی از اطلاعات دریافت نشد؛ برای تلاش مجدد دکمه به‌روزرسانی را انتخاب کنید.");
 };
 useEffect(()=>{reload();if(section==="buildings")axios.get(adminPathApi+"/partner-panels",{params:{type:"building_manager",per_page:100}}).then(r=>setBuildingManagers(r.data.data?.data||[])).catch(()=>{});},[]);
 useEffect(()=>{
  if(section!=="auctions"||!newAuction)return;
  let cancelled=false;
  const timer=setTimeout(async()=>{
   setAuctionAdLoading(true);setAuctionAdLookupError("");
   try{
    const response=await axios.get(api+"/auctions/eligible-advertisements",{params:{q:auctionAdSearch.trim()}});
    if(!cancelled)setEligibleAuctionAds(response.data.data||[]);
   }catch(e){
    if(!cancelled){setEligibleAuctionAds([]);setAuctionAdLookupError("دریافت آگهی‌های تأییدشده انجام نشد. بدون اتصال به آگهی هم می‌توان پیش‌نویس ثبت کرد.");}
   }finally{if(!cancelled)setAuctionAdLoading(false)}
  },300);
  return()=>{cancelled=true;clearTimeout(timer);};
 },[section,newAuction,auctionAdSearch]);
 const pickAuctionAd=(ad)=>{
  setAuctionLinkedAd(ad);
  setAuctionForm(old=>({...old,advertisement_id:String(ad.id),
   title:old.title||ad.title||"",
   city:old.city||ad.city||"شیراز"}));
 };
 const clearAuctionAd=()=>{setAuctionLinkedAd(null);setAuctionForm(old=>({...old,advertisement_id:""}));};


 const saveSettings=async()=>{
  setBusy(true);
  try{
   const payload={...settings,minimum_bid_increment:Number(settings.minimum_bid_increment),
     auction_deposit_enabled:!!settings.auction_deposit_enabled,
     auction_deposit_toman:Number(settings.auction_deposit_toman||0)};
   await axios.post(api+"/settings",payload);ok("تنظیمات خدمات اسپاد ذخیره شد");await reload();
  }catch(e){error(e)}finally{setBusy(false)}
 };
 const updateBuilding=async(item,status)=>{
  if(!window.confirm("وضعیت این ساختمان تغییر کند؟"))return;
  try{await axios.patch(api+"/buildings/"+item.id,{status});ok("وضعیت ساختمان ثبت شد");await reload()}catch(e){error(e)}
 };
 const openBuilding=(b)=>{setBuildingEditing(b?b.id:null);setBuildingForm(b?{...emptyBuilding,...b,partner_panel_id:b.partner_panel_id||""}:{...emptyBuilding});setBuildingFormOpen(true);};
 const saveBuilding=async()=>{setBusy(true);try{
  const payload={...buildingForm,unit_count:Number(buildingForm.unit_count),default_charge_toman:Number(buildingForm.default_charge_toman||0),
   partner_panel_id:buildingForm.partner_panel_id?Number(buildingForm.partner_panel_id):null};
  if(buildingEditing)await axios.put(api+"/buildings/"+buildingEditing,payload);
  else await axios.post(api+"/buildings",payload);
  ok(buildingEditing?"ساختمان ویرایش شد":"ساختمان ثبت شد");setBuildingFormOpen(false);await reload();
 }catch(e){error(e)}finally{setBusy(false)}};
 const removeBuilding=async(b)=>{if(!window.confirm("ساختمان «"+b.name+"» حذف شود؟ ساختمان دارای واحد یا سابقه مالی قابل حذف نیست."))return;
  setBusy(true);try{await axios.delete(api+"/buildings/"+b.id);if(unitsBuilding?.id===b.id)setUnitsBuilding(null);ok("ساختمان حذف شد");await reload()}catch(e){error(e)}finally{setBusy(false)}};
 const openUnits=async(b)=>{setUnitsBuilding(b);setUnitEditing(null);setUnitForm({...emptyUnit});
  try{const res=await axios.get(api+"/buildings/"+b.id+"/units");setBuildingUnits(res.data.data||[])}catch(e){error(e)}};
 const saveUnit=async()=>{if(!unitsBuilding)return;setBusy(true);try{
  const payload={...unitForm,floor_number:Number(unitForm.floor_number),is_rented:!!unitForm.is_rented,
   tenant_move_out_date:unitForm.is_rented?(unitForm.tenant_move_out_date||null):null};
  if(unitEditing)await axios.put(api+"/buildings/"+unitsBuilding.id+"/units/"+unitEditing,payload);
  else await axios.post(api+"/buildings/"+unitsBuilding.id+"/units",payload);
  ok("مشخصات واحد ذخیره شد");setUnitEditing(null);setUnitForm({...emptyUnit});await openUnits(unitsBuilding);
 }catch(e){error(e)}finally{setBusy(false)}};
 const removeUnit=async(u)=>{if(!window.confirm("واحد «"+u.label+"» حذف شود؟"))return;setBusy(true);
  try{await axios.delete(api+"/buildings/"+unitsBuilding.id+"/units/"+u.id);ok("واحد حذف شد");await openUnits(unitsBuilding)}
  catch(e){error(e)}finally{setBusy(false)}};
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
 const updateAuction=async(item,datesOverride=null)=>{
  const draft={...item,...auctionDrafts[item.id],...(datesOverride||{})};
  const start=timeInput(draft.starts_at),end=timeInput(draft.ends_at);
  if((start||end)&&!validAuctionTimeRange(start,end)){
   NotificationManager.error("ابتدا تاریخ و ساعت شروع و پایان را انتخاب کنید؛ پایان باید بعد از شروع باشد.");return;
  }
  if(draft.status==="published"&&!validAuctionTimeRange(start,end)){
   NotificationManager.error("برای انتشار مزایده انتخاب تاریخ و ساعت شروع و پایان الزامی است.");return;
  }
  setBusy(true);
  try{
   await axios.patch(api+"/auctions/"+item.id,{
    status:draft.status,starts_at:start||null,
    ends_at:end||null,
    minimum_increment_toman:Number(draft.minimum_increment_toman)
   });
   ok("وضعیت و زمان‌بندی مزایده ذخیره شد");
   setScheduleFor(null);
   setAuctionDrafts(previous=>{const updated={...previous};delete updated[item.id];return updated;});
   await reload();
  }catch(e){error(e)}finally{setBusy(false)}
 };
 const openAuctionSchedule=(auction)=>{
  const draft={...auction,...auctionDrafts[auction.id]};
  setScheduleValues({starts_at:timeInput(draft.starts_at),ends_at:timeInput(draft.ends_at)});
  setScheduleFor(auction);
 };
 const changeSchedule=(key,value)=>setScheduleValues(previous=>({...previous,[key]:value}));
 const openBuilder=(b)=>{setEditBuilder(b?b.id:null);setBuilderForm(b?{...blankBuilder,...b}:{...blankBuilder});setNewBuilder(true);window.requestAnimationFrame(()=>document.getElementById("builder-editor")?.scrollIntoView({behavior:"smooth",block:"start"}));};
 const saveBuilder=async()=>{if(!builderForm.name.trim()){NotificationManager.error("نام سازنده را وارد کنید.");return;}setBusy(true);try {
  const p={...builderForm,experience_years:Number(builderForm.experience_years),completed_projects:Number(builderForm.completed_projects),
   rank_priority:Number(builderForm.rank_priority),published:!!builderForm.published,verified:!!builderForm.verified};
  if(editBuilder)await axios.put(api+"/builders/"+editBuilder,p);
  else await axios.post(api+"/builders",p);
  ok(editBuilder?"اطلاعات سازنده ویرایش شد":"سازنده جدید با موفقیت اضافه شد");setNewBuilder(false);setEditBuilder(null);await reload();
 }catch(e){error(e)}finally{setBusy(false)}};
 const uploadBuilderPhoto=async(file)=>{
  if(!file)return;
  if(!["image/jpeg","image/png","image/webp"].includes(file.type)||file.size>5*1024*1024){
   NotificationManager.error("فقط تصویر JPG، PNG یا WebP تا ۵ مگابایت مجاز است.");return;
  }
  setUploading(true);
  try{
   const body=new FormData();body.append("image",file);
   const result=await axios.post(api+"/builders/upload-image",body,{headers:{"Content-Type":"multipart/form-data"}});
   setBuilderForm(v=>({...v,logo_url:result.data.url}));
   ok("تصویر بارگذاری شد");
  }catch(e){error(e)}finally{setUploading(false)}
 };
 const deleteBuilder=async(b)=>{if(!window.confirm(`آیا از حذف «${b.name}» و تمام نمونه‌کارهای او مطمئن هستید؟ این عملیات قابل بازگشت نیست.`))return;
  setBusy(true);try{await axios.delete(api+"/builders/"+b.id);if(editBuilder===b.id){setNewBuilder(false);setEditBuilder(null);}if(projectFor?.id===b.id)setProjectFor(null);ok("سازنده و نمونه‌کارهای او حذف شدند");await reload()}catch(e){error(e)}finally{setBusy(false)}
 };
 const openProjects=async(b)=>{setProjectFor(b);setProjectDraft({title:"",city:b.city||"",description:"",image_url:"",completed_year:""});
  try{const r=await axios.get(api+"/builders/"+b.id+"/projects");setProjects(r.data.data||[])}catch(e){error(e)}
 };
 const addProject=async()=>{setBusy(true);try{await axios.post(api+"/builders/"+projectFor.id+"/projects",{...projectDraft,completed_year:projectDraft.completed_year?Number(projectDraft.completed_year):null});
  ok("نمونه‌کار ثبت شد");await openProjects(projectFor);
 }catch(e){error(e)}finally{setBusy(false)}};
 const removeProject=async(p)=>{if(!window.confirm("نمونه‌کار حذف شود؟"))return;
  try{await axios.delete(api+"/builders/"+projectFor.id+"/projects/"+p.id);await openProjects(projectFor)}catch(e){error(e)}};
 const createAuction=async()=>{
  if((auctionForm.starts_at||auctionForm.ends_at)&&!validAuctionTimeRange(auctionForm.starts_at,auctionForm.ends_at)){
   NotificationManager.error("برای زمان‌بندی، شروع و پایان را انتخاب کنید؛ زمان پایان باید بعد از شروع باشد.");return;
  }
  setBusy(true);try{
  const p={...auctionForm,starts_at:timeInput(auctionForm.starts_at)||null,ends_at:timeInput(auctionForm.ends_at)||null,opening_bid_toman:Number(auctionForm.opening_bid_toman),minimum_increment_toman:Number(auctionForm.minimum_increment_toman),
   advertisement_id:auctionForm.advertisement_id?Number(auctionForm.advertisement_id):null};
  await axios.post(api+"/auctions",p);ok("پیش‌نویس مزایده ثبت شد؛ قبل از انتشار مجوز مالکیت بررسی شود.");
  setNewAuction(false);setAuctionForm({...blankAuction});setAuctionLinkedAd(null);setAuctionAdSearch("");await reload();
 }catch(e){error(e)}finally{setBusy(false)}};
 const fetchBids=async(id)=>{
  try{const r=await axios.get(api+"/auctions/"+id+"/bids");setBids(o=>({...o,[id]:r.data.data||[]}))}
  catch(e){error(e)}
 };
 const fetchAuctionParticipants=async(id)=>{
  try{const r=await axios.get(api+"/auctions/"+id+"/participations");
   setAuctionParticipants(old=>({...old,[id]:r.data.data||[]}));
   setAuctionHeldTotals(old=>({...old,[id]:r.data.total_held_toman||0}));
  }catch(e){error(e)}
 };
 const refundAuctionDeposit=async(auction,row)=>{
  const reason=window.prompt("علت بازگشت ودیعه به کیف پول کاربر را وارد کنید (حداقل ۸ حرف):","بازگشت ودیعه مشارکت در مزایده");
  if(!reason||reason.trim().length<8)return;
  if(!window.confirm("تأیید بازگشت "+money(row.amount_toman)+" به کیف پول کاربر شماره "+row.user_id+"؟ این عملیات مالی ثبت خواهد شد."))return;
  setBusy(true);
  try{
   await axios.post(api+"/auctions/"+auction.id+"/participations/"+row.id+"/refund",{reason:reason.trim()});
   ok("بازگشت ودیعه و سند مالی با موفقیت ثبت شد");
   await fetchAuctionParticipants(auction.id);
  }catch(e){error(e)}finally{setBusy(false)}
 };
 const filtered=(arr)=>arr.filter(x=>!search||[x.id,x.name,x.title,x.city,x.status,x.user_id].join(" ").includes(search));
 const updBuilder=(item,key,value)=>setBuilderDrafts(old=>({...old,[item.id]:{...item,...old[item.id],[key]:value}}));
 const updAuction=(item,key,value)=>setAuctionDrafts(old=>({...old,[item.id]:{...item,...old[item.id],[key]:value}}));
 return <div className="container-fluid" dir="rtl" style={{paddingBottom:60}}>
  <CommonHeading title={section==="buildings"?"مدیریت ساختمان‌های اسپاد":section==="builders"?"باشگاه سازندگان برتر اسپاد":"مرکز مدیریت مزایده ملک"}
   desc="تأیید محتوا، مدیریت فعالیت‌ها و کنترل تنظیمات از سامانه یکپارچه اسپاد"/>
  {section==="builders"&&<Card style={{borderRadius:16,border:"1px solid #cee8f8",marginBottom:18}}><CardBody style={{display:"flex",alignItems:"center",justifyContent:"space-between",gap:12,flexWrap:"wrap"}}><div><h3 style={{fontSize:18,color:"#075c98",marginBottom:5}}>افزودن و مدیریت سازندگان برتر</h3><p style={{margin:0,fontSize:12,color:"#5f7c94"}}>از این قسمت سازنده جدید ثبت کنید یا از فهرست پایین، سازندگان را ویرایش و حذف کنید. نمایش عمومی فقط پس از انتشار فعال می‌شود.</p></div><div style={{display:"flex",gap:8,flexWrap:"wrap"}}><Button color="primary" disabled={busy} onClick={()=>openBuilder(null)}>+ افزودن سازنده جدید</Button><Button color="light" disabled={busy} onClick={reload}>به‌روزرسانی فهرست</Button></div></CardBody></Card>}
  <Row><Kpi title="ساختمان‌های ثبت‌شده" value={buildings.length}/><Kpi title="رزومه‌های سازندگان" value={builders.length}/><Kpi title="مزایده‌های ثبت‌شده" value={auctions.length}/></Row>
  {<Card style={{borderRadius:16,marginBottom:18}}><CardBody>
   <h3 style={{fontSize:17}}>تنظیمات اختصاصی {section==="buildings"?"مدیریت ساختمان":section==="builders"?"سازندگان برتر":"مزایده ملک"}</h3>
   {[
    ["buildings_enabled","فعال‌بودن سامانه مدیریت ساختمان"],
    ["builders_enabled","نمایش سازندگان برتر و پذیرش رزومه"],
    ["auctions_enabled","نمایش مزایده‌ها و پذیرش پیشنهاد"]
   ].filter(([key])=>key===({buildings:"buildings_enabled",builders:"builders_enabled",auctions:"auctions_enabled"}[section])).map(([key,label])=><FormGroup check key={key} style={{marginBottom:14}}>
    <Label check><Input type="checkbox" checked={!!settings[key]} onChange={e=>setSettings(v=>({...v,[key]:e.target.checked}))}/>{label}</Label>
   </FormGroup>)}
   {section==="auctions"&&<><FormGroup><Label>حداقل گام مجاز مزایده (تومان)</Label><Input type="number" min={1000000} value={settings.minimum_bid_increment} onChange={e=>setSettings(v=>({...v,minimum_bid_increment:e.target.value}))}/></FormGroup>
   <FormGroup><Label>اطلاعیه و شرایط شرکت در مزایده (نمایش عمومی)</Label><Input type="textarea" rows={3} maxLength={1500} value={settings.auction_notice||""} onChange={e=>setSettings(v=>({...v,auction_notice:e.target.value}))}/></FormGroup>
   <div style={{padding:17,border:"1px solid #cfe5f4",borderRadius:15,background:"linear-gradient(120deg,#f2faff,#fff)",marginTop:12}}>
    <h4 style={{fontSize:16,color:"#136795"}}>تنظیمات ودیعه شرکت در مزایده و کیف پول</h4>
    <p style={{fontSize:12,color:"#6a889d",lineHeight:2}}>ودیعه از کیف پول واقعی SPAD کسر می‌شود. درگاه شارژ را در تنظیمات کیف پول تنظیم کنید. بازپرداخت با تأیید مدیر و ثبت تراکنش بازگشت وجه انجام می‌شود؛ این بخش پیش‌فرض غیرفعال است.</p>
    <FormGroup check style={{margin:"12px 0"}}><Label check>
     <Input type="checkbox" checked={!!settings.auction_deposit_enabled} onChange={e=>setSettings(v=>({...v,auction_deposit_enabled:e.target.checked}))}/>
     دریافت ودیعه پیش از اولین پیشنهاد کاربران جدید
    </Label></FormGroup>
    <FormGroup><Label>مبلغ ثابت ودیعه (تومان)</Label>
     <Input type="number" min={0} max={100000000000} value={settings.auction_deposit_toman||0} onChange={e=>setSettings(v=>({...v,auction_deposit_toman:e.target.value}))}/>
    </FormGroup>
    <FormGroup><Label>شرایط ودیعه و بازپرداخت، قابل نمایش برای کاربر</Label>
     <Input type="textarea" rows={3} maxLength={2000} value={settings.auction_deposit_terms||""} onChange={e=>setSettings(v=>({...v,auction_deposit_terms:e.target.value}))}/>
    </FormGroup>
    <a href="/spad-admin/wallets" style={{fontSize:12,color:"#0a80b7"}}>مدیریت درگاه‌های پرداخت و کیف پول اسپاد</a>
    <p style={{fontSize:11,color:"#7c92a5",margin:"10px 0 0"}}>بدون فعال‌سازی و تنظیم مبلغ، هیچ ودیعه‌ای کسر نمی‌شود.</p>
   </div></>}
   <Button color="primary" onClick={saveSettings} disabled={busy}>ذخیره تنظیمات</Button>
  </CardBody></Card>}
  {<FormGroup><Input style={{maxWidth:440,borderRadius:11}} placeholder="جستجو بر اساس عنوان، شهر، شماره یا وضعیت" value={search} onChange={e=>setSearch(e.target.value)}/></FormGroup>}
  {section==="buildings"&&<Card style={{borderRadius:16}}><CardBody>
   <div style={{display:"flex",justifyContent:"space-between",gap:12,alignItems:"center",flexWrap:"wrap",marginBottom:15}}><div><h3 style={{color:"#086baf",fontSize:20}}>مدیریت ساختمان‌ها و واحدهای مسکونی</h3><p style={{fontSize:12}}>ایجاد ساختمان، تعیین مدیر همکاری، ثبت اطلاعات کامل مالکان و مستأجران و مدیریت وضعیت</p></div><Button color="primary" onClick={()=>openBuilding(null)}>+ افزودن ساختمان</Button></div>
   {buildingFormOpen&&<Card className="mb-3" style={{background:"#f5faff",border:"1px solid #d0e9fa"}}><CardBody><h4>{buildingEditing?"ویرایش ساختمان":"ثبت ساختمان جدید"}</h4>
    <Row>{[["name","نام ساختمان"],["city","شهر"],["unit_count","تعداد کل واحدها"],["default_charge_toman","شارژ پیش‌فرض (تومان)"]].map(([k,title])=><Col md="6" key={k}><FormGroup><Label>{title}</Label><Input type={["unit_count","default_charge_toman"].includes(k)?"number":"text"} value={buildingForm[k]??""} onChange={e=>setBuildingForm(f=>({...f,[k]:e.target.value}))}/></FormGroup></Col>)}</Row>
    <FormGroup><Label>آدرس دقیق ساختمان (خیابان، کوچه، پلاک)</Label><Input type="textarea" rows={2} maxLength={600} value={buildingForm.address||""} onChange={e=>setBuildingForm(f=>({...f,address:e.target.value}))}/></FormGroup>
    <Row><Col md="6"><FormGroup><Label>وضعیت ساختمان</Label><Input type="select" value={buildingForm.status} onChange={e=>setBuildingForm(f=>({...f,status:e.target.value}))}>{["pending","approved","suspended","rejected"].map(k=><option value={k} key={k}>{statuses[k]}</option>)}</Input></FormGroup></Col>
    <Col md="6"><FormGroup><Label>پنل مدیر ساختمان (اختیاری)</Label><Input type="select" value={buildingForm.partner_panel_id||""} onChange={e=>setBuildingForm(f=>({...f,partner_panel_id:e.target.value}))}><option value="">بدون مدیر اختصاصی</option>{buildingManagers.map(p=><option key={p.id} value={p.id}>#{p.id} - {p.user?.first_name||""} {p.user?.last_name||""} - {p.user?.mobile||""}</option>)}</Input><small>ابتدا پنل «مدیر ساختمان» را از بخش پنل‌های همکاری برای شخص ایجاد کنید.</small></FormGroup></Col></Row>
    <Button color="primary" disabled={busy} onClick={saveBuilding}>{busy?"در حال ذخیره...":"ذخیره ساختمان"}</Button>{" "}<Button color="light" onClick={()=>setBuildingFormOpen(false)}>انصراف</Button>
   </CardBody></Card>}
   <Table responsive hover><thead><tr><th>ساختمان</th><th>آدرس دقیق</th><th>واحدها</th><th>مدیر</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>{filtered(buildings).map(b=><tr key={b.id}>
    <td><strong>{b.name}</strong><small className="d-block">#{b.id} · {b.city}</small></td><td style={{minWidth:160}}>{b.address}</td>
    <td>{fmt(b.unit_count)}</td><td>{b.partner_panel_id?"پنل #"+b.partner_panel_id:b.owner_user_id?"کاربر #"+b.owner_user_id:"تعیین نشده"}</td><td>{statuses[b.status]||b.status}</td>
    <td style={{minWidth:245}}><Button size="sm" color="primary" outline onClick={()=>openUnits(b)}>آپارتمان‌ها</Button>{" "}<Button size="sm" color="info" outline onClick={()=>openBuilding(b)}>ویرایش</Button>{" "}<Button size="sm" color="danger" outline disabled={busy} onClick={()=>removeBuilding(b)}>حذف</Button>{" "}
    <Button size="sm" color="secondary" outline onClick={()=>updateBuilding(b,b.status==="approved"?"suspended":"approved")}>{b.status==="approved"?"تعلیق":"تأیید"}</Button></td>
   </tr>)}</tbody></Table>{!buildings.length&&<p>ساختمانی ثبت نشده است.</p>}
   {unitsBuilding&&<Card style={{marginTop:20,border:"1px solid #bedff3",borderRadius:15}}><CardBody>
    <div style={{display:"flex",justifyContent:"space-between",flexWrap:"wrap"}}><h4>آپارتمان‌های {unitsBuilding.name}</h4><Button color="light" onClick={()=>setUnitsBuilding(null)}>بستن</Button></div>
    <Row>{[["floor_number","طبقه","number"],["unit_number","شماره واحد","text"],["postal_code","کد پستی ده‌رقمی","text"],["owner_name","نام و نام خانوادگی مالک","text"],["owner_phone","شماره تماس مالک","tel"]].map(([k,label,type])=><Col md="4" key={k}><FormGroup><Label>{label}</Label><Input type={type} value={unitForm[k]??""} onChange={e=>setUnitForm(v=>({...v,[k]:e.target.value}))}/></FormGroup></Col>)}</Row>
    <FormGroup check style={{marginBottom:15}}><Label check><Input type="checkbox" checked={!!unitForm.is_rented} onChange={e=>setUnitForm(v=>({...v,is_rented:e.target.checked}))}/> ملک در اختیار مستأجر است</Label></FormGroup>
    {unitForm.is_rented&&<Row>{[["tenant_first_name","نام مستأجر","text"],["tenant_last_name","نام خانوادگی مستأجر","text"],["tenant_phone","شماره تماس مستأجر","tel"],["tenant_move_out_date","تاریخ تخلیه (میلادی)","date"]].map(([k,label,type])=><Col md="3" key={k}><FormGroup><Label>{label}</Label><Input type={type} value={unitForm[k]||""} onChange={e=>setUnitForm(v=>({...v,[k]:e.target.value}))}/></FormGroup></Col>)}</Row>}
    <div style={{background:"#eff8ff",borderRadius:13,padding:"16px 18px",margin:"18px 0"}}>
     <h5 style={{color:"#0766a1"}}>اطلاعات قبوض آب، برق، گاز و تلفن</h5>
     <small>شناسه قبض و شماره اشتراک معمولاً ثابت‌اند؛ شناسه پرداخت و مبلغ هر دوره را جداگانه برای همان قبض ثبت کنید.</small>
     <Row style={{marginTop:12}}>{unitPaymentFields.map(([key,label])=><Col md="4" key={key}><FormGroup><Label>{label}</Label>
      <Input type="text" inputMode={key.includes("bill_id")||key==="landline_number"?"numeric":"text"} value={unitForm[key]||""} maxLength={50}
       onChange={e=>setUnitForm(v=>({...v,[key]:e.target.value}))}/></FormGroup></Col>)}</Row>
    </div>
    <div style={{background:"#f6f9fc",borderRadius:13,padding:"16px 18px",margin:"18px 0"}}>
     <h5 style={{color:"#0766a1"}}>عوارض شهرداری و اطلاعات شناسایی سند برای امور مالیاتی</h5>
     <p style={{fontSize:12}}>کد نوسازی برای عوارض شیراز مهم است؛ مشخصات ثبتی برای پیگیری پرونده مالیاتی قابل استفاده‌اند و به‌تنهایی قبض مالیاتی صادر نمی‌کنند. اطلاعات سند فقط برای مدیریت مجاز نگهداری می‌شود.</p>
     <Row>{unitDeedFields.map(([key,label])=><Col md="4" key={key}><FormGroup><Label>{label}</Label><Input type={key==="deed_area_sqm"?"number":"text"} min={key==="deed_area_sqm"?0:undefined} value={unitForm[key]??""} onChange={e=>setUnitForm(v=>({...v,[key]:e.target.value}))}/></FormGroup></Col>)}</Row>
    </div>
    <Button color="primary" disabled={busy} onClick={saveUnit}>{unitEditing?"ذخیره ویرایش واحد":"افزودن آپارتمان"}</Button>{" "}{unitEditing&&<Button color="light" onClick={()=>{setUnitEditing(null);setUnitForm({...emptyUnit})}}>انصراف از ویرایش</Button>}
    <Table responsive hover style={{marginTop:20}}><thead><tr><th>طبقه / واحد</th><th>کد پستی</th><th>مالک و تماس</th><th>مستأجر / تخلیه</th><th>عملیات</th></tr></thead><tbody>{buildingUnits.map(u=><tr key={u.id}>
     <td>{u.floor_number} / {u.unit_number}</td><td>{u.postal_code||"—"}</td><td>{u.owner_name}<small className="d-block">{u.owner_phone}</small></td>
     <td>{u.is_rented?((u.tenant_first_name||"")+" "+(u.tenant_last_name||"")+" | "+(u.tenant_phone||"")+" | "+(u.tenant_move_out_date||"")):"مالک‌نشین"}</td>
     <td><Button color="info" outline size="sm" onClick={()=>{setUnitEditing(u.id);setUnitForm({...emptyUnit,...u,is_rented:!!u.is_rented})}}>ویرایش</Button>{" "}<Button color="danger" outline size="sm" onClick={()=>removeUnit(u)}>حذف</Button></td>
    </tr>)}</tbody></Table>
   </CardBody></Card>}
  </CardBody></Card>}
  {section==="builders"&&<Card><CardBody>
   <div style={{display:"flex",alignItems:"center",justifyContent:"space-between",gap:12,flexWrap:"wrap",marginBottom:18}}><div><h3 style={{margin:0,fontSize:19,color:"#075c98"}}>مدیریت سازندگان برتر شیراز</h3><small>ثبت سازنده، رزومه، تصویر و نمونه‌کارها</small></div><div><Button color="primary" onClick={()=>openBuilder(null)}>+ افزودن سازنده جدید</Button>{" "}<Button color="light" onClick={reload}>به‌روزرسانی</Button></div></div>
   {newBuilder&&<Card id="builder-editor" className="mb-3" style={{border:"1px solid #b6ddf2",borderRadius:14}}><CardBody><h4>{editBuilder?"ویرایش رزومه سازنده":"ثبت سازنده جدید"}</h4>
    <Row>{[["name","نام و نام خانوادگی"],["city","شهر"],["activity_area","محدوده فعالیت"],["building_names","نام ساختمان‌ها و پروژه‌ها"],["experience_years","سال‌های فعالیت"],["completed_projects","تعداد پروژه"],["headline","عنوان حرفه‌ای"],["logo_url","آدرس HTTPS تصویر / لوگو"],["rank_priority","اولویت نمایش"]].map(([key,label])=><Col md="6" key={key}><FormGroup><Label>{label}</Label><Input type={["experience_years","completed_projects","rank_priority"].includes(key)?"number":"text"} value={builderForm[key]||""} onChange={e=>setBuilderForm(v=>({...v,[key]:e.target.value}))}/></FormGroup></Col>)}</Row>
    <FormGroup><Label>عکس یا لوگوی سازنده</Label><Input type="file" accept="image/png,image/jpeg,image/webp" disabled={uploading} onChange={e=>uploadBuilderPhoto(e.target.files&&e.target.files[0])}/><small>{uploading?"در حال آپلود تصویر...":"عکس را از دستگاه انتخاب کنید (حداکثر ۵ مگابایت) یا آدرس HTTPS تصویر را در بالا وارد کنید."}</small>{builderForm.logo_url&&<div style={{marginTop:10}}><img src={builderForm.logo_url} alt="پیش‌نمایش عکس سازنده" style={{width:110,height:110,objectFit:"cover",borderRadius:15}}/></div>}</FormGroup>
    <FormGroup><Label>رزومه و سوابق تخصصی</Label><Input type="textarea" rows={5} value={builderForm.biography||""} onChange={e=>setBuilderForm(v=>({...v,biography:e.target.value}))}/></FormGroup>
    <Label check className="mr-3"><Input type="checkbox" checked={!!builderForm.published} onChange={e=>setBuilderForm(v=>({...v,published:e.target.checked}))}/> انتشار عمومی</Label>
    {editBuilder&&<Label check className="mr-3"><Input type="checkbox" checked={!!builderForm.verified} onChange={e=>setBuilderForm(v=>({...v,verified:e.target.checked}))}/> تأیید مدیریت</Label>}
    <div className="mt-3"><Button color="primary" disabled={busy||uploading} onClick={saveBuilder}>{busy?"در حال ذخیره...":editBuilder?"ذخیره تغییرات سازنده":"ثبت سازنده جدید"}</Button>{" "}<Button color="light" onClick={()=>setNewBuilder(false)}>انصراف</Button></div>
   </CardBody></Card>}
   {projectFor&&<Card className="mb-3"><CardBody><h4>نمونه‌کارهای {projectFor.name}</h4>
     {projects.map(p=><p key={p.id}><strong>{p.title}</strong> — {p.city} <Button size="sm" color="danger" outline onClick={()=>removeProject(p)}>حذف</Button></p>)}
     <Row>{[["title","نام پروژه"],["city","شهر"],["image_url","لینک تصویر HTTPS"],["completed_year","سال تکمیل"],["description","توضیحات پروژه"]].map(([key,label])=><Col md="6" key={key}><FormGroup><Label>{label}</Label><Input value={projectDraft[key]||""} onChange={e=>setProjectDraft(v=>({...v,[key]:e.target.value}))}/></FormGroup></Col>)}</Row>
     <Button color="primary" onClick={addProject} disabled={busy}>افزودن نمونه‌کار</Button>{" "}<Button color="light" onClick={()=>setProjectFor(null)}>بستن</Button>
   </CardBody></Card>}
   <h4>رزومه‌ها، تأیید هویت و اولویت نمایش</h4>
   <p style={{fontSize:12,color:"#789"}}>رتبه‌بندی فعلاً اولویت سردبیری است؛ ادعای رتبه آماری یا امتیاز کاربران نمایش داده نمی‌شود.</p>
   <Table responsive hover><thead><tr><th>سازنده</th><th>سابقه</th><th>پروژه‌ها</th><th>تأیید مدیریت</th><th>انتشار عمومی</th><th>اولویت</th><th>ثبت</th></tr></thead><tbody>
   {filtered(builders).map(x=>{const d={...x,...builderDrafts[x.id]};return <tr key={x.id}>
    <td><strong>{x.name}</strong><div style={{fontSize:11}}>{x.city} | کاربر {x.user_id}</div><p style={{fontSize:11,maxWidth:260}}>{x.headline}</p></td>
    <td>{fmt(x.experience_years)} سال</td><td>{fmt(x.completed_projects)}</td>
    <td><Input type="select" bsSize="sm" value={d.verified?"1":"0"} onChange={e=>updBuilder(x,"verified",e.target.value==="1")}><option value="0">تأیید نشده</option><option value="1">تأیید شده</option></Input></td>
    <td><Input type="select" bsSize="sm" value={d.published?"1":"0"} onChange={e=>updBuilder(x,"published",e.target.value==="1")}><option value="0">مخفی / بررسی</option><option value="1">منتشر شود</option></Input></td>
    <td><Input bsSize="sm" style={{width:90}} type="number" min={1} max={10000} value={d.rank_priority} onChange={e=>updBuilder(x,"rank_priority",e.target.value)}/></td>
    <td><Button size="sm" color="primary" disabled={busy} onClick={()=>updateBuilder(x)}>ذخیره وضعیت</Button>{" "}
     <Button size="sm" color="info" outline onClick={()=>openBuilder(x)}>ویرایش</Button>{" "}
     <Button size="sm" color="secondary" outline onClick={()=>openProjects(x)}>نمونه‌کارها</Button>{" "}
     <Button size="sm" color="danger" outline disabled={busy} onClick={()=>deleteBuilder(x)}>حذف</Button>
     <a className="d-block mt-1" href={"https://spad.estate/top-builders/"+x.id} target="_blank" rel="noopener noreferrer">صفحه اختصاصی</a></td>
   </tr>})}
   </tbody></Table>{!builders.length&&<p>هنوز رزومه‌ای ثبت نشده است.</p>}
  </CardBody></Card>}
  {section==="auctions"&&<Card><CardBody>
   <Button color="primary" className="mb-3" onClick={()=>setNewAuction(v=>!v)}>+ ایجاد پیش‌نویس مزایده</Button>
   {newAuction&&<Card className="mb-3"><CardBody><h4>ثبت پیش‌نویس مزایده ملک</h4><p>فقط پس از تأیید مالکیت و اجازه کتبی مالک منتشر کنید.</p>
    <div style={{background:"#f1f9ff",padding:"15px 17px",border:"1px solid #d4e9f8",borderRadius:13,marginBottom:18}}>
     <Label style={{fontWeight:600,color:"#11628e"}}>ارتباط با آگهی موجود اسپاد (اختیاری)</Label>
     <p style={{fontSize:12,color:"#507b98",marginBottom:9}}>به‌جای واردکردن شناسه دستی، آگهی تأییدشده را انتخاب کنید؛ ثبت پیش‌نویس بدون ارتباط با آگهی نیز امکان‌پذیر است.</p>
     {auctionLinkedAd?<div style={{display:"flex",gap:12,alignItems:"center",justifyContent:"space-between",flexWrap:"wrap"}}>
      <strong style={{fontSize:13}}>آگهی انتخاب‌شده: #{auctionLinkedAd.id} · {auctionLinkedAd.title||"ملک اسپاد"} {auctionLinkedAd.city?" · "+auctionLinkedAd.city:""}</strong>
      <Button outline color="secondary" size="sm" type="button" onClick={clearAuctionAd}>حذف ارتباط با آگهی</Button>
     </div>:<>
      <Input type="text" placeholder="جستجوی آگهی تأییدشده با عنوان یا شناسه داخلی" value={auctionAdSearch} onChange={e=>setAuctionAdSearch(e.target.value)}/>
      {auctionAdLoading&&<small style={{display:"block",marginTop:7}}>در حال دریافت آگهی‌ها...</small>}
      {auctionAdLookupError&&<small style={{display:"block",marginTop:7,color:"#bd673a"}}>{auctionAdLookupError}</small>}
      <div style={{maxHeight:190,overflowY:"auto",marginTop:8,display:"grid",gap:5}}>
       {eligibleAuctionAds.map(ad=><button key={ad.id} type="button" onClick={()=>pickAuctionAd(ad)}
        style={{width:"100%",border:"1px solid #d7e8f4",background:"#fff",borderRadius:8,padding:"9px 12px",fontSize:12,textAlign:"right",cursor:"pointer",color:"#145d86"}}>
        کد {ad.id} · {ad.title||"ملک بدون عنوان"} {ad.city?" · "+ad.city:""}
       </button>)}
      </div>
      {!auctionAdLoading&&!eligibleAuctionAds.length&&!auctionAdLookupError&&<small style={{display:"block",marginTop:8}}>آگهی تأییدشده‌ای پیدا نشد؛ می‌توانید بدون انتخاب آگهی ثبت کنید.</small>}
     </>}
    </div>
    <Row>{[["title","عنوان مزایده"],["city","شهر"],["district","محله"],["image_url","تصویر HTTPS"],["opening_bid_toman","قیمت شروع (تومان)"],["minimum_increment_toman","حداقل گام پیشنهاد (تومان)"]].map(([key,label])=><Col md="6" key={key}><FormGroup><Label>{label}</Label><Input value={auctionForm[key]||""} type={["opening_bid_toman","minimum_increment_toman"].includes(key)?"number":"text"} onChange={e=>setAuctionForm(v=>({...v,[key]:e.target.value}))}/></FormGroup></Col>)}</Row>
    <FormGroup><Label>شرح کامل ملک، شرایط بازدید و مقررات مزایده</Label><Input type="textarea" rows={5} value={auctionForm.description} onChange={e=>setAuctionForm(v=>({...v,description:e.target.value}))}/></FormGroup>
    <div style={{border:"1px solid #d5e8f5",borderRadius:14,padding:15,margin:"16px 0",background:"#fafdff"}}>
     <h5 style={{fontSize:15,color:"#12648e",marginBottom:6}}>زمان‌بندی اختیاری مزایده</h5>
     <p style={{fontSize:11,color:"#6c8ca4",lineHeight:2}}>تاریخ‌ها شمسی هستند و ساعت به وقت تهران انتخاب می‌شود. اگر هنوز زمان مزایده قطعی نیست، این بخش را خالی بگذارید و پس از تأیید ملک تنظیم کنید.</p>
     <AuctionDateTimeFields values={auctionForm} onChange={(key,value)=>setAuctionForm(old=>({...old,[key]:value}))} disabled={busy}/>
     {(auctionForm.starts_at||auctionForm.ends_at)&&!validAuctionTimeRange(auctionForm.starts_at,auctionForm.ends_at)&&<small style={{color:"#c55744"}}>زمان پایان باید بعد از شروع باشد و هر دو زمان انتخاب شوند.</small>}
    </div>
    <Button color="primary" disabled={busy} onClick={createAuction}>ثبت در وضعیت بررسی</Button>
   </CardBody></Card>}
   <h4>بازبینی و مدیریت مزایده‌ها</h4>
   <p style={{fontSize:12,color:"#778ca1"}}>پیشنهادها با قفل تراکنش ثبت می‌شوند. شروع و پایان پس از ثبت اولین پیشنهاد قابل تغییر نیست. برنده صرفاً موقت اعلام می‌شود.</p>
   <Table responsive hover><thead><tr><th>ملک</th><th>قیمت آغازین</th><th>زمان‌بندی</th><th>وضعیت و گام</th><th>عملیات</th></tr></thead><tbody>
   {filtered(auctions).map(x=>{const d={...x,...auctionDrafts[x.id]};return <tr key={x.id}>
    <td style={{minWidth:185}}><strong>{x.title}</strong><div style={{fontSize:11,color:"#789"}}>{x.city} / {x.district} | کاربر {x.owner_user_id}</div>
     <p style={{fontSize:10,maxWidth:260,whiteSpace:"normal"}}>{x.description}</p></td>
    <td style={{minWidth:100}}>{money(x.opening_bid_toman)}</td>
    <td style={{minWidth:210}}>
     <div style={{display:"grid",gap:6,fontSize:11,color:"#597e99"}}>
      <div><span style={{color:"#0b87c1"}}>شروع: </span>{persianAuctionTime(d.starts_at)}</div>
      <div><span style={{color:"#0b87c1"}}>پایان: </span>{persianAuctionTime(d.ends_at)}</div>
     </div>
     <Button size="sm" color="info" outline style={{marginTop:8,fontSize:11,borderRadius:9}} disabled={busy} onClick={()=>openAuctionSchedule(x)}>انتخاب تاریخ و ساعت</Button>
     <div style={{fontSize:9,color:"#93a7b6",marginTop:6}}>تقویم شمسی · ساعت تهران</div>
    </td>
    <td style={{minWidth:170}}><Input type="select" bsSize="sm" value={d.status} onChange={e=>updAuction(x,"status",e.target.value)}>
      {["review","published","paused","closed","rejected"].map(k=><option value={k} key={k}>{statuses[k]}</option>)}
     </Input><Label style={{fontSize:10,marginTop:7}}>گام پیشنهاد (تومان)</Label>
     <Input bsSize="sm" type="number" min={1000000} value={d.minimum_increment_toman} onChange={e=>updAuction(x,"minimum_increment_toman",e.target.value)}/>
    </td>
    <td><Button color="primary" size="sm" disabled={busy} onClick={()=>updateAuction(x)}>ثبت</Button>
      <Button size="sm" color="info" outline className="mt-2" onClick={()=>fetchBids(x.id)}>پیشنهادها</Button>
      <Button size="sm" color="secondary" outline className="mt-2" onClick={()=>fetchAuctionParticipants(x.id)}>ودیعه کاربران</Button>
      {auctionParticipants[x.id]&&<div style={{marginTop:10,padding:9,minWidth:260,maxHeight:300,overflowY:"auto",border:"1px solid #d7e9f3",borderRadius:10,background:"#f8fcff"}}>
       <strong style={{color:"#136c96",fontSize:12}}>ودیعه‌های نگهداری‌شده: {money(auctionHeldTotals[x.id]||0)}</strong>
       {auctionParticipants[x.id].length?auctionParticipants[x.id].map(p=><div key={p.id} style={{borderBottom:"1px solid #e1edf5",padding:"9px 0",fontSize:11}}>
        <div>{[p.first_name,p.last_name].filter(Boolean).join(" ")||"کاربر"} · #{p.user_id}</div>
        <small>{p.mobile||"—"} · {p.status==="held"?"ودیعه نگهداری‌شده":"بازگشت‌داده‌شده"}</small>
        <div style={{color:"#0b77a7"}}>{money(p.amount_toman)}</div>
        {p.status==="held"&&<Button size="sm" outline color="success" disabled={busy} onClick={()=>refundAuctionDeposit(x,p)}>بازگشت به کیف پول</Button>}
        {p.status==="refunded"&&<small>{p.refund_reason||"بازپرداخت ثبت‌شده"}</small>}
       </div>):<p style={{fontSize:11}}>ودیعه‌ای برای این مزایده ثبت نشده است.</p>}
      </div>
      {bids[x.id]&&<div style={{marginTop:8,minWidth:180,maxHeight:190,overflowY:"auto",fontSize:11}}>{bids[x.id].length?bids[x.id].map(b=><div key={b.id}>{money(b.amount_toman)} | کاربر {b.user_id} | {dtime(b.created_at)}</div>):"پیشنهادی ثبت نشده"}</div>}
    </td>
   </tr>})}
   </tbody></Table>{!auctions.length&&<p>هنوز مزایده‌ای ثبت نشده است.</p>}
  </CardBody></Card>}
   <Modal isOpen={!!scheduleFor} centered size="lg" toggle={()=>!busy&&setScheduleFor(null)} style={{direction:"rtl",maxWidth:720}}>
    <ModalHeader toggle={()=>!busy&&setScheduleFor(null)} style={{color:"#0d6197"}}>
     زمان‌بندی مزایده {scheduleFor?("«"+scheduleFor.title+"»"):""}
    </ModalHeader>
    <ModalBody style={{padding:20}}>
     <p style={{fontSize:12,color:"#6489a2",lineHeight:2}}>برای انتخاب تاریخ روی کادرها بزنید و از تقویم شمسی، روز و ساعت و دقیقه را تعیین کنید. این زمان‌ها با منطقه زمانی تهران ذخیره می‌شوند.</p>
     <AuctionDateTimeFields values={scheduleValues} onChange={changeSchedule} disabled={busy}/>
     {!validAuctionTimeRange(scheduleValues.starts_at,scheduleValues.ends_at)&&
      <div style={{fontSize:11,color:"#c3543f",padding:10,background:"#fff5f2",borderRadius:9}}>
       هر دو تاریخ الزامی‌اند و زمان پایان باید بعد از زمان شروع باشد.
      </div>}
     <p style={{fontSize:11,color:"#7896aa",lineHeight:2,marginTop:13}}>توجه: پس از ثبت اولین پیشنهاد، زمان شروع و پایان مزایده به‌دلایل حفاظتی قابل تغییر نیست. همچنین زمان پایان در مزایده فعال ممکن است مطابق قوانین تمدید خودکار تغییر کند.</p>
    </ModalBody>
    <ModalFooter style={{justifyContent:"flex-start",gap:9}}>
     <Button color="primary" disabled={busy||!validAuctionTimeRange(scheduleValues.starts_at,scheduleValues.ends_at)} onClick={()=>scheduleFor&&updateAuction(scheduleFor,scheduleValues)}>
      {busy?"در حال ذخیره...":"ذخیره تاریخ و ساعت مزایده"}
     </Button>
     <Button outline color="secondary" disabled={busy} onClick={()=>setScheduleFor(null)}>انصراف</Button>
    </ModalFooter>
   </Modal>
 </div>;
}

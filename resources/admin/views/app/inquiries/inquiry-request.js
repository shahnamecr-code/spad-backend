import React,{useEffect,useState} from "react";
import axios from "axios";
import {Badge,Button,Card,CardBody,FormGroup,Input,Label,Row} from "reactstrap";
import {Colxx,Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";
const money=v=>new Intl.NumberFormat("fa-IR").format(Number(v||0));
const pretty=v=>v?new Date(v).toLocaleString("fa-IR"):"—";
const InquiryRequest=({match})=>{
 const id=match.params.id; const [item,setItem]=useState(); const [partnerOptions,setPartnerOptions]=useState([]); const [partnerId,setPartnerId]=useState(""); const [resultText,setResultText]=useState(""); const [resultJson,setResultJson]=useState("{}"); const [note,setNote]=useState(""); const [reason,setReason]=useState(""); const [busy,setBusy]=useState(false);
 const load=()=>axios.get(`${adminPathApi}/inquiries/requests/${id}`).then(r=>{setItem(r.data.data);setPartnerOptions(r.data.partner_options||[]);setPartnerId(r.data.data.assigned_partner_panel_id||"");setResultText(r.data.data.result_text||"");setResultJson(JSON.stringify(r.data.data.result_data||{},null,2));setNote(r.data.data.operator_note||"");});
 useEffect(()=>{load();},[id]);
 const action=(url,payload={},msg)=>{setBusy(true);return axios.post(`${adminPathApi}/inquiries/requests/${id}/${url}`,payload).then(r=>{NotificationManager.success(r.data.message||msg,null,1800,null,null,"filled");load();}).catch(e=>NotificationManager.error(e.response?.data?.message||"عملیات انجام نشد",null,2500,null,null,"filled")).finally(()=>setBusy(false));};
 const complete=()=>{let json={};try{json=resultJson.trim()?JSON.parse(resultJson):{}}catch(e){return NotificationManager.error("JSON نتیجه معتبر نیست",null,2200,null,null,"filled")};action("complete",{result_text:resultText,result_data:json,operator_note:note});};
 if(!item)return <div className="spad-inquiry-admin"><div className="loading"/></div>;
 const isManual=item.service?.execution_type==="manual"; const isPartnerRouted=!!item.service?.partner_handler_type;
 return <div className="spad-inquiry-admin">
  <Row><Colxx xxs="12"><Breadcrumb heading={`استعلام ${item.reference}`} match={match}/><Separator className="mb-4"/></Colxx></Row>
  <div className="inq-admin-hero compact mb-4"><div><span>{item.service?.execution_type==="api"?"استعلام API":"استعلام اپراتوری"}</span><h2>{item.service?.title}</h2><p>کد پیگیری: <b dir="ltr">{item.reference}</b></p></div><div className="inq-admin-hero-badge"><strong>{item.status}</strong><span>وضعیت فعلی</span></div></div>
  <Row>
   <Colxx xxs="12" lg="8" className="mb-4">
    <Card className="inq-card"><CardBody>
      <div className="inq-card-title"><div><span>01</span><div><h3>اطلاعات درخواست</h3><p>ورودی‌هایی که کاربر برای استعلام ثبت کرده است</p></div></div><Badge color={item.payment_status==="paid"?"success":"secondary"}>{item.payment_status}</Badge></div>
      <div className="inq-detail-grid">{Object.entries(item.input_data||{}).map(([k,v])=><div key={k}><span>{k}</span><strong>{typeof v==="object"?JSON.stringify(v):String(v||"—")}</strong></div>)}</div>
    </CardBody></Card>
    <Card className="inq-card mt-4"><CardBody>
      <div className="inq-card-title"><div><span>02</span><div><h3>{isManual?"ثبت نتیجه توسط اپراتور":"نتیجه سرویس API"}</h3><p>{isManual?"نتیجه نهایی را ثبت کنید تا در پنل کاربر نمایش داده شود":"در صورت خطای موقت می‌توانید API را دوباره اجرا کنید"}</p></div></div>{!isPartnerRouted&&!item.assigned_admin_id&&isManual&&<Button size="sm" outline color="primary" onClick={()=>action("assign")}>تحویل گرفتن</Button>}</div>
      {isManual?<>
       <FormGroup><Label>متن نتیجه</Label><Input type="textarea" rows="5" value={resultText} onChange={e=>setResultText(e.target.value)} placeholder="توضیح قابل مشاهده برای کاربر..."/></FormGroup>
       <FormGroup><Label>Result Data (JSON - اختیاری)</Label><Input type="textarea" rows="6" dir="ltr" value={resultJson} onChange={e=>setResultJson(e.target.value)}/></FormGroup>
       <FormGroup><Label>یادداشت داخلی اپراتور</Label><Input type="textarea" rows="3" value={note} onChange={e=>setNote(e.target.value)} /></FormGroup>
       <div className="d-flex flex-wrap" style={{gap:8}}><Button color="success" disabled={busy||item.payment_status!=="paid"} onClick={complete}>ثبت نتیجه و تکمیل</Button><Button outline color="danger" disabled={busy} onClick={()=>{if(!reason) return NotificationManager.warning("دلیل رد را وارد کنید",null,1800,null,null,"filled");action("reject",{reason});}}>رد درخواست</Button></div>
       <Input className="mt-3" value={reason} onChange={e=>setReason(e.target.value)} placeholder="دلیل رد درخواست..."/>
      </>:<>
       <div className="inq-api-result"><div><span>وضعیت</span><strong>{item.status}</strong></div><div><span>آخرین خطا</span><strong>{item.failure_message||"بدون خطا"}</strong></div></div>
       {item.result_text&&<div className="inq-result-text">{item.result_text}</div>}
       {item.result_data&&<pre className="inq-json">{JSON.stringify(item.result_data,null,2)}</pre>}
       <Button color="primary" outline disabled={busy||item.payment_status!=="paid"} onClick={()=>action("retry-api")}>اجرای مجدد API</Button>
      </>}
    </CardBody></Card>
   </Colxx>
   <Colxx xxs="12" lg="4" className="mb-4">
    <Card className="inq-card inq-sticky"><CardBody>
      <h3 className="mb-3">خلاصه سفارش</h3>
      <div className="inq-summary-list"><div><span>کاربر</span><strong>{item.user?.full_name||`${item.user?.first_name||""} ${item.user?.last_name||""}`}</strong></div><div><span>موبایل</span><strong dir="ltr">{item.user?.mobile}</strong></div><div><span>هزینه</span><strong>{item.amount?`${money(item.amount)} تومان`:"رایگان"}</strong></div><div><span>روش پرداخت</span><strong>{item.payment_method==="wallet"?"کیف پول":item.payment_method==="gateway"?"درگاه":"رایگان"}</strong></div><div><span>ثبت</span><strong>{pretty(item.created_at)}</strong></div><div><span>تکمیل</span><strong>{pretty(item.completed_at)}</strong></div></div>
      {item.payment_method==="wallet"&&item.payment_status==="paid"&&<Button color="warning" outline block disabled={busy} onClick={()=>{if(window.confirm("مبلغ به کیف پول کاربر بازگردانده شود؟")) action("refund")}}>بازگشت وجه به کیف پول</Button>}
    </CardBody></Card>
    {isPartnerRouted&&<Card className="inq-card mt-4"><CardBody>
      <h3 className="mb-2">ارجاع به همکار</h3>
      <p className="text-muted mb-3">نوع مسئول: {item.service?.partner_handler_type==="notary_office"?"دفاتر اسناد رسمی":"نظام مهندسی"}</p>
      {item.assigned_partner&&<div className="inq-result-text mb-3"><strong>{item.assigned_partner.organization_name||item.assigned_partner.title||`پنل #${item.assigned_partner.id}`}</strong><br/><small>این سفارش اکنون در پنل همکار نمایش داده می‌شود.</small></div>}
      <Input type="select" value={partnerId} onChange={e=>setPartnerId(e.target.value)} className="mb-2"><option value="">انتخاب خودکار کم‌بارترین همکار</option>{partnerOptions.map(p=><option value={p.id} key={p.id}>{p.organization_name||p.title||`${p.user?.first_name||""} ${p.user?.last_name||""}`} - {p.user?.mobile||""}</option>)}</Input>
      <Button color="primary" outline block disabled={busy} onClick={()=>action("assign-partner",{partner_panel_id:partnerId||null})}>{item.assigned_partner_panel_id?"تغییر همکار مسئول":"ارجاع سفارش"}</Button>
    </CardBody></Card>}
    <Card className="inq-card mt-4"><CardBody><h3 className="mb-3">تاریخچه عملیات</h3><div className="inq-log-list">{item.logs?.length?item.logs.map(log=><div key={log.id}><span></span><div><strong>{log.event}</strong><p>{log.message}</p><small>{pretty(log.created_at)}</small></div></div>):<div className="inq-empty">لاگی ثبت نشده است.</div>}</div></CardBody></Card>
   </Colxx>
  </Row>
 </div>;
};
export default InquiryRequest;

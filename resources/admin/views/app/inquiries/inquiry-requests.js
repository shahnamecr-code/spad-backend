import React, { useEffect, useState } from "react";
import axios from "axios";
import { Badge, Button, Card, CardBody, Input, Row, Table } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { adminPathApi } from "../../../constants/defaultValues";

const money=(v)=>new Intl.NumberFormat("fa-IR").format(Number(v||0));
const InquiryRequests=({match,history})=>{
 const [data,setData]=useState({data:[],current_page:1,last_page:1});
 const [filters,setFilters]=useState({search:"",status:"",payment_status:"",execution_type:""});
 const [loading,setLoading]=useState(false);
 const load=(page=1)=>{setLoading(true);axios.get(`${adminPathApi}/inquiries/requests`,{params:{...filters,page}}).then(r=>setData(r.data)).finally(()=>setLoading(false));};
 useEffect(()=>{load();},[]);
 const statusBadge=(s)=>({completed:"success",waiting_operator:"warning",processing:"primary",needs_correction:"warning",pending_payment:"secondary",failed:"danger",rejected:"danger",cancelled:"secondary"}[s]||"secondary");
 const statusText=(s,item)=>({completed:"تکمیل شده",waiting_operator:item?.service?.partner_handler_type?"صف همکار":"صف اپراتور",processing:item?.assigned_partner?"در حال انجام توسط همکار":"در حال پردازش",needs_correction:"منتظر اصلاح اطلاعات کاربر",pending_payment:"در انتظار پرداخت",failed:"ناموفق",rejected:"رد شده",cancelled:"لغو شده"}[s]||s);
 return <div className="spad-inquiry-admin">
  <Row><Colxx xxs="12"><Breadcrumb heading="درخواست‌های استعلام" match={match}/><Separator className="mb-4"/></Colxx></Row>
  <div className="inq-admin-hero compact mb-4"><div><span>صف پردازش و اپراتورها</span><h2>مدیریت درخواست‌های استعلام</h2><p>پرداخت، وضعیت اجرا، درخواست‌های API و کارهای دستی اپراتورها را یکجا ببینید.</p></div><div className="inq-admin-hero-badge"><strong>{data.total||0}</strong><span>درخواست</span></div></div>
  <Card className="inq-card"><CardBody>
   <div className="inq-filter-grid">
    <Input value={filters.search} onChange={e=>setFilters({...filters,search:e.target.value})} onKeyDown={e=>e.key==="Enter"&&load()} placeholder="کد پیگیری، کاربر یا سرویس..."/>
    <Input type="select" value={filters.status} onChange={e=>setFilters({...filters,status:e.target.value})}><option value="">همه وضعیت‌ها</option><option value="waiting_operator">صف اپراتور</option><option value="processing">در حال پردازش</option><option value="needs_correction">منتظر اصلاح اطلاعات کاربر</option><option value="completed">تکمیل شده</option><option value="failed">ناموفق</option><option value="pending_payment">در انتظار پرداخت</option></Input>
    <Input type="select" value={filters.execution_type} onChange={e=>setFilters({...filters,execution_type:e.target.value})}><option value="">همه روش‌ها</option><option value="manual">اپراتوری</option><option value="api">API</option></Input>
    <Input type="select" value={filters.payment_status} onChange={e=>setFilters({...filters,payment_status:e.target.value})}><option value="">همه پرداخت‌ها</option><option value="paid">پرداخت شده</option><option value="pending">در انتظار</option><option value="failed">ناموفق</option><option value="refunded">برگشت وجه</option></Input>
    <Button color="primary" onClick={()=>load(1)}>اعمال فیلتر</Button>
   </div>
   <div className="inq-table-wrap"><Table responsive hover className="inq-table"><thead><tr><th>کد</th><th>سرویس / نوع اجرا</th><th>کاربر</th><th>مبلغ</th><th>پرداخت</th><th>وضعیت</th><th>مسئول</th><th></th></tr></thead><tbody>
    {loading?<tr><td colSpan="8" className="text-center py-5">در حال دریافت...</td></tr>:data.data?.length?data.data.map(item=><tr key={item.id}>
      <td><strong dir="ltr">{item.reference}</strong><small className="d-block text-muted">#{item.id}</small></td>
      <td><strong>{item.service?.title}</strong><small className="d-block text-muted">{item.service?.execution_type==="api"?"API آنلاین":"دستی / اپراتور"}</small></td>
      <td><span>{item.user?.full_name || `${item.user?.first_name||""} ${item.user?.last_name||""}`}</span><small className="d-block text-muted" dir="ltr">{item.user?.mobile}</small></td>
      <td>{item.amount?`${money(item.amount)} تومان`:"رایگان"}</td>
      <td><Badge color={item.payment_status==="paid"?"success":item.payment_status==="failed"?"danger":"secondary"}>{item.payment_status==="paid"?"پرداخت‌شده":item.payment_status==="refunded"?"برگشت وجه":item.payment_status}</Badge></td>
      <td><Badge color={statusBadge(item.status)}>{statusText(item.status,item)}</Badge></td>
      <td>{item.assigned_partner?(item.assigned_partner.organization_name||item.assigned_partner.title||"همکار تخصصی"):item.operator?`${item.operator.first_name||""} ${item.operator.last_name||""}`:item.service?.partner_handler_type?"در انتظار تخصیص همکار":"—"}</td>
      <td><Button size="sm" color="primary" outline onClick={()=>history.push(`${match.url}/${item.id}`)}>مشاهده</Button></td>
    </tr>):<tr><td colSpan="8" className="text-center py-5">درخواستی یافت نشد.</td></tr>}
   </tbody></Table></div>
   <div className="inq-pagination"><Button disabled={data.current_page<=1} onClick={()=>load(data.current_page-1)}>قبلی</Button><span>صفحه {data.current_page||1} از {data.last_page||1}</span><Button disabled={data.current_page>=data.last_page} onClick={()=>load(data.current_page+1)}>بعدی</Button></div>
  </CardBody></Card>
 </div>;
};
export default InquiryRequests;

import React, { useEffect, useState } from "react";
import axios from "axios";
import { Button, Card, CardBody, FormGroup, Input, Label, Table } from "reactstrap";
import { adminPathApi } from "../../../constants/defaultValues";
import { NotificationManager } from "../../../components/common/react-notifications";

const statusNames = {new:"جدید",reviewing:"در حال بررسی",documents_required:"نیازمند مدارک",submitted_to_bank:"ارسال به بانک",completed:"تکمیل شده",rejected:"رد شده"};
const defaults = {bond_price:119200,bond_updated_at:"۲۷ مهر ۱۴۰۵",broker_enabled:false};
const MortgageAdmin = () => {
 const [form,setForm]=useState(defaults);
 const [requests,setRequests]=useState([]);
 const [loading,setLoading]=useState(true);
 const refresh = () => Promise.all([
   axios.get(`${adminPathApi}/mortgage/settings`),
   axios.get(`${adminPathApi}/mortgage/requests`)
 ]).then(([a,b])=>{setForm({...defaults,...a.data.data});setRequests(b.data.data.data || []);})
 .catch(e=>NotificationManager.error(e.response?.data?.message || "خطا در دریافت اطلاعات وام"));
 useEffect(()=>{refresh().finally(()=>setLoading(false));},[]);
 const save=()=>axios.post(`${adminPathApi}/mortgage/settings`,form)
   .then(()=>NotificationManager.success("تنظیمات وام ذخیره شد"))
   .catch(e=>NotificationManager.error(e.response?.data?.message || "ثبت تنظیمات ناموفق بود"));
 const change=(item,status)=>axios.patch(`${adminPathApi}/mortgage/requests/${item.id}`,{status,admin_note:item.admin_note || ""})
  .then(()=>{NotificationManager.success("وضعیت درخواست به‌روزرسانی شد");refresh();})
  .catch(e=>NotificationManager.error(e.response?.data?.message || "ثبت وضعیت ناموفق بود"));
 return <div className="container-fluid" dir="rtl">
  <h2 className="mb-4">مدیریت وام مسکن اسپاد</h2>
  <Card className="mb-4"><CardBody>
   <h3>نرخ‌های رسمی و سرویس کارپرداز</h3>
   <FormGroup><Label>قیمت هر برگ اوراق تسه (تومان)</Label><Input type="number" min="1" value={form.bond_price} onChange={e=>setForm({...form,bond_price:Number(e.target.value)})}/></FormGroup>
   <FormGroup><Label>تاریخ به‌روزرسانی نرخ</Label><Input value={form.bond_updated_at} onChange={e=>setForm({...form,bond_updated_at:e.target.value})}/></FormGroup>
   <FormGroup check className="mb-3"><Label check><Input type="checkbox" checked={!!form.broker_enabled} onChange={e=>setForm({...form,broker_enabled:e.target.checked})}/>فعال بودن ثبت درخواست کارپرداز اسپاد</Label></FormGroup>
   <Button color="primary" onClick={save}>ذخیره تنظیمات</Button>
  </CardBody></Card>
  <Card><CardBody>
   <h3>درخواست‌های وام با کارپرداز</h3>
   {loading ? <p>در حال بارگذاری...</p> : <div style={{overflowX:"auto"}}><Table responsive striped>
    <thead><tr><th>کد پیگیری</th><th>موبایل</th><th>کاربر</th><th>مبلغ خرید (میلیون)</th><th>وضعیت</th><th>یادداشت داخلی</th><th>عملیات</th></tr></thead>
    <tbody>{requests.map(item=><tr key={item.id}>
     <td>{item.reference}</td><td>{item.phone}</td><td>{item.user_id}</td><td>{item.purchase_million}</td>
     <td><Input type="select" value={item.status} onChange={e=>setRequests(old=>old.map(x=>x.id===item.id?{...x,status:e.target.value}:x))}>{Object.keys(statusNames).map(key=><option key={key} value={key}>{statusNames[key]}</option>)}</Input></td>
     <td><Input value={item.admin_note||""} onChange={e=>setRequests(old=>old.map(x=>x.id===item.id?{...x,admin_note:e.target.value}:x))}/></td>
     <td><Button size="sm" color="primary" onClick={()=>change(item,item.status)}>ثبت</Button></td>
    </tr>)}</tbody>
   </Table></div>}
  </CardBody></Card>
 </div>;
};
export default MortgageAdmin;

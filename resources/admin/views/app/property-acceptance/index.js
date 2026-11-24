import React,{useCallback,useEffect,useState} from "react";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";
import "./property-acceptance.scss";

const endpoint=adminPathApi+"/property-acceptance";
const labels={
 new:"در انتظار بررسی",contacted:"تماس انجام شد",visit_scheduled:"بازدید هماهنگ شد",
 under_review:"در حال کارشناسی",accepted:"پذیرفته‌شده",declined:"انصراف / عدم پذیرش",closed:"مختومه",
};
const kind={apartment:"آپارتمان",house:"خانه ویلایی",land:"زمین",shop:"مغازه",office:"اداری"};
const toman=n=>typeof n==="number"||/^\d+$/.test(String(n||""))?Number(n).toLocaleString("fa-IR")+" تومان":"—";
const showDate=s=>s?new Date(s).toLocaleString("fa-IR"):"—";

export default function PropertyAcceptanceAdmin(){
 const [list,setList]=useState([]),[summary,setSummary]=useState({}),[pagination,setPagination]=useState({});
 const [statusFilter,setStatusFilter]=useState(""),[search,setSearch]=useState(""),[city,setCity]=useState("");
 const [page,setPage]=useState(1),[loading,setLoading]=useState(false),[listError,setListError]=useState("");
 const [selected,setSelected]=useState(null),[history,setHistory]=useState([]),[detailBusy,setDetailBusy]=useState(false);
 const [operators,setOperators]=useState([]),[saving,setSaving]=useState(false);
 const [form,setForm]=useState({status:"new",assigned_admin_id:"",follow_up_at:"",admin_notes:""});

 const load=useCallback(async()=>{
   setLoading(true);setListError("");
   try{
     const res=await axios.get(endpoint,{params:{page,status:statusFilter||undefined,city:city.trim()||undefined,search:search.trim()||undefined}});
     setList(res.data.data?.data||[]);
     setPagination({current_page:res.data.data?.current_page||1,last_page:res.data.data?.last_page||1,total:res.data.data?.total||0});
     setSummary(res.data.summary||{});
   }catch(err){setListError(err.response?.data?.message||"دریافت درخواست‌های پذیرش ممکن نشد.");}
   finally{setLoading(false);}
 },[page,statusFilter,city,search]);
 useEffect(()=>{load();},[load]);
 useEffect(()=>{
   axios.get(endpoint+"/operators").then(r=>setOperators(r.data.data||[])).catch(()=>{});
 },[]);

 const openCase=async id=>{
   setDetailBusy(true);setSelected(null);setHistory([]);
   try{
     const res=await axios.get(endpoint+"/"+id);
     const item=res.data.data;
     setSelected(item);
     setHistory(res.data.history||[]);
     setForm({
       status:item.status||"new",
       assigned_admin_id:item.assigned_admin_id ? String(item.assigned_admin_id) : "",
       follow_up_at:item.follow_up_at ? String(item.follow_up_at).replace(" ","T").slice(0,16) : "",
       admin_notes:item.admin_notes||"",
     });
   }catch(err){NotificationManager.error(err.response?.data?.message||"دریافت پرونده ممکن نشد.");}
   finally{setDetailBusy(false);}
 };
 const save=async e=>{
   e.preventDefault();
   if(!selected)return;
   setSaving(true);
   try{
     const res=await axios.post(endpoint+"/"+selected.id,{
       status:form.status,
       assigned_admin_id:form.assigned_admin_id ? Number(form.assigned_admin_id) : null,
       follow_up_at:form.follow_up_at||null,
       admin_notes:form.admin_notes||null,
     });
     if(res.data.status){NotificationManager.success("تغییرات پرونده ذخیره شد.");await openCase(selected.id);await load();}
   }catch(err){NotificationManager.error(err.response?.data?.message||"ثبت تغییرات ناموفق بود.");}
   finally{setSaving(false);}
 };
 const stats=[
   ["کل درخواست‌ها",pagination.total||0,"simple-icon-layers"],
   ["جدید",summary.new||0,"simple-icon-envelope"],
   ["در حال پیگیری",(Number(summary.contacted||0)+Number(summary.visit_scheduled||0)+Number(summary.under_review||0)),"simple-icon-calendar"],
   ["پذیرفته‌شده",summary.accepted||0,"simple-icon-check"],
 ];
 return <main className="spad-accept-admin" dir="rtl">
   <header className="spad-accept-admin__hero">
     <div><span>مرکز مدیریت درخواست‌های مالکان</span><h1>پذیرش هوشمند ملک</h1>
       <p>سرنخ‌های فروش، برآوردهای ثبت‌شده، پیگیری کارشناسان و سوابق فعالیت را در یک صفحه مدیریت کنید.</p></div>
     <button type="button" onClick={load} disabled={loading}><i className="simple-icon-refresh"/> به‌روزرسانی</button>
   </header>
   <section className="spad-accept-admin__stats">
    {stats.map(([name,value,icon])=><div key={name} className="spad-accept-admin__stat">
     <i className={icon}/><small>{name}</small><strong>{Number(value).toLocaleString("fa-IR")}</strong></div>)}
   </section>
   <section className="spad-accept-admin__filters" aria-label="فیلتر پذیرش">
     <label>وضعیت پرونده<select value={statusFilter} onChange={e=>{setStatusFilter(e.target.value);setPage(1);}}>
       <option value="">همه وضعیت‌ها</option>{Object.entries(labels).map(([v,l])=><option key={v} value={v}>{l}</option>)}
     </select></label>
     <label>جستجو کد درخواست / شماره همراه
       <input value={search} onChange={e=>{setSearch(e.target.value);setPage(1);}} placeholder="شناسه یا شماره" maxLength={80}/>
     </label>
     <label>شهر
       <input value={city} onChange={e=>{setCity(e.target.value);setPage(1);}} placeholder="همه شهرها" maxLength={120}/>
     </label>
   </section>
   <section className="spad-accept-admin__workspace">
    <div className="spad-accept-admin__results">
      <div className="spad-accept-admin__section-head"><strong>پرونده‌های پذیرش</strong><small>{Number(pagination.total||0).toLocaleString("fa-IR")} نتیجه</small></div>
      {listError&&<div className="spad-accept-admin__error">{listError}</div>}
      {loading?<div className="spad-accept-admin__loading">در حال دریافت اطلاعات...</div>:!list.length?
        <div className="spad-accept-admin__empty">درخواستی مطابق فیلترهای فعلی وجود ندارد.</div>:
        <div className="spad-accept-admin__cards">
          {list.map(item=><button type="button" key={item.id}
           className={"spad-accept-admin__case "+(selected?.id===item.id?"is-active":"")}
           onClick={()=>openCase(item.id)}>
             <div className="spad-accept-admin__case-top"><strong>پرونده {item.id.toLocaleString("fa-IR")}</strong>
               <span className={"spad-accept-admin__status spad-accept-admin__status--"+item.status}>{labels[item.status]||item.status}</span>
             </div>
             <h3>{[item.first_name,item.last_name].filter(Boolean).join(" ")||"مالک اسپاد"}</h3>
             <p>{item.city||"شهر نامشخص"} • {kind[item.property_kind]||item.property_kind} • {Number(item.area_m2).toLocaleString("fa-IR")} متر</p>
             <div className="spad-accept-admin__case-bottom"><span>{toman(item.estimated_total_toman)}</span><small>{showDate(item.created_at)}</small></div>
           </button>)}
        </div>}
      <div className="spad-accept-admin__paging">
       <button type="button" disabled={page<=1||loading} onClick={()=>setPage(v=>v-1)}>صفحه قبل</button>
       <span>صفحه {page.toLocaleString("fa-IR")} از {Number(pagination.last_page||1).toLocaleString("fa-IR")}</span>
       <button type="button" disabled={page>=(pagination.last_page||1)||loading} onClick={()=>setPage(v=>v+1)}>صفحه بعد</button>
      </div>
    </div>
    <aside className="spad-accept-admin__detail">
      <div className="spad-accept-admin__section-head"><strong>جزئیات و پیگیری پرونده</strong></div>
      {detailBusy?<div className="spad-accept-admin__loading">در حال دریافت پرونده...</div>:!selected?
        <div className="spad-accept-admin__empty"><i className="simple-icon-folder-alt"/> یک پرونده را از فهرست انتخاب کنید.</div>:<>
        <div className="spad-accept-admin__selected">
          <div><small>کد پیگیری</small><strong>#{selected.id.toLocaleString("fa-IR")}</strong></div>
          <span className={"spad-accept-admin__status spad-accept-admin__status--"+selected.status}>{labels[selected.status]}</span>
        </div>
        <div className="spad-accept-admin__info">
          <div><small>مالک</small><strong>{[selected.first_name,selected.last_name].filter(Boolean).join(" ")||"ثبت نشده"}</strong></div>
          <div><small>شماره تماس</small><a dir="ltr" href={"tel:"+selected.mobile}>{selected.mobile||"—"}</a></div>
          <div><small>کدپستی</small><strong dir="ltr">{selected.postal_code}</strong></div>
          <div><small>نوع / متراژ</small><strong>{kind[selected.property_kind]} / {selected.area_m2} متر</strong></div>
          <div><small>شهر / استان</small><strong>{selected.city} / {selected.province}</strong></div>
          <div><small>قیمت برآوردی</small><strong>{toman(selected.estimated_total_toman)}</strong></div>
        </div>
        <div className="spad-accept-admin__address"><small>نشانی استعلام‌شده</small>
          <p>{selected.address?.address||[selected.address?.street,selected.address?.number,selected.address?.floor,selected.address?.sideFloor].filter(Boolean).join("، ")||"اطلاعات نشانی تکمیلی موجود نیست."}</p>
          <small>منبع برآورد: {selected.market_source} — {selected.coverage||"نامشخص"}</small>
        </div>
        <p className="spad-accept-admin__valuation-note">این مبلغ قیمت پیشنهادی بازار است و کارشناسی قطعی محسوب نمی‌شود.</p>
        <form className="spad-accept-admin__edit" onSubmit={save}>
          <label>وضعیت پیگیری<select value={form.status} onChange={e=>setForm(v=>({...v,status:e.target.value}))}>
            {Object.entries(labels).map(([k,v])=><option key={k} value={k}>{v}</option>)}
          </select></label>
          <label>کارشناس مسئول<select value={form.assigned_admin_id} onChange={e=>setForm(v=>({...v,assigned_admin_id:e.target.value}))}>
            <option value="">بدون مسئول</option>{operators.map(op=><option key={op.id} value={op.id}>{[op.first_name,op.last_name].filter(Boolean).join(" ")||("اپراتور "+op.id)}</option>)}
          </select></label>
          <label>زمان پیگیری بعدی<input type="datetime-local" dir="ltr" value={form.follow_up_at} onChange={e=>setForm(v=>({...v,follow_up_at:e.target.value}))}/></label>
          <label>یادداشت داخلی کارشناسان<textarea rows={4} maxLength={2000} value={form.admin_notes} onChange={e=>setForm(v=>({...v,admin_notes:e.target.value}))} placeholder="جزئیات تماس، نیازهای مالک یا نتیجه بررسی..."/></label>
          <button type="submit" disabled={saving}>{saving?"در حال ذخیره...":"ذخیره تغییرات پرونده"}</button>
        </form>
        <div className="spad-accept-admin__history">
          <h3>تاریخچه پرونده</h3>{history.length?history.map(item=><div key={item.id}>
            <i className="simple-icon-check"/><span>{item.action==="submitted"?"ثبت درخواست":item.action==="status_changed"?"تغییر وضعیت":item.action==="assignment_changed"?"تغییر کارشناس":item.action==="follow_up_changed"?"تنظیم پیگیری":"ثبت یادداشت"}
              {item.to_status?" • "+(labels[item.to_status]||item.to_status):""}</span><small>{showDate(item.created_at)}</small>
           </div>):<p>هنوز فعالیتی ثبت نشده است.</p>}
        </div>
      </>}
    </aside>
   </section>
 </main>;
}

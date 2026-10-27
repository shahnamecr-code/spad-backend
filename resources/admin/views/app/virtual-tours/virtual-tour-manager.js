import React, { useEffect, useState } from "react";
import axios from "axios";
import { Badge, Button, Card, CardBody, Input, Modal, ModalBody, ModalFooter, ModalHeader, Row } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { adminPathApi, adminRoot } from "../../../constants/defaultValues";
import { NotificationManager } from "../../../components/common/react-notifications";

const Manager = ({ match, history, startCreate }) => {
  const [data, setData] = useState({ data: [], current_page: 1, last_page: 1 });
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [loading, setLoading] = useState(false);
  const [showCreate, setShowCreate] = useState(false);
  const [propertySearch, setPropertySearch] = useState("");
  const [properties, setProperties] = useState([]);
  const [propertyLoading, setPropertyLoading] = useState(false);
  const [creating, setCreating] = useState(false);

  const load = (page = 1) => {
    setLoading(true);
    axios.get(`${adminPathApi}/virtual-tours`, { params: { page, search, status } })
      .then((r) => setData(r.data || { data: [] }))
      .catch((e) => NotificationManager.error(e.response?.data?.message || "دریافت تورها انجام نشد"))
      .finally(() => setLoading(false));
  };

  const loadProperties = () => {
    setPropertyLoading(true);
    axios.get(`${adminPathApi}/virtual-tours/properties`, { params: { search: propertySearch } })
      .then((r) => setProperties(r.data?.data || []))
      .catch((e) => NotificationManager.error(e.response?.data?.message || "دریافت فایل‌های ملکی انجام نشد"))
      .finally(() => setPropertyLoading(false));
  };

  useEffect(() => { load(1); }, []);
  useEffect(() => { if (showCreate) loadProperties(); }, [showCreate]);
  useEffect(() => { if (startCreate) setShowCreate(true); }, [startCreate]);

  const createForProperty = (property) => {
    setCreating(true);
    axios.post(`${adminPathApi}/virtual-tours/property/${property.id}`)
      .then((r) => {
        const tour = r.data?.data;
        if (!tour?.id) throw new Error("تور ایجاد نشد");
        setShowCreate(false);
        history.push(`${adminRoot}/virtual-tours/${tour.id}/edit`);
      })
      .catch((e) => NotificationManager.error(e.response?.data?.message || "ساخت تور انجام نشد"))
      .finally(() => setCreating(false));
  };

  const act = (id, action) => {
    if (action === "delete" && !window.confirm("این تور و تمام Sceneهای آن حذف شود؟")) return;
    const req = action === "delete"
      ? axios.delete(`${adminPathApi}/virtual-tours/${id}`)
      : axios.post(`${adminPathApi}/virtual-tours/${id}/${action}`);
    req.then(() => { NotificationManager.success("عملیات انجام شد"); load(data.current_page || 1); })
      .catch((e) => NotificationManager.error(e.response?.data?.message || "عملیات انجام نشد"));
  };

  return <div className="spad-vtour-admin">
    <Row><Colxx xxs="12"><Breadcrumb heading="استودیو تور مجازی ۳۶۰°" match={match}/><Separator className="mb-4"/></Colxx></Row>

    <div className="vt-manager-hero">
      <div>
        <span className="vt-eyebrow">SPAD VIRTUAL TOUR STUDIO</span>
        <div className="vt-studio-badge"><span className="vt-studio-badge-dot"/> سازنده حرفه‌ای تور ۳۶۰°</div>
        <h2>مدیریت تورهای مجازی</h2>
        <p>ساخت، ویرایش، انتشار و تحلیل تورهای ۳۶۰ درجه فایل‌های ملکی؛ کاملاً مستقل و Self-Hosted.</p>
        <div className="vt-hero-actions">
          <Button color="primary" className="vt-main-create" onClick={() => setShowCreate(true)}>+ شروع ساخت تور مجازی</Button>
          <Button outline color="primary" onClick={() => load(1)}>بروزرسانی</Button>
        </div>
      </div>
      <div className="vt-hero-stat"><strong>{data.total || 0}</strong><span>تور ثبت‌شده</span></div>
    </div>

    <Card className="vt-card mb-4"><CardBody><div className="vt-toolbar">
      <Input value={search} onChange={(e)=>setSearch(e.target.value)} placeholder="جستجو با عنوان تور، کد ملک یا Slug..."/>
      <Input type="select" value={status} onChange={(e)=>setStatus(e.target.value)}>
        <option value="">همه وضعیت‌ها</option><option value="draft">پیش‌نویس</option><option value="published">منتشرشده</option>
      </Input>
      <Button color="primary" onClick={()=>load(1)}>جستجو</Button>
    </div></CardBody></Card>

    {loading ? <div className="loading"/> : <div className="vt-tour-grid">
      {(data.data || []).map((item)=><Card className="vt-tour-card" key={item.id}>
        <div className="vt-cover">
          {item.cover ? <img src={item.cover} alt=""/> : <div className="vt-cover-empty">360°</div>}
          <Badge color={item.status === "published" ? "success" : "secondary"}>{item.status === "published" ? "منتشرشده" : "پیش‌نویس"}</Badge>
        </div>
        <CardBody>
          <h3>{item.title}</h3>
          <div className="vt-tour-meta"><span>ملک #{item.advertisement_id}</span><span>{item.scenes_count} فضا</span><span>{item.hotspots_count} نقطه</span><span>{item.views_count} بازدید</span></div>
          <div className="vt-card-actions">
            <Button color="primary" onClick={()=>history.push(`${adminRoot}/virtual-tours/${item.id}/edit`)}>باز کردن استودیو</Button>
            <Button outline color="primary" onClick={()=>item.status==="published"?window.open(item.public_url || `/virtual-tour/${item.slug}`,"_blank"):history.push(`${adminRoot}/virtual-tours/${item.id}/edit`)}>Preview</Button>
            <Button outline color="secondary" onClick={()=>act(item.id,"duplicate")}>کپی</Button>
            <Button outline color="danger" onClick={()=>act(item.id,"delete")}>حذف</Button>
          </div>
        </CardBody>
      </Card>)}
      {!(data.data || []).length && <div className="vt-empty"><b>هنوز توری ساخته نشده است.</b><span>برای شروع، روی «ساخت تور برای یک ملک» بزنید یا از ویرایش هر ملک استودیو را باز کنید.</span></div>}
    </div>}

    {(data.last_page || 1) > 1 && <div className="vt-pagination">{Array.from({length:data.last_page},(_,i)=>i+1).map(p=><Button key={p} size="sm" color={p===data.current_page?"primary":"light"} onClick={()=>load(p)}>{p}</Button>)}</div>}

    <Modal isOpen={showCreate} toggle={()=>setShowCreate(false)} size="lg" className="vt-modal">
      <ModalHeader toggle={()=>setShowCreate(false)}>ساخت تور مجازی برای ملک</ModalHeader>
      <ModalBody>
        <div className="vt-create-help">
          <strong>ابتدا ملک را انتخاب کنید</strong>
          <span>بعد از انتخاب، مستقیماً وارد Studio می‌شوید و می‌توانید تصاویر ۳۶۰، Sceneها، Hotspotها، پلان و تنظیمات تور را مدیریت کنید.</span>
        </div>
        <div className="vt-property-search">
          <Input value={propertySearch} onChange={(e)=>setPropertySearch(e.target.value)} onKeyDown={(e)=>{if(e.key==="Enter")loadProperties()}} placeholder="کد ملک یا بخشی از آدرس..."/>
          <Button color="primary" onClick={loadProperties}>جستجو</Button>
        </div>
        <div className="vt-property-list">
          {propertyLoading ? <div className="loading"/> : properties.map((item)=>(
            <button type="button" className="vt-property-option" key={item.id} onClick={()=>createForProperty(item)} disabled={creating}>
              <span className="vt-property-option__icon">360°</span>
              <span className="vt-property-option__body">
                <b>ملک {item.code}</b>
                <small>{[item.city,item.region,item.address].filter(Boolean).join(" • ") || "بدون آدرس"}</small>
                {item.tour_id && <em>{item.tour_status === "published" ? "تور منتشرشده دارد" : "تور پیش‌نویس دارد"}</em>}
              </span>
              <span className="vt-property-option__action">{item.tour_id ? "باز کردن تور" : "ساخت تور"}</span>
            </button>
          ))}
          {!propertyLoading && !properties.length && <div className="vt-empty-floor">ملکی با این جستجو پیدا نشد.</div>}
        </div>
      </ModalBody>
      <ModalFooter><Button color="secondary" onClick={()=>setShowCreate(false)}>بستن</Button></ModalFooter>
    </Modal>
  </div>;
};
export default Manager;

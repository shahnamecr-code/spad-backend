import React, { useEffect, useState } from "react";
import axios from "axios";
import { Badge, Button, Card, CardBody, FormGroup, Input, Label, Modal, ModalBody, ModalFooter, ModalHeader, Row, Table } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { NotificationManager } from "../../../components/common/react-notifications";
import { adminPathApi } from "../../../constants/defaultValues";
import "../../../assets/css/sass/investor-listings.scss";

const emptyForm = {
  advertisement_id: "", status: "active", market_price: null, investor_price: "",
  investment_score: "", investment_horizon_months: "", deal_summary: "", risk_note: ""
};
const money = value => value && Number(value) > 0 ? Number(value).toLocaleString("fa-IR") + " تومان" : "—";

const InvestorListings = ({ match }) => {
  const [rows, setRows] = useState([]);
  const [meta, setMeta] = useState({ statuses: {}, total: 0, active: 0, pending: 0, investor_submissions: 0 });
  const [loading, setLoading] = useState(true);
  const [modal, setModal] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(emptyForm);
  const [filters, setFilters] = useState({ search: "", status: "", source: "" });
  const [propertySearch, setPropertySearch] = useState("");
  const [properties, setProperties] = useState([]);
  const [propertyLoading, setPropertyLoading] = useState(false);
  const [selectedProperty, setSelectedProperty] = useState(null);
  const [saving, setSaving] = useState(false);

  const set = (key, value) => setForm(old => ({ ...old, [key]: value }));
  const load = async () => {
    setLoading(true);
    try {
      const [list, metadata] = await Promise.all([
        axios.get(`${adminPathApi}/investor-listings`, { params: { ...filters, per_page: 50 } }),
        axios.get(`${adminPathApi}/investor-listings/meta`)
      ]);
      setRows(list.data?.data || []);
      setMeta(metadata.data?.data || {});
    } catch (error) {
      NotificationManager.error(error.response?.data?.message || "دریافت ملک‌های سرمایه‌گذاری انجام نشد.");
    } finally { setLoading(false); }
  };
  useEffect(() => { load(); }, []);

  useEffect(() => {
    if (!modal || editing) return;
    let alive = true;
    const timer = window.setTimeout(async () => {
      setPropertyLoading(true);
      try {
        const response = await axios.get(`${adminPathApi}/investor-listings/available-properties`, {
          params: { search: propertySearch.trim() }
        });
        if (alive) setProperties(response.data?.data || []);
      } catch (error) {
        if (alive) NotificationManager.error("دریافت آگهی‌های عمومی انجام نشد.");
      } finally { if (alive) setPropertyLoading(false); }
    }, 300);
    return () => { alive = false; window.clearTimeout(timer); };
  }, [modal, editing, propertySearch]);

  const openCreate = () => {
    setEditing(null); setForm({ ...emptyForm }); setSelectedProperty(null);
    setPropertySearch(""); setProperties([]); setModal(true);
  };
  const openEdit = item => {
    setEditing(item);
    setSelectedProperty(item.advertisement ? {
      id: item.advertisement_id,
      title: item.advertisement.title,
      public_price_label: "قیمت عمومی در صفحه اصلی ملک"
    } : { id: item.advertisement_id, title: "ملک کد " + item.advertisement_id });
    setForm({
      ...emptyForm,
      advertisement_id: item.advertisement_id,
      status: item.status,
      market_price: item.market_price,
      investor_price: item.investor_price || "",
      investment_score: item.investment_score || "",
      investment_horizon_months: item.investment_horizon_months || "",
      deal_summary: item.deal_summary || "",
      risk_note: item.risk_note || ""
    });
    setModal(true);
  };
  useEffect(() => {
    if (typeof window !== "undefined" && new URLSearchParams(window.location.search).get("create") === "1") {
      openCreate();
    }
  }, []);

  const choose = property => {
    setSelectedProperty(property);
    set("advertisement_id", property.id);
  };
  const submit = async () => {
    if (!editing && !selectedProperty) {
      NotificationManager.error("ابتدا یکی از آگهی‌های عمومی تاییدشده را انتخاب کنید.");
      return;
    }
    if (!Number.isInteger(Number(form.investor_price)) || Number(form.investor_price) <= 0) {
      NotificationManager.error("قیمت اختصاصی سرمایه‌گذاری را به تومان وارد کنید.");
      return;
    }
    const payload = { ...form };
    ["market_price", "investment_score", "investment_horizon_months"].forEach(key => {
      if (payload[key] === "") payload[key] = null;
    });
    if (editing) delete payload.advertisement_id;
    setSaving(true);
    try {
      if (editing) await axios.put(`${adminPathApi}/investor-listings/${editing.id}`, payload);
      else await axios.post(`${adminPathApi}/investor-listings`, payload);
      NotificationManager.success("قیمت اختصاصی ثبت شد؛ آگهی اصلی در سایت عمومی باقی می‌ماند.");
      setModal(false); load();
    } catch (error) {
      const fields = error.response?.data?.errors;
      NotificationManager.error(fields ? Object.values(fields).flat()[0] :
        error.response?.data?.message || "ثبت قیمت سرمایه‌گذاری ناموفق بود.");
    } finally { setSaving(false); }
  };

  const action = async (item, type) => {
    const captions = { approve: "فعال‌سازی قیمت اختصاصی", reject: "غیرفعال‌سازی قیمت اختصاصی", delete: "حذف قیمت اختصاصی" };
    if (!window.confirm(`${captions[type]} برای ملک ${item.advertisement_id} انجام شود؟ ملک عمومی بدون تغییر می‌ماند.`)) return;
    try {
      if (type === "delete") await axios.delete(`${adminPathApi}/investor-listings/${item.id}`);
      else await axios.post(`${adminPathApi}/investor-listings/${item.id}/${type}`);
      NotificationManager.success("تغییر قیمت اختصاصی ذخیره شد؛ وضعیت ملک عمومی تغییر نکرد.");
      load();
    } catch (error) { NotificationManager.error(error.response?.data?.message || "عملیات ناموفق بود."); }
  };
  const selectedId = selectedProperty?.id || form.advertisement_id;

  return <div className="spad-investor-admin" dir="rtl">
    <Row><Colxx xxs="12">
      <div className="text-zero top-right-button-container">
        <Button color="primary" size="lg" className="top-right-button" onClick={openCreate}>
          <i className="simple-icon-plus mr-2"/> افزودن ملک سرمایه‌گذاری
        </Button>
      </div>
      <Breadcrumb heading="menu.broker-properties" match={match}/>
      <Separator className="mb-4"/>
    </Colxx></Row>
    <div className="spad-investor-admin__hero">
      <div>
        <span>SPAD INVESTMENT PRICING</span>
        <h2>قیمت ویژه برای آگهی‌های عمومی</h2>
        <p>آگهی اصلی، تصاویر و تور مجازی برای همه قابل مشاهده است. اینجا فقط قیمت متفاوت ویژه پنل سرمایه‌گذاران را تعریف می‌کنید.</p>
      </div>
      <div className="spad-investor-admin__lock"><i className="simple-icon-tag"/> قیمت ویژه اعضای سرمایه‌گذاری</div>
    </div>
    <div className="spad-investor-admin__stats">
      <div><small>کل قیمت‌های ویژه</small><strong>{meta.total || 0}</strong></div>
      <div><small>قیمت ویژه فعال</small><strong>{meta.active || 0}</strong></div>
      <div><small>در انتظار بررسی</small><strong>{meta.pending || 0}</strong></div>
      <div><small>آگهی اصلی</small><strong>عمومی</strong></div>
    </div>
    <Card><CardBody>
      <div className="spad-investor-admin__filters">
        <Input placeholder="کد یا عنوان ملک" value={filters.search} onChange={e => setFilters({ ...filters, search: e.target.value })}/>
        <Input type="select" value={filters.status} onChange={e => setFilters({ ...filters, status: e.target.value })}>
          <option value="">همه وضعیت‌ها</option>
          {Object.entries(meta.statuses || {}).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
        </Input>
        <Input type="select" value={filters.source} onChange={e => setFilters({ ...filters, source: e.target.value })}>
          <option value="">همه منابع</option><option value="admin">مدیریت</option><option value="investor">سرمایه‌گذار</option>
        </Input>
        <Button color="secondary" onClick={load}>جستجو</Button>
      </div>
      <div className="table-responsive">
        <Table hover>
          <thead><tr>
            <th>ملک عمومی</th><th>وضعیت قیمت ویژه</th><th>قیمت مبنا</th><th>قیمت سرمایه‌گذاری</th><th>اختلاف</th><th>عملیات</th>
          </tr></thead>
          <tbody>
            {loading ? <tr><td colSpan="6" className="text-center">در حال دریافت...</td></tr> :
              rows.length ? rows.map(item => <tr key={item.id}>
                <td><strong>#{item.advertisement_id}</strong><small>{item.advertisement?.title || "بدون عنوان"}</small>
                  <a target="_blank" rel="noopener noreferrer" href={`https://spad.estate/property/${item.advertisement_id}`}>مشاهده آگهی عمومی</a>
                </td>
                <td><Badge color={item.status === "active" ? "success" : item.status === "pending" ? "warning" : "secondary"}>{item.status_label}</Badge></td>
                <td>{money(item.market_price)}</td>
                <td className="spad-investor-admin__price">{money(item.investor_price)}</td>
                <td>{item.discount_percent ? `${Number(item.discount_percent).toLocaleString("fa-IR")}٪` : "—"}</td>
                <td className="spad-investor-admin__actions">
                  <Button size="sm" color="light" onClick={() => openEdit(item)}>ویرایش قیمت</Button>
                  {item.status !== "active" && <Button size="sm" color="success" onClick={() => action(item, "approve")}>فعال‌سازی</Button>}
                  {item.status === "active" && <Button size="sm" color="warning" outline onClick={() => action(item, "reject")}>غیرفعال‌سازی</Button>}
                  <Button size="sm" color="danger" outline onClick={() => action(item, "delete")}>حذف قیمت</Button>
                </td>
              </tr>) : <tr><td colSpan="6" className="text-center">قیمت سرمایه‌گذاری ثبت نشده است.</td></tr>}
          </tbody>
        </Table>
      </div>
    </CardBody></Card>

    <Modal isOpen={modal} toggle={() => !saving && setModal(false)} size="lg" className="spad-investor-pricing-modal">
      <ModalHeader toggle={() => !saving && setModal(false)}>
        {editing ? "ویرایش قیمت اختصاصی" : "افزودن ملک سرمایه‌گذاری"}
      </ModalHeader>
      <ModalBody>
        {!editing && <div className="spad-investor-property-picker">
          <Label>انتخاب ملک از آگهی‌های عمومی تأییدشده شیراز</Label>
          <Input type="search" placeholder="کد ملک یا بخشی از عنوان را جستجو کنید"
            value={propertySearch} onChange={event => setPropertySearch(event.target.value)}/>
          <div className="spad-investor-property-picker__results" role="listbox" aria-label="آگهی‌های عمومی">
            {propertyLoading ? <p>در حال جستجوی ملک...</p> :
              properties.length ? properties.map(property =>
                <button key={property.id} type="button"
                  role="option" aria-selected={Number(selectedId) === Number(property.id)}
                  className={Number(selectedId) === Number(property.id) ? "is-selected" : ""}
                  onClick={() => choose(property)}>
                  <span><strong>کد {Number(property.id).toLocaleString("fa-IR")} · {property.title}</strong>
                    <small>{[property.city, property.region].filter(Boolean).join("، ")} · {property.public_price_label}</small></span>
                  <i className={Number(selectedId) === Number(property.id) ? "simple-icon-check" : "simple-icon-arrow-left"}/>
                </button>
              ) : <p>ملک عمومی جدیدی پیدا نشد؛ کد یا عنوان را تغییر دهید.</p>}
          </div>
        </div>}
        {selectedProperty && <div className="spad-investor-property-picker__selected">
          <i className="simple-icon-check" /> انتخاب‌شده: کد {Number(selectedProperty.id).toLocaleString("fa-IR")} — {selectedProperty.title}
        </div>}
        <Row>
          <Colxx xxs="12" md="6"><FormGroup>
            <Label>قیمت ویژه سرمایه‌گذاری (تومان) *</Label>
            <Input type="number" min="1" step="1" autoComplete="off"
              value={form.investor_price} onChange={e => set("investor_price", e.target.value)}
              placeholder="قیمت اختصاصی برای سرمایه‌گذاران"/>
          </FormGroup></Colxx>
          <Colxx xxs="12" md="6"><FormGroup>
            <Label>قیمت مبنا (تومان، اختیاری)</Label>
            <Input type="number" min="0" step="1" value={form.market_price || ""}
              onChange={e => set("market_price", e.target.value || null)}
              placeholder="فقط برای محاسبه درصد تخفیف"/>
            <small className="text-muted">قیمت خط‌خورده در صفحه ملک، مستقیماً از قیمت عمومی همان آگهی خوانده می‌شود.</small>
          </FormGroup></Colxx>
          <Colxx xxs="12" md="6"><FormGroup>
            <Label>وضعیت نمایش قیمت سرمایه‌گذاری</Label>
            <Input type="select" value={form.status} onChange={e => set("status", e.target.value)}>
              {Object.entries(meta.statuses || { active: "فعال", pending: "در انتظار", rejected: "غیرفعال" })
                .map(([k, v]) => <option value={k} key={k}>{v}</option>)}
            </Input>
          </FormGroup></Colxx>
          <Colxx xxs="12" md="6"><FormGroup>
            <Label>امتیاز فرصت (اختیاری، ۱ تا ۱۰۰)</Label>
            <Input type="number" min="1" max="100" value={form.investment_score}
              onChange={e => set("investment_score", e.target.value)} />
          </FormGroup></Colxx>
          <Colxx xxs="12"><FormGroup>
            <Label>توضیحات اختصاصی سرمایه‌گذاران (اختیاری)</Label>
            <Input type="textarea" rows="2" value={form.deal_summary}
              onChange={e => set("deal_summary", e.target.value)} />
          </FormGroup></Colxx>
        </Row>
        <div className="alert alert-info" style={{fontSize:12}}>
          این تغییر تنها برای پنل سرمایه‌گذاران است؛ آگهی عمومی، عکس‌ها، تور مجازی و قیمت عمومی ملک تغییر نمی‌کنند.
        </div>
      </ModalBody>
      <ModalFooter>
        <Button color="secondary" disabled={saving} onClick={() => setModal(false)}>انصراف</Button>
        <Button color="primary" disabled={saving || (!editing && !selectedProperty)} onClick={submit}>
          {saving ? "در حال ثبت قیمت..." : "ثبت قیمت اختصاصی"}
        </Button>
      </ModalFooter>
    </Modal>
  </div>;
};
export default InvestorListings;

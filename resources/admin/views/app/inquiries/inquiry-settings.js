import React, { useEffect, useState } from "react";
import axios from "axios";
import { Button, Card, CardBody, FormGroup, Input, Label, Row } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { adminPathApi } from "../../../constants/defaultValues";
import { NotificationManager } from "../../../components/common/react-notifications";

const defaults = {
  inquiries_enabled: true,
  wallet_payment_enabled: true,
  gateway_payment_enabled: true,
  page_title: "استعلامات آنلاین SPAD",
  page_subtitle: "استعلام‌های ملکی و ثبتی را سریع، امن و یکپارچه انجام دهید.",
  support_text: "در استعلام‌های دستی، نتیجه پس از بررسی اپراتور در پنل کاربری شما قرار می‌گیرد.",
  seven_inquiry_service_ids: [],
};
const money = (v) => new Intl.NumberFormat("fa-IR").format(Number(v || 0));

const InquirySettings = ({ match }) => {
  const [form, setForm] = useState(defaults);
  const [services, setServices] = useState([]);
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      axios.get(`${adminPathApi}/inquiries/settings`),
      axios.get(`${adminPathApi}/inquiries/seven-options`),
    ]).then(([settings, options]) => {
      setForm({ ...defaults, ...(settings.data.data || {}) });
      setServices(options.data.data || []);
    }).catch((e) => NotificationManager.error(
      e.response?.data?.message || "دریافت تنظیمات استعلامات انجام نشد",
      null, 2400, null, null, "filled"
    )).finally(() => setLoading(false));
  }, []);

  const chosen = form.seven_inquiry_service_ids || [];
  const toggleService = (id) => {
    const exists = chosen.includes(id);
    if (!exists && chosen.length >= 7) return;
    setForm((old) => ({
      ...old,
      seven_inquiry_service_ids: exists
        ? old.seven_inquiry_service_ids.filter((v) => v !== id)
        : [...old.seven_inquiry_service_ids, id],
    }));
  };

  const save = () => {
    if (chosen.length !== 7) {
      NotificationManager.error("برای استعلامات هفت‌گانه دقیقاً ۷ سرویس فعال را انتخاب کنید.", null, 2300, null, null, "filled");
      return;
    }
    setSaving(true);
    axios.post(`${adminPathApi}/inquiries/settings`, form)
      .then((r) => NotificationManager.success(r.data.message, null, 1800, null, null, "filled"))
      .catch((e) => NotificationManager.error(e.response?.data?.message || "ذخیره انجام نشد", null, 2200, null, null, "filled"))
      .finally(() => setSaving(false));
  };

  return <div className="spad-inquiry-admin">
    <Row><Colxx xxs="12"><Breadcrumb heading="تنظیمات استعلامات" match={match}/><Separator className="mb-4"/></Colxx></Row>
    <div className="inq-admin-hero mb-4"><div>
      <span>تنظیمات عمومی صفحه</span>
      <h2>مرکز استعلامات SPAD</h2>
      <p>پیکربندی سرویس‌ها، نحوه نمایش و روش پرداخت استعلامات را مدیریت کنید.</p>
    </div></div>
    <Row>
      <Colxx xxs="12" lg="7" className="mb-4"><Card className="inq-card"><CardBody>
        <h3>محتوای صفحه عمومی</h3>
        <FormGroup><Label>عنوان صفحه</Label><Input value={form.page_title} onChange={e => setForm({ ...form, page_title: e.target.value })}/></FormGroup>
        <FormGroup><Label>زیرعنوان</Label><Input type="textarea" rows="3" value={form.page_subtitle} onChange={e => setForm({ ...form, page_subtitle: e.target.value })}/></FormGroup>
        <FormGroup><Label>متن راهنمای نتیجه</Label><Input type="textarea" rows="3" value={form.support_text} onChange={e => setForm({ ...form, support_text: e.target.value })}/></FormGroup>
      </CardBody></Card></Colxx>
      <Colxx xxs="12" lg="5" className="mb-4"><Card className="inq-card"><CardBody>
        <h3>فعال‌سازی و پرداخت</h3>
        <div className="inq-setting-switch">
          <label><input type="checkbox" checked={!!form.inquiries_enabled} onChange={e => setForm({ ...form, inquiries_enabled: e.target.checked })}/><div><strong>فعال بودن مرکز استعلامات</strong><span>در صورت غیرفعال‌سازی، ثبت درخواست جدید متوقف می‌شود.</span></div></label>
          <label><input type="checkbox" checked={!!form.wallet_payment_enabled} onChange={e => setForm({ ...form, wallet_payment_enabled: e.target.checked })}/><div><strong>پرداخت از کیف پول</strong><span>برای پرداخت یک‌مرحله‌ای هفت‌گانه لازم است.</span></div></label>
          <label><input type="checkbox" checked={!!form.gateway_payment_enabled} onChange={e => setForm({ ...form, gateway_payment_enabled: e.target.checked })}/><div><strong>پرداخت مستقیم درگاه</strong><span>برای استعلامات تکی فعال است.</span></div></label>
        </div>
      </CardBody></Card></Colxx>
    </Row>
    <Row><Colxx xxs="12" className="mb-4"><Card className="inq-card"><CardBody>
      <div className="inq-seven-admin-heading">
        <div>
          <span className="inq-seven-eyebrow">بسته ثابت معاملات ملکی</span>
          <h3>تنظیم هفت استعلام الزامی</h3>
          <p>این ترکیب فقط توسط مدیر کل تعیین می‌شود و کاربر هیچ گزینه‌ای برای انتخاب یا حذف سرویس‌ها ندارد.</p>
        </div>
        <span className="inq-seven-count">{money(chosen.length)} از ۷ انتخاب شده</span>
      </div>
      {loading ? <p>در حال دریافت سرویس‌های فعال...</p> : (
        <div className="inq-seven-services">
          {services.map((service) => {
            const active = chosen.includes(service.id);
            return <label key={service.id} className={`inq-seven-option ${active ? "selected" : ""}`}>
              <input type="checkbox" checked={active} disabled={!active && chosen.length >= 7} onChange={() => toggleService(service.id)}/>
              <div><strong>{service.title}</strong><span>{service.category || "خدمات استعلام"} · {service.execution_type === "api" ? "آنلاین" : "اپراتوری"}</span></div>
              <small>{service.price ? `${money(service.price)} تومان` : "رایگان"}</small>
            </label>;
          })}
        </div>
      )}
      {!loading && chosen.length !== 7 && <p className="inq-seven-warning">برای فعال‌شدن بسته در سایت باید دقیقاً هفت سرویس فعال انتخاب و تنظیمات ذخیره شود.</p>}
      {!loading && services.length < 7 && <p className="inq-seven-warning">سرویس فعال کافی وجود ندارد؛ ابتدا سرویس‌ها را از بخش مدیریت خدمات ایجاد یا فعال کنید.</p>}
      <div className="inq-seven-footer">
        <div><strong>مجموع تعرفه فعلی: {money(services.filter(s => chosen.includes(s.id)).reduce((sum, s) => sum + Number(s.price || 0), 0))} تومان</strong><span>قیمت‌ها در زمان ثبت درخواست از سرور دوباره محاسبه می‌شوند.</span></div>
        <Button color="primary" disabled={loading || saving || chosen.length !== 7} onClick={save}>{saving ? "در حال ذخیره..." : "ذخیره تمامی تنظیمات و هفت سرویس"}</Button>
      </div>
    </CardBody></Card></Colxx></Row>
  </div>;
};
export default InquirySettings;

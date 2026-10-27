import React, { useEffect, useMemo, useState } from "react";
import axios from "axios";
import {
  Badge, Button, Card, CardBody, FormGroup, Input, Label, Row, Table,
} from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { adminPathApi } from "../../../constants/defaultValues";
import { NotificationManager } from "../../../components/common/react-notifications";

const blankField = (key = "field_1") => ({ key, label: "", type: "text", required: true, placeholder: "", help: "", options: [] });
const nextFieldKey = (schema = []) => { let i = 1; while (schema.some((f) => f.key === `field_${i}`)) i += 1; return `field_${i}`; };
const blank = {
  id: null, title: "", slug: "", category: "", icon: "", accent: "#0984e3", short_description: "", description: "",
  price: 0, partner_payout: 0, execution_type: "manual", partner_handler_type: "", estimated_minutes: 30, enabled: true, featured: false, sort_order: 0,
  form_schema: [blankField("field_1")],
  api_config: { url: "", method: "POST", timeout: 25, result_path: "", success_path: "", success_value: "", result_title: "", headers: {}, static_payload: {} },
};
const clone = (x) => JSON.parse(JSON.stringify(x));
const money = (v) => new Intl.NumberFormat("fa-IR").format(Number(v || 0));

const InquiryServices = ({ match }) => {
  const [items, setItems] = useState([]);
  const [form, setForm] = useState(clone(blank));
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [headersText, setHeadersText] = useState("");
  const [payloadText, setPayloadText] = useState("{}");

  const load = () => {
    setLoading(true);
    axios.get(`${adminPathApi}/inquiries/services`, { params: { search } })
      .then((r) => setItems(r.data.data || []))
      .finally(() => setLoading(false));
  };
  useEffect(() => { load(); }, []);

  const reset = () => { setForm(clone(blank)); setHeadersText(""); setPayloadText("{}"); window.scrollTo({ top: 0, behavior: "smooth" }); };
  const headersToText = (h) => Object.entries(h || {}).map(([k, v]) => `${k}: ${v}`).join("\n");
  const textToHeaders = (text) => text.split("\n").reduce((acc, row) => { const i = row.indexOf(":"); if (i > 0) acc[row.slice(0, i).trim()] = row.slice(i + 1).trim(); return acc; }, {});

  const edit = (id) => axios.get(`${adminPathApi}/inquiries/services/${id}`).then((r) => {
    const item = { ...clone(blank), ...r.data.data, api_config: { ...blank.api_config, ...(r.data.data.api_config || {}) } };
    item.form_schema = item.form_schema?.length ? item.form_schema : [blankField("field_1")];
    setForm(item);
    setHeadersText(headersToText(item.api_config.headers));
    setPayloadText(JSON.stringify(item.api_config.static_payload || {}, null, 2));
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  const updateField = (index, key, value) => setForm((old) => ({ ...old, form_schema: old.form_schema.map((f, i) => i === index ? { ...f, [key]: value } : f) }));
  const addField = () => setForm((old) => ({ ...old, form_schema: [...old.form_schema, blankField(nextFieldKey(old.form_schema))] }));
  const removeField = (i) => setForm((old) => ({ ...old, form_schema: old.form_schema.filter((_, x) => x !== i) }));

  const save = () => {
    let staticPayload = {};
    try { staticPayload = payloadText.trim() ? JSON.parse(payloadText) : {}; }
    catch (e) { return NotificationManager.error("JSON بخش Static Payload معتبر نیست.", null, 2500, null, null, "filled"); }
    if (!form.title) return NotificationManager.warning("عنوان سرویس را وارد کنید.", null, 2000, null, null, "filled");
    if ((form.form_schema || []).some((f) => !f.key || !f.label)) return NotificationManager.warning("کلید و عنوان همه فیلدها را کامل کنید.", null, 2500, null, null, "filled");
    if ((form.form_schema || []).some((f) => !/^[A-Za-z_][A-Za-z0-9_-]*$/.test(String(f.key || "")))) return NotificationManager.warning("کلید فیلد باید با حرف انگلیسی یا _ شروع شود و فقط شامل حروف انگلیسی، عدد، _ یا - باشد.", null, 3200, null, null, "filled");
    if (new Set((form.form_schema || []).map((f) => f.key)).size !== (form.form_schema || []).length) return NotificationManager.warning("کلید فیلدها باید یکتا باشند.", null, 2500, null, null, "filled");
    if (form.execution_type === "manual" && !form.partner_handler_type) return NotificationManager.warning("برای سرویس اپراتوری، گروه همکار مسئول را انتخاب کنید.", null, 2600, null, null, "filled");
    if (form.execution_type === "manual" && Number(form.partner_payout || 0) > Number(form.price || 0)) return NotificationManager.warning("دستمزد همکار نمی‌تواند بیشتر از هزینه سرویس باشد.", null, 2600, null, null, "filled");

    setSaving(true);
    const payload = {
      ...form,
      api_config: form.execution_type === "api" ? { ...form.api_config, headers: textToHeaders(headersText), static_payload: staticPayload } : {},
      form_schema: (form.form_schema || []).map((f) => ({ ...f, options: Array.isArray(f.options) ? f.options : String(f.options || "").split(",").map((x) => x.trim()).filter(Boolean) })),
    };
    const url = form.id ? `${adminPathApi}/inquiries/services/${form.id}` : `${adminPathApi}/inquiries/services`;
    axios.post(url, payload).then((r) => {
      NotificationManager.success(r.data.message, null, 1700, null, null, "filled");
      reset(); load();
    }).catch((e) => { const errors = e.response?.data?.errors || {}; const first = Object.values(errors)[0]; const detail = Array.isArray(first) ? first[0] : first; NotificationManager.error(detail || e.response?.data?.message || "ذخیره سرویس انجام نشد.", null, 3200, null, null, "filled"); }).finally(() => setSaving(false));
  };

  const remove = (id) => {
    if (!window.confirm("این سرویس حذف/غیرفعال شود؟")) return;
    axios.delete(`${adminPathApi}/inquiries/services/${id}`).then((r) => { NotificationManager.success(r.data.message, null, 1700, null, null, "filled"); load(); });
  };

  const serviceCountText = useMemo(() => `${items.length} سرویس در این صفحه`, [items.length]);

  return <div className="spad-inquiry-admin">
    <Row><Colxx xxs="12"><Breadcrumb heading="سرویس‌های استعلام" match={match}/><Separator className="mb-4"/></Colxx></Row>

    <div className="inq-admin-hero mb-4">
      <div><span>مرکز مدیریت استعلامات</span><h2>{form.id ? "ویرایش سرویس استعلام" : "ساخت سرویس استعلام جدید"}</h2><p>هر سرویس فقط به دو روش اجرا می‌شود: API اتوماتیک یا اپراتوری با ارجاع به شبکه همکاران SPAD.</p></div>
      <div className="inq-admin-hero-badge"><strong>{items.length}</strong><span>سرویس</span></div>
    </div>

    <Row>
      <Colxx xxs="12" xl="8" className="mb-4">
        <Card className="inq-card"><CardBody>
          <div className="inq-card-title"><div><span>01</span><div><h3>اطلاعات و نمایش سرویس</h3><p>مشخصات عمومی و نحوه انجام استعلام</p></div></div>{form.id && <Button color="light" size="sm" onClick={reset}>سرویس جدید</Button>}</div>
          <Row>
            <Colxx md="6"><FormGroup><Label>عنوان سرویس *</Label><Input value={form.title} onChange={(e) => setForm({...form,title:e.target.value})} placeholder="مثلاً استعلام سند ملک"/></FormGroup></Colxx>
            <Colxx md="6"><FormGroup><Label>Slug</Label><Input dir="ltr" value={form.slug || ""} onChange={(e) => setForm({...form,slug:e.target.value})} placeholder="auto if empty"/></FormGroup></Colxx>
            <Colxx md="4"><FormGroup><Label>دسته‌بندی</Label><Input value={form.category || ""} onChange={(e) => setForm({...form,category:e.target.value})} placeholder="ثبتی، خودرو، هویتی..."/></FormGroup></Colxx>
            <Colxx md="4"><FormGroup><Label>هزینه (تومان)</Label><Input type="number" min="0" value={form.price} onChange={(e) => setForm({...form,price:Number(e.target.value)})}/></FormGroup></Colxx>
            <Colxx md="4"><FormGroup><Label>زمان تقریبی (دقیقه)</Label><Input type="number" min="0" value={form.estimated_minutes || ""} onChange={(e) => setForm({...form,estimated_minutes:Number(e.target.value)})}/></FormGroup></Colxx>
            <Colxx md="4"><FormGroup><Label>نحوه انجام *</Label><Input type="select" value={form.execution_type} onChange={(e) => setForm({...form,execution_type:e.target.value,partner_handler_type:e.target.value === "api" ? "" : form.partner_handler_type,partner_payout:e.target.value === "api" ? 0 : form.partner_payout})}><option value="api">API اتوماتیک</option><option value="manual">اپراتوری / ارجاع به همکار</option></Input><small className="text-muted">API بدون دخالت اپراتور اجرا می‌شود؛ اپراتوری بعد از پرداخت به پنل همکار ارجاع می‌شود.</small></FormGroup></Colxx>
            {form.execution_type === "manual" && <>
              <Colxx md="4"><FormGroup><Label>گروه همکار مسئول *</Label><Input type="select" value={form.partner_handler_type || ""} onChange={(e) => setForm({...form,partner_handler_type:e.target.value})}><option value="">انتخاب گروه همکار...</option><option value="notary_office">دفاتر اسناد رسمی</option><option value="engineering_organization">دفاتر نظام مهندسی</option></Input><small className="text-muted">خودِ همکار بر اساس تقویم ارجاع اپراتوری انتخاب می‌شود.</small></FormGroup></Colxx>
              <Colxx md="4"><FormGroup><Label>دستمزد همکار (تومان) *</Label><Input type="number" min="0" max={Number(form.price || 0)} value={form.partner_payout || 0} onChange={(e) => setForm({...form,partner_payout:Number(e.target.value)})}/><small className="text-muted">محرمانه؛ فقط مدیریت و پنل همکار می‌بینند. این مبلغ به کاربر نمایش داده نمی‌شود.</small></FormGroup></Colxx>
              <Colxx md="4"><FormGroup><Label>سهم SPAD</Label><Input readOnly className="bg-light" value={`${money(Math.max(0, Number(form.price || 0) - Number(form.partner_payout || 0)))} تومان`}/><small className="text-muted">هزینه سرویس منهای دستمزد همکار.</small></FormGroup></Colxx>
            </>}
            <Colxx md="4"><FormGroup><Label>آیکن (URL اختیاری)</Label><Input dir="ltr" value={form.icon || ""} onChange={(e) => setForm({...form,icon:e.target.value})}/></FormGroup></Colxx>
            <Colxx xxs="12"><FormGroup><Label>توضیح کوتاه</Label><Input value={form.short_description || ""} onChange={(e) => setForm({...form,short_description:e.target.value})}/></FormGroup></Colxx>
            <Colxx xxs="12"><FormGroup><Label>توضیحات کامل</Label><Input type="textarea" rows="3" value={form.description || ""} onChange={(e) => setForm({...form,description:e.target.value})}/></FormGroup></Colxx>
          </Row>
          <div className="inq-switches"><label><input type="checkbox" checked={!!form.enabled} onChange={(e)=>setForm({...form,enabled:e.target.checked})}/> فعال</label><label><input type="checkbox" checked={!!form.featured} onChange={(e)=>setForm({...form,featured:e.target.checked})}/> نمایش ویژه</label></div>
        </CardBody></Card>

        <Card className="inq-card mt-4"><CardBody>
          <div className="inq-card-title"><div><span>02</span><div><h3>فرم اطلاعات کاربر</h3><p>فیلدهای موردنیاز هر سرویس را تعریف کنید</p></div></div><Button outline color="primary" size="sm" onClick={addField}>+ افزودن فیلد</Button></div>
          <div className="inq-field-builder">
            {form.form_schema.map((field,index)=><div className="inq-field-row" key={index}>
              <div className="inq-field-number">{index+1}</div>
              <Input value={field.label} onChange={(e)=>updateField(index,"label",e.target.value)} placeholder="عنوان فیلد"/>
              <Input dir="ltr" value={field.key} onChange={(e)=>updateField(index,"key",e.target.value.replace(/\s+/g,"_").replace(/[^A-Za-z0-9_-]/g,""))} placeholder="field_key"/>
              <Input type="select" value={field.type} onChange={(e)=>updateField(index,"type",e.target.value)}><option value="text">متن</option><option value="number">عدد</option><option value="national_code">کد ملی</option><option value="mobile">موبایل</option><option value="email">ایمیل</option><option value="date">تاریخ</option><option value="select">انتخابی</option><option value="textarea">متن بلند</option></Input>
              <label className="inq-required"><input type="checkbox" checked={!!field.required} onChange={(e)=>updateField(index,"required",e.target.checked)}/> اجباری</label>
              <Button color="light" size="sm" onClick={()=>removeField(index)} disabled={form.form_schema.length===1}>×</Button>
              <Input className="inq-wide" value={field.placeholder || ""} onChange={(e)=>updateField(index,"placeholder",e.target.value)} placeholder="Placeholder / راهنما"/>
              {field.type === "select" && <Input className="inq-wide" value={Array.isArray(field.options)?field.options.join(", "):field.options||""} onChange={(e)=>updateField(index,"options",e.target.value.split(",").map(x=>x.trim()).filter(Boolean))} placeholder="گزینه‌ها با کاما جدا شوند"/>}
            </div>)}
          </div>
        </CardBody></Card>

        {form.execution_type === "api" && <Card className="inq-card mt-4"><CardBody>
          <div className="inq-card-title"><div><span>03</span><div><h3>اتصال API</h3><p>این اطلاعات فقط سمت سرور نگهداری می‌شوند و به کاربر نمایش داده نمی‌شوند</p></div></div><Badge color="success">Encrypted</Badge></div>
          <Row>
            <Colxx md="8"><FormGroup><Label>Endpoint URL *</Label><Input dir="ltr" value={form.api_config.url || ""} onChange={(e)=>setForm({...form,api_config:{...form.api_config,url:e.target.value}})} placeholder="https://api.example.com/check"/></FormGroup></Colxx>
            <Colxx md="2"><FormGroup><Label>Method</Label><Input type="select" value={form.api_config.method || "POST"} onChange={(e)=>setForm({...form,api_config:{...form.api_config,method:e.target.value}})}><option>POST</option><option>GET</option><option>PUT</option><option>PATCH</option></Input></FormGroup></Colxx>
            <Colxx md="2"><FormGroup><Label>Timeout</Label><Input type="number" value={form.api_config.timeout || 25} onChange={(e)=>setForm({...form,api_config:{...form.api_config,timeout:Number(e.target.value)}})}/></FormGroup></Colxx>
            <Colxx md="6"><FormGroup><Label>Result Path</Label><Input dir="ltr" value={form.api_config.result_path || ""} onChange={(e)=>setForm({...form,api_config:{...form.api_config,result_path:e.target.value}})} placeholder="data.result"/></FormGroup></Colxx>
            <Colxx md="3"><FormGroup><Label>Success Path</Label><Input dir="ltr" value={form.api_config.success_path || ""} onChange={(e)=>setForm({...form,api_config:{...form.api_config,success_path:e.target.value}})} placeholder="success"/></FormGroup></Colxx>
            <Colxx md="3"><FormGroup><Label>Success Value</Label><Input dir="ltr" value={form.api_config.success_value ?? ""} onChange={(e)=>setForm({...form,api_config:{...form.api_config,success_value:e.target.value}})} placeholder="true"/></FormGroup></Colxx>
            <Colxx md="6"><FormGroup><Label>Headers (هر خط Key: Value)</Label><Input type="textarea" rows="5" dir="ltr" value={headersText} onChange={(e)=>setHeadersText(e.target.value)} placeholder={'Authorization: Bearer TOKEN\nX-API-Key: SECRET'}/></FormGroup></Colxx>
            <Colxx md="6"><FormGroup><Label>Static Payload (JSON)</Label><Input type="textarea" rows="5" dir="ltr" value={payloadText} onChange={(e)=>setPayloadText(e.target.value)} placeholder='{"service":"estate"}'/></FormGroup></Colxx>
            <Colxx xxs="12"><FormGroup><Label>متن نتیجه موفق</Label><Input value={form.api_config.result_title || ""} onChange={(e)=>setForm({...form,api_config:{...form.api_config,result_title:e.target.value}})} placeholder="نتیجه استعلام با موفقیت دریافت شد."/></FormGroup></Colxx>
          </Row>
        </CardBody></Card>}

        <div className="inq-save-bar"><div><strong>{form.title || "سرویس جدید"}</strong><span>{form.price ? `${money(form.price)} تومان` : "رایگان"} • {form.execution_type === "api" ? "API اتوماتیک" : form.partner_handler_type === "notary_office" ? "اپراتوری • دفاتر اسناد رسمی" : form.partner_handler_type === "engineering_organization" ? "اپراتوری • دفاتر نظام مهندسی" : "اپراتوری"}{form.execution_type === "manual" ? ` • دستمزد محرمانه همکار: ${money(form.partner_payout || 0)} تومان` : ""}</span></div><Button color="primary" disabled={saving} onClick={save}>{saving ? "در حال ذخیره..." : form.id ? "ذخیره تغییرات" : "ایجاد سرویس"}</Button></div>
      </Colxx>

      <Colxx xxs="12" xl="4" className="mb-4">
        <Card className="inq-card inq-sticky"><CardBody>
          <div className="inq-list-head"><div><h3>سرویس‌های موجود</h3><span>{serviceCountText}</span></div><Badge color="primary">{items.length}</Badge></div>
          <div className="inq-search"><Input value={search} onChange={(e)=>setSearch(e.target.value)} onKeyDown={(e)=>e.key==="Enter"&&load()} placeholder="جستجوی سرویس..."/><Button color="light" onClick={load}>جستجو</Button></div>
          <div className="inq-service-list">
            {loading ? <div className="inq-empty">در حال دریافت...</div> : items.length ? items.map((item)=><div className="inq-service-item" key={item.id}>
              <div className="inq-service-icon">{item.title?.charAt(0)}</div>
              <div className="inq-service-info"><strong>{item.title}</strong><span>{item.execution_type === "api" ? "API اتوماتیک" : item.partner_handler_type === "notary_office" ? "اپراتوری • دفاتر اسناد رسمی" : item.partner_handler_type === "engineering_organization" ? "اپراتوری • دفاتر نظام مهندسی" : "اپراتوری"} • {item.price ? `${money(item.price)} تومان` : "رایگان"}{item.execution_type === "manual" ? ` • سهم همکار: ${money(item.partner_payout || 0)} تومان` : ""} • {item.requests_count || 0} درخواست</span></div>
              <div className="inq-service-actions"><Button outline color="primary" size="sm" onClick={()=>edit(item.id)}>ویرایش</Button><Button outline color="danger" size="sm" onClick={()=>remove(item.id)}>×</Button></div>
            </div>) : <div className="inq-empty">هنوز سرویسی ثبت نشده است.</div>}
          </div>
        </CardBody></Card>
      </Colxx>
    </Row>
  </div>;
};
export default InquiryServices;

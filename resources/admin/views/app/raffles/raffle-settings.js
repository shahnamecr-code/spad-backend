import React, { useEffect, useState } from "react";
import { Badge, Button, Card, CardBody, CardTitle, FormGroup, Input, Label, Row, Table } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { adminPathApi } from "../../../constants/defaultValues";
import { NotificationManager } from "../../../components/common/react-notifications";

const defaultSettings = { page_title: "", page_intro: "", support_phone: "", payment_notice: "", reservation_minutes: 15 };
const blankGateway = { id: null, driver: "zarinpal", title: "زرین‌پال", enabled: false, is_default: false, config: { merchantId: "", mode: "normal", currency: "T" } };

const RaffleSettings = ({ match }) => {
  const [settings, setSettings] = useState(defaultSettings);
  const [gateways, setGateways] = useState([]);
  const [designs, setDesigns] = useState([]);
  const [gateway, setGateway] = useState(blankGateway);
  const [designTitle, setDesignTitle] = useState("");
  const [designImage, setDesignImage] = useState("");
  const [uploading, setUploading] = useState(false);

  const load = () => axios.get(`${adminPathApi}/raffles/settings/all`).then((res) => {
    setSettings({...defaultSettings, ...(res.data.settings || {})});
    setGateways(res.data.gateways || []); setDesigns(res.data.ticket_designs || []);
  });
  useEffect(() => { load(); }, []);

  const saveSettings = () => axios.post(`${adminPathApi}/raffles/settings/save`, settings).then((res) => NotificationManager.success(res.data.message, null, 1500, null, null, "filled"));
  const setGatewayField = (key, value) => setGateway((g) => ({...g, [key]: value}));
  const setConfig = (key, value) => setGateway((g) => ({...g, config: {...(g.config || {}), [key]: value}}));

  const saveGateway = () => {
    const url = gateway.id ? `${adminPathApi}/raffles/gateways/${gateway.id}/save` : `${adminPathApi}/raffles/gateways/save`;
    axios.post(url, gateway).then((res) => {
      NotificationManager.success(res.data.message, null, 1500, null, null, "filled");
      setGateway(blankGateway); load();
    }).catch((e) => NotificationManager.error(e.response?.data?.message || "ذخیره درگاه انجام نشد", null, 2000, null, null, "filled"));
  };

  const editGateway = (g) => setGateway({
    id: g.id, driver: g.driver, title: g.title, enabled: g.enabled, is_default: g.is_default,
    config: {...(g.config || {})}
  });
  const removeGateway = (id) => { if (window.confirm("درگاه حذف شود؟")) axios.delete(`${adminPathApi}/raffles/gateways/${id}`).then(() => load()); };

  const uploadDesign = (e) => {
    const file = e.target.files && e.target.files[0]; if (!file) return;
    const fd = new FormData(); fd.append("file", file); fd.append("type", "ticket"); setUploading(true);
    axios.post(`${adminPathApi}/raffles/upload`, fd, {headers: {"Content-Type": "multipart/form-data"}}).then((res) => { setDesignImage(res.data.url); setUploading(false); }).catch(() => setUploading(false));
  };
  const saveDesign = () => {
    if (!designTitle || !designImage) return NotificationManager.error("عنوان و تصویر طرح بلیط را وارد کنید", null, 1800, null, null, "filled");
    axios.post(`${adminPathApi}/raffles/ticket-designs/save`, {title: designTitle, image: designImage, is_active: designs.length === 0}).then((res) => {
      NotificationManager.success(res.data.message, null, 1500, null, null, "filled"); setDesignTitle(""); setDesignImage(""); load();
    });
  };
  const activateDesign = (d) => axios.post(`${adminPathApi}/raffles/ticket-designs/${d.id}/save`, {title: d.title, image: d.image, is_active: true}).then(() => load());
  const removeDesign = (id) => { if (window.confirm("طرح بلیط حذف شود؟")) axios.delete(`${adminPathApi}/raffles/ticket-designs/${id}`).then(() => load()); };

  const gatewayConfigFields = () => {
    if (gateway.driver === "local") return <div className="alert alert-warning">درگاه Local فقط برای تست روند پرداخت استفاده شود و در محیط واقعی فعال نماند.</div>;
    return <Row>
      <Colxx md="6"><FormGroup><Label>{gateway.driver === "idpay" ? "API Key / Merchant ID" : "Merchant ID"}</Label><Input dir="ltr" value={gateway.config?.merchantId || ""} onChange={(e) => setConfig("merchantId", e.target.value)} placeholder="برای حفظ مقدار قبلی خالی بگذارید" /></FormGroup></Colxx>
      {(gateway.driver === "zarinpal" || gateway.driver === "zibal") && <Colxx md="3"><FormGroup><Label>Mode</Label><Input type="select" value={gateway.config?.mode || "normal"} onChange={(e) => setConfig("mode", e.target.value)}><option value="normal">normal</option>{gateway.driver === "zarinpal" && <option value="sandbox">sandbox</option>}{gateway.driver === "zarinpal" && <option value="zaringate">zaringate</option>}{gateway.driver === "zibal" && <option value="direct">direct</option>}</Input></FormGroup></Colxx>}
      {gateway.driver === "idpay" && <Colxx md="3"><FormGroup><Label>Sandbox</Label><Input type="select" value={String(gateway.config?.sandbox || false)} onChange={(e) => setConfig("sandbox", e.target.value === "true")}><option value="false">خیر</option><option value="true">بله</option></Input></FormGroup></Colxx>}
      <Colxx md="3"><FormGroup><Label>واحد مبلغ</Label><Input type="select" value={gateway.config?.currency || "T"} onChange={(e) => setConfig("currency", e.target.value)}><option value="T">تومان</option><option value="R">ریال</option></Input></FormGroup></Colxx>
    </Row>;
  };

  return <>
    <Row><Colxx xxs="12"><Breadcrumb heading="تنظیمات سیستم قرعه‌کشی" match={match}/><Separator className="mb-4"/></Colxx></Row>
    <Row>
      <Colxx xxs="12" lg="6" className="mb-4"><Card><CardBody><CardTitle>تنظیمات عمومی</CardTitle>
        <FormGroup><Label>عنوان صفحه</Label><Input value={settings.page_title || ""} onChange={(e) => setSettings({...settings, page_title: e.target.value})}/></FormGroup>
        <FormGroup><Label>متن معرفی</Label><Input type="textarea" rows="3" value={settings.page_intro || ""} onChange={(e) => setSettings({...settings, page_intro: e.target.value})}/></FormGroup>
        <FormGroup><Label>شماره پشتیبانی</Label><Input value={settings.support_phone || ""} onChange={(e) => setSettings({...settings, support_phone: e.target.value})}/></FormGroup>
        <FormGroup><Label>متن راهنمای پرداخت</Label><Input type="textarea" rows="3" value={settings.payment_notice || ""} onChange={(e) => setSettings({...settings, payment_notice: e.target.value})}/></FormGroup>
        <FormGroup><Label>مدت رزرو ظرفیت هنگام پرداخت (دقیقه)</Label><Input type="number" min="5" max="120" value={settings.reservation_minutes || 15} onChange={(e) => setSettings({...settings, reservation_minutes: Number(e.target.value)})}/></FormGroup>
        <Button color="primary" onClick={saveSettings}>ذخیره تنظیمات</Button>
      </CardBody></Card></Colxx>

      <Colxx xxs="12" lg="6" className="mb-4"><Card><CardBody><CardTitle>درگاه‌های پرداخت</CardTitle>
        <Row><Colxx md="5"><FormGroup><Label>نوع درگاه</Label><Input type="select" value={gateway.driver} onChange={(e) => setGateway({...blankGateway, driver: e.target.value, title: e.target.options[e.target.selectedIndex].text})}><option value="zarinpal">زرین‌پال</option><option value="idpay">IDPay</option><option value="zibal">زیبال</option><option value="payir">Pay.ir</option><option value="local">درگاه تست داخلی</option></Input></FormGroup></Colxx><Colxx md="7"><FormGroup><Label>عنوان نمایشی</Label><Input value={gateway.title} onChange={(e) => setGatewayField("title", e.target.value)}/></FormGroup></Colxx></Row>
        {gatewayConfigFields()}
        <div className="d-flex mb-3"><label className="ml-4"><input type="checkbox" checked={!!gateway.enabled} onChange={(e) => setGatewayField("enabled", e.target.checked)}/> فعال</label><label><input type="checkbox" checked={!!gateway.is_default} onChange={(e) => setGatewayField("is_default", e.target.checked)}/> درگاه پیش‌فرض</label></div>
        <Button color="primary" onClick={saveGateway}>{gateway.id ? "ویرایش درگاه" : "افزودن درگاه"}</Button>{gateway.id && <Button color="light" className="mr-2" onClick={() => setGateway(blankGateway)}>انصراف</Button>}
        <hr/><Table responsive size="sm"><thead><tr><th>عنوان</th><th>Driver</th><th>وضعیت</th><th></th></tr></thead><tbody>{gateways.map((g) => <tr key={g.id}><td>{g.title}{g.is_default && <Badge color="primary" className="mr-2">پیش‌فرض</Badge>}</td><td>{g.driver}</td><td><Badge color={g.enabled ? "success" : "secondary"}>{g.enabled ? "فعال" : "غیرفعال"}</Badge></td><td><Button size="sm" color="primary" outline onClick={() => editGateway(g)}>ویرایش</Button><Button size="sm" color="danger" outline className="mr-1" onClick={() => removeGateway(g.id)}>حذف</Button></td></tr>)}</tbody></Table>
      </CardBody></Card></Colxx>
    </Row>

    <Row><Colxx xxs="12"><Card><CardBody><CardTitle>طرح بلیط</CardTitle>
      <Row><Colxx md="4"><FormGroup><Label>عنوان طرح</Label><Input value={designTitle} onChange={(e) => setDesignTitle(e.target.value)}/></FormGroup></Colxx><Colxx md="5"><FormGroup><Label>تصویر بلیط</Label><Input type="file" accept="image/*" onChange={uploadDesign}/>{uploading && <small>در حال آپلود...</small>}</FormGroup></Colxx><Colxx md="3" className="d-flex align-items-center"><Button color="primary" onClick={saveDesign} block>افزودن طرح</Button></Colxx></Row>
      {designImage && <img src={designImage} alt="" className="mb-3" style={{maxWidth: 320, borderRadius: 12}}/>}
      <Row>{designs.map((d) => <Colxx md="4" className="mb-3" key={d.id}><Card className={d.is_active ? "border-primary" : ""}><img src={d.image} alt={d.title} style={{width: "100%", height: 150, objectFit: "cover"}}/><CardBody><div className="d-flex justify-content-between"><strong>{d.title}</strong>{d.is_active && <Badge color="success">فعال</Badge>}</div><div className="mt-2">{!d.is_active && <Button size="sm" color="primary" outline onClick={() => activateDesign(d)}>فعال‌سازی</Button>}<Button size="sm" color="danger" outline className="mr-1" onClick={() => removeDesign(d.id)}>حذف</Button></div></CardBody></Card></Colxx>)}</Row>
    </CardBody></Card></Colxx></Row>
  </>;
};
export default RaffleSettings;

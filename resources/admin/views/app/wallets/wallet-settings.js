import React, { useEffect, useState } from "react";
import axios from "axios";
import { Badge, Button, Card, CardBody, FormGroup, Input, Label, Row, Table } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { adminPathApi } from "../../../constants/defaultValues";
import { NotificationManager } from "../../../components/common/react-notifications";

const defaults = { wallet_enabled: true, topup_enabled: true, min_topup: 100000, max_topup: 100000000, wallet_title: "کیف پول SPAD", wallet_intro: "" };
const blankGateway = { id: null, driver: "zarinpal", title: "زرین‌پال", enabled: false, is_default: false, config: { merchantId: "", mode: "normal", currency: "T" } };

const WalletSettings = ({ match }) => {
  const [settings, setSettings] = useState(defaults);
  const [gateways, setGateways] = useState([]);
  const [gateway, setGateway] = useState(blankGateway);

  const load = () => axios.get(`${adminPathApi}/wallets/settings/all`).then((r) => {
    const s = r.data.settings || {};
    setSettings({ ...defaults, ...s, wallet_enabled: String(s.wallet_enabled ?? "1") === "1", topup_enabled: String(s.topup_enabled ?? "1") === "1", min_topup: Number(s.min_topup || defaults.min_topup), max_topup: Number(s.max_topup || defaults.max_topup) });
    setGateways(r.data.gateways || []);
  });
  useEffect(() => { load(); }, []);

  const saveSettings = () => axios.post(`${adminPathApi}/wallets/settings/save`, settings).then((r) => NotificationManager.success(r.data.message, null, 1600, null, null, "filled"));
  const setConfig = (key, value) => setGateway((g) => ({ ...g, config: { ...(g.config || {}), [key]: value } }));
  const saveGateway = () => {
    const url = gateway.id ? `${adminPathApi}/wallets/gateways/${gateway.id}/save` : `${adminPathApi}/wallets/gateways/save`;
    axios.post(url, gateway).then((r) => { NotificationManager.success(r.data.message, null, 1500, null, null, "filled"); setGateway(blankGateway); load(); }).catch((e) => NotificationManager.error(e.response?.data?.message || "ذخیره درگاه انجام نشد", null, 2000, null, null, "filled"));
  };
  const editGateway = (g) => setGateway({ id: g.id, driver: g.driver, title: g.title, enabled: g.enabled, is_default: g.is_default, config: { ...(g.config || {}) } });
  const removeGateway = (id) => { if (window.confirm("درگاه حذف شود؟")) axios.delete(`${adminPathApi}/wallets/gateways/${id}`).then(() => load()); };

  return <>
    <Row><Colxx xxs="12"><Breadcrumb heading="تنظیمات کیف پول" match={match}/><Separator className="mb-4"/></Colxx></Row>
    <Row>
      <Colxx xxs="12" lg="5" className="mb-4"><Card><CardBody><h3>تنظیمات عمومی</h3>
        <FormGroup><Label>عنوان کیف پول</Label><Input value={settings.wallet_title} onChange={(e) => setSettings({...settings, wallet_title: e.target.value})}/></FormGroup>
        <FormGroup><Label>متن معرفی</Label><Input type="textarea" rows="3" value={settings.wallet_intro} onChange={(e) => setSettings({...settings, wallet_intro: e.target.value})}/></FormGroup>
        <Row><Colxx md="6"><FormGroup><Label>حداقل شارژ (تومان)</Label><Input type="number" value={settings.min_topup} onChange={(e) => setSettings({...settings, min_topup: Number(e.target.value)})}/></FormGroup></Colxx><Colxx md="6"><FormGroup><Label>حداکثر شارژ (تومان)</Label><Input type="number" value={settings.max_topup} onChange={(e) => setSettings({...settings, max_topup: Number(e.target.value)})}/></FormGroup></Colxx></Row>
        <div className="d-flex flex-column mb-4" style={{gap: 10}}><label><input type="checkbox" checked={!!settings.wallet_enabled} onChange={(e) => setSettings({...settings, wallet_enabled: e.target.checked})}/> فعال بودن سیستم کیف پول</label><label><input type="checkbox" checked={!!settings.topup_enabled} onChange={(e) => setSettings({...settings, topup_enabled: e.target.checked})}/> امکان شارژ آنلاین توسط کاربر</label></div>
        <Button color="primary" onClick={saveSettings}>ذخیره تنظیمات</Button>
      </CardBody></Card></Colxx>
      <Colxx xxs="12" lg="7" className="mb-4"><Card><CardBody><h3>درگاه‌های شارژ کیف پول</h3>
        <Row><Colxx md="5"><FormGroup><Label>نوع درگاه</Label><Input type="select" value={gateway.driver} onChange={(e) => setGateway({...blankGateway, driver: e.target.value, title: e.target.options[e.target.selectedIndex].text})}><option value="zarinpal">زرین‌پال</option><option value="idpay">IDPay</option><option value="zibal">زیبال</option><option value="payir">Pay.ir</option></Input></FormGroup></Colxx><Colxx md="7"><FormGroup><Label>عنوان نمایشی</Label><Input value={gateway.title} onChange={(e) => setGateway({...gateway, title: e.target.value})}/></FormGroup></Colxx></Row>
        <Row><Colxx md="6"><FormGroup><Label>{gateway.driver === "idpay" ? "API Key / Merchant ID" : "Merchant ID"}</Label><Input dir="ltr" value={gateway.config?.merchantId || ""} onChange={(e) => setConfig("merchantId", e.target.value)} placeholder="برای حفظ مقدار قبلی خالی بگذارید"/></FormGroup></Colxx><Colxx md="3"><FormGroup><Label>واحد</Label><Input type="select" value={gateway.config?.currency || "T"} onChange={(e) => setConfig("currency", e.target.value)}><option value="T">تومان</option><option value="R">ریال</option></Input></FormGroup></Colxx>{gateway.driver === "zarinpal" && <Colxx md="3"><FormGroup><Label>Mode</Label><Input type="select" value={gateway.config?.mode || "normal"} onChange={(e) => setConfig("mode", e.target.value)}><option value="normal">normal</option><option value="sandbox">sandbox</option><option value="zaringate">zaringate</option></Input></FormGroup></Colxx>}</Row>
        <div className="d-flex mb-3" style={{gap: 18}}><label><input type="checkbox" checked={!!gateway.enabled} onChange={(e) => setGateway({...gateway, enabled: e.target.checked})}/> فعال</label><label><input type="checkbox" checked={!!gateway.is_default} onChange={(e) => setGateway({...gateway, is_default: e.target.checked})}/> پیش‌فرض</label></div>
        <Button color="primary" onClick={saveGateway}>{gateway.id ? "ویرایش درگاه" : "افزودن درگاه"}</Button>{gateway.id && <Button color="light" className="mr-2" onClick={() => setGateway(blankGateway)}>انصراف</Button>}
        <hr/><Table responsive size="sm"><thead><tr><th>عنوان</th><th>Driver</th><th>وضعیت</th><th></th></tr></thead><tbody>{gateways.map((g) => <tr key={g.id}><td>{g.title}{g.is_default && <Badge color="primary" className="mr-2">پیش‌فرض</Badge>}</td><td>{g.driver}</td><td><Badge color={g.enabled ? "success" : "secondary"}>{g.enabled ? "فعال" : "غیرفعال"}</Badge></td><td><Button size="sm" outline color="primary" onClick={() => editGateway(g)}>ویرایش</Button><Button size="sm" outline color="danger" className="mr-1" onClick={() => removeGateway(g.id)}>حذف</Button></td></tr>)}</tbody></Table>
      </CardBody></Card></Colxx>
    </Row>
  </>;
};
export default WalletSettings;

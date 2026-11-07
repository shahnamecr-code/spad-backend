import React, { useEffect, useRef, useState } from "react";
import axios from "axios";
import { Badge, Button, Card, CardBody, Input, Label, Row } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { adminPathApi } from "../../../constants/defaultValues";
import { NotificationManager } from "../../../components/common/react-notifications";

const api = adminPathApi + "/virtual-tours";
const VirtualTourEditor = ({ match }) => {
  const id = match.params.id;
  const fileInput = useRef(null);
  const [tour, setTour] = useState(null);
  const [titles, setTitles] = useState({});
  const [busy, setBusy] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [validation, setValidation] = useState(null);

  const updateTour = data => {
    setTour(data);
    setTitles(Object.fromEntries((data?.scenes || []).map(s => [s.id, s.title])));
  };
  const error = e => NotificationManager.error(e.response?.data?.message || "عملیات انجام نشد");
  const load = () => axios.get(api + "/" + id).then(r => updateTour(r.data.data)).catch(error);
  useEffect(() => { load(); }, [id]);

  const request = async (path, body, method = "post", success = "تغییرات ذخیره شد") => {
    setBusy(true);
    try {
      const r = await axios({ url: api + path, method, data: body });
      if (r.data?.data) updateTour(r.data.data);
      else await load();
      NotificationManager.success(success);
      setValidation(null);
    } catch (e) { error(e); }
    finally { setBusy(false); }
  };
  const upload = async files => {
    if (!files || !files.length) return;
    if (files.length > 20) { NotificationManager.error("در هر مرحله حداکثر ۲۰ تصویر انتخاب کنید"); return; }
    const body = new FormData();
    Array.from(files).forEach(file => body.append("images[]", file));
    setUploading(true);
    try {
      const r = await axios.post(api + "/" + id + "/scenes", body, {
        headers: { "Content-Type": "multipart/form-data" }, timeout: 300000
      });
      if (r.data?.data) updateTour(r.data.data);
      else await load();
      NotificationManager.success("تصاویر ۳۶۰ درجه ثبت شدند");
    } catch (e) { error(e); }
    finally {
      setUploading(false);
      if (fileInput.current) fileInput.current.value = "";
    }
  };
  const saveScene = scene => request("/scenes/" + scene.id, { title: titles[scene.id] || scene.title }, "put", "نام فضا ذخیره شد");
  const setStart = scene => request("/" + id, { start_scene_id: scene.id }, "put", "فضای شروع تور ثبت شد");
  const reorder = (index, delta) => {
    const ordered = [...(tour.scenes || [])].sort((a,b) => a.sort_order - b.sort_order || a.id - b.id);
    const target = index + delta;
    if (target < 0 || target >= ordered.length) return;
    [ordered[index], ordered[target]] = [ordered[target], ordered[index]];
    request("/" + id + "/reorder-scenes", { scene_ids: ordered.map(s => s.id) }, "post", "ترتیب فضاها ذخیره شد");
  };
  const deleteScene = scene => {
    if (!window.confirm("فضای «" + scene.title + "» و تمام اتصال‌های آن حذف شود؟")) return;
    request("/scenes/" + scene.id, { force: true }, "delete", "فضای انتخاب‌شده حذف شد");
  };
  const publish = async () => {
    setBusy(true);
    try {
      const r = await axios.get(api + "/" + id + "/validate-publish");
      const check = r.data;
      setValidation(check);
      if (check.errors?.length) { NotificationManager.error(check.errors.join(" - ")); return; }
      if (check.warnings?.length && !window.confirm(check.warnings.join("\n") + "\nتور منتشر شود؟")) return;
      const done = await axios.post(api + "/" + id + "/publish", { accept_warnings: true });
      updateTour(done.data.data);
      NotificationManager.success("تور مجازی ۳۶۰ درجه منتشر شد");
    } catch (e) { error(e); }
    finally { setBusy(false); }
  };

  if (!tour) return <div className="loading"/>;
  const scenes = [...(tour.scenes || [])].sort((a,b) => a.sort_order - b.sort_order || a.id - b.id);
  const ready = scenes.filter(s => s.process_status === "ready" && (s.images?.medium || s.images?.high || s.images?.preview));
  const unavailable = busy || uploading;
  return <div className="spad-vtour-admin vt-editor-page" dir="rtl">
    <Row><Colxx xxs="12"><Breadcrumb heading="استودیو تور مجازی اسپاد" match={match}/><Separator className="mb-3"/></Colxx></Row>
    <section className="vt-manager-hero">
      <div>
        <span className="vt-studio-badge">تور مجازی با عکس‌های ۳۶۰ درجه</span>
        <h2>مدیریت تور ملک {tour.advertisement_id}</h2>
        <p>تصاویر پانورامای ۲ به ۱ را بارگذاری کنید، ترتیب فضاها را مشخص کنید و تور را منتشر کنید. ساخت مدل سه‌بعدی لازم نیست. هر سکانس در بازترین زاویه دید باز می‌شود.</p>
      </div>
      <div className="vt-hero-stat"><strong>{ready.length} / {scenes.length}</strong><span>فضای آماده نمایش</span></div>
    </section>

    <Card className="vt-card mb-4"><CardBody>
      <Label>عنوان تور</Label>
      <Input value={tour.title || ""} onChange={e => setTour({ ...tour, title: e.target.value })}/>
      <div className="vt-hero-actions mt-3">
        <Button color="primary" disabled={unavailable} onClick={() => request("/" + id, { title: tour.title }, "put")}>ذخیره عنوان</Button>
      </div>
    </CardBody></Card>

    <Card className="vt-card mb-4"><CardBody>
      <h3>تصاویر و سکانس‌های ۳۶۰ درجه</h3>
      <p>تصاویر JPG، PNG یا WebP با نسبت ۲ به ۱ را انتخاب کنید. نام هر فایل به‌صورت اولیه نام سکانس می‌شود.</p>
      <input ref={fileInput} type="file" hidden multiple accept="image/jpeg,image/png,image/webp" onChange={e => upload(e.target.files)}/>
      <div className="vt-hero-actions">
        <Button color="primary" disabled={unavailable} onClick={() => fileInput.current?.click()}>{uploading ? "در حال بارگذاری..." : "افزودن تصاویر ۳۶۰ درجه"}</Button>
        <Button outline color="primary" disabled={unavailable || scenes.length < 2 || ready.length !== scenes.length}
          onClick={() => request("/" + id + "/generate-navigation", {}, "post", "مسیرهای جابه‌جایی ایجاد شدند")}>ساخت مسیر بین فضاهای متوالی</Button>
      </div>
      <p className="mt-3">برای هر فضا نام دلخواه ثبت کنید، فضای شروع را تعیین کنید و با کلیدهای بالا و پایین ترتیب را تغییر دهید. موقعیت دقیق فلش‌های جابه‌جایی پس از ایجاد مسیر باید با پلان ملک تطبیق داده شود.</p>
      <div className="row">
        {scenes.map((scene, i) => <div className="col-12 col-xl-6 mb-3" key={scene.id}>
          <div className="border rounded p-3 h-100" style={{background:"rgba(25,55,90,.035)"}}>
            <div style={{display:"flex",gap:12,alignItems:"center"}}>
              {scene.images?.thumbnail || scene.images?.preview ?
                <img src={scene.images.thumbnail || scene.images.preview} alt="" style={{width:110,height:65,objectFit:"cover",borderRadius:9}}/> :
                <div style={{width:110,height:65,display:"grid",placeItems:"center",background:"#e6edf4",borderRadius:9}}>۳۶۰</div>}
              <div>
                <strong>سکانس {i + 1}</strong>
                <div><Badge color={scene.process_status === "ready" ? "success" : scene.process_status === "failed" ? "danger" : "warning"}>
                  {scene.process_status === "ready" ? "آماده" : scene.process_status === "failed" ? "خطای پردازش" : "در حال پردازش"}
                </Badge></div>
              </div>
            </div>
            <Label className="mt-3">نام فضا</Label>
            <div className="d-flex" style={{gap:8}}>
              <Input value={titles[scene.id] ?? scene.title} onChange={e => setTitles({ ...titles, [scene.id]: e.target.value })}/>
              <Button color="primary" outline disabled={unavailable} onClick={() => saveScene(scene)}>ذخیره</Button>
            </div>
            <div className="vt-hero-actions mt-3" style={{flexWrap:"wrap",gap:6}}>
              <Button size="sm" color={tour.start_scene_id === scene.id ? "success" : "secondary"} disabled={unavailable} onClick={() => setStart(scene)}>
                {tour.start_scene_id === scene.id ? "فضای شروع" : "انتخاب به‌عنوان شروع"}
              </Button>
              <Button size="sm" outline color="secondary" disabled={unavailable || i === 0} onClick={() => reorder(i,-1)}>بالاتر</Button>
              <Button size="sm" outline color="secondary" disabled={unavailable || i === scenes.length-1} onClick={() => reorder(i,1)}>پایین‌تر</Button>
              {scene.process_status !== "ready" && <Button size="sm" outline color="warning" disabled={unavailable}
                onClick={() => request("/scenes/" + scene.id + "/reprocess", {}, "post", "پردازش مجدد درخواست شد")}>پردازش مجدد</Button>}
              <Button size="sm" outline color="danger" disabled={unavailable} onClick={() => deleteScene(scene)}>حذف</Button>
            </div>
            {scene.process_error && <div className="alert alert-warning mt-2">{scene.process_error}</div>}
          </div>
        </div>)}
      </div>
      {!scenes.length && <div className="alert alert-info mt-3">هنوز تصویری برای این تور ثبت نشده است.</div>}
    </CardBody></Card>

    <Card className="vt-card mb-4"><CardBody>
      <h3>انتشار تور مجازی</h3>
      <p>حداقل یک تصویر پانورامای پردازش‌شده لازم است. هنگام ورود به هر سکانس، تصویر به‌صورت خودکار با حداکثر زوم‌اوت مجاز نمایش داده می‌شود.</p>
      <div className="vt-hero-actions">
        {tour.status === "published"
          ? <Button color="warning" disabled={unavailable} onClick={() => request("/" + id + "/unpublish", {}, "post", "انتشار متوقف شد")}>توقف انتشار</Button>
          : <Button color="success" disabled={unavailable || !ready.length} onClick={publish}>بررسی و انتشار</Button>}
        {tour.status === "published" && <Button outline color="primary" onClick={() => window.open(tour.public_url, "_blank", "noopener,noreferrer")}>مشاهده تور مجازی</Button>}
        <Badge color={tour.status === "published" ? "success" : "secondary"}>{tour.status === "published" ? "منتشرشده" : "پیش‌نویس"}</Badge>
      </div>
      {validation?.errors?.length > 0 && <div className="alert alert-danger mt-3">{validation.errors.join(" - ")}</div>}
      {validation?.warnings?.length > 0 && <div className="alert alert-warning mt-3">{validation.warnings.join(" - ")}</div>}
    </CardBody></Card>
  </div>;
};
export default VirtualTourEditor;

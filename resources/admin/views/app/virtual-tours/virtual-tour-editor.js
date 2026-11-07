import React, { useEffect, useRef, useState } from "react";
import axios from "axios";
import { Badge, Button, Card, CardBody, Input, Label, Row } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { adminPathApi } from "../../../constants/defaultValues";
import { NotificationManager } from "../../../components/common/react-notifications";

const Studio3d = ({match}) => {
  const id = match.params.id;
  const fileInput = useRef(null);
  const [tour,setTour] = useState(null);
  const [busy,setBusy] = useState(false);
  const [uploading,setUploading] = useState(false);
  const [validation,setValidation] = useState(null);
  const load = () => axios.get(adminPathApi + "/virtual-tours/" + id)
    .then(r => setTour(r.data.data))
    .catch(e => NotificationManager.error(e.response?.data?.message || "دریافت اطلاعات تور انجام نشد"));
  useEffect(() => {load()},[id]);

  const apply = (url, body, method = "post", doneMessage = "تغییرات ذخیره شد") => {
    setBusy(true);
    return axios({url:adminPathApi + "/virtual-tours/" + id + url,method,data:body})
      .then(r => {if(r.data?.data)setTour(r.data.data);NotificationManager.success(doneMessage);})
      .catch(e => NotificationManager.error(e.response?.data?.message || "عملیات انجام نشد"))
      .finally(() => setBusy(false));
  };
  const save = () => apply("", {title:tour.title,settings:tour.settings}, "put");
  const upload = files => {
    const file=files?.[0];
    if (!file) return;
    if (!file.name.toLowerCase().endsWith(".glb")) {NotificationManager.error("فقط فایل GLB انتخاب کنید");return}
    if (file.size > 200*1024*1024) {NotificationManager.error("حجم فایل باید کمتر از ۲۰۰ مگابایت باشد");return}
    const body = new FormData();body.append("model",file);
    setUploading(true);
    axios.post(adminPathApi + "/virtual-tours/" + id + "/model",body, {
      headers:{"Content-Type":"multipart/form-data"},timeout:300000
    }).then(r=>{setTour(r.data.data);setValidation(null);NotificationManager.success("مدل سه‌بعدی بارگذاری شد")})
      .catch(e=>NotificationManager.error(e.response?.data?.message || "بارگذاری مدل ناموفق بود"))
      .finally(()=>{setUploading(false);if(fileInput.current)fileInput.current.value=""});
  };
  const remove = () => {
    if (!window.confirm("مدل فعلی حذف شود؟ تصاویر قدیمی تور محفوظ می‌مانند.")) return;
    apply("/model",null,"delete","مدل حذف شد");
  };
  const publish=async()=>{
    setBusy(true);
    try {
      const response=await axios.get(adminPathApi + "/virtual-tours/" + id + "/validate-publish");
      const v=response.data;setValidation(v);
      if (v.errors?.length) {NotificationManager.error(v.errors.join(" - "));return}
      if (v.warnings?.length && !window.confirm(v.warnings.join("\n") + "\nبا این وضعیت منتشر شود؟")) return;
      const r=await axios.post(adminPathApi + "/virtual-tours/" + id + "/publish",{accept_warnings:true});
      setTour(r.data.data);NotificationManager.success("بازدید سه‌بعدی منتشر شد");
    } catch (e) { NotificationManager.error(e.response?.data?.message || "انتشار انجام نشد"); }
    finally {setBusy(false)}
  };
  if(!tour)return <div className="loading"/>;
  const settings=tour.settings || {};
  const setValue=(key,value)=>setTour({...tour,settings:{...settings,[key]:value}});
  return <div className="spad-vtour-admin vt-editor-page" dir="rtl">
    <Row><Colxx xxs="12"><Breadcrumb heading="استودیو بازدید سه‌بعدی اسپاد" match={match}/><Separator className="mb-3"/></Colxx></Row>
    <section className="vt-manager-hero">
      <div><span className="vt-studio-badge">موتور نمایش سه‌بعدی اسپاد</span><h2>مدیریت مدل واقعی ملک {tour.advertisement_id}</h2>
        <p>برای حرکت آزاد بازدیدکننده، مدل سه‌بعدی دقیق ساختمان را با فرمت جی‌ال‌بی بارگذاری کنید. تصاویر و داده‌های قدیمی همچنان در آرشیو باقی می‌مانند.</p></div>
      <div className="vt-hero-stat"><strong>{tour.model_url?"آماده":"فاقد مدل"}</strong><span>وضعیت مدل سه‌بعدی</span></div>
    </section>
    <Card className="vt-card mb-4"><CardBody>
      <Label>عنوان تور</Label><Input value={tour.title||""} onChange={e=>setTour({...tour,title:e.target.value})}/>
      <div className="mt-4 mb-3"><h3>فایل مدل سه‌بعدی</h3><p>فایل استاندارد مدل سه‌بعدی باید دارای کف، دیوارها، سقف و راه‌های عبور واقعی باشد. واحد اندازه‌گیری پیشنهادی: متر.</p></div>
      <input ref={fileInput} type="file" hidden accept=".glb,model/gltf-binary" onChange={e=>upload(e.target.files)}/>
      <div className="vt-hero-actions">
        <Button color="primary" disabled={busy||uploading} onClick={()=>fileInput.current?.click()}>{uploading?"در حال بارگذاری...":tour.model_url?"جایگزینی مدل سه‌بعدی":"بارگذاری مدل سه‌بعدی"}</Button>
        {tour.model_url && tour.status!=="published" && <Button outline color="danger" disabled={busy} onClick={remove}>حذف مدل</Button>}
      </div>
      <div className="mt-3"><Badge color={tour.model_url?"success":"secondary"}>{tour.model_url?"مدل سه‌بعدی روی سرور موجود است":"هنوز مدل واقعی برای ملک ثبت نشده است"}</Badge>
        {tour.model_filename && <p className="mt-2">فایل: {tour.model_filename} | حجم: {Math.round((tour.model_bytes||0)/1048576)} مگابایت</p>}</div>
    </CardBody></Card>
    <Card className="vt-card mb-4"><CardBody>
      <h3>نقطه شروع بازدیدکننده</h3><p>مختصات نقطه ورود و جهت اولیه نگاه را متناسب با مدل تنظیم کنید. ارتفاع چشم معمولاً ۱٫۶۵ متر است.</p>
      <Row>{[
        {key:"spawn_x",title:"موقعیت افقی",fallback:0},
        {key:"spawn_y",title:"ارتفاع چشم",fallback:1.65},
        {key:"spawn_z",title:"موقعیت عمقی",fallback:0},
        {key:"heading",title:"زاویه شروع (درجه)",fallback:0}
      ].map(entry=><Colxx key={entry.key} xxs="12" sm="6" lg="3"><Label>{entry.title}</Label>
        <Input type="number" step="0.1" value={settings[entry.key] ?? entry.fallback} onChange={e=>setValue(entry.key,Number(e.target.value))}/></Colxx>)}</Row>
      <div className="vt-hero-actions mt-4"><Button color="primary" disabled={busy} onClick={save}>ذخیره عنوان و موقعیت شروع</Button></div>
    </CardBody></Card>
    <Card className="vt-card mb-4"><CardBody>
      <h3>انتشار بازدید سه‌بعدی</h3>
      <p>پس از بارگذاری مدل واقعی و تعیین موقعیت شروع، انتشار باعث نمایش دکمه بازدید سه‌بعدی در صفحه ملک می‌شود.</p>
      <div className="vt-hero-actions">
        {tour.status==="published"
          ? <Button color="warning" disabled={busy} onClick={()=>apply("/unpublish",{},"post","انتشار متوقف شد")}>توقف انتشار</Button>
          : <Button color="success" disabled={busy||!tour.model_url} onClick={publish}>بررسی و انتشار</Button>}
        {tour.status==="published" && tour.model_url && <Button outline color="primary" onClick={()=>window.open(tour.public_url,"_blank","noopener,noreferrer")}>مشاهده بازدید سه‌بعدی</Button>}
        <Badge color={tour.status==="published"?"success":"secondary"}>{tour.status==="published"?"منتشرشده":"پیش‌نویس"}</Badge>
      </div>
      {validation?.errors?.length>0 && <div className="alert alert-danger mt-3">{validation.errors.join(" - ")}</div>}
      {validation?.warnings?.length>0 && <div className="alert alert-warning mt-3">{validation.warnings.join(" - ")}</div>}
    </CardBody></Card>
  </div>;
};
export default Studio3d;

import React, { useEffect, useMemo, useState } from "react";
import { Button, Card, CardBody, CardTitle, FormGroup, Input, Label, Row } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { adminPathApi, adminRoot } from "../../../constants/defaultValues";
import { NotificationManager } from "../../../components/common/react-notifications";

const blank = {
  advertisement_id: "", slug: "", title: "", subtitle: "", description: "", terms: "",
  start_at: "", end_at: "", draw_at: "", capacity: 100, ticket_price: 0,
  max_tickets_per_user: 1, status: "draft", hero_image: "", panorama_image: "",
  virtual_tour_url: "", live_enabled: false, live_url: "", live_start_at: "",
  highlights: [], ticket_design_id: ""
};

const toLocalInput = (value) => value ? String(value).replace(" ", "T").slice(0, 16) : "";

const RaffleForm = ({ match, history, edit = false }) => {
  const [form, setForm] = useState(blank);
  const [ads, setAds] = useState([]);
  const [designs, setDesigns] = useState([]);
  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState("");
  const id = match.params.id;

  useEffect(() => {
    axios.get(`${adminPathApi}/raffles/advertisements`).then((res) => setAds(res.data.data || []));
    axios.get(`${adminPathApi}/raffles/settings/all`).then((res) => setDesigns(res.data.ticket_designs || []));
    if (edit) {
      axios.get(`${adminPathApi}/raffles/${id}`).then((res) => {
        const d = res.data.data || {};
        setForm({
          ...blank, ...d,
          advertisement_id: d.advertisement_id || "",
          ticket_design_id: d.ticket_design_id || "",
          start_at: toLocalInput(d.start_at), end_at: toLocalInput(d.end_at),
          draw_at: toLocalInput(d.draw_at), live_start_at: toLocalInput(d.live_start_at),
          highlights: d.highlights || []
        });
      });
    }
  }, [id, edit]);

  const selectedAd = useMemo(() => ads.find((x) => String(x.id) === String(form.advertisement_id)), [ads, form.advertisement_id]);
  const set = (key, value) => setForm((old) => ({...old, [key]: value}));

  const upload = (e, type, target) => {
    const file = e.target.files && e.target.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append("file", file); fd.append("type", type);
    setUploading(target);
    axios.post(`${adminPathApi}/raffles/upload`, fd, {headers: {"Content-Type": "multipart/form-data"}})
      .then((res) => set(target, res.data.url))
      .then(() => setUploading(""))
      .catch(() => { setUploading(""); NotificationManager.error("آپلود فایل انجام نشد", null, 1800, null, null, "filled"); });
  };

  const submit = (e) => {
    e.preventDefault(); setSaving(true);
    const url = edit ? `${adminPathApi}/raffles/${id}/edit` : `${adminPathApi}/raffles/create`;
    axios.post(url, form).then((res) => {
      NotificationManager.success(res.data.message, null, 1800, null, null, "filled");
      history.push(`${adminRoot}/raffles/list`);
    }).catch((error) => {
      setSaving(false);
      const errors = error.response?.data?.errors;
      const first = errors ? Object.values(errors)[0]?.[0] : error.response?.data?.message;
      NotificationManager.error(first || "ذخیره انجام نشد", null, 2500, null, null, "filled");
    });
  };

  return (
    <>
      <Row><Colxx xxs="12"><Breadcrumb heading={edit ? "ویرایش قرعه‌کشی ملک" : "افزودن قرعه‌کشی ملک"} match={match}/><Separator className="mb-4"/></Colxx></Row>
      <form onSubmit={submit}>
        <Row>
          <Colxx xxs="12" lg="8">
            <Card className="mb-4"><CardBody>
              <CardTitle>ملک و معرفی دوره</CardTitle>
              <FormGroup><Label>ملک</Label><Input type="select" value={form.advertisement_id} onChange={(e) => set("advertisement_id", e.target.value)} required>
                <option value="">انتخاب ملک تاییدشده</option>{ads.map((ad) => <option key={ad.id} value={ad.id}>#{ad.id} - {ad.title} - {ad.location}</option>)}
              </Input></FormGroup>
              {selectedAd && <div className="alert alert-info py-2">ملک انتخابی: {selectedAd.title} / {selectedAd.category}</div>}
              <Row>
                <Colxx md="8"><FormGroup><Label>عنوان قرعه‌کشی</Label><Input value={form.title} onChange={(e) => set("title", e.target.value)} required /></FormGroup></Colxx>
                <Colxx md="4"><FormGroup><Label>Slug انگلیسی</Label><Input dir="ltr" value={form.slug} onChange={(e) => set("slug", e.target.value)} required placeholder="villa-shiraz-01" /></FormGroup></Colxx>
              </Row>
              <FormGroup><Label>زیرعنوان کوتاه</Label><Input value={form.subtitle || ""} onChange={(e) => set("subtitle", e.target.value)} /></FormGroup>
              <FormGroup><Label>توضیحات جذاب قرعه‌کشی</Label><Input type="textarea" rows="5" value={form.description || ""} onChange={(e) => set("description", e.target.value)} /></FormGroup>
              <FormGroup><Label>نکات شاخص (هر خط یک مورد)</Label><Input type="textarea" rows="4" value={(form.highlights || []).join("\n")} onChange={(e) => set("highlights", e.target.value.split("\n"))} /></FormGroup>
              <FormGroup><Label>قوانین و شرایط</Label><Input type="textarea" rows="5" value={form.terms || ""} onChange={(e) => set("terms", e.target.value)} /></FormGroup>
            </CardBody></Card>

            <Card className="mb-4"><CardBody>
              <CardTitle>زمان، ظرفیت و بلیط</CardTitle>
              <Row>
                <Colxx md="4"><FormGroup><Label>شروع ثبت‌نام</Label><Input type="datetime-local" value={form.start_at || ""} onChange={(e) => set("start_at", e.target.value)} required /></FormGroup></Colxx>
                <Colxx md="4"><FormGroup><Label>پایان ثبت‌نام</Label><Input type="datetime-local" value={form.end_at || ""} onChange={(e) => set("end_at", e.target.value)} required /></FormGroup></Colxx>
                <Colxx md="4"><FormGroup><Label>زمان قرعه‌کشی</Label><Input type="datetime-local" value={form.draw_at || ""} onChange={(e) => set("draw_at", e.target.value)} /></FormGroup></Colxx>
                <Colxx md="4"><FormGroup><Label>ظرفیت کل</Label><Input type="number" min="1" value={form.capacity} onChange={(e) => set("capacity", Number(e.target.value))} required /></FormGroup></Colxx>
                <Colxx md="4"><FormGroup><Label>قیمت هر بلیط (تومان)</Label><Input type="number" min="0" value={form.ticket_price} onChange={(e) => set("ticket_price", Number(e.target.value))} required /></FormGroup></Colxx>
                <Colxx md="4"><FormGroup><Label>حداکثر بلیط هر کاربر</Label><Input type="number" min="1" value={form.max_tickets_per_user} onChange={(e) => set("max_tickets_per_user", Number(e.target.value))} required /></FormGroup></Colxx>
              </Row>
              <FormGroup><Label>وضعیت انتشار</Label><Input type="select" value={form.status} onChange={(e) => set("status", e.target.value)}>
                <option value="draft">پیش‌نویس</option><option value="published">منتشر شده</option><option value="completed">تکمیل شده</option><option value="cancelled">لغو شده</option>
              </Input></FormGroup>
              <FormGroup><Label>طرح بلیط</Label><Input type="select" value={form.ticket_design_id || ""} onChange={(e) => set("ticket_design_id", e.target.value || null)}>
                <option value="">طرح پیش‌فرض</option>{designs.map((d) => <option key={d.id} value={d.id}>{d.title}{d.is_active ? " (فعال)" : ""}</option>)}
              </Input></FormGroup>
            </CardBody></Card>

            <Card className="mb-4"><CardBody>
              <CardTitle>پخش زنده و بازدید ۳۶۰ درجه</CardTitle>
              <FormGroup check className="mb-3"><Label check><Input type="checkbox" checked={!!form.live_enabled} onChange={(e) => set("live_enabled", e.target.checked)} /> فعال‌سازی بخش پخش زنده</Label></FormGroup>
              <Row>
                <Colxx md="8"><FormGroup><Label>لینک پخش زنده / Embed URL</Label><Input dir="ltr" value={form.live_url || ""} onChange={(e) => set("live_url", e.target.value)} placeholder="https://..." /></FormGroup></Colxx>
                <Colxx md="4"><FormGroup><Label>زمان شروع پخش</Label><Input type="datetime-local" value={form.live_start_at || ""} onChange={(e) => set("live_start_at", e.target.value)} /></FormGroup></Colxx>
              </Row>
              <FormGroup><Label>لینک تور مجازی ۳۶۰ (اختیاری)</Label><Input dir="ltr" value={form.virtual_tour_url || ""} onChange={(e) => set("virtual_tour_url", e.target.value)} placeholder="Matterport / Kuula / iframe URL" /></FormGroup>
              <FormGroup><Label>تصویر پانورامای ۳۶۰</Label><Input type="file" accept="image/*" onChange={(e) => upload(e, "panorama", "panorama_image")} />{uploading === "panorama_image" && <small>در حال آپلود...</small>}{form.panorama_image && <img src={form.panorama_image} alt="" className="mt-2" style={{maxWidth: 260, borderRadius: 10}} />}</FormGroup>
            </CardBody></Card>
          </Colxx>

          <Colxx xxs="12" lg="4">
            <Card className="mb-4"><CardBody>
              <CardTitle>تصویر اصلی قرعه‌کشی</CardTitle>
              <Input type="file" accept="image/*" onChange={(e) => upload(e, "hero", "hero_image")} />
              {uploading === "hero_image" && <small>در حال آپلود...</small>}
              {form.hero_image && <img src={form.hero_image} alt="" className="mt-3 w-100" style={{borderRadius: 12, maxHeight: 300, objectFit: "cover"}} />}
              <small className="d-block mt-2 text-muted">اگر تصویر اختصاصی انتخاب نشود، تصویر اصلی همان ملک نمایش داده می‌شود.</small>
            </CardBody></Card>
            <Card className="mb-4"><CardBody>
              <CardTitle>کنترل انتشار</CardTitle>
              <p className="text-muted">قبل از انتشار، تاریخ‌ها، ظرفیت، قیمت و درگاه پرداخت را بررسی کنید.</p>
              <Button color="primary" type="submit" block disabled={saving}>{saving ? "در حال ذخیره..." : "ذخیره قرعه‌کشی"}</Button>
              <Button color="light" type="button" block className="mt-2" onClick={() => history.push(`${adminRoot}/raffles/list`)}>بازگشت</Button>
            </CardBody></Card>
          </Colxx>
        </Row>
      </form>
    </>
  );
};

export default RaffleForm;

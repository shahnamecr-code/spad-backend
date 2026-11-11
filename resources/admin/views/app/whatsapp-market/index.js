import React, {useCallback, useEffect, useMemo, useRef, useState} from "react";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import "./whatsapp-market.scss";

// Personal-only WhatsApp banner sender: hourly gold/FX + property banners to
// the owner's own "message yourself" chat. No Meta / group publishing here.
const endpoint = adminPathApi + "/whatsapp-market";
const HOURS = Array.from({length: 24}, (_, h) => h);
// From the price feed's history: fresh FX/gold quotes usually exist from about
// 11:30 to 21:00 (Sat-Thu). Shown on the day strip as guidance only.
const FX_FRESH_FROM = 12, FX_FRESH_TO = 21;
const defaultSchedule = {hourly_markets: false, hourly_cities: false, hourly_from: 7, hourly_to: 23};
const emptyPersonal = {
 connection: {state: "loading", ready: false, service_online: true, number_masked: "", pairing_available: false},
 preferences: {enabled: false, max_messages_per_day: 12},
 jobs: []
};

const fa = v => String(v).replace(/\d/g, d => "۰۱۲۳۴۵۶۷۸۹"[d]);
const faHour = h => fa(String(h).padStart(2, "0")) + ":۰۰";
const hourOr = (v, fallback) => {
 const n = Number(v);
 return Number.isInteger(n) && n >= 0 && n <= 23 ? n : fallback;
};
const inWindow = (h, from, to) => (from <= to ? h >= from && h <= to : h >= from || h <= to);
const tehranHour = () => {
 try {
  return Number(new Intl.DateTimeFormat("en-US", {timeZone: "Asia/Tehran", hour: "2-digit", hourCycle: "h23"}).format(new Date())) % 24;
 } catch (_) { return new Date().getHours(); }
};
const normSchedule = s => {
 const x = {...defaultSchedule, ...(s || {})};
 return {hourly_markets: !!x.hourly_markets, hourly_cities: !!x.hourly_cities,
  hourly_from: hourOr(x.hourly_from, 7), hourly_to: hourOr(x.hourly_to, 23)};
};
const normCities = list => (list || []).filter(c => c && c.province && c.city)
 .map(c => ({province: String(c.province), city: String(c.city)}));
const formatTime = value => {
 if (!value) return "";
 // Laravel sends "Y-m-d H:i:s" in Tehran time.
 const m = String(value).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
 if (!m) return value;
 try {
  const d = new Date(Date.UTC(+m[1], +m[2] - 1, +m[3], +m[4], +m[5]) - 3.5 * 3600 * 1000);
  return d.toLocaleString("fa-IR", {timeZone: "Asia/Tehran", weekday: "short", hour: "2-digit", minute: "2-digit"});
 } catch (_) { return value; }
};
const placeLabel = c => c.city === c.province ? c.city : c.city + "، " + c.province;
const firstError = (e, fallback) => {
 if (e?.response?.status === 429) return "درخواست‌ها پشت سر هم بود؛ چند لحظه بعد دوباره امتحان کن.";
 const errs = e?.response?.data?.errors;
 const first = errs && typeof errs === "object" ? Object.values(errs).flat()[0] : null;
 return first || e?.response?.data?.message || fallback;
};
const previewErrorText = async error => {
 const status = error?.response?.status;
 if (status === 429) return "پیش‌نمایش چند لحظه محدود شده است؛ کمی بعد دوباره امتحان کن.";
 if (status === 401 || status === 403) return "نشست مدیریت منقضی شده است؛ دوباره وارد پنل شو.";
 const payload = error?.response?.data;
 if (payload instanceof Blob) {
  try { const data = JSON.parse(await payload.text()); if (data?.message) return data.message; } catch (_) {}
 }
 if (payload?.message) return payload.message;
 if (!error?.response) return "ارتباط با سرور برقرار نشد؛ اینترنت را بررسی کن.";
 return "ساخت بنر با خطا روبه‌رو شد (کد " + fa(status) + ").";
};
const jobStatus = j => {
 if (j.status === "accepted_local") return {text: "ارسال شد", tone: "ok"};
 if (j.status === "failed" && j.error_code === "NO_FRESH_PRICE") return {text: "قیمت تازه نبود", tone: "warn"};
 if (j.status === "failed") return {text: "ناموفق", tone: "bad"};
 if (j.status === "expired") return {text: "منقضی شد", tone: "mute"};
 if (j.status === "sending") return {text: "در حال ارسال", tone: "info"};
 return {text: "در صف", tone: "info"};
};

function Switch({checked, onChange, disabled, label, hint, id}) {
 return <label className={"wa2-switch" + (disabled ? " is-disabled" : "")} htmlFor={id}>
  <input id={id} type="checkbox" role="switch" checked={!!checked} disabled={disabled}
   onChange={e => onChange(e.target.checked)}/>
  <span className="wa2-switch-track" aria-hidden="true"><span/></span>
  <span className="wa2-switch-text"><strong>{label}</strong>{hint ? <small>{hint}</small> : null}</span>
 </label>;
}

function DayStrip({schedule, nowHour}) {
 const {hourly_markets: fx, hourly_cities: prop, hourly_from: from, hourly_to: to} = schedule;
 const any = fx || prop;
 return <div className="wa2-strip">
  <ol className="wa2-hours" aria-label={any ? "ارسال ساعتی از " + faHour(from) + " تا " + faHour(to) : "ارسال ساعتی خاموش است"}>
   {HOURS.map(h => {
    const on = any && inWindow(h, from, to);
    return <li key={h} className={(on ? "is-on" : "") + (h === nowHour ? " is-now" : "")}
     aria-hidden="true" title={faHour(h) + (on ? "، ارسال" : "")}>
     <span className="wa2-hour">{fa(h)}</span>
     <span className="wa2-marks">
      {on && fx ? <i className="wa2-mark-fx"/> : null}
      {on && prop ? <i className="wa2-mark-prop"/> : null}
     </span>
    </li>;
   })}
  </ol>
  <ol className="wa2-fresh" aria-hidden="true">
   {HOURS.map(h => <li key={h} className={h >= FX_FRESH_FROM && h <= FX_FRESH_TO ? "is-fresh" : ""}/>)}
  </ol>
  <div className="wa2-legend">
   <span><i className="wa2-mark-fx"/>ارز، طلا و سکه</span>
   <span><i className="wa2-mark-prop"/>قیمت ملک</span>
   <span><i className="wa2-mark-fresh"/>معمولاً قیمت تازه ارز منتشر شده</span>
  </div>
 </div>;
}

export default function WhatsAppMarketAdmin() {
 const [loaded, setLoaded] = useState(false);
 const [loadError, setLoadError] = useState("");
 const [schedule, setSchedule] = useState(defaultSchedule);
 const [cities, setCities] = useState([]);
 const [saved, setSaved] = useState(JSON.stringify({s: defaultSchedule, c: []}));
 const [saveBusy, setSaveBusy] = useState(false);
 const [saveMsg, setSaveMsg] = useState(""), [saveErr, setSaveErr] = useState("");
 const [newProvince, setNewProvince] = useState(""), [newCity, setNewCity] = useState("");

 const [personal, setPersonal] = useState(emptyPersonal);
 const [linkBusy, setLinkBusy] = useState(false);
 const [qr, setQr] = useState("");
 const [linkMsg, setLinkMsg] = useState(""), [linkErr, setLinkErr] = useState("");
 const [autoBusy, setAutoBusy] = useState(false);

 const [nowBusy, setNowBusy] = useState(false);
 const [nowMsg, setNowMsg] = useState(""), [nowErr, setNowErr] = useState("");

 const [pvKind, setPvKind] = useState("market"), [pvCity, setPvCity] = useState(0);
 const [pvImg, setPvImg] = useState(""), [pvBusy, setPvBusy] = useState(false), [pvErr, setPvErr] = useState("");
 const [testBusy, setTestBusy] = useState(false), [testMsg, setTestMsg] = useState("");
 const pvUrl = useRef(""), pvFlight = useRef(false), qrRun = useRef(0), qrActive = useRef(false);

 const [nowHour, setNowHour] = useState(tehranHour);
 useEffect(() => {
  const t = setInterval(() => setNowHour(tehranHour()), 60000);
  return () => clearInterval(t);
 }, []);

 const ready = !!personal.connection?.ready;
 const autoOn = !!personal.preferences?.enabled;
 const dirty = saved !== JSON.stringify({s: schedule, c: cities});

 const applySaved = (s, c) => {
  setSchedule(s); setCities(c); setSaved(JSON.stringify({s, c}));
 };

 const load = useCallback(async () => {
  setLoadError("");
  try {
   const {data} = await axios.get(endpoint);
   const s = normSchedule(data?.data?.schedule), c = normCities(data?.data?.cities);
   setSchedule(s); setCities(c); setSaved(JSON.stringify({s, c}));
  } catch (e) {
   setLoadError(firstError(e, "تنظیمات دریافت نشد؛ صفحه را دوباره باز کن."));
  } finally { setLoaded(true); }
 }, []);

 const refreshPersonal = useCallback(async () => {
  try {
   const {data} = await axios.get(endpoint + "/personal/status");
   setPersonal(data || emptyPersonal);
   return data;
  } catch (e) {
   setPersonal(p => ({...p, connection: {...emptyPersonal.connection, state: "unavailable", service_online: false}}));
   return null;
  }
 }, []);

 useEffect(() => {
  load(); refreshPersonal();
  const t = setInterval(() => { if (!qrActive.current) refreshPersonal(); }, 30000);
  return () => {
   clearInterval(t); qrRun.current++;
   if (pvUrl.current) URL.revokeObjectURL(pvUrl.current);
  };
 }, [load, refreshPersonal]);

 const nextSend = useMemo(() => {
  if (!schedule.hourly_markets && !schedule.hourly_cities) return {text: "هیچ بنری برای ارسال ساعتی روشن نیست."};
  if (!ready) return {text: "واتساپ وصل نیست؛ اول از بخش اتصال وصلش کن."};
  if (!autoOn) return {text: "ارسال خودکار خاموش است؛ از بخش اتصال روشنش کن."};
  for (let i = 1; i <= 24; i++) {
   const h = (nowHour + i) % 24;
   if (!inWindow(h, schedule.hourly_from, schedule.hourly_to)) continue;
   const what = [];
   if (schedule.hourly_markets) what.push(h >= FX_FRESH_FROM && h <= FX_FRESH_TO ? "ارز و طلا" : "ارز و طلا اگر قیمت تازه باشد");
   if (schedule.hourly_cities) cities.forEach(c => what.push(c.city));
   return {text: "ارسال بعدی" + (nowHour + i >= 24 ? " فردا" : "") + " ساعت " + faHour(h), what: what.join("، ")};
  }
  return {text: ""};
 }, [schedule, cities, autoOn, ready, nowHour]);

 const setS = (key, value) => { setSaveMsg(""); setSaveErr(""); setSchedule(s => ({...s, [key]: value})); };

 const addCity = e => {
  e.preventDefault();
  const province = newProvince.trim(), city = newCity.trim();
  setSaveErr(""); setSaveMsg("");
  if (province.length < 2 || city.length < 2) { setSaveErr("نام استان و شهر را کامل بنویس."); return; }
  if (cities.length >= 10) { setSaveErr("حداکثر ۱۰ شهر را می‌توان اضافه کرد."); return; }
  if (cities.some(c => c.province === province && c.city === city)) { setSaveErr("این شهر از قبل در فهرست هست."); return; }
  setCities(list => [...list, {province, city}]); setNewProvince(""); setNewCity("");
 };
 const removeCity = i => {
  setSaveErr(""); setSaveMsg("");
  setCities(list => list.filter((_, k) => k !== i));
  setPvCity(0);
 };

 const save = async () => {
  setSaveErr(""); setSaveMsg("");
  if (!cities.length) { setSaveErr("حداقل یک شهر برای بنر قیمت ملک لازم است."); return; }
  setSaveBusy(true);
  try {
   const {data} = await axios.post(endpoint + "/schedule", {...schedule, cities});
   applySaved(normSchedule(data?.data?.schedule), normCities(data?.data?.cities || cities));
   setSaveMsg(data?.message || "زمان‌بندی ذخیره شد.");
   refreshPersonal();
  } catch (e) {
   setSaveErr(firstError(e, "زمان‌بندی ذخیره نشد؛ دوباره امتحان کن."));
  } finally { setSaveBusy(false); }
 };

 // WhatsApp pairing codes expire after ~20s: keep fetching the current one
 // while it is shown, and stop as soon as the phone is linked.
 const showQr = async () => {
  const run = ++qrRun.current;
  const alive = () => qrRun.current === run;
  const wait = ms => new Promise(r => setTimeout(r, ms));
  qrActive.current = true;
  setLinkBusy(true); setLinkErr(""); setQr("");
  setLinkMsg("در حال آماده‌سازی کد؛ ممکن است تا یک دقیقه طول بکشد.");
  const started = Date.now();
  let deadline = started + 4 * 60 * 1000, shown = false;
  try {
   while (alive() && Date.now() < deadline) {
    let data = null;
    try { ({data} = await axios.get(endpoint + "/personal/qr")); }
    catch (e) { const s = e?.response?.status; if (s !== 429 && s !== 503) throw e; }
    if (!alive()) return;
    if (data?.state === "ready") { setQr(""); setLinkMsg(""); await refreshPersonal(); return; }
    if (data?.image?.startsWith("data:image/png;base64,")) {
     setQr(data.image); shown = true; setLinkBusy(false);
     setLinkMsg("کد را از داخل واتساپ اسکن کن. هر چند ثانیه خودکار تازه می‌شود.");
    } else if (data?.state === "authenticating") {
     deadline = Math.min(started + 12 * 60 * 1000, Math.max(deadline, Date.now() + 3 * 60 * 1000));
     setQr(""); setLinkBusy(true);
     setLinkMsg("اسکن شد. واتساپ در حال همگام‌سازی است و ممکن است چند دقیقه طول بکشد؛ صفحه را نبند و دوباره اسکن نکن.");
    } else if (!shown) {
     setLinkMsg("در حال ساخت کد تازه روی سرور…");
    }
    await wait(data?.image ? 5000 : 3000);
    if (!alive()) return;
    const st = await refreshPersonal();
    if (st?.connection?.ready) { setQr(""); setLinkMsg("وصل شد. حالا ارسال خودکار ساعتی را روشن کن."); return; }
   }
   if (alive()) { setQr(""); setLinkMsg("زمان نمایش کد تمام شد؛ برای کد تازه دوباره بزن."); }
  } catch (e) {
   if (alive()) setLinkErr(firstError(e, "کد QR دریافت نشد؛ کمی بعد دوباره امتحان کن."));
  } finally {
   if (alive()) { setLinkBusy(false); qrActive.current = false; }
  }
 };

 const toggleAuto = async enabled => {
  if (enabled && !window.confirm("ارسال خودکار ساعتی به «پیام به خودم» روشن شود؟ این اتصال از واتساپ وب استفاده می‌کند و ارسال زیاد ممکن است برای حساب محدودیت ایجاد کند.")) return;
  setAutoBusy(true); setLinkErr("");
  try {
   await axios.post(endpoint + "/personal/settings", {enabled});
   await refreshPersonal();
  } catch (e) {
   setLinkErr(firstError(e, "تغییر ارسال خودکار ذخیره نشد."));
  } finally { setAutoBusy(false); }
 };

 const sendNow = async () => {
  if (!ready) return;
  if (!window.confirm("بنر ارز و طلا و بنر قیمت ملک همه شهرهای ذخیره‌شده همین الان به «پیام به خودم» فرستاده شود؟")) return;
  setNowBusy(true); setNowErr(""); setNowMsg("");
  try {
   const {data} = await axios.post(endpoint + "/personal/send-now", {confirmed: true});
   setNowMsg(data?.message || "بنرها در صف ارسال قرار گرفتند.");
   await refreshPersonal();
  } catch (e) {
   setNowErr(e?.response?.status === 429
    ? "همین چند دقیقه پیش ارسال فوری انجام شد؛ کمی بعد دوباره امتحان کن."
    : firstError(e, "ارسال انجام نشد؛ اتصال واتساپ را بررسی کن."));
  } finally { setNowBusy(false); }
 };

 const pvPlace = cities[pvCity] || cities[0];
 const showPreview = async () => {
  if (pvFlight.current) return;
  if (pvKind === "city" && !pvPlace) { setPvErr("اول یک شهر اضافه کن."); return; }
  pvFlight.current = true; setPvBusy(true); setPvErr(""); setTestMsg("");
  const params = {kind: pvKind, ...(pvKind === "city" ? pvPlace : {})};
  try {
   // Banners are built by a background worker; 202 means "still building".
   for (let attempt = 0; attempt < 11; attempt++) {
    const res = await axios.get(endpoint + "/preview", {params, responseType: "blob", timeout: 18000});
    if (res.status === 202) {
     if (attempt === 10) throw new Error(pvKind === "market"
      ? "بنر ارز و طلا ساخته نشد؛ احتمالاً قیمت تازه هنوز منتشر نشده (معمولاً از حدود ۱۱:۳۰)."
      : "ساخت بنر هنوز تمام نشده؛ چند لحظه بعد دوباره امتحان کن.");
     await new Promise(r => setTimeout(r, 2800));
     continue;
    }
    const mime = String(res.headers?.["content-type"] || res.data?.type || "").toLowerCase();
    if (!mime.includes("image/png") || !(res.data instanceof Blob) || res.data.size < 40000) {
     if (mime.includes("text/html")) throw new Error("نشست مدیریت منقضی شده است؛ دوباره وارد پنل شو.");
     throw new Error("پاسخ سرور تصویر معتبر نبود.");
    }
    if (pvUrl.current) URL.revokeObjectURL(pvUrl.current);
    pvUrl.current = URL.createObjectURL(res.data);
    setPvImg(pvUrl.current);
    return;
   }
  } catch (e) {
   setPvErr(e?.message && !e.response ? e.message : await previewErrorText(e));
  } finally { pvFlight.current = false; setPvBusy(false); }
 };

 const sendThis = async () => {
  if (!ready) return;
  setTestBusy(true); setTestMsg(""); setPvErr("");
  try {
   const {data} = await axios.post(endpoint + "/personal/send",
    {kind: pvKind, ...(pvKind === "city" ? pvPlace : {}), confirmed: true});
   setTestMsg(data?.message || "ارسال شد؛ گوشی را بررسی کن.");
   refreshPersonal();
  } catch (e) {
   setPvErr(firstError(e, "این بنر ارسال نشد."));
  } finally { setTestBusy(false); }
 };

 const conn = personal.connection || {};
 const pill = ready
  ? {tone: "ok", text: "وصل به واتساپ"}
  : conn.state === "loading" ? {tone: "mute", text: "در حال بررسی اتصال…"}
  : conn.service_online === false ? {tone: "bad", text: "سرویس اتصال در دسترس نیست"}
  : {tone: "warn", text: "واتساپ وصل نیست"};
 const jobs = (personal.jobs || []).slice(0, 12);

 return <div className="wa2" dir="rtl">
  <header className="wa2-hero">
   <div className="wa2-hero-top">
    <div className="wa2-title">
     <h1>ارسال قیمت‌ها به واتساپ من</h1>
     <p>بنر قیمت ارز و طلا و بنر قیمت ملک، هر ساعت در «پیام به خودم».</p>
    </div>
    <span className={"wa2-pill is-" + pill.tone} role="status">
     <span className="wa2-dot" aria-hidden="true"/>{pill.text}
    </span>
   </div>
   <DayStrip schedule={schedule} nowHour={nowHour}/>
   <div className="wa2-hero-foot">
    <div className="wa2-next">
     <strong>{nextSend.text}{dirty && nextSend.what ? "، پس از ذخیره" : ""}</strong>
     {nextSend.what ? <span>{nextSend.what}</span> : null}
    </div>
    <div className="wa2-now">
     <button type="button" className="wa2-btn-gold" onClick={sendNow} disabled={nowBusy || !ready}>
      {nowBusy ? "در حال ارسال…" : "ارسال همین الان"}
     </button>
     <small>{ready ? "همه بنرها، بیرون از برنامه ساعتی" : "برای ارسال، اول واتساپ را وصل کن"}</small>
    </div>
   </div>
   {nowErr ? <p className="wa2-note is-bad" role="alert">{nowErr}</p> : null}
   {nowMsg ? <p className="wa2-note is-ok" role="status">{nowMsg}</p> : null}
  </header>

  {loadError ? <p className="wa2-note is-bad wa2-page-note" role="alert">{loadError}</p> : null}

  <div className="wa2-layout">
   <section className="wa2-panel wa2-schedule" aria-labelledby="wa2-schedule-h">
    <h2 id="wa2-schedule-h">برنامه ارسال ساعتی</h2>
    {!loaded ? <p className="wa2-muted">در حال دریافت تنظیمات…</p> : <>
     <div className="wa2-switches">
      <Switch id="wa2-fx" checked={schedule.hourly_markets} onChange={v => setS("hourly_markets", v)}
       label="بنر قیمت ارز، طلا و سکه"
       hint="فقط وقتی فرستاده می‌شود که قیمت تازه منتشر شده باشد؛ معمولاً از حدود ۱۱:۳۰ تا ۲۱، به‌جز جمعه‌ها."/>
      <Switch id="wa2-prop" checked={schedule.hourly_cities} onChange={v => setS("hourly_cities", v)}
       label="بنر قیمت ملک"
       hint="برای هر شهر یک بنر، با قیمت هر متر آپارتمان در محله‌ها."/>
     </div>

     <fieldset className="wa2-window">
      <legend>ساعت‌های ارسال</legend>
      <label>از
       <select value={schedule.hourly_from} onChange={e => setS("hourly_from", Number(e.target.value))}>
        {HOURS.map(h => <option key={h} value={h}>{faHour(h)}</option>)}
       </select>
      </label>
      <label>تا
       <select value={schedule.hourly_to} onChange={e => setS("hourly_to", Number(e.target.value))}>
        {HOURS.map(h => <option key={h} value={h}>{faHour(h)}</option>)}
       </select>
      </label>
      <p className="wa2-muted wa2-small">هر ساعت سرِ ساعت، بنرهای روشن با فاصله حدود یک دقیقه از هم فرستاده می‌شوند.</p>
     </fieldset>

     <div className="wa2-cities">
      <h3>شهرهای بنر قیمت ملک</h3>
      {cities.length ? <ul>
       {cities.map((c, i) => <li key={c.province + "|" + c.city}>
        <span>{placeLabel(c)}</span>
        <button type="button" onClick={() => removeCity(i)} aria-label={"حذف " + c.city}>×</button>
       </li>)}
      </ul> : <p className="wa2-muted">هنوز شهری اضافه نشده.</p>}
      <form className="wa2-add" onSubmit={addCity}>
       <input value={newProvince} onChange={e => setNewProvince(e.target.value)} placeholder="استان" aria-label="استان" maxLength={80}/>
       <input value={newCity} onChange={e => setNewCity(e.target.value)} placeholder="شهر" aria-label="شهر" maxLength={80}/>
       <button type="submit" className="wa2-btn-quiet">افزودن</button>
      </form>
     </div>

     <div className="wa2-save">
      <button type="button" className="wa2-btn-ink" onClick={save} disabled={saveBusy || !dirty}>
       {saveBusy ? "در حال ذخیره…" : "ذخیره برنامه"}
      </button>
      {dirty && !saveBusy && !saveErr ? <span className="wa2-muted">تغییرات هنوز ذخیره نشده.</span> : null}
      {saveErr ? <span className="wa2-inline is-bad" role="alert">{saveErr}</span> : null}
      {saveMsg && !dirty ? <span className="wa2-inline is-ok" role="status">{saveMsg}</span> : null}
     </div>
    </>}
   </section>

   <section className="wa2-panel wa2-link" aria-labelledby="wa2-link-h">
    <h2 id="wa2-link-h">اتصال واتساپ</h2>
    {ready ? <>
     <p className="wa2-linked">
      <span className="wa2-dot" aria-hidden="true"/>
      <span>واتساپ {conn.number_masked ? <bdi dir="ltr">{fa(conn.number_masked)}</bdi> : null} وصل است.</span>
     </p>
     <Switch id="wa2-auto" checked={autoOn} disabled={autoBusy} onChange={toggleAuto}
      label="ارسال خودکار ساعتی"
      hint={autoOn ? "بنرها طبق برنامه ارسال، خودکار فرستاده می‌شوند." : "تا روشن نشود، فقط با «ارسال همین الان» بنر می‌رسد."}/>
     <p className="wa2-muted wa2-small">برای قطع اتصال: در گوشی، واتساپ ← دستگاه‌های متصل ← خروج از این دستگاه.</p>
    </> : <>
     <ol className="wa2-steps">
      <li>«نمایش کد QR» را بزن.</li>
      <li>در گوشی: واتساپ ← دستگاه‌های متصل ← اتصال دستگاه.</li>
      <li>کد را یک بار اسکن کن و تا وصل شدن صبر کن.</li>
     </ol>
     <button type="button" className="wa2-btn-ink" onClick={showQr} disabled={linkBusy}>
      {linkBusy ? "لطفاً صبر کن…" : "نمایش کد QR"}
     </button>
     {qr ? <figure className="wa2-qr">
      <img src={qr} alt="کد QR اتصال واتساپ"/>
      <figcaption>این کد محرمانه است؛ از آن عکس نفرست.</figcaption>
     </figure> : null}
    </>}
    {linkMsg ? <p className="wa2-note is-info" role="status">{linkMsg}</p> : null}
    {linkErr ? <p className="wa2-note is-bad" role="alert">{linkErr}</p> : null}
    <p className="wa2-muted wa2-small wa2-fine">این اتصال از واتساپ وب استفاده می‌کند و فقط به گفت‌وگوی «پیام به خودم» می‌فرستد.</p>
   </section>

   <section className="wa2-panel wa2-preview" aria-labelledby="wa2-preview-h">
    <h2 id="wa2-preview-h">پیش‌نمایش بنر</h2>
    <div className="wa2-seg" role="radiogroup" aria-label="انتخاب بنر">
     <button type="button" role="radio" aria-checked={pvKind === "market"} className={pvKind === "market" ? "is-on" : ""}
      onClick={() => { setPvKind("market"); setPvErr(""); }}>ارز و طلا</button>
     {cities.map((c, i) => <button type="button" role="radio" key={c.province + "|" + c.city}
      aria-checked={pvKind === "city" && pvCity === i} className={pvKind === "city" && pvCity === i ? "is-on" : ""}
      onClick={() => { setPvKind("city"); setPvCity(i); setPvErr(""); }}>{c.city}</button>)}
    </div>
    <div className="wa2-preview-actions">
     <button type="button" className="wa2-btn-ink" onClick={showPreview} disabled={pvBusy}>
      {pvBusy ? "در حال ساخت…" : "نمایش بنر"}
     </button>
     <button type="button" className="wa2-btn-quiet" onClick={sendThis} disabled={!ready || testBusy}>
      {testBusy ? "در حال ارسال…" : "فقط همین بنر را بفرست"}
     </button>
    </div>
    {pvErr ? <p className="wa2-note is-bad" role="alert">{pvErr}</p> : null}
    {testMsg ? <p className="wa2-note is-ok" role="status">{testMsg}</p> : null}
    {pvImg ? <img className="wa2-poster" src={pvImg} alt="پیش‌نمایش بنر اسپاد"/>
     : <div className="wa2-poster-empty">بنر انتخاب‌شده با قیمت‌های لحظه‌ای این‌جا نمایش داده می‌شود.</div>}
   </section>

   <section className="wa2-panel wa2-history" aria-labelledby="wa2-history-h">
    <div className="wa2-history-head">
     <h2 id="wa2-history-h">ارسال‌های اخیر</h2>
     <button type="button" className="wa2-link-btn" onClick={refreshPersonal}>به‌روزرسانی</button>
    </div>
    {jobs.length ? <ul className="wa2-jobs">
     {jobs.map(j => {
      const st = jobStatus(j);
      return <li key={j.id}>
       <span className="wa2-job-subject">{j.subject}</span>
       <span className="wa2-job-time">{formatTime(j.created_at)}</span>
       <span className={"wa2-chip is-" + st.tone}>{st.text}</span>
      </li>;
     })}
    </ul> : <p className="wa2-muted">هنوز بنری ارسال نشده. با «ارسال همین الان» اولین بنرها را بفرست.</p>}
   </section>
  </div>
 </div>;
}

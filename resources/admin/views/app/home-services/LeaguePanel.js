import React, { useEffect, useRef, useState } from "react";
import { actionError } from "./api";

const dates = {
  submission_starts_at: "آغاز ثبت آثار",
  submission_ends_at: "پایان ثبت آثار",
  voting_starts_at: "آغاز رأی‌گیری",
  voting_ends_at: "پایان رأی‌گیری",
};

const tabs = {
  entries: "آثار",
  votes: "رأی‌ها",
  reports: "گزارش‌ها",
  audit: "سابقهٔ مدیریت",
};

const statuses = {
  upcoming: "آینده",
  active: "در جریان",
  finished: "پایان‌یافته",
  pending: "منتظر بررسی",
  approved: "تأیید شده",
  rejected: "رد شده",
  withdrawn: "انصراف داده",
  resolved: "رسیدگی شده",
  dismissed: "رد گزارش",
};

const auditLabels = {
  contest_created: "ساخت مسابقه",
  contest_updated: "ویرایش مسابقه",
  entry_approved: "تأیید اثر",
  entry_rejected: "رد اثر",
  vote_invalidated: "ابطال رأی",
  report_resolved: "رسیدگی به گزارش",
  report_dismissed: "رد گزارش",
  result_frozen: "نهایی‌کردن نتیجه",
};

const emptyPages = { entries: 1, votes: 1, reports: 1 };
const emptyContest = {
  slug: "",
  title: "",
  theme: "",
  description: "",
  rules: "",
  published: false,
  submission_starts_at: "",
  submission_ends_at: "",
  voting_starts_at: "",
  voting_ends_at: "",
};

const formatNumber = (value) => Number(value).toLocaleString("fa-IR");
const labelStatus = (value) => statuses[value] || value || "—";

function Paging({ number, hasMore, onChange, disabled }) {
  if (number === 1 && !hasMore) return null;
  return (
    <div className="d-flex align-items-center flex-wrap mt-3">
      <button type="button" className="btn btn-sm btn-outline-primary ml-2"
        disabled={disabled || number <= 1} onClick={() => onChange(number - 1)}>قبلی</button>
      <span className="ml-2">صفحهٔ {formatNumber(number)}</span>
      <button type="button" className="btn btn-sm btn-outline-primary"
        disabled={disabled || !hasMore} onClick={() => onChange(number + 1)}>بعدی</button>
    </div>
  );
}

function LeaguePhoto({ request, entryId, photoId }) {
  const [visible, setVisible] = useState(false);
  const [source, setSource] = useState("");
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    if (!visible) return undefined;
    let active = true;
    let objectUrl;
    setFailed(false);
    request("get", `/home-league/entries/${entryId}/photos/${photoId}`, undefined, { responseType: "blob" })
      .then((blob) => {
        if (!active) return;
        objectUrl = URL.createObjectURL(blob);
        setSource(objectUrl);
      })
      .catch(() => { if (active) setFailed(true); });
    return () => {
      active = false;
      if (objectUrl) URL.revokeObjectURL(objectUrl);
    };
  }, [request, entryId, photoId, visible]);

  if (!visible) {
    return <button type="button" className="btn btn-sm btn-outline-secondary ml-2 mb-2"
      onClick={() => setVisible(true)}>نمایش تصویر {formatNumber(photoId)}</button>;
  }

  return (
    <div className="mb-2">
      {source ? <img src={source} alt={`تصویر اثر ${formatNumber(entryId)}`}
        className="img-fluid rounded d-block mb-1" style={{ maxHeight: 280 }} /> : (
        <span role="status" className={failed ? "text-danger" : "text-muted"}>
          {failed ? "دریافت تصویر ناموفق بود." : "در حال دریافت تصویر…"}
        </span>
      )}
      <button type="button" className="btn btn-sm btn-link"
        onClick={() => { setVisible(false); setSource(""); }}>بستن تصویر</button>
    </div>
  );
}

export default function LeaguePanel({ request }) {
  const [contests, setContests] = useState([]);
  const [contestPage, setContestPage] = useState(1);
  const [hasMoreContests, setHasMoreContests] = useState(false);
  const [listLoading, setListLoading] = useState(true);
  const [listError, setListError] = useState("");
  const [selected, setSelected] = useState(null);
  const [review, setReview] = useState(null);
  const [reviewLoading, setReviewLoading] = useState(false);
  const [reviewError, setReviewError] = useState("");
  const [page, setPage] = useState(emptyPages);
  const [tab, setTab] = useState("entries");
  const [editing, setEditing] = useState(null);
  const [draft, setDraft] = useState(emptyContest);
  const [action, setAction] = useState(null);
  const [reason, setReason] = useState("");
  const [busy, setBusy] = useState(false);
  const [success, setSuccess] = useState("");
  const [error, setError] = useState("");
  const [refresh, setRefresh] = useState(0);
  const pending = useRef(false);
  const reviewHeading = useRef(null);
  const decisionHeading = useRef(null);

  useEffect(() => {
    let active = true;
    setListLoading(true);
    setListError("");
    request("get", `/home-league/contests?page=${contestPage}`)
      .then((result) => {
        if (!active) return;
        setContests(result.data || []);
        setHasMoreContests(Boolean(result.has_more));
      })
      .catch((err) => { if (active) setListError(actionError(err, "دریافت مسابقه‌ها ناموفق بود.")); })
      .finally(() => { if (active) setListLoading(false); });
    return () => { active = false; };
  }, [request, contestPage, refresh]);

  useEffect(() => {
    if (selected === null) return undefined;
    let active = true;
    setReviewLoading(true);
    setReviewError("");
    const query = Object.keys(emptyPages).map((key) => `${key}_page=${page[key]}`).join("&");
    request("get", `/home-league/contests/${selected}/review?${query}`)
      .then((result) => { if (active) setReview(result.data); })
      .catch((err) => { if (active) setReviewError(actionError(err, "دریافت جزئیات مسابقه ناموفق بود.")); })
      .finally(() => { if (active) setReviewLoading(false); });
    return () => { active = false; };
  }, [request, selected, page, refresh]);

  useEffect(() => {
    if (selected !== null && reviewHeading.current) reviewHeading.current.focus();
  }, [selected]);

  useEffect(() => {
    if (action && decisionHeading.current) decisionHeading.current.focus();
  }, [action]);

  function selectContest(id) {
    if (id === selected) return;
    setReview(null);
    setSelected(id);
    setPage(emptyPages);
    setTab("entries");
    setAction(null);
  }

  function openEditor(contest = null) {
    const values = { ...emptyContest };
    if (contest) {
      Object.keys(values).forEach((key) => {
        if (key in dates) values[key] = String(contest[key] || "").slice(0, 19);
        else values[key] = contest[key];
      });
    }
    setEditing(contest ? contest.id : "new");
    setDraft(values);
    setAction(null);
    setError("");
    setSuccess("");
  }

  async function change(method, path, payload, fallback, onSuccess) {
    if (pending.current) return;
    pending.current = true;
    setError("");
    setSuccess("");
    setBusy(true);
    try {
      const result = await request(method, path, payload);
      setSuccess(result.message || fallback);
      onSuccess(result);
      setRefresh((value) => value + 1);
    } catch (err) {
      setError(actionError(err, "ذخیرهٔ تغییر ناموفق بود."));
    } finally {
      pending.current = false;
      setBusy(false);
    }
  }

  function saveContest(event) {
    event.preventDefault();
    const payload = { ...draft };
    ["slug", "title", "theme", "description", "rules"].forEach((key) => { payload[key] = payload[key].trim(); });
    const isNew = editing === "new";
    change(isNew ? "post" : "put", isNew ? "/home-league/contests" : `/home-league/contests/${editing}`,
      payload, "مسابقه ذخیره شد.", (result) => {
        setEditing(null);
        if (isNew) setContestPage(1);
        if (result.data && result.data.id) selectContest(result.data.id);
      });
  }

  function openAction(value) {
    setReason("");
    setAction(value);
    setError("");
    setSuccess("");
  }

  function submitAction(event) {
    event.preventDefault();
    const note = reason.trim();
    if (note.length < (action.minimum || 0)) {
      setError("دلیل واردشده کافی نیست.");
      return;
    }
    const body = {
      ...(action.status ? { status: action.status } : {}),
      ...(action.revision != null ? { revision: action.revision } : {}),
      ...(action.freeze ? {} : { reason: note || null }),
    };
    change("post", action.path, body, "تغییر ذخیره شد.", () => setAction(null));
  }

  const updateDraft = (key, value) => setDraft((old) => ({ ...old, [key]: value }));
  const current = review && review.contest;
  const frozen = Boolean(current && current.frozen_at);
  const list = (review && review[tab]) || [];

  return (
    <section className="mt-3" aria-label="مدیریت لیگ خانه">
      <div className="card mb-3">
        <div className="card-body">
          <div className="d-flex align-items-center justify-content-between flex-wrap mb-3">
            <h2 className="h5 mb-2">مسابقه‌های لیگ خانه</h2>
            <div className="mb-2">
              <button type="button" className="btn btn-sm btn-outline-primary ml-2"
                disabled={busy || listLoading} onClick={() => setRefresh((value) => value + 1)}>به‌روزرسانی</button>
              <button type="button" className="btn btn-sm btn-primary"
                disabled={busy} onClick={() => openEditor()}>مسابقهٔ جدید</button>
            </div>
          </div>
          {listLoading && <p role="status">در حال دریافت مسابقه‌ها…</p>}
          {listError && <p className="text-danger" role="alert">{listError}</p>}
          {!listLoading && !listError && contests.length === 0 && <p className="text-muted">مسابقه‌ای ثبت نشده است.</p>}
          {!listLoading && !listError && contests.map((contest) => (
            <div className="border rounded p-2 mb-2 d-flex align-items-center justify-content-between flex-wrap" key={contest.id}>
              <div className="mb-1">
                <strong>{contest.title}</strong>
                <div className="small text-muted">
                  {labelStatus(contest.status)} · {contest.published ? "منتشر شده" : "پیش‌نویس"}
                  {contest.frozen_at ? " · نتیجهٔ نهایی" : ""}
                </div>
              </div>
              <div className="mb-1">
                <button type="button" className="btn btn-sm btn-outline-primary ml-2"
                  aria-pressed={selected === contest.id}
                  disabled={busy} onClick={() => selectContest(contest.id)}>بررسی مسابقه</button>
                <button type="button" className="btn btn-sm btn-outline-secondary"
                  disabled={busy || Boolean(contest.frozen_at)} onClick={() => openEditor(contest)}>ویرایش</button>
              </div>
            </div>
          ))}
          <Paging number={contestPage} hasMore={hasMoreContests} onChange={setContestPage} disabled={busy || listLoading} />
        </div>
      </div>

      {success && <div className="alert alert-success" role="status">{success}</div>}
      {error && <div className="alert alert-danger" role="alert">{error}</div>}

      {editing !== null && (
        <form className="card mb-3" onSubmit={saveContest}>
          <div className="card-body">
            <h3 className="h5">{editing === "new" ? "ایجاد مسابقه" : "ویرایش مسابقه"}</h3>
            <fieldset disabled={busy}>
              <div className="row">
                <label className="col-md-6 mb-3">عنوان
                  <input className="form-control" required minLength={3} maxLength={180} autoFocus
                    value={draft.title} onChange={(event) => updateDraft("title", event.target.value)} />
                </label>
                <label className="col-md-6 mb-3">موضوع
                  <input className="form-control" required minLength={2} maxLength={130}
                    value={draft.theme} onChange={(event) => updateDraft("theme", event.target.value)} />
                </label>
              </div>
              <label className="d-block mb-3">شناسهٔ انگلیسی مسابقه
                <input className="form-control" dir="ltr" required maxLength={100}
                  pattern="[a-z0-9]+(?:-[a-z0-9]+)*" value={draft.slug}
                  onChange={(event) => updateDraft("slug", event.target.value)} />
              </label>
              <label className="d-block mb-3">توضیحات
                <textarea className="form-control" rows={3} required maxLength={5000}
                  value={draft.description} onChange={(event) => updateDraft("description", event.target.value)} />
              </label>
              <label className="d-block mb-3">قوانین
                <textarea className="form-control" rows={4} required maxLength={12000}
                  value={draft.rules} onChange={(event) => updateDraft("rules", event.target.value)} />
              </label>
              <p className="small text-muted">تاریخ‌ها میلادی و ساعت‌ها به وقت تهران هستند.</p>
              <div className="row">
                {Object.entries(dates).map(([key, title]) => (
                  <label className="col-md-6 mb-3" key={key}>{title}
                    <input className="form-control" dir="ltr" type="datetime-local" step="1" required
                      value={draft[key]} onChange={(event) => updateDraft(key, event.target.value)} />
                  </label>
                ))}
              </div>
              <label className="d-block mb-3">
                <input type="checkbox" className="ml-2" checked={draft.published}
                  onChange={(event) => updateDraft("published", event.target.checked)} />
                انتشار عمومی مسابقه
              </label>
              <button type="submit" className="btn btn-primary ml-2">{busy ? "در حال ذخیره…" : "ذخیرهٔ مسابقه"}</button>
              <button type="button" className="btn btn-outline-secondary" onClick={() => setEditing(null)}>انصراف</button>
            </fieldset>
          </div>
        </form>
      )}

      {selected !== null && (
        <div className="card mb-3">
          <div className="card-body">
            <h3 className="h5" ref={reviewHeading} tabIndex={-1}>بررسی مسابقه</h3>
            {reviewLoading && <p role="status">در حال دریافت جزئیات مسابقه…</p>}
            {reviewError && <p className="text-danger" role="alert">{reviewError}</p>}
            {current && !reviewError && (
              <>
                <div className="d-flex align-items-center justify-content-between flex-wrap mb-2">
                  <div>
                    <h4 className="h6 mb-1">{current.title}</h4>
                    <p className="text-muted small">{labelStatus(current.status)}{frozen ? " · نتیجهٔ نهایی" : ""}</p>
                  </div>
                  {!frozen && current.status === "finished" && (
                    <button type="button" className="btn btn-sm btn-outline-primary mb-2"
                      disabled={busy || reviewLoading}
                      onClick={() => openAction({
                        title: "نهایی‌کردن نتیجه",
                        path: `/home-league/contests/${current.id}/freeze`, freeze: true,
                      })}>نهایی‌کردن نتیجه</button>
                  )}
                </div>

                {action && (
                  <form className="border rounded p-3 mb-3" onSubmit={submitAction}>
                    <fieldset disabled={busy}>
                      <h4 className="h6" ref={decisionHeading} tabIndex={-1}>{action.title}</h4>
                      {action.freeze ? <p>پس از نهایی‌کردن، نتیجهٔ رتبه‌بندی ذخیره می‌شود و رأی‌ها و آثار مسابقه قابل تغییر نیستند.</p> : (
                        <label className="d-block mb-2">
                          {action.minimum ? "دلیل تصمیم" : "یادداشت اختیاری"}
                          <textarea className="form-control" rows={3} required={Boolean(action.minimum)}
                            minLength={action.minimum || undefined} maxLength={1000} value={reason}
                            onChange={(event) => setReason(event.target.value)} />
                        </label>
                      )}
                      <button type="submit" className="btn btn-sm btn-primary ml-2">{busy ? "در حال ذخیره…" : "تأیید و ثبت"}</button>
                      <button type="button" className="btn btn-sm btn-outline-secondary"
                        onClick={() => setAction(null)}>انصراف</button>
                    </fieldset>
                  </form>
                )}

                <div className="d-flex flex-wrap mb-3" role="group" aria-label="بخش‌های بررسی مسابقه">
                  {Object.entries(tabs).map(([key, title]) => (
                    <button type="button" key={key} className={`btn btn-sm ml-2 mb-2 ${tab === key ? "btn-primary" : "btn-outline-primary"}`}
                      aria-pressed={tab === key} disabled={busy}
                      onClick={() => { setTab(key); setAction(null); }}>{title}</button>
                  ))}
                </div>
                {!list.length && !reviewLoading && <p className="text-muted">موردی ثبت نشده است.</p>}

                {tab === "entries" && list.map((entry) => (
                  <article className="border rounded p-3 mb-2" key={entry.id}>
                    <h4 className="h6">{entry.title}</h4>
                    <p className="text-muted small mb-2">
                      #{formatNumber(entry.id)} · {labelStatus(entry.status)} · {formatNumber(entry.vote_count)} رأی معتبر
                      {entry.place ? ` · رتبهٔ ${formatNumber(entry.place)}` : ""}
                      {entry.city ? ` · ${entry.city}` : ""}
                      {entry.ai_disclosed ? " · استفادهٔ اعلام‌شده از هوش مصنوعی" : ""}
                    </p>
                    {entry.description && <p className="mb-2 text-break">{entry.description}</p>}
                    {entry.review_reason && <p className="mb-2 text-break"><strong>دلیل بررسی</strong> {entry.review_reason}</p>}
                    {(entry.photos || []).map((photo) => (
                      <LeaguePhoto key={photo.id} request={request} entryId={entry.id} photoId={photo.id} />
                    ))}
                    {!frozen && entry.status !== "withdrawn" && (
                      <div className="mt-2">
                        {entry.status !== "approved" && entry.photos && entry.photos.length > 0 && (
                          <button type="button" className="btn btn-sm btn-outline-success ml-2" disabled={busy || reviewLoading}
                            onClick={() => openAction({
                              title: `تأیید اثر «${entry.title}»`,
                              path: `/home-league/entries/${entry.id}/moderate`, status: "approved", revision: entry.revision,
                            })}>تأیید اثر</button>
                        )}
                        {entry.status !== "rejected" && (
                          <button type="button" className="btn btn-sm btn-outline-danger" disabled={busy || reviewLoading}
                            onClick={() => openAction({
                              title: `رد اثر «${entry.title}»`,
                              path: `/home-league/entries/${entry.id}/moderate`, status: "rejected", revision: entry.revision, minimum: 1,
                            })}>رد اثر</button>
                        )}
                      </div>
                    )}
                  </article>
                ))}

                {tab === "votes" && list.map((vote) => {
                  const valid = vote.valid === true || Number(vote.valid) === 1;
                  return (
                    <article className="border rounded p-3 mb-2" key={vote.id}>
                      <h4 className="h6">رأی {formatNumber(vote.id)}</h4>
                      <p className="mb-2">
                        اثر {formatNumber(vote.entry_id)} · کاربر {formatNumber(vote.user_id)} · {valid ? "معتبر" : "باطل شده"}
                        {vote.vote_date && <time className="mr-2" dir="ltr">{vote.vote_date}</time>}
                      </p>
                      {vote.invalidation_reason && <p className="text-break">{vote.invalidation_reason}</p>}
                      {!frozen && valid && <button type="button" className="btn btn-sm btn-outline-danger" disabled={busy || reviewLoading}
                        onClick={() => openAction({
                          title: `ابطال رأی ${formatNumber(vote.id)}`,
                          path: `/home-league/votes/${vote.id}/invalidate`, minimum: 5,
                        })}>ابطال رأی</button>}
                    </article>
                  );
                })}

                {tab === "reports" && list.map((report) => (
                  <article className="border rounded p-3 mb-2" key={report.id}>
                    <h4 className="h6">گزارش {formatNumber(report.id)}</h4>
                    <p className="text-muted small mb-2">اثر {formatNumber(report.entry_id)} · {labelStatus(report.status)}</p>
                    <p className="mb-2 text-break">{report.reason}</p>
                    {report.resolution && <p className="mb-2 text-break">{report.resolution}</p>}
                    {report.status === "pending" && (
                      <div>
                        <button type="button" className="btn btn-sm btn-outline-success ml-2" disabled={busy || reviewLoading}
                          onClick={() => openAction({
                            title: `ثبت رسیدگی به گزارش ${formatNumber(report.id)}`,
                            path: `/home-league/reports/${report.id}/resolve`, status: "resolved",
                          })}>ثبت رسیدگی</button>
                        <button type="button" className="btn btn-sm btn-outline-secondary" disabled={busy || reviewLoading}
                          onClick={() => openAction({
                            title: `رد گزارش ${formatNumber(report.id)}`,
                            path: `/home-league/reports/${report.id}/resolve`, status: "dismissed",
                          })}>رد گزارش</button>
                      </div>
                    )}
                  </article>
                ))}

                {tab === "audit" && (
                  <ul className="list-unstyled mb-0">
                    {list.map((record) => (
                      <li className="border-bottom py-2" key={record.id}>
                        {auditLabels[record.action] || record.action}
                        {record.subject_id && ` · #${formatNumber(record.subject_id)}`}
                        {record.created_at && <time className="text-muted mr-2" dir="ltr">{record.created_at}</time>}
                        {record.reason && <p className="mb-0 text-break">{record.reason}</p>}
                      </li>
                    ))}
                  </ul>
                )}

                {tab !== "audit" && <Paging number={page[tab]} hasMore={Boolean(review[`${tab}_has_more`])}
                  disabled={busy || reviewLoading} onChange={(value) => {
                    setPage((old) => ({ ...old, [tab]: value }));
                    setAction(null);
                  }} />}
              </>
            )}
          </div>
        </div>
      )}
    </section>
  );
}

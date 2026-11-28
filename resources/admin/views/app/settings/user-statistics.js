import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { useSelector } from 'react-redux';
import { Link, useHistory, useLocation, useParams } from 'react-router-dom';
import { adminPathApi, adminRoot } from '../../../constants/defaultValues';
import './user-statistics.scss';

const directoryPath = `${adminRoot}/settings/user-statistics`;
const endpoint = `${adminPathApi}/site-usage/directory`;
const number = new Intl.NumberFormat('fa-IR');
const date = new Intl.DateTimeFormat('fa-IR', { timeZone: 'Asia/Tehran', dateStyle: 'medium' });
const time = new Intl.DateTimeFormat('fa-IR', { timeZone: 'Asia/Tehran', timeStyle: 'short' });
const statusLabels = { active: 'فعال', inactive: 'غیرفعال', archived: 'بایگانی‌شده' };
const eventTypes = { all: 'همهٔ فعالیت‌ها', page: 'بازدید صفحات', property: 'بازدید ملک‌ها', operation: 'عملیات خدمات' };
const eventIcons = { page: 'simple-icon-docs', property: 'simple-icon-home', operation: 'simple-icon-check' };
const metrics = [
  ['accounts', 'کل حساب‌ها', 'simple-icon-people'],
  ['new_accounts', 'ثبت‌نام امروز', 'simple-icon-user-follow'],
  ['visitors', 'مرورگرهای یکتای امروز', 'simple-icon-globe'],
  ['signed_in_visitors', 'کاربران واردشدهٔ بازدیدکننده امروز', 'simple-icon-eye'],
];

function icon(name) {
  return <i className={name} aria-hidden="true" />;
}

function formatCount(value) {
  return value == null ? '—' : number.format(value);
}

function formatDateTime(value) {
  if (!value) return '—';
  const instant = new Date(value);
  return `${date.format(instant)}، ${time.format(instant)}`;
}

function useReport(url, search, method = 'get') {
  const [attempt, setAttempt] = useState(0);
  const [state, setState] = useState({ data: null, error: '', loading: true });

  useEffect(() => {
    let active = true;
    setState((current) => ({ ...current, error: '', loading: true }));
    const request = method === 'post'
      ? axios.post(url, Object.fromEntries(new URLSearchParams(search)), { timeout: 15000 })
      : axios.get(`${url}${search}`, { timeout: 15000 });
    request.then(({ data }) => {
      if (active) setState({ data: data.data, error: '', loading: false });
    }).catch((failure) => {
      if (!active) return;
      const messages = {
        403: 'دسترسی به اطلاعات کاربران فقط برای مدیر اصلی اسپاد فعال است.',
        404: 'این حساب کاربری پیدا نشد.',
        422: 'مقادیر فیلترها را بررسی کنید.',
        429: 'تعداد درخواست‌ها زیاد است. کمی بعد دوباره تلاش کنید.',
      };
      setState({ data: null, error: messages[failure?.response?.status] || 'دریافت اطلاعات ممکن نشد.', loading: false });
    });
    return () => { active = false; };
  }, [url, search, method, attempt]);

  return { ...state, reload: () => setAttempt((value) => value + 1) };
}

function ReportState({ error, reload }) {
  if (!error) return <p className="spad-users__feedback" role="status">در حال دریافت اطلاعات…</p>;
  return (
    <div className="spad-users__feedback" role="alert">
      <p>{error}</p>
      <button className="spad-users__button" type="button" onClick={reload}>تلاش دوباره</button>
    </div>
  );
}

function Pagination({ page, pages, onPage }) {
  if (pages <= 1 && page === 1) return null;
  return (
    <nav className="spad-users__pagination" aria-label="صفحه‌بندی نتایج">
      <button type="button" disabled={page <= 1} onClick={() => onPage(page - 1)}>قبلی</button>
      <span>صفحهٔ {number.format(page)} از {number.format(pages)}</span>
      <button type="button" disabled={page >= pages} onClick={() => onPage(page + 1)}>بعدی</button>
    </nav>
  );
}

function Status({ status }) {
  return <span className={`spad-users__status spad-users__status--${status}`}>{statusLabels[status]}</span>;
}

function Directory() {
  const { search, state } = useLocation();
  const history = useHistory();
  const params = new URLSearchParams(search);
  const query = state?.userDirectoryQuery || '';
  const [draft, setDraft] = useState(query);
  const requestParams = new URLSearchParams(search);
  requestParams.set('q', query);
  const report = useReport(`${endpoint}/search`, requestParams.toString(), 'post');
  useEffect(() => setDraft(query), [query]);

  const changeFilters = (values) => {
    const next = new URLSearchParams(search);
    next.delete('page');
    const { q = query, ...filters } = values;
    Object.entries(filters).forEach(([key, value]) => {
      if (value) next.set(key, value);
      else next.delete(key);
    });
    history.push({ pathname: directoryPath, search: next.toString(), state: { userDirectoryQuery: q } });
  };

  return (
    <>
      <header className="spad-users__hero">
        <div>
          <h1>آمار و فعالیت کاربران</h1>
          <p>حساب‌های سایت اصلی اسپاد، سابقهٔ بازدید و عملیات خدمات هر کاربر</p>
        </div>
        <button type="button" onClick={report.reload} className="spad-users__hero-action">{icon('simple-icon-refresh')} به‌روزرسانی</button>
      </header>
      {report.data && (
        <div className="spad-users__metrics" aria-label="آمار کاربران به وقت تهران">
          {metrics.map(([key, label, symbol]) => (
            <div key={key}>
              <span className="spad-users__metric-icon">{icon(symbol)}</span>
              <div><span>{label}</span><strong>{formatCount(report.data.summary[key])}</strong></div>
            </div>
          ))}
        </div>
      )}
      <section className="spad-users__panel" aria-labelledby="user-directory">
        <div className="spad-users__section-head">
          <h2 id="user-directory">فهرست کاربران</h2>
          {report.data && <span>{number.format(report.data.total)} حساب</span>}
        </div>
        <div className="spad-users__filters">
          <form className="spad-users__search" onSubmit={(event) => { event.preventDefault(); changeFilters({ q: draft.trim() }); }}>
            <label htmlFor="spad-user-search">جست‌وجوی کاربر</label>
            <div>
              <input id="spad-user-search" type="search" value={draft} onChange={(event) => setDraft(event.target.value)} placeholder="نام، شماره تماس، ایمیل یا شناسه" maxLength={100} />
              <button type="submit" className="spad-users__button">{icon('simple-icon-magnifier')} جست‌وجو</button>
            </div>
          </form>
          <label>زمان ثبت‌نام
            <select value={params.get('registered') || 'all'} onChange={(event) => changeFilters({ registered: event.target.value })}>
              <option value="all">همهٔ زمان‌ها</option><option value="today">امروز</option>
              <option value="week">۷ روز اخیر</option><option value="month">۳۰ روز اخیر</option>
            </select>
          </label>
          <label>وضعیت حساب
            <select value={params.get('status') || 'all'} onChange={(event) => changeFilters({ status: event.target.value })}>
              <option value="all">همهٔ وضعیت‌ها</option>
              {Object.entries(statusLabels).map(([value, label]) => <option value={value} key={value}>{label}</option>)}
            </select>
          </label>
          <label>ترتیب نمایش
            <select value={params.get('sort') || 'newest'} onChange={(event) => changeFilters({ sort: event.target.value })}>
              <option value="newest">جدیدترین ثبت‌نام</option><option value="oldest">قدیمی‌ترین ثبت‌نام</option>
            </select>
          </label>
        </div>
        {report.loading || !report.data ? <ReportState {...report} /> : (
          <>
            {report.data.users.length ? (
              <div className="spad-users__table-scroll" tabIndex={0} role="region" aria-label="جدول حساب‌های کاربری">
                <table className="spad-users__table">
                  <thead><tr><th scope="col">کاربر و شناسه</th><th scope="col">شماره تماس و ایمیل</th><th scope="col">وضعیت</th><th scope="col">تاریخ ثبت‌نام</th><th scope="col">آخرین بازدید ثبت‌شده</th></tr></thead>
                  <tbody>
                    {report.data.users.map((user) => (
                      <tr key={user.id}>
                        <td>
                          <Link className="spad-users__user-link" to={{ pathname: `${directoryPath}/${user.id}`, search, state }}>
                            <span className="spad-users__avatar">{icon('simple-icon-user')}</span>
                            <span><strong>{user.name || 'کاربر بدون نام'}</strong><small>شناسهٔ {number.format(user.id)}</small></span>
                            {icon('simple-icon-arrow-left')}
                          </Link>
                        </td>
                        <td><bdi>{user.mobile || '—'}</bdi>{user.email && <small><bdi>{user.email}</bdi></small>}</td>
                        <td><Status status={user.status} /></td>
                        <td>{formatDateTime(user.registered_at)}</td>
                        <td>{formatDateTime(user.last_view_at)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : <p className="spad-users__feedback">کاربری با این فیلترها پیدا نشد.</p>}
            <Pagination {...report.data} onPage={(page) => changeFilters({ page })} />
          </>
        )}
      </section>
    </>
  );
}

function Profile({ userId }) {
  const { search, state } = useLocation();
  const [filter, setFilter] = useState({ kind: 'all', page: 1 });
  const [dates, setDates] = useState({ from: '', to: '' });
  const report = useReport(`${endpoint}/${encodeURIComponent(userId)}`, `?${new URLSearchParams(filter)}`);
  const user = report.data?.user;
  const details = user && [
    ['شناسهٔ کاربری', number.format(user.id)], ['نام', user.first_name], ['نام خانوادگی', user.last_name],
    ['شماره تماس', user.mobile], ['ایمیل', user.email], ['ثبت‌نام', formatDateTime(user.registered_at)],
    ['آخرین تغییر حساب', formatDateTime(user.updated_at)],
    ['تأیید شماره تماس', user.phone_verified_at ? formatDateTime(user.phone_verified_at) : 'تأیید نشده'],
    ['تأیید ایمیل', user.email_verified_at ? formatDateTime(user.email_verified_at) : 'تأیید نشده'],
    ['آخرین بازدید ثبت‌شده', formatDateTime(user.last_view_at)],
  ];
  if (user?.archived_at) details.push(['بایگانی حساب', formatDateTime(user.archived_at)]);

  return (
    <>
      <Link className="spad-users__back" to={{ pathname: directoryPath, search, state }}>{icon('simple-icon-arrow-right')} بازگشت به فهرست کاربران</Link>
      <header className="spad-users__hero">
        <div>
          <h1>{user?.name || 'پروندهٔ کاربر'}</h1>
          <p>اطلاعات حساب و تاریخچهٔ فعالیت در سایت اصلی اسپاد</p>
        </div>
        {user && <Status status={user.status} />}
      </header>
      {user && <div className="spad-users__metrics" aria-label="خلاصهٔ بازدیدهای ثبت‌شده">
        {[
          ['page_views', 'بازدید سایر صفحات', 'simple-icon-docs'],
          ['property_views', 'دفعات مشاهدهٔ ملک‌ها', 'simple-icon-eye'],
          ['properties', 'ملک‌های مشاهده‌شدهٔ متمایز', 'simple-icon-home'],
        ].map(([key, label, symbol]) => (
          <div key={key}><span className="spad-users__metric-icon">{icon(symbol)}</span>
            <div><span>{label}</span><strong>{formatCount(report.data.totals[key])}</strong></div>
          </div>
        ))}
      </div>}
      <div className="spad-users__profile">
        {user && <section className="spad-users__panel" aria-labelledby="user-details">
          <div className="spad-users__section-head"><h2 id="user-details">مشخصات حساب</h2></div>
          <dl className="spad-users__details">
            {details.map(([label, value]) => <div key={label}><dt>{label}</dt><dd><bdi>{value || '—'}</bdi></dd></div>)}
          </dl>
        </section>}
        <section className="spad-users__panel" aria-labelledby="user-timeline">
          <div className="spad-users__section-head"><h2 id="user-timeline">خط زمانی فعالیت</h2>{report.data && <span>{number.format(report.data.total)} رویداد</span>}</div>
          <div className="spad-users__types" role="group" aria-label="نوع فعالیت">
            {Object.entries(eventTypes).map(([kind, label]) => (
              <button key={kind} type="button" aria-pressed={filter.kind === kind} onClick={() => setFilter({ ...filter, kind, page: 1 })}>{label}</button>
            ))}
          </div>
          <form className="spad-users__dates" onSubmit={(event) => { event.preventDefault(); setFilter({ ...filter, ...dates, page: 1 }); }}>
            <label>از تاریخ<input type="date" value={dates.from} max={dates.to || undefined} onChange={(event) => setDates({ ...dates, from: event.target.value })} /></label>
            <label>تا تاریخ<input type="date" value={dates.to} min={dates.from || undefined} onChange={(event) => setDates({ ...dates, to: event.target.value })} /></label>
            <button type="submit" className="spad-users__button">اعمال بازه</button>
          </form>
          <p className="spad-users__hint">زمان‌ها به وقت تهران است. تاریخچهٔ بازدید از زمان فعال‌شدن ثبت بازدید در دسترس است.</p>
          {report.loading || !report.data ? <ReportState {...report} /> : (
            <>
              {report.data.events.length ? (
                <ol className="spad-users__timeline">
                  {report.data.events.map((event) => (
                    <li key={event.id}>
                      <span className={`spad-users__event-icon spad-users__event-icon--${event.kind}`}>{icon(eventIcons[event.kind])}</span>
                      <div>
                        <strong>{event.label}</strong>
                        {event.property_id && <div className="spad-users__event-property">
                          {event.property_url ? <a href={`https://spad.estate${event.property_url}`} target="_blank" rel="noreferrer">{event.property_title || 'مشاهدهٔ آگهی'}</a> : (event.property_title || 'آگهی در دسترس نیست')}
                          <small>شناسهٔ ملک {number.format(event.property_id)}</small>
                        </div>}
                        {event.kind === 'page' && <bdi className="spad-users__event-path">{event.page}</bdi>}
                      </div>
                      <time dateTime={event.at}>{formatDateTime(event.at)}</time>
                    </li>
                  ))}
                </ol>
              ) : <p className="spad-users__feedback">{!report.data.page_tracking_available && ['page', 'property'].includes(filter.kind) ? 'ثبت بازدید هنوز فعال نشده است.' : 'رویدادی در این بازه و نوع فعالیت ثبت نشده است.'}</p>}
              <Pagination {...report.data} onPage={(page) => setFilter({ ...filter, page })} />
            </>
          )}
        </section>
      </div>
    </>
  );
}

export default function UserStatistics() {
  const currentUser = useSelector((state) => state.authUser.currentUser);
  const { userId } = useParams();

  return (
    <div className="spad-users" dir="rtl">
      {!currentUser ? <p role="status">در حال بارگذاری حساب مدیر…</p>
        : !currentUser.roles?.includes('Super Admin') ? <p role="alert">این صفحه برای مدیر اصلی اسپاد در دسترس است.</p>
          : userId ? <Profile key={userId} userId={userId} /> : <Directory />}
    </div>
  );
}

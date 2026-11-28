import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { Link } from 'react-router-dom';
import { adminPathApi, adminRoot } from '../../../constants/defaultValues';
import './user-activity-overview.scss';

const number = new Intl.NumberFormat('fa-IR');
const weekday = new Intl.DateTimeFormat('fa-IR', { timeZone: 'Asia/Tehran', weekday: 'short' });
const calendarDay = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { timeZone: 'Asia/Tehran', day: 'numeric', month: 'short' });
const clock = new Intl.DateTimeFormat('fa-IR', { timeZone: 'Asia/Tehran', hour: '2-digit', minute: '2-digit' });
const displayNumber = (value) => value == null ? '—' : number.format(value);

function UserChart({ days }) {
  const [metric, setMetric] = useState('registrations');
  const labels = { registrations: 'ثبت‌نام‌ها', active_accounts: 'کاربران فعال' };
  const ceiling = Math.max(1, ...days.map((day) => day[metric] ?? 0));
  return (
    <div className="spad-user-overview__chart-card">
      <div className="spad-user-overview__chart-head">
        <div>
          <h3>روند هفت روز اخیر</h3>
          <p>به وقت تهران</p>
        </div>
        <div className="spad-user-overview__toggle" role="group" aria-label="شاخص نمودار کاربران">
          {Object.entries(labels).map(([key, label]) => (
            <button key={key} type="button" aria-pressed={metric === key} onClick={() => setMetric(key)}>{label}</button>
          ))}
        </div>
      </div>
      <ol className="spad-user-overview__chart" aria-label={`${labels[metric]} در هفت روز اخیر`}>
        {days.map((day, index) => {
          const date = new Date(`${day.day}T12:00:00+03:30`);
          const current = index === days.length - 1;
          const value = day[metric];
          const label = current ? 'امروز' : weekday.format(date);
          return (
            <li key={day.day} className={current ? 'is-today' : undefined}
              aria-label={`${calendarDay.format(date)}، ${value === null ? 'داده در دسترس نیست' : displayNumber(value)}`}>
              <span className="spad-user-overview__bar-number">{displayNumber(value)}</span>
              <div className="spad-user-overview__bar" aria-hidden="true">
                <span style={{ height: `${value == null ? 0 : value / ceiling * 100}%` }} />
              </div>
              <span className="spad-user-overview__bar-label">{label}</span>
            </li>
          );
        })}
      </ol>
    </div>
  );
}

export default function UserActivityOverview() {
  const [report, setReport] = useState(null);
  const [error, setError] = useState(false);
  const [loading, setLoading] = useState(true);
  const [attempt, setAttempt] = useState(0);

  useEffect(() => {
    let active = true;
    let pending = false;
    const load = async () => {
      if (pending) return;
      pending = true;
      setLoading(true);
      try {
        const { data } = await axios.get(`${adminPathApi}/site-usage/directory/overview`, { timeout: 12000 });
        if (active) {
          setReport(data.data);
          setError(false);
        }
      } catch (_) {
        if (active) setError(true);
      } finally {
        pending = false;
        if (active) setLoading(false);
      }
    };
    load();
    const timer = window.setInterval(load, 60000);
    return () => { active = false; window.clearInterval(timer); };
  }, [attempt]);

  const summary = report?.summary;
  const metrics = [
    { key: 'registrations', label: 'ثبت‌نام امروز', detail: 'از آغاز روز در تهران', icon: 'simple-icon-user-follow' },
    { key: 'online', label: 'کاربران آنلاین', detail: `فعال در ${displayNumber(report?.presence_minutes ?? 5)} دقیقهٔ اخیر`, icon: 'simple-icon-energy' },
    { key: 'active_accounts', label: 'کاربران فعال امروز', detail: 'حساب‌های یکتا با بازدید ثبت‌شده', icon: 'simple-icon-people' },
  ];
  const pages = summary?.page_views;
  const properties = summary?.property_views;
  const totalViews = pages == null || properties == null ? null : pages + properties;

  return (
    <section className="spad-user-overview" aria-labelledby="spad-user-overview-title" aria-busy={loading}>
      <header className="spad-user-overview__head">
        <div className="spad-user-overview__title">
          <span className="spad-user-overview__mark"><i className="simple-icon-people" aria-hidden="true" /></span>
          <div><h2 id="spad-user-overview-title">نمای کاربران اسپاد</h2><p>ثبت‌نام، حضور و بازدید کاربران سایت اصلی</p></div>
        </div>
        <div className="spad-user-overview__actions">
          <button type="button" disabled={loading} onClick={() => setAttempt((count) => count + 1)}
            aria-label="به‌روزرسانی آمار کاربران" title="به‌روزرسانی آمار کاربران">
            <i className="simple-icon-refresh" aria-hidden="true" />
          </button>
          <Link to={`${adminRoot}/settings/user-statistics`}>آمار و فعالیت کاربران <i className="simple-icon-arrow-left" aria-hidden="true" /></Link>
        </div>
      </header>
      <dl className="spad-user-overview__metrics">
        {metrics.map(({ key, label, detail, icon }) => (
          <div key={key} className={`spad-user-overview__metric is-${key}`}>
            <dt><i className={icon} aria-hidden="true" />{label}</dt>
            <dd><strong>{displayNumber(summary?.[key])}</strong><small>{detail}</small></dd>
          </div>
        ))}
      </dl>
      {report ? (
        <div className="spad-user-overview__body">
          <UserChart days={report.days} />
          <div className="spad-user-overview__activity">
            <div className="spad-user-overview__activity-head">
              <h3>فعالیت امروز</h3>
              <span>{displayNumber(totalViews)} بازدید</span>
            </div>
            <div className="spad-user-overview__distribution" aria-hidden="true">
              {Boolean(totalViews) && <><span style={{ flexGrow: pages }} /><span style={{ flexGrow: properties }} /></>}
            </div>
            <dl className="spad-user-overview__breakdown">
              <div><dt><i aria-hidden="true" />بازدید صفحات</dt><dd>{displayNumber(pages)}</dd></div>
              <div><dt><i aria-hidden="true" />مشاهدهٔ آگهی ملک</dt><dd>{displayNumber(properties)}</dd></div>
            </dl>
            <p className="spad-user-overview__stamp"><i className="simple-icon-clock" aria-hidden="true" /> به‌روزرسانی {clock.format(new Date(report.generated_at))}</p>
          </div>
        </div>
      ) : !error && <p className="spad-user-overview__feedback" role="status">در حال دریافت آمار کاربران…</p>}
      {error && <p className="spad-user-overview__feedback" role="status">{report ? 'به‌روزرسانی انجام نشد. آخرین آمار دریافت‌شده نمایش داده می‌شود.' : 'دریافت آمار کاربران ممکن نشد.'}</p>}
    </section>
  );
}

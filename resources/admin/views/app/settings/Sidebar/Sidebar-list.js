import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Card, CardBody } from 'reactstrap';
import axios from 'axios';
import { adminPathApi, adminRoot } from '../../../../constants/defaultValues';

const date = new Intl.DateTimeFormat('fa-IR', { timeZone: 'Asia/Tehran', dateStyle: 'medium' });
const widgetLabels = {
  latestBlogPosts: 'آخرین نوشته‌های وبلاگ',
  latestAds: 'آخرین آگهی‌ها',
  advertising: 'تبلیغات',
  BlogCategories: 'دسته‌بندی‌های وبلاگ',
};

export default function SidebarList() {
  const [sidebars, setSidebars] = useState(null);
  const [error, setError] = useState(false);
  const [attempt, setAttempt] = useState(0);
  useEffect(() => {
    let active = true;
    setError(false);
    axios.get(`${adminPathApi}/settings/sidebar`, { timeout: 15000 }).then(({ data }) => {
      if (!Array.isArray(data)) throw new Error('Unexpected sidebar list');
      if (active) setSidebars(data);
    }).catch(() => { if (active) setError(true); });
    return () => { active = false; };
  }, [attempt]);

  return (
    <Card>
      <CardBody>
        <header className="spad-work-list-heading">
          <div><h1>فهرست عناصر سایدبار سایت</h1><p>چیدمان‌ها و محتوای سایدبار وب‌سایت اسپاد</p></div>
          <Link className="btn btn-primary" to={`${adminRoot}/settings/sidebar-post`}>افزودن سایدبار</Link>
        </header>
        {error ? <div role="alert"><p>دریافت فهرست سایدبار ممکن نشد.</p>
          <button className="btn btn-outline-primary" type="button" onClick={() => setAttempt((value) => value + 1)}>تلاش دوباره</button></div>
          : !sidebars ? <p role="status">در حال دریافت فهرست…</p>
            : sidebars.length === 0 ? <p role="status">هنوز سایدباری ثبت نشده است.</p>
              : <div className="table-responsive" tabIndex={0} role="region" aria-label="جدول سایدبارها">
                <table className="table mb-0">
                  <thead><tr><th scope="col">نام سایدبار</th><th scope="col">تاریخ ایجاد</th><th scope="col">محتوا</th></tr></thead>
                  <tbody>
                    {sidebars.map(({ id, name, created_at: createdAt, content }) => {
                      const widgets = Array.isArray(content) ? content : [];
                      const created = createdAt ? new Date(createdAt) : null;
                      return <tr key={id}>
                        <td>{name}</td><td>{created && !Number.isNaN(created.getTime()) ? date.format(created) : '—'}</td>
                        <td>{widgets.length ? <details><summary>مشاهده عناصر</summary>
                          <ul>{widgets.map((widget, index) => <li key={index}>{widget?.title || widgetLabels[widget?.type] || 'عنصر سایدبار'}</li>)}</ul>
                        </details> : '—'}</td>
                      </tr>;
                    })}
                  </tbody>
                </table>
              </div>}
      </CardBody>
    </Card>
  );
}

import React from "react";
import { Card, CardBody } from "reactstrap";
import { adminRoot } from "../../constants/defaultValues";

const icons = {
  dashboards: "iconsminds-dashboard",
  advertisement: "iconsminds-folder",
  raffles: "iconsminds-gift-box",
  "virtual-tours": "simple-icon-picture",
  inquiries: "iconsminds-search-people",
  wallets: "iconsminds-wallet",
  crm: "iconsminds-affiliate",
  customers: "simple-icon-people",
  "partner-panels": "iconsminds-handshake",
  operators: "simple-icon-user-following",
  settings: "simple-icon-settings",
  blogs: "iconsminds-digital-drawing",
  location: "simple-icon-location-pin",
  employRequests: "simple-icon-briefcase",
  expertRequests: "simple-icon-user",
  license: "simple-icon-notebook",
};

const fallback = [
  ["dashboards", "داشبورد", `${adminRoot}/dashboards`],
  ["advertisement", "مدیریت آگهی‌ها", `${adminRoot}/advertisements/adver-list`],
  ["virtual-tours", "استودیو تور مجازی ۳۶۰°", `${adminRoot}/virtual-tours/create`],
  ["inquiries", "استعلامات", `${adminRoot}/inquiries/requests`],
  ["wallets", "کیف پول‌ها", `${adminRoot}/wallets/list`],
  ["raffles", "قرعه‌کشی املاک", `${adminRoot}/raffles/list`],
  ["crm", "CRM", `${adminRoot}/crm`],
  ["customers", "کاربران", `${adminRoot}/customers`],
  ["partner-panels", "پنل‌های همکاری", `${adminRoot}/partner-panels`],
  ["operators", "اپراتورها", `${adminRoot}/operators`],
  ["settings", "تنظیمات", `${adminRoot}/settings`],
];

const flatten = (items = []) => {
  const result = [];
  items.forEach((item) => {
    if (item?.to && item?.label) result.push([item.id, item.label, item.to]);
    (item?.subs || []).forEach((sub) => {
      if (sub?.to && sub?.label) result.push([`${item.id}-${sub.label}`, sub.label, sub.to]);
    });
  });
  return result;
};

const AdminQuickAccess = ({ menu = [] }) => {
  const source = flatten(menu);
  const items = source.length ? source : fallback;
  const seen = new Set();
  const unique = items.filter((item) => {
    if (seen.has(item[2])) return false;
    seen.add(item[2]);
    return true;
  });

  return (
    <Card className="spad-admin-quick-card">
      <CardBody>
        <div className="spad-admin-quick-head">
          <div>
            <span>SPAD ADMIN CONTROL CENTER</span>
            <h3>دسترسی سریع به بخش‌های پنل</h3>
            <p>تمام بخش‌های اصلی پنل را مستقیماً از همین صفحه باز کنید.</p>
          </div>
          <div className="spad-admin-quick-mark">SPAD</div>
        </div>
        <div className="spad-admin-quick-grid">
          {unique.map(([id, label, to]) => (
            <a className={`spad-admin-quick-item ${id.includes("virtual-tours") ? "is-featured" : ""}`} href={to} key={to}>
              <span className="spad-admin-quick-icon"><i className={icons[id] || icons[id.split("-")[0]] || "simple-icon-grid"} /></span>
              <span className="spad-admin-quick-copy"><b>{String(label).replace("menu.", "")}</b><small>ورود به بخش</small></span>
              <span className="spad-admin-quick-arrow">‹</span>
            </a>
          ))}
        </div>
      </CardBody>
    </Card>
  );
};

export default AdminQuickAccess;

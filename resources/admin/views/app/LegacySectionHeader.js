import React from 'react';
import {Link} from 'react-router-dom';
import {adminRoot} from '../../constants/defaultValues';

/**
 * Route-aware heading for pre-v8 SPAD administration pages.
 * Only existing destinations are linked; leaves each module's data and forms untouched.
 */
const SECTIONS = [
 {slug:'settings',name:'تنظیمات سامانه',subtitle:'مدیریت تنظیمات عمومی، شهرها، مناطق و ساختار وب‌سایت',icon:'simple-icon-settings',
  actions:[['تنظیمات عمومی','settings/main'],['شهرها','settings/cities'],['مناطق','settings/regions'],['وب‌سرویس‌ها','web-services']]},
 {slug:'customers',name:'مدیریت مشتریان',subtitle:'پرونده‌های کاربران، اطلاعات تماس و مدیریت فهرست مشتریان',icon:'simple-icon-people',
  actions:[['فهرست مشتریان','customers/list'],['افزودن مشتری','customers/create']]},
 {slug:'blogs',name:'مدیریت وبلاگ و محتوا',subtitle:'نوشته‌ها، دسته‌بندی‌ها و انتشار محتوای ملکی',icon:'simple-icon-notebook',
  actions:[['فهرست نوشته‌ها','blogs/posts/list'],['نوشته جدید','blogs/posts/create'],['دسته‌بندی‌ها','blogs/categories/list']]},
 {slug:'advertisements',name:'مدیریت آگهی‌های ملکی',subtitle:'ثبت، اصلاح، بررسی و دسته‌بندی املاک اسپاد',icon:'iconsminds-building',
  actions:[['لیست ملک‌ها','advertisements/adver-list'],['آگهی جدید','advertisements/add'],['در انتظار بررسی','advertisements/unapproved'],['دسته‌بندی‌ها','advertisements/categories/list']]},
 {slug:'media_management',name:'کتابخانه رسانه اسپاد',subtitle:'مدیریت تصاویر، پوشه‌ها، فایل‌های آپلودشده و محتوای تصویری سایت',icon:'simple-icon-picture',
  actions:[['مدیریت رسانه','media_management'],['مطالب وبلاگ','blogs/posts/list']]},
 {slug:'crm',name:'مدیریت ارتباط با مشتریان',subtitle:'درخواست‌های ملکی، پیگیری تماس‌ها و کارشناسی مشتریان',icon:'simple-icon-bubbles',
  actions:[['همه درخواست‌ها','crm/all-requests'],['ثبت مشتری','crm/add-customer'],['درخواست بازدید','crm/visit-requests']]},
 {slug:'operators',name:'مدیریت همکاران و دسترسی',subtitle:'کاربران پنل، نقش‌ها، سطح دسترسی و حساب‌های همکاران',icon:'simple-icon-user-following',
  actions:[['همکاران','operators/list'],['افزودن همکار','operators/create']]},
 {slug:'raffles',name:'مدیریت قرعه‌کشی',subtitle:'تنظیم و مدیریت رویدادها و قرعه‌کشی‌های اسپاد',icon:'simple-icon-present',
  actions:[['فهرست','raffles/list'],['افزودن','raffles/create'],['تنظیمات','raffles/settings']]},
 {slug:'wallets',name:'کیف پول و تراکنش‌ها',subtitle:'مانده‌ها، گزارش پرداخت‌ها و تنظیمات مالی سامانه',icon:'simple-icon-wallet',
  actions:[['کیف پول‌ها','wallets/list'],['تراکنش‌ها','wallets/transactions'],['تنظیمات کیف پول','wallets/settings']]},
 {slug:'inquiries',name:'استعلامات ملکی',subtitle:'درخواست‌ها، پیکربندی و مدیریت سرویس‌های استعلام',icon:'simple-icon-magnifier',
  actions:[['درخواست‌ها','inquiries/requests'],['سرویس‌ها','inquiries/services'],['تنظیمات','inquiries/settings']]},
 {slug:'virtual-tours',name:'مدیریت تور مجازی',subtitle:'تورهای سه‌بعدی و تصاویر ۳۶۰ درجه املاک',icon:'simple-icon-picture',
  actions:[['همه تورها','virtual-tours/list'],['ایجاد تور','virtual-tours/create']]},
 {slug:'contacts',name:'پیام‌های تماس',subtitle:'پیگیری پیام‌ها و درخواست‌های ارتباط با اسپاد',icon:'simple-icon-envelope'},
 {slug:'employ-requests',name:'درخواست‌های همکاری',subtitle:'مدیریت درخواست‌های استخدام و همکاری با اسپاد',icon:'simple-icon-people'},
 {slug:'expert-requests',name:'درخواست کارشناسی',subtitle:'پیگیری تقاضاهای کارشناسی و بازدید ملک',icon:'simple-icon-badge'},
 {slug:'location',name:'مدیریت موقعیت‌ها',subtitle:'شهرها، مناطق و اطلاعات مکانی سرویس‌ها',icon:'simple-icon-location-pin'},
 {slug:'license',name:'مجوزها و دسترسی',subtitle:'اطلاعات و مدیریت مجوزهای پنل',icon:'simple-icon-shield'},
 {slug:'transactions',name:'تراکنش‌ها',subtitle:'سوابق و گزارش تراکنش‌های سامانه',icon:'simple-icon-credit-card'},
 {slug:'land-types',name:'انواع ملک',subtitle:'مدیریت ساختار و ویژگی‌های دسته‌بندی املاک',icon:'simple-icon-grid'},
 {slug:'partner-panels',name:'پنل همکاران',subtitle:'مدیریت همکاری‌ها و پرونده‌های مشاوران',icon:'simple-icon-people'},
 {slug:'investor-listings',name:'فرصت‌های سرمایه‌گذاری',subtitle:'مدیریت فهرست‌های سرمایه‌گذاری',icon:'simple-icon-graph'},
 {slug:'media-management',name:'مدیریت رسانه',subtitle:'کتابخانه محتوای تصویری و فایل‌ها',icon:'simple-icon-picture'}
];
const LEGACY=new Set(SECTIONS.map(s=>s.slug));
export const isLegacyAdminRoute=pathname=>{
 const root=adminRoot+'/';
 if(!pathname||!pathname.startsWith(root))return false;
 const slug=pathname.slice(root.length).split('/')[0];
 return LEGACY.has(slug);
};
const LegacySectionHeader=({pathname})=>{
 const root=adminRoot+'/';
 if(!pathname||!pathname.startsWith(root))return null;
 const slug=pathname.slice(root.length).split('/')[0];
 const section=SECTIONS.find(s=>s.slug===slug);
 if(!section)return null;
 const actions=section.actions||[];
 return <header className="spad-v9-route-head" dir="rtl">
  <div className="spad-v9-route-head__copy">
   <span className="spad-v9-route-head__eyebrow"><i className={section.icon}/> SPAD / ADMIN WORKSPACE</span>
   <h2>{section.name}</h2><p>{section.subtitle}</p>
  </div>
  <nav aria-label="دسترسی سریع مرتبط با این بخش" className="spad-v9-route-head__actions">
   {actions.map(([label,to])=><Link key={to} to={adminRoot+'/'+to} className={pathname===adminRoot+'/'+to?'active':''}>{label}</Link>)}
   <Link to={adminRoot+'/dashboards/default'} className="spad-v9-route-head__home"><i className="simple-icon-grid"/> داشبورد</Link>
  </nav>
 </header>;
};
export default LegacySectionHeader;

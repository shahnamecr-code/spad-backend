import { adminRoot, UserRole } from "./defaultValues";
import { homeServices } from "./homeServices";

const data = [
  {
    id: "dashboards",
    icon: "iconsminds-dashboard",
    label: "menu.dashboards",
    to: `${adminRoot}/dashboards/default`,
    // subs: [
    //   {
    //     icon: "simple-icon-briefcase",
    //     label: "menu.default",
    //     to: `${adminRoot}/dashboards/default`,
    //     // roles: [UserRole.Admin],
    //   },
    // ],
  },
  {
    id: "blogs",
    icon: "iconsminds-digital-drawing",
    label: "مدیریت وبلاگ",
    roles: [UserRole.Admin],
    subs: [
      { icon: "simple-icon-list", label: "فهرست نوشته‌ها", to: `${adminRoot}/blogs/posts/list`, matchPaths: [`${adminRoot}/blogs/posts/edit`] },
      { icon: "simple-icon-plus", label: "افزودن نوشته", to: `${adminRoot}/blogs/posts/create` },
      { icon: "simple-icon-layers", label: "دسته‌بندی نوشته‌ها", to: `${adminRoot}/blogs/categories/list` },
    ],
  },
  {
    id: "advertisement",
    icon: "iconsminds-folder",
    label: "menu.advertisements",
    to: `${adminRoot}/advertisements/register`,
    subs: [
      {
        icon: "iconsminds-folder-add--",
        label: "menu.advertisements.register",
        to: `${adminRoot}/advertisements/add`,
        // roles: [UserRole.Admin],
      },
      {
        icon: "simple-icon-diamond",
        label: "افزودن ملک سرمایه‌گذاری",
        to: `${adminRoot}/partner-panels/investor-opportunities?create=1`,
        roles: [UserRole.Admin],
      },
      {
        icon: "simple-icon-list",
        label: "فهرست و ویرایش آگهی‌ها",
        to: `${adminRoot}/advertisements/adver-list`,
        matchPaths: [`${adminRoot}/advertisements/edit`],
        keywords: "مدیریت تور مجازی نصب بسته ZIP",
        // roles: [UserRole.Admin],
      },
      {
        icon: "simple-icon-clock",
        label: "menu.advertisements.unapproved",
        to: `${adminRoot}/advertisements/unapproved`,
      },
      {
        icon: "simple-icon-location-pin",
        label: "menu.advertisements.check-list",
        to: `${adminRoot}/advertisements/check-list`,
        // roles: [UserRole.Admin],
      },
      {
        icon: "simple-icon-envelope-letter",
        label: "menu.advertisements.report-list",
        to: `${adminRoot}/advertisements/report-list`,
        roles: [UserRole.Admin],
      },
    ],
  },
  {
    id: "property-acceptance",
    icon: "iconsminds-building",
    label: "پذیرش ملک",
    to: `${adminRoot}/property-acceptance`,
    roles: [UserRole.Admin],
  },
  {
    id: "raffles",
    icon: "iconsminds-gift-box",
    label: "menu.raffles",
    to: `${adminRoot}/raffles/list`,
    subs: [
      { icon: "simple-icon-list", label: "menu.raffles.list", to: `${adminRoot}/raffles/list` },
      { icon: "simple-icon-plus", label: "menu.raffles.create", to: `${adminRoot}/raffles/create` },
      { icon: "simple-icon-settings", label: "menu.raffles.settings", to: `${adminRoot}/raffles/settings`, roles: [UserRole.Admin] },
    ],
  },
  {
    id: "inquiries",
    icon: "iconsminds-search-people",
    label: "استعلامات ملکی",
    to: `${adminRoot}/inquiries/requests`,
    subs: [
      { icon: "simple-icon-list", label: "سفارش‌های استعلام", to: `${adminRoot}/inquiries/requests`, shortcut: true, keywords: "سفارش های استعلام لیست درخواست استعلام سفارشات رهگیری" },
      { icon: "simple-icon-grid", label: "menu.inquiries.services", to: `${adminRoot}/inquiries/services`, roles: [UserRole.Admin] },
      { icon: "simple-icon-settings", label: "menu.inquiries.settings", to: `${adminRoot}/inquiries/settings`, roles: [UserRole.Admin] },
    ],
  },
  {
    id: "material-prices", icon: "iconsminds-coins",
    label: "menu.material-prices", to: `${adminRoot}/material-prices`, roles: [UserRole.Admin]
  },
  {
    id: "whatsapp-market", icon: "simple-icon-picture",
    label: "انتشار قیمت‌ها در واتساپ", to: `${adminRoot}/whatsapp-market`, roles: [UserRole.Admin]
  },
  {id:"mortgage",icon:"simple-icon-wallet",label:"وام مسکن و درخواست‌ها",to:`${adminRoot}/mortgage`,roles:[UserRole.Admin]},
  {id:"building-management",icon:"iconsminds-building",label:"menu.building-management",to:`${adminRoot}/building-management`,roles:[UserRole.Admin]},
  {id:"top-builders",icon:"iconsminds-business-man",label:"menu.top-builders",to:`${adminRoot}/top-builders`,roles:[UserRole.Admin]},
  {id:"property-auctions",icon:"iconsminds-gavel",label:"menu.property-auctions",to:`${adminRoot}/property-auctions`,roles:[UserRole.Admin]},
   {id:"property-affairs",icon:"iconsminds-file",label:"menu.property-affairs",to:`${adminRoot}/property-affairs`,roles:[UserRole.Admin]},
   {id:"online-renovation",icon:"iconsminds-paint-brush",label:"menu.online-renovation",to:`${adminRoot}/online-renovation`,roles:[UserRole.Admin]},
  {
    id: "wallets",
    icon: "iconsminds-wallet",
    label: "menu.wallets",
    to: `${adminRoot}/wallets/center`,
    roles: [UserRole.Admin],
    subs: [
      { icon: "simple-icon-grid", label: "menu.wallets.center", to: `${adminRoot}/wallets/center`, roles: [UserRole.Admin] },
      { icon: "simple-icon-credit-card", label: "menu.wallets.gateway", to: `${adminRoot}/wallets/center?tab=gateways`, roles: [UserRole.Admin] },
      { icon: "simple-icon-wallet", label: "menu.wallets.list", to: `${adminRoot}/wallets/center?tab=wallets`, roles: [UserRole.Admin] },
      { icon: "simple-icon-list", label: "menu.wallets.transactions", to: `${adminRoot}/wallets/center?tab=transactions`, roles: [UserRole.Admin] },
      { icon: "simple-icon-settings", label: "menu.wallets.settings", to: `${adminRoot}/wallets/center?tab=settings`, roles: [UserRole.Admin] },
    ],
  },
  {
    id: "crm",
    icon: "iconsminds-affiliate",
    label: "menu.crm",
    to: `${adminRoot}/crm`,
    subs: [
      {
        icon: "simple-icon-grid",
        label: "menu.crm-pro",
        to: `${adminRoot}/crm/pro`,
        roles: [UserRole.Admin],
      },
      {
        icon: "simple-icon-user-follow",
        label: "menu.crm.add-customer",
        to: `${adminRoot}/crm/add-customer`,
      },
      {
        icon: "simple-icon-user-following",
        label: "menu.all-requests",
        to: `${adminRoot}/crm/all-requests`,
      },
      {
        icon: "simple-icon-user",
        label: "menu.my-requests",
        to: `${adminRoot}/crm/my-requests`,
      },
      {
        icon: "simple-icon-people",
        label: "menu.my-region-requests",
        to: `${adminRoot}/crm/my-region-requests`,
      },
      {
        icon: "simple-icon-clock",
        label: "menu.visit-requests",
        to: `${adminRoot}/crm/visit-requests`,
      },
    ],
  },
  {
    id: "forms",
    icon: "iconsminds-post-mail-2",
    label: "menu.forms",
    to: `${adminRoot}/forms`,
    roles: [UserRole.Admin],
    subs: [
      {
        icon: "iconsminds-telephone-2",
        label: "menu.expert-request.list",
        to: `${adminRoot}/expert-requests/list`,
      },
      {
        icon: "iconsminds-handshake",
        label: "menu.employ-request.list",
        to: `${adminRoot}/employ-requests/list`,
        roles: [UserRole.Admin],
      },
      {
        icon: "iconsminds-user",
        label: "menu.contact",
        to: `${adminRoot}/contacts`,
      }
    ],
  },
  // {
  //   id: "expert-requests",
  //   icon: "iconsminds-telephone-2",
  //   label: "menu.expert-request.list",
  //   to: `${adminRoot}/expert-requests`,
  //   roles: [UserRole.Admin],
  //   subs: [
  //     {
  //       icon: "simple-icon-home",
  //       label: "menu.expert-request.list",
  //       to: `${adminRoot}/expert-requests/list`,
  //     },
  //   ],
  // },
  {
    id: "operators",
    icon: "iconsminds-user",
    label: "menu.operators",
    to: `${adminRoot}/operators`,
    // roles: [UserRole.Admin, UserRole.Editor],
    permission: ["operators.create", "operators.list", "operators.edit"],
    subs: [
      {
        icon: "simple-icon-user-follow",
        label: "menu.operators.create",
        to: `${adminRoot}/operators/create`,
        // roles: [UserRole.Admin],
        permission: ["operators.create"],
      },
      {
        icon: "simple-icon-list",
        label: "menu.operators.list",
        to: `${adminRoot}/operators/list`,
        matchPaths: [`${adminRoot}/operators/edit`, `${adminRoot}/operators/permission`],
        // roles: [UserRole.Admin],
        permission: ["operators.create", "operators.list", "operators.edit"],
      }
    ],
  },
  {
    id: "advertisement-settings",
    icon: "iconsminds-management",
    label: "menu.advertisement.setting",
    to: `${adminRoot}/advertisements`,
    roles: [UserRole.Admin],
    subs: [
      { icon: "simple-icon-layers", label: "قالب‌های ویژگی ملک", to: `${adminRoot}/advertisements/wrappers/list`, matchPaths: [`${adminRoot}/advertisements/wrappers`] },
      {
        icon: "simple-icon-list",
        label: "menu.advertisement-category",
        to: `${adminRoot}/advertisements/categories/list`,
        matchPaths: [`${adminRoot}/advertisements/categories`],
        // roles: [UserRole.Admin],
      },
      {
        icon: "simple-icon-check",
        label: "menu.properties",
        to: `${adminRoot}/advertisements/properties/list`,
        matchPaths: [`${adminRoot}/advertisements/properties`],
        // roles: [UserRole.Admin],
      },
    ],
  },
  {
    id: "partner-panels",
    icon: "iconsminds-handshake",
    label: "menu.partner-panels",
    to: `${adminRoot}/partner-panels`,
    roles: [UserRole.Admin],
    subs: [
      {
        icon: "simple-icon-grid",
        label: "menu.partner-panels.list",
        to: `${adminRoot}/partner-panels`,
        roles: [UserRole.Admin],
      },
      {
        icon: "simple-icon-diamond",
        label: "menu.broker-properties",
        to: `${adminRoot}/partner-panels/broker-properties`,
        roles: [UserRole.Admin],
      },
      { icon: "simple-icon-diamond", label: "فرصت‌های سرمایه‌گذاری", to: `${adminRoot}/partner-panels/investor-opportunities`, roles: [UserRole.Admin] },
    ],
  },
  {
    id: "customers",
    icon: "iconsminds-user",
    label: "مدیریت مشتریان اسپاد",
    to: `${adminRoot}/customers`,
    roles: [UserRole.Admin],
  },
  {
    id: "customer-sms",
    icon: "simple-icon-envelope-letter",
    label: "ارسال پیامک",
    to: `${adminRoot}/settings/sms/send`,
    roles: [UserRole.Admin],
  },
  // {
  //   id: "employ-requests",
  //   icon: "iconsminds-handshake",
  //   label: "menu.employ-request.list",
  //   to: `${adminRoot}/employ-requests/list`,
  //   // subs: [
  //   //   {
  //   //     icon: "simple-icon-list",
  //   //     label: "menu.employ-request.list",
  //   //     to: ``,
  //   //   },
  //   // ],
  //   roles: [UserRole.Admin],
  // },
  // {
  //   id: "contact",
  //   icon: "iconsminds-user",
  //   label: "menu.contact",
  //   to: `${adminRoot}/contacts`,
  //   permission: [],
  //   // subs: [
  //   //   {
  //   //     icon: "simple-icon-user-following",
  //   //     label: "menu.posts",
  //   //     to: `${adminRoot}/blogs/posts/list`,
  //   //   }
  //   // ],
  // },
  {
    id: "media_management",
    icon: "iconsminds-video-tripod",
    label: "menu.media_management",
    to: `${adminRoot}/media_management`,
    permission: [],
    // subs: [
    //   {
    //     icon: "simple-icon-user-following",
    //     label: "menu.posts",
    //     to: `${adminRoot}/blogs/posts/list`,
    //   }
    // ],
  },
  {
    id: "ai-assistant",
    icon: "simple-icon-bubbles",
    label: "دستیار هوشمند ملکی",
    to: `${adminRoot}/ai-assistant`,
    roles: [UserRole.Admin],
  },
  {
    id: "home-services",
    icon: "simple-icon-home",
    label: "خدمات خانه اسپاد",
    to: `${adminRoot}/home-services/scan`,
    roles: [UserRole.Admin],
    subs: homeServices.map(({ title, id, icon }) => ({
      icon,
      label: title,
      to: `${adminRoot}/home-services/${id}`,
      roles: [UserRole.Admin],
    })),
  },
  {
    id: "web-services",
    icon: "simple-icon-puzzle",
    label: "menu.web-services",
    to: `${adminRoot}/web-services`,
    roles: [UserRole.Admin],
  },
  {
    id: "settings",
    icon: "simple-icon-settings",
    label: "menu.settings",
    to: `${adminRoot}/settings`,
    roles: [UserRole.Admin],
    subs: [
      {
        icon: "simple-icon-envelope-letter",
        label: "تنظیمات پیامک ایران پیامک",
        to: `${adminRoot}/settings/sms`,
        roles: [UserRole.Admin],
      },
      {
        icon: "simple-icon-settings",
        id: "main-settings",
        label: "menu.settings",
        to: `${adminRoot}/settings/main`,
      },
      {
        icon: "simple-icon-direction",
        id: "cities",
        label: "menu.cities",
        to: `${adminRoot}/settings/cities`,
      },
      {
        icon: "simple-icon-location-pin",
        id: "regions",
        label: "menu.regions",
        to: `${adminRoot}/settings/regions`,
      },
      {
        icon: "simple-icon-people",
        id: "public-site-users",
        label: "آمار و فعالیت کاربران",
        to: `${adminRoot}/settings/user-statistics`,
        roles: ["Super Admin"],
      },
      { icon: "simple-icon-menu", label: "فهرست عناصر سایدبار سایت", to: `${adminRoot}/settings/sidebar-list` },
      { icon: "simple-icon-plus", label: "افزودن عنصر سایدبار سایت", to: `${adminRoot}/settings/sidebar-post` },
    ],
  },
  {
    id: "locations",
    icon: "simple-icon-location-pin",
    label: "تقسیمات جغرافیایی",
    roles: [UserRole.Admin],
    subs: [
      { icon: "simple-icon-map", label: "استان‌ها", to: `${adminRoot}/location/provinces/list`, matchPaths: [`${adminRoot}/location/provinces`] },
      { icon: "simple-icon-plus", label: "افزودن استان", to: `${adminRoot}/location/provinces/create` },
      { icon: "simple-icon-direction", label: "شهرها", to: `${adminRoot}/location/cities/list`, matchPaths: [`${adminRoot}/location/cities`] },
      { icon: "simple-icon-location-pin", label: "مناطق", to: `${adminRoot}/location/regions/list`, matchPaths: [`${adminRoot}/location/regions`] },
    ],
  },
  {
    id: "license",
    icon: "simple-icon-settings",
    label: "menu.license",
    to: `${adminRoot}/license`,
    roles: [UserRole.Admin],
  }
  /*{
    id: 'applications',
    icon: 'iconsminds-air-balloon-1',
    label: 'menu.applications',
    to: `${adminRoot}/applications`,
    subs: [
      {
        icon: 'simple-icon-check',
        label: 'menu.todo',
        to: `${adminRoot}/applications/todo`,
      },
      {
        icon: 'simple-icon-calculator',
        label: 'menu.survey',
        to: `${adminRoot}/applications/survey`,
      },
      {
        icon: 'simple-icon-bubbles',
        label: 'menu.chat',
        to: `${adminRoot}/applications/chat`,
      },
    ],
  },*/
];
export default data;

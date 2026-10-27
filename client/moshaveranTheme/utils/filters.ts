import { IFilters } from "../types/ads.types";

export const filters: IFilters = {
  global_filters: {
    "doc-status": {
      id: "doc-status",
      label: "وضعیت سند",
      type: "select",
      choices: ["شش دانگ", "قراردادی", "قولنامه ای", "در دست اقدام"],
    },
    orientation: {
      id: "orientation",
      label: "موقعیت",
      type: "select",
      choices: [
        "شمالی",
        "جنوبی",
        "شرقی",
        "غربی",
        "شمالی- شرقی (نبش یا 2 بر)",
        "شمالی- غربی (نبش یا 2 بر)",
        "جنوبی- شرقی (نبش یا 2 بر)",
        "جنوبی- غربی (نبش یا 2 بر)",
        "2 کله شمالی- جنوبی",
        "2 کله شرقی- غربی",
        "3 نبش یا 3 بر",
      ],
    },
    build_year: {
      id: "build_year",
      label: "سال ساخت",
      type: "year-from",
      start: "1370",
      end: "1401",
    },
    area: {
      id: "area",
      label: "متراژ",
      type: "number",
    },
    elevator: {
      id: "elevator",
      type: "on-off",
      std: "on",
      label: "آسانسور",
    },
    parking: {
      id: "parking",
      type: "on-off",
      std: "off",
      label: "پارکینگ",
    },
    immediate: {
      id: "immediate",
      type: "on-off",
      std: "off",
      label: "فوری",
    },
    changeable: {
      id: "changeable",
      type: "on-off",
      std: "off",
      label: "قابل معاوضه",
    },
    luxe: {
      id: "luxe",
      type: "on-off",
      std: "off",
      label: "لوکس",
    },
    cellar: {
      id: "cellar",
      type: "on-off",
      std: "off",
      label: "انباری",
    },
  },
  transactions: {
    "48": {
      term_id: 48,
      term_name: "فروش",
      related: {
        sale_price: {
          id: "sale_price",
          label: "قیمت",
          type: "number",
          choices: null,
        },
      },
      land_types: [
        {
          term_id: 42,
          term_name: "تجاری",
          related: {
            "commercial-aperture_area": {
              id: "commercial-aperture_area",
              label: "متراژ دهنه",
              type: "number",
              choices: null,
            },
            "commercial-cave_height": {
              id: "commercial-cave_height",
              label: "ارتفاع مغازه",
              type: "number",
              choices: null,
            },
          },
        },
        {
          term_id: 44,
          term_name: "آپارتمان",
          related: {
            "apartment-foundation-type": {
              id: "apartment-foundation-type",
              label: "نوع ساخت",
              type: "select",
              choices: ["شخصی ساز", "مجتمع", "ویلا"],
            },
            "apartment-floor": {
              id: "apartment-floor",
              label: "طبقه",
              type: "number",
              choices: null,
            },
            "apartment-rooms-count": {
              id: "apartment-rooms-count",
              label: "تعداد اتاق",
              type: "number",
              choices: null,
            },
            "apartment-floors-count": {
              id: "apartment-floors-count",
              label: "تعداد کل طبقات",
              type: "number",
              choices: null,
            },
            "apartment-floor-sections": {
              id: "apartment-floor-sections",
              label: "تعداد واحدهای طبقه",
              type: "number",
              choices: null,
            },
          },
        },
        {
          term_id: 45,
          term_name: "حیاط",
          related: {
            "yard-foundation-area": {
              id: "yard-foundation-area",
              label: "متراژ بنا",
              type: "number",
              choices: null,
            },
            "yard-floors-count": {
              id: "yard-floors-count",
              label: "تعداد طبقات",
              type: "number",
              choices: null,
            },
          },
        },
        {
          term_id: 46,
          term_name: "باغ و ویلا",
          related: {
            "garden-foundation-area": {
              id: "garden-foundation-area",
              label: "متراژ ویلا",
              type: "number",
              choices: null,
            },
            "garden-trees-estimation": {
              id: "garden-trees-estimation",
              label: "حدود درختان",
              type: "number",
              choices: null,
            },
            "garden-fencing": {
              id: "garden-fencing",
              label: "حصارکشی",
              type: "on-off",
              choices: null,
            },
          },
        },
        {
          term_id: 72,
          term_name: "زمین",
        },
        {
          term_id: 73,
          term_name: "کارخانه",
        },
        {
          term_id: 74,
          term_name: "کارگاه",
        },
        {
          term_id: 75,
          term_name: "سوله",
          related: {
            niches_height: {
              id: "niches_height",
              label: "ارتفاع",
              type: "number",
              choices: null,
            },
          },
        },
      ],
      icon: "https://lara-melk.ir/storage/thumbnails/2021/05/02/Realtor-rafiki_1619966371.png",
    },
    "49": {
      term_id: 49,
      term_name: "رهن و اجاره",
      related: {
        prepayment: {
          id: "prepayment",
          label: "ودیعه",
          type: "number",
          choices: null,
        },
        rent: {
          id: "rent",
          label: "اجاره",
          type: "number",
          choices: null,
        },
      },
      land_types: [
        {
          term_id: 42,
          term_name: "تجاری",
          related: {
            "commercial-aperture_area": {
              id: "commercial-aperture_area",
              label: "متراژ دهنه",
              type: "number",
              choices: null,
            },
            "commercial-cave_height": {
              id: "commercial-cave_height",
              label: "ارتفاع مغازه",
              type: "number",
              choices: null,
            },
          },
        },
        {
          term_id: 44,
          term_name: "آپارتمان",
          related: {
            "apartment-foundation-type": {
              id: "apartment-foundation-type",
              label: "نوع ساخت",
              type: "select",
              choices: ["شخصی ساز", "مجتمع", "ویلا"],
            },
            "apartment-floor": {
              id: "apartment-floor",
              label: "طبقه",
              type: "number",
              choices: null,
            },
            "apartment-rooms-count": {
              id: "apartment-rooms-count",
              label: "تعداد اتاق",
              type: "number",
              choices: null,
            },
            "apartment-floors-count": {
              id: "apartment-floors-count",
              label: "تعداد کل طبقات",
              type: "number",
              choices: null,
            },
            "apartment-floor-sections": {
              id: "apartment-floor-sections",
              label: "تعداد واحدهای طبقه",
              type: "number",
              choices: null,
            },
          },
        },
        {
          term_id: 45,
          term_name: "حیاط",
          related: {
            "yard-foundation-area": {
              id: "yard-foundation-area",
              label: "متراژ بنا",
              type: "number",
              choices: null,
            },
            "yard-floors-count": {
              id: "yard-floors-count",
              label: "تعداد طبقات",
              type: "number",
              choices: null,
            },
          },
        },
      ],
      icon: "https://lara-melk.ir/storage/thumbnails/2021/05/02/Realtor-amico_1619966333.png",
    },
    "50": {
      term_id: 50,
      term_name: "پیش فروش",
      related: {
        presell_price: {
          id: "presell_price",
          label: "قیمت ملک",
          type: "number",
          choices: null,
        },
      },
      land_types: [
        {
          term_id: 42,
          term_name: "تجاری",
          related: {
            "commercial-aperture_area": {
              id: "commercial-aperture_area",
              label: "متراژ دهنه",
              type: "number",
              choices: null,
            },
            "commercial-cave_height": {
              id: "commercial-cave_height",
              label: "ارتفاع مغازه",
              type: "number",
              choices: null,
            },
          },
        },
        {
          term_id: 44,
          term_name: "آپارتمان",
          related: {
            "apartment-foundation-type": {
              id: "apartment-foundation-type",
              label: "نوع ساخت",
              type: "select",
              choices: ["شخصی ساز", "مجتمع", "ویلا"],
            },
            "apartment-floor": {
              id: "apartment-floor",
              label: "طبقه",
              type: "number",
              choices: null,
            },
            "apartment-rooms-count": {
              id: "apartment-rooms-count",
              label: "تعداد اتاق",
              type: "number",
              choices: null,
            },
            "apartment-floors-count": {
              id: "apartment-floors-count",
              label: "تعداد کل طبقات",
              type: "number",
              choices: null,
            },
            "apartment-floor-sections": {
              id: "apartment-floor-sections",
              label: "تعداد واحدهای طبقه",
              type: "number",
              choices: null,
            },
          },
        },
      ],
      icon: "https://lara-melk.ir/storage/thumbnails/2021/05/02/Building-bro_1619966301.png",
    },
  },
};

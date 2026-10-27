import { ICreateAd } from "../types/ads.types";

export const createAd: ICreateAd = {
  metas: {
    "0": {
      label: "مشخصات ساختمان",
      position: "side",
      sort: "3",
      content: [
        {
          id: "transaction",
          label: "نوع معامله",
          type: "transaction-select",
          choices: [
            {
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
                      choices: ["شخصی ساز", "مجتمع"],
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
                {
                  term_id: 80,
                  term_name: "دفتر خدماتی اداری",
                  related: {
                    "official-foundation-type": {
                      id: "official-foundation-type",
                      label: "نوع ساخت",
                      type: "select",
                      choices: ["شخصی ساز", "مجتمع"],
                    },
                    "apartment-floor": {
                      id: "apartment-floor",
                      label: "طبقه",
                      type: "number",
                      choices: null,
                    },
                    "official-rooms-count": {
                      id: "official-rooms-count",
                      label: "تعداد اتاق",
                      type: "number",
                      choices: null,
                    },
                    "official-floors-count": {
                      id: "official-floors-count",
                      label: "تعداد کل طبقات",
                      type: "number",
                      choices: null,
                    },
                    "official-floor-sections": {
                      id: "official-floor-sections",
                      label: "تعداد واحدهای طبقه",
                      type: "number",
                      choices: null,
                    },
                  },
                },
              ],
            },
            {
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
                      choices: ["شخصی ساز", "مجتمع"],
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
                  term_id: 80,
                  term_name: "دفتر خدماتی اداری",
                  related: {
                    "official-foundation-type": {
                      id: "official-foundation-type",
                      label: "نوع ساخت",
                      type: "select",
                      choices: ["شخصی ساز", "مجتمع"],
                    },
                    "apartment-floor": {
                      id: "apartment-floor",
                      label: "طبقه",
                      type: "number",
                      choices: null,
                    },
                    "official-rooms-count": {
                      id: "official-rooms-count",
                      label: "تعداد اتاق",
                      type: "number",
                      choices: null,
                    },
                    "official-floors-count": {
                      id: "official-floors-count",
                      label: "تعداد کل طبقات",
                      type: "number",
                      choices: null,
                    },
                    "official-floor-sections": {
                      id: "official-floor-sections",
                      label: "تعداد واحدهای طبقه",
                      type: "number",
                      choices: null,
                    },
                  },
                },
              ],
            },
            {
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
                      choices: ["شخصی ساز", "مجتمع"],
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
            },
            {
              term_id: 77,
              term_name: "مشارکت",
              related: {
                swit_and_hotel_daily_rent: {
                  id: "swit_and_hotel_daily_rent",
                  label: "اجاره روزانه",
                  type: "number",
                  choices: null,
                },
              },
              land_types: [
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
            },
          ],
        },
      ],
    },
    "1": {
      label: "مشخصات تماس",
      sort: "2",
      position: "normal",
      content: [
        {
          id: "owner_name",
          label: "نام مالک",
          type: "text",
          validate_rule: {
            owner_name: "required",
          },
          validate_message: {
            "owner_name.required": "وارد کردن نام مالک الزامی است.",
          },
        },
        {
          id: "phone",
          label: "شماره صاحب ملک",
          type: "text",

          validate_rule: {
            phone: "required",
          },
          validate_message: {
            "phone.required": "وارد کردن شماره ملک الزامی است.",
          },
        },
      ],
    },
    "2": {
      label: "گالری تصاویر",
      content: [
        {
          id: "image_gallery",
          type: "gallery",
          class: "col-xl-3 col-lg-4 col-md-6 col-sm-12",
          description: "تصاویر خود را انتخاب کنید",
        },
        {
          id: "vidoe_link",
          label: "لینک ویدیو",
          type: "text",
          description:
            "اگر می خواهید ویدویی نشان داده شود لینک ویدیو را وارد کنید",
        },
      ],
      position: "normal",
      sort: "3",
    },
    "4": {
      label: "وضعیت ملک",
      position: "side",
      sort: "3",
      content: [
        {
          id: "doc-status",
          label: "وضعیت سند",
          type: "select",
          choices: ["شش دانگ", "قراردادی", "قولنامه ای", "در دست اقدام"],
        },
        {
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
        {
          id: "build_year",
          label: "سال ساخت",
          type: "year-from",
          start: "1370",

          end: "1401",
        },
        {
          id: "area",
          label: "متراژ",
          type: "number",
        },
      ],
    },
    "5": {
      label: "ویژگی های ملک",
      content: [
        {
          id: "elevator",
          type: "on-off",
          std: "off",
          label: "آسانسور",

          class: "col-md-6 float-left",
        },
        {
          id: "parking",
          type: "on-off",
          std: "off",
          label: "پارکینگ",
          class: "col-md-6 float-left",
        },
        {
          id: "immediate",
          type: "on-off",
          std: "off",
          label: "فوری",
          class: "col-md-6 float-left",
        },
        {
          id: "changeable",
          type: "on-off",
          std: "off",
          label: "قابل معاوضه",
          class: "col-md-6 float-left",
        },
        {
          id: "luxe",
          type: "on-off",
          std: "off",
          label: "لوکس",
          class: "col-md-6 float-left",
        },
        {
          id: "cellar",
          type: "on-off",
          std: "off",
          label: "انباری",
          class: "col-md-6 float-left",
        },
      ],
      position: "normal",
      sort: "2",
    },
    "6": {
      id: "branches",
      label: "انشعابات",
      position: "side",
      content: [
        {
          id: "water",
          label: "آب",
          type: "on-off",
          std: "on",
        },
        {
          id: "electricity",
          label: "برق",
          type: "on-off",
          std: "on",
        },
        {
          id: "gas",
          label: "گاز",
          type: "on-off",
          std: "on",
        },
      ],
    },
  },
  terms: [
    {
      label: "امکانات رفاهی",
      choices: {
        "59": "وای فای",
        "60": "استخر",
        "61": "تراس",
        "63": "امکانات بدنسازی",
        "76": "سالن اجتماعات",
        "81": "لابی",
        "82": "پارکینگ مازاد",
      },
    },
  ],
};

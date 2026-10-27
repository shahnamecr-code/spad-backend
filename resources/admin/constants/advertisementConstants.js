export const advertisementPropertyTypes=[
  {label: 'متن', value: 'text'},
  {label: 'ناحیه متنی', value: 'textarea'},
  {label: 'عدد صحیح', value: 'integer'},
  {label: 'فیلد منطقی', value: 'boolean'},
  {label: 'انتخاب کشویی', value: 'select'},
  {label: 'انتخاب کشویی چندگانه', value: 'multiselect'},
  {label: 'رادیو', value: 'radio'},
  {label: 'چک باکس', value: 'checkbox'},
  {label: 'تصویر', value: 'images'},
  {label: 'موقعیت آگهی', value: 'geo'},
  // {label: 'نوع ملک', value: 'land_type'},
];

export const advertisementSearchTypes=[
  {label: 'تایپ کاربر', value: 'text'},
  {label: 'انتخاب کشویی', value: 'select'},
  {label: 'انتخاب کشویی چندگانه', value: 'multi-select'},
  {label: 'رادیو', value: 'radio'},
  {label: 'چک باکس', value: 'checkbox'},
  {label: 'فیلد منطقی', value: 'switch'},
  {label: 'موقعیت', value: 'geo'},
];

export const advertisementSearchOperators=[
  {label: 'کوچکتر', value: 'lt'},
  {label: 'مساوی', value: 'eq'},
  {label: 'بزرگتر', value: 'gt'},
  {label: 'شامل', value: 'like'},
];

/*export const advertisementDependencyTypes=[
  {label: 'و', value: 'and'},
  {label: 'یا', value: 'or'},
];*/

export const advertisementDependencyTypes=[
  {label: 'کوچکتر', value: 'lt'},
  {label: 'مساوی', value: 'eq'},
  {label: 'بزرگتر', value: 'gt'},
  {label: 'شامل', value: 'like'},
];

export const advertisementSearchLevels=[
  {label: 'جستجوی دقیق', value: 1},
  {label: 'جستجوی ۹۰ درصد', value: 2},
  {label: 'جستجوی ۸۰ درصد', value: 3},
  {label: 'جستجوی ۷۰ درصد', value: 4},
  {label: 'جستجوی ۶۰ درصد', value: 5},
  {label: 'جستجوی ۵۰ درصد', value: 6},
];

export const advertisementUnitTypes=[
  {label: 'متر', value: 'm'},
  {label: 'متر مربع', value: 'm2'},
  {label: 'متر مکعب', value: 'm3'},
  {label: 'تومان', value: 'IRT'},
  {label: 'ریال', value: 'IRR'},
  {label: 'سال', value: 'year'},
  {label: 'ماه', value: 'month'},
  {label: 'روز', value: 'day'},
  {label: 'ساعت', value: 'hour'},
  {label: 'دقیقه', value: 'min'},
  {label: 'ثانیه', value: 'second'},
];
export const advertisementFieldAccessOptions=[
  {label:'فیلد عمومی است',value:0},
  {label:'فیلد مختص کارشناسان است',value:1},
]

export const advertisementSingleGroupLayouts=[
  {label:'نمایش عنوان به همراه مقادیر ویژگی',value:'layout1'},
  {label:'نمایش آیکون ویژگی به همراه عنوان',value:'layout2'},
];

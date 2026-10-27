const tabs = [
  {
    name : "تب یک",
    group: "first_tab",
    subs : [
      {
        code : "first_name",
        name : "نام",
        type : "text",
        default : ""

      },
      {
        code : "logo",
        name : "لوگو",
        type : "image",
        default : "",

      },
      {
        code : "last_name",
        name : "نام خانوادگی",
        type : "text",
        default : ""

      },
      {
        code : "gender",
        name : "جنسیت",
        type : "text",
        default : "",
      },
    ]
  },
  {
    name : "تب دو",
    group: "second_tab",
    subs : [
      {
        code : "action_type",
        name : "نوع فعالیت",
        type : "select",
        default : "",
        options : [{name : "لیبل اول", value : "value_one"},]


      },
      {
        code : "active",
        name : "فعال",
        type : "boolean",
        default : "",

      },
      {
        code : "first_select",
        name : "اینپوت سلکت",
        type : "select",
        default : "",
        options : [{label : "لیبل اول", value : "value_one"},]

      },
    ]
  }
]

export default tabs;

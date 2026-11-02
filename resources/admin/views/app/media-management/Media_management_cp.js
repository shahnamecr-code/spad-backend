import React, {useEffect, useState} from "react";
import 'dropzone/dist/min/dropzone.min.css';
import {MediaLibrary} from "../../../components/mediaLibrary/MediaLibrary";

const ReactDOMServer = require('react-dom/server');

const Media_management = () => {
  const [show, setShow] = useState(false);
  const [files, setFiles] = useState([]);

  const onSubmit = (values) => {
    console.log(values);
  }

  return (
    <section className="spad-v9-media-library" dir="rtl">
      <div className="spad-v9-media-library__intro">
        <div><span>SPAD MEDIA ARCHIVE</span><h3>فایل‌ها و تصاویر سایت</h3>
          <p>آپلود، دسته‌بندی و مدیریت تصاویر و فایل‌های رسانه‌ای بدون تغییر در محتوای موجود</p></div>
        <i className="simple-icon-picture" aria-hidden="true" />
      </div>
      <MediaLibrary
        multiSelect
        onSubmit={(filesValues) => {
          setFiles(filesValues);
          // onSubmit(filesValues);
        }}
        // defaultFileIdz={defaultFileIdz && defaultFileIdz.map((el) => el.id)}
        setShow={setShow}
      />
    </section>
  )
}

export default Media_management

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
    <>
      <MediaLibrary
        multiSelect
        onSubmit={(filesValues) => {
          setFiles(filesValues);
          // onSubmit(filesValues);
        }}
        // defaultFileIdz={defaultFileIdz && defaultFileIdz.map((el) => el.id)}
        setShow={setShow}
      />
    </>
  )
}

export default Media_management

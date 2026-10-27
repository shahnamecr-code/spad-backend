import React from "react";
import {Button, Input, InputGroup, InputGroupAddon, InputGroupText, Modal, ModalBody, ModalHeader} from "reactstrap";
import {Field, Form, Formik} from "formik";
import {adminPathApi, uploadPath} from "../constants/defaultValues";
import axios from "axios";
import DropzoneComponent from "react-dropzone-component";
import 'dropzone/dist/min/dropzone.min.css';
import {connect} from "react-redux";

const ReactDOMServer = require('react-dom/server');

const MediaLibrary = ({onSubmit, show, setShow, multiple, shopId, default_value = [], ...props}) => {
  const [files, setFiles] = React.useState([]);
  const [dir,setDir]=React.useState(['']);
  const [loading,setLoading]=React.useState(true);
  const [uploading,setUploading]=React.useState(false);
  const [show_dropzone,setShowDropzone]=React.useState(false);

  const go_back=()=>{
    if(dir.length>1 && !uploading){
      let dir_cp=[...dir];
      dir_cp.pop();
      setDir(dir_cp);
    }
  }

  const load_files=()=>{
    setLoading(true);
    axios
      .post(`${adminPathApi}/file`, {dir:dir.join('/')})
      .then(res=>res.data)
      .then(data=>{
        if(data.status){
          setFiles(data.files);
          setLoading(false);
        }
      })
  }

  const add_folder=()=>{
    if(!uploading){
      swal({
        title: 'نام پوشه را وارد کنید',
        content: {
          element: "input",
          attributes: {
            type: "text",
          },
        },
        buttons: ['لغو', 'ایجاد'],
      }).then(async (result)=> {
        if (result !== null && result.length>0) {
          axios
            .post(`${adminPathApi}/file/create`,{directory:dir.join('/'),folder:result})
            .then(res=>res.data)
            .then(data=>{
              if(data.status){
                setFiles(data.files)
                swal(data.message,{icon:'success',timer:3000,buttons:false});
              }else{
                swal(data.message,{icon:'error',timer:3000,buttons:false})
              }
            })
        }
      })
    }
  }

  React.useEffect(async () => {
    if (show) {
      await load_files()
    }
  }, [dir, shopId, show]);

  let componentConfig = {
    postUrl: `${adminPathApi}/file/upload`,
  };

  const selectFiles=(values)=>{
    onSubmit(values);
    setShow(false);
  }

  let djsConfig = {
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="token"]').content,
      shopId:shopId.value
    },
    params:{
      upload_path:dir.join('/'),
    },
    dictDefaultMessage: "فایل ها را برای ارسال اینجا بکشید",
    dictFallbackMessage: "مرورگر شما از کشیدن و رها سازی برای ارسال فایل پشتیبانی نمی کند.",
    dictFallbackText: "لطفا از فرم زیر برای ارسال فایل های خود مانند گذشته استفاده کنید.",
    dictInvalidFileType: "شما مجاز به ارسال این نوع فایل نیستید.",
    dictCancelUpload: "لغو ارسال",
    dictUploadCanceled: "ارسال لغو شد.",
    dictCancelUploadConfirmation: "آیا از لغو این ارسال اطمینان دارید؟",
    dictRemoveFile: "حذف فایل",
    dictRemoveFileConfirmation: "آیا از حذف این فایل اطمینان دارید؟",
    dictMaxFilesExceeded: "شما نمی توانید فایل دیگری ارسال کنید.",
    previewTemplate: ReactDOMServer.renderToStaticMarkup(
      <div className="dz-preview dz-file-preview mb-3">
        <div className="d-flex flex-row ">
          <div className="p-0 w-30 position-relative">
            <div className="dz-error-mark">
            <span>
              <i/>{' '}
            </span>
            </div>
            <div className="dz-success-mark">
            <span>
              <i/>
            </span>
            </div>
            <div className="preview-container">
              {/*  eslint-disable-next-line jsx-a11y/alt-text */}
              <img data-dz-thumbnail className="img-thumbnail border-0"/>
              <i className="simple-icon-doc preview-icon"/>
            </div>
          </div>
          <div className="pl-3 pt-2 pr-2 pb-1 w-70 dz-details position-relative">
            <div>
              {' '}
              <span data-dz-name/>{' '}
            </div>
            <div className="text-primary text-extra-small" data-dz-size/>
            <div className="dz-error-message">
              <span data-dz-errormessage/>
            </div>
          </div>
        </div>
      </div>
    ),
  };

  return (
    <Modal size="xl" isOpen={show} toggle={() => setShow(!show)}>
      <Formik initialValues={{files:default_value}} onSubmit={selectFiles}>
        {({errors, touched, setFieldValue, setFieldTouched, values}) => (
        <Form>
          <ModalHeader>
            مدیریت رسانه ها
          </ModalHeader>
          <ModalBody>
            <div className="d-flex justify-content-between">
              <div className="btn-group">
                <Button color="primary" disabled={dir.length<=1} onClick={go_back}>
                  <span className="iconsminds-to-top"/>
                </Button>
                <Button color="info" onClick={load_files}>
                  <span className="simple-icon-refresh"/>
                </Button>
                <Button color="primary" onClick={()=>setShowDropzone(!show_dropzone)}>
                  <span className="simple-icon-cloud-upload"/>
                </Button>
                <Button color="secondary" onClick={add_folder}>
                  <span className="iconsminds-folder-open"/>
                </Button>
                <Button color="success" type="submit">
                  <span className="simple-icon-check"/>
                </Button>
              </div>
              <InputGroup style={{width: '400px'}}>
                <Input className={'form-control'} placeholder='جستجو...'/>
                <InputGroupAddon addonType="append">
                  <InputGroupText><i className="fa fa-search"/></InputGroupText>
                </InputGroupAddon>
              </InputGroup>
            </div>
            <div className={`mt-4 ${show_dropzone?'':'d-none'}`}>
              <DropzoneComponent config={componentConfig} djsConfig={djsConfig} eventHandlers={{
              addedfile: (file) => {
                setUploading(true);
              },
              success:(file, response)=> {
                if(response.status){
                  setFiles(response.files);
                }
              },
              queuecomplete:(file)=>{
                setUploading(false);
              },
            }}/>
            </div>
            {loading?(
              <div className="library-files">
                <div className="lds-ripple">
                  <div/><div/>
                </div>
              </div>
            ):(
              <div className="library-files">
                {files.map(file=>(
                  <div className={`file-wrapper ${file.type=='folder'?'has-icon':''}`} key={file.name}>
                    <div className={`img-box ${(values.files && values.files.includes(file.type!='folder' && file.relative_path)) ? 'selected' : ''}`}>
                      <label className={multiple?'multiple':'single'} htmlFor={file.name} onDoubleClick={()=>{
                        if (!uploading && file.type == "folder") {
                          setDir([...dir, file.name])
                        }
                      }}>
                        {file.type=='folder'?(
                          <span className="simple-icon-folder"/>
                        ):(
                          <img src={file.path}/>
                        )}
                      </label>
                      <Field type={multiple?'checkbox':'radio'} id={file.name} className="form-check-input" name={file.type=='folder'?undefined:'files'} disabled={file.type=='folder'} value={file.relative_path}/>
                    </div>
                    <p className="file-name">{file.name}</p>
                  </div>
                ))}
              </div>
            )}
          </ModalBody>
        </Form>
          )}
      </Formik>
    </Modal>
  );
};
const mapStateToProps=({authUser: {shopId}})=>{
  return {shopId};
}
const MediaLibraryWithShopId = connect(mapStateToProps)(MediaLibrary);
export {MediaLibraryWithShopId};

const MediaButton = ({default_value, name, setFieldValue, multiple = false}) => {
  const [show, setShow] = React.useState(false);

  const onClick = () => {
    setShow(true);
  }

  const onSubmit = (values) => {
    setFieldValue(name, values.files);
  }

  return (
    <>
      <Button color={'primary'} onClick={onClick} className="ml-1">انتخاب</Button>
      {multiple ? (
        <>
          {default_value && default_value.map(file => (
            <div className="media_select_image_wrapper">
              <img className="media_select_image" src={`${uploadPath}/${file}`}/>
              <span className="simple-icon-trash remove-icon" onClick={() => {
                let prev=[...default_value];
                setFieldValue(name,prev.filter(val=>val!=file));
              }
              }/>
            </div>
          ))}
        </>
      ):(
        <>
        {default_value && (
            <div className="media_select_image_wrapper">
              <img className="media_select_image" src={`${uploadPath}/${default_value}`}/>
              <span className="simple-icon-trash remove-icon" onClick={()=>{
                setFieldValue(name,undefined);
              }
              }/>
            </div>
          )}
          </>
      )}
      <MediaLibraryWithShopId onSubmit={onSubmit} setShow={setShow} show={show} multiple={multiple} default_value={default_value}/>

    </>
  )
}

export default MediaButton

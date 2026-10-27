import React, {Component} from "react";
import {CardTitle, CustomInput, FormGroup, Input, Label} from "reactstrap";
import DropzoneComponent from "react-dropzone-component";
import 'dropzone/dist/min/dropzone.min.css';

const ReactDOMServer = require('react-dom/server');

const UploadDocument = ({setFieldValue, values}) => {
  return (
    <>
      <CardTitle className="mb-4">
        مالیات بر ارزش افزوده
      </CardTitle>
      <FormGroup className="mb-4">
        <Label className={'ml-1'}>آیا مشمول مالیات بر افزوده می باشید؟</Label>
        <div>
          <CustomInput key={'actual'} type="radio" id={'actual'} label={"بلی"}
                       name={'maliyat'}
                       onChange={(val) => setFieldValue('maliyat', true)}
                       inline checked={values.maliyat}
          />
          <CustomInput key={'legal'} type="radio" id={'legal'} label={"خیر"}
                       name={'maliyat'}
                       onChange={(val) => setFieldValue('maliyat', false)}
                       value={!values.maliyat}
                       inline/>
        </div>
      </FormGroup>
      {values.maliyat &&
      <FormGroup className="mb-4">
        <Label className={'ml-1'}>تاریخ انقضای ارزش افزوده</Label>
        <Input/>
      </FormGroup>
      }
      <CardTitle>بارگذاری مدارک</CardTitle>
      {values.maliyat &&
      <FormGroup>
        <Label>تصویر گواهی ارزش افزوده</Label>
        <DropzoneCustom setFieldValue={setFieldValue} name={"photoTax"} values={values}
                        placeHolder={'آپلود تصویر ارزش افزوده'}/>
      </FormGroup>
      }
      <FormGroup>
        <Label>کارت ملی</Label>
        <FormGroup>
          <DropzoneCustom setFieldValue={setFieldValue} name={"frontIdCard"} values={values}
                          placeHolder={'آپلود تصویر جلوی کارت ملی'}/>
        </FormGroup>
        <FormGroup>
          <DropzoneCustom setFieldValue={setFieldValue} name={"backIdCard"} values={values}
                          placeHolder={'آپلود تصویر پشت کارت ملی'}/>
        </FormGroup>
      </FormGroup>
    </>
  );
};

export default UploadDocument;


const dropzoneComponentConfig = {
  postUrl: 'no-url',
};
const dropzoneConfig = {
  autoProcessQueue: false,
  thumbnailHeight: 160,
  maxFilesize: 1,
  withCredentials: true,
  maxFiles: 1,
  acceptedFiles: 'image/*',
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
      <a href="#/" className="remove" data-dz-remove>
        {' '}
        <i className="glyph-icon simple-icon-trash"/>{' '}
      </a>
    </div>
  ),
};

export class DropzoneCustom extends Component {
  clear() {
    this.myDropzone.removeAllFiles(true);
  }

  render() {
    return (
      <DropzoneComponent
        config={dropzoneComponentConfig}
        djsConfig={{
          ...dropzoneConfig,
          dictDefaultMessage: this.props.placeHolder ? this.props.placeHolder : dropzoneConfig.dictDefaultMessage
        }}
        eventHandlers={{
          init: (dropzone) => {
            this.myDropzone = dropzone;
          },
          addedfile: (file) => {
            this.props.setFieldValue(this.props.name, file);
            console.log(file);
          },
          maxfilesexceeded: (file) => {
            this.clear();
            this.myDropzone.addFile(file);
          },
          removedfile: () => {
            this.props.setFieldValue(this.props.name, undefined);
          }
        }}
      />
    );
  }
}

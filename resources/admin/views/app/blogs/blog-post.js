import React, {useEffect, useState} from 'react';
import {Row , Card,CardBody, CardTitle, FormGroup, Label,Input, CustomInput,CardSubtitle, CardText ,Button } from 'reactstrap';
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {Field, Form, Formik} from "formik";
import {FormikSwitch, FormikReactSelect} from "../../../containers/form-validations/FormikFields";
import ReactQuill , { Quill } from 'react-quill';
import 'react-quill/dist/quill.snow.css';
import ImageUploader from "quill-image-uploader";
import ImageResize from 'quill-image-resize-module-react';
import SearchMultiSelect from "../../../components/SearchMultiSelect";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import MediaButton from "../../../components/MediaLibrary";
import {NotificationManager} from "../../../components/common/react-notifications";
import AlertError from "../../../components/AlertError";
import {useHistory} from "react-router-dom";
import DatePicker from "react-multi-date-picker"
import persian from "react-date-object/calendars/persian"
import persian_fa from "react-date-object/locales/persian_fa"
import TimePicker from "react-multi-date-picker/plugins/time_picker";
import MediaLibraryButton from "../../../components/mediaLibrary/MediaLibrary";

Quill.register("modules/imageUploader", ImageUploader);
Quill.register('modules/imageResize', ImageResize);


const quillFormats = [
  'header',
  'bold',
  'italic',
  'underline',
  'strike',
  'blockquote',
  'list',
  'bullet',
  'indent',
  'link',
  'image',
];


const BlogPost = ({match,...props})=>{
  const [initialValues, steInitialValues] = useState({
    title : undefined,
    slug:undefined,
    category:undefined,
    start_time:undefined,
    end_time:undefined,
    status:false,
    image:undefined,
    description:undefined,
    short_description:undefined,
    meta_title:undefined,
    meta_description:undefined,
    meta_keywords:undefined,
  })
  const [validationErrors,setValidationErrors]=React.useState(false);
  const [loading,setLoading]=React.useState(true);
  const [options , setOptions]=React.useState([{}]);
  const [notChanging , setNotChanging]=React.useState(null);
  const [fileUrls, setFileUrls] = useState([]);

  const quillModules = {
    // #3 Add "image" to the toolbar
    toolbar: [
      ['bold', 'italic', 'underline', 'strike', 'blockquote'],
      [
        { list: 'ordered' },
        { list: 'bullet' },
        { indent: '-1' },
        { indent: '+1' },
      ],
      ['link', 'image'],
      ['clean'],
    ],
    imageResize: {
      parchment: Quill.import('parchment'),
      modules: ['Resize', 'DisplaySize']
    },
    // # 4 Add module and upload function
    imageUploader: {
      upload: (file) => {
        return new Promise((resolve, reject) => {
          const formData = new FormData();
          formData.append("file", file);
          axios
          .post(`${adminPathApi}/file/upload`, formData)
          .then((response) => {
             resolve(response.data.url)
            })
          .catch((error) => {
              reject("Upload failed");
              console.error("Error:", error)
            });
          })
      }
    }
  };

  let history = useHistory();
  useEffect(()=>{
    axios.get(`${adminPathApi}/settings/sidebar`)
      .then((res) => {
        setOptions(res.data);
        console.log(res)
      })
  },[notChanging])
  const getCategoriesList = (setloadOptions, search = '') => {
    axios
      .get(
        `${adminPathApi}/blog-category?page=1&per_page=10&search=${search}`,
      )
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        //setShowAttributeList(false)
        setloadOptions(data.data);
      });
  }

  React.useEffect(()=>{
    if(props.edit && match.params.id){
      setLoading(true);
      axios
        .get(`${adminPathApi}/blog-post/${match.params.id}`)
        .then(res=>res.data)
        .then(data=>{
          steInitialValues(data)
          setFileUrls(data.image)
          setLoading(false);
        })
        .catch(error=>{
          NotificationManager.error('خطایی در ارسال اطلاعات رخ داده است',null,1000,null,null,'filled');
        })
    }else{
      setLoading(false);
    }
  },[]);

  const save=(values)=>{
    if(props.edit && match.params.id){
      axios
        .post(`${adminPathApi}/blog-post/${match.params.id}/edit`,values)
        .then(res=>res.data)
        .then(data=>{
          if(data.status){
            NotificationManager.success(data.message,null,1000,null,null,'filled');
            history.push("/spad-admin/blogs/posts/list");
          }else{
            NotificationManager.error(data.message,null,1000,null,null,'filled');
          }
          setValidationErrors(false);
        })
        .catch(error=>{
          if(error.response.status == 422){
            setValidationErrors(error.response.data.errors)
          }else {
            NotificationManager.error('خطایی در ارسال اطلاعات رخ داده است',null,1000,null,null,'filled');
          }
        })
    }else{
      axios
        .post(`${adminPathApi}/blog-post/create`,values)
        .then(res=>res.data)
        .then(data=>{
          if(data.status){
            NotificationManager.success(data.message,null,1000,null,null,'filled');
            history.push("/spad-admin/blogs/posts/list");
          }else{
            NotificationManager.error(data.message,null,1000,null,null,'filled');
          }
          setValidationErrors(false);
        })
        .catch(error=>{
          if(error.response.status == 422){
            setValidationErrors(error.response.data.errors)
          }else {
            NotificationManager.error('خطایی در ارسال اطلاعات رخ داده است',null,1000,null,null,'filled');
          }
        })
    }
  }

  if (loading){
    return <div className="loading"/>
  }

  return(
    <React.Fragment>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading = "menu.blogs.posts" match = {match} />
          <Separator className = "mb-5"/>
        </Colxx>
      </Row>

      <Formik enableReinitialize initialValues = {initialValues} onSubmit = {save}>
        {
          ({errors, touched, setFieldValue, setFieldTouched, values})=>(
            <Form>
              <AlertError errors={validationErrors}/>
              <Row>
                <Colxx md = "12">
                  <Card>
                    <CardBody>
                      <CardTitle>اطلاعات عمومی</CardTitle>
                      <Row>
                        <Colxx md="6">
                          <FormGroup className="mt-3">
                            <Label>عنوان</Label>
                            <Field
                              name="title"
                              className="form-control"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="6">
                          <FormGroup className="mt-3">
                            <Label>پیوند یکتا</Label>
                            <Field
                              name="slug"
                              className="form-control"
                            />
                          </FormGroup>
                        </Colxx>
                        {/* <Colxx md="6">
                          <FormGroup className="mt-3">
                            <Label>دسته بندی</Label>
                            <SearchMultiSelect
                              request={getCategoriesList}
                              value={values.categories ? values.categories : []}
                              name='categories'
                              onChange={setFieldValue}
                              getOptionLabel={option => option.name}
                              getOptionValue={option => option.id}/>
                          </FormGroup>
                        </Colxx> */}
                        <Colxx md="12">
                          <FormGroup className="mt-3">
                            <Label>تصویر</Label>
                            <MediaLibraryButton
                              multiple={false}
                              onSubmit={(values) => setFieldValue('image', values)}
                              onChange={(values) => setFieldValue('image', values)}
                              defaultFileIdz={values['image'] || []}
                              defaultFileUrls={fileUrls ? fileUrls : []}
                              setDefaultFileUrls={setFileUrls}
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="12">
                          <FormGroup className="mt-4">
                            <Label>چکیده کوتاه</Label>
                            <Field
                              name="short_description"
                              className="form-control"
                              as="textarea"
                              rows="8"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="12">
                          <FormGroup className="mt-4">
                            <Label>شرح</Label>
                            <ReactQuill
                              theme="snow"
                              modules={quillModules}
                              formats={quillFormats}
                              value={values.description ? values.description : ''}
                              onChange={(val) => {
                                setFieldValue('description', val)
                              }}
                              name='description'
                            />
                          </FormGroup>
                        </Colxx>
                      </Row>
                    </CardBody>
                  </Card>
                </Colxx>
                <Colxx md = "12 mt-4">
                  <Card>
                    <CardBody>
                      <CardTitle>تنظیمات نمایش</CardTitle>
                      <Row>
                        <Colxx md="6">
                          <FormGroup>
                            <label>
                              شروع نمایش
                            </label>
                            <DatePicker
                                onChange={(value) => setFieldValue('start_time', value.valueOf())}
                                selected={values.start_time}
                                value={values.start_time}
                                format="DD/MM/YYYY"
                                calendar={persian}
                                locale={persian_fa}
                                className='form-control'
                                minDate={new Date()}
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="6">
                          <FormGroup>
                            <label>
                              پایان نمایش
                            </label>
                            <DatePicker
                              onChange={(value) => setFieldValue('end_time', value.valueOf())}
                              selected={values.end_time}
                              value={values.end_time}
                              format="DD/MM/YYYY"
                              calendar={persian}
                              locale={persian_fa}
                              className='form-control'
                              minDate={new Date()}
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="2">
                          <FormGroup className="mt-3">
                            <Label>وضعیت</Label>
                            <FormikSwitch
                              name="status"
                              onChange={setFieldValue}
                              onBlur={setFieldTouched}
                              className={'custom-switch custom-switch-primary ml-2'}
                              value={values.status}
                            />
                          </FormGroup>
                        </Colxx>
{/*                        <Colxx md="2">
                          <FormGroup className="mt-3">
                            <Label>نمایش سایدبار</Label>
                            <FormikSwitch
                              name="show_sidebar"
                              onChange={setFieldValue}
                              onBlur={setFieldTouched}
                              className={'custom-switch custom-switch-primary ml-2'}
                              value={values.show_sidebar}
                            />
                          </FormGroup>
                        </Colxx>
                        {values.show_sidebar &&
                        <Colxx md="4">
                          <FormGroup className="mt-3">
                            <Label>جهت نمایش سایدبار</Label>
                            <FormikReactSelect
                              options={[
                                {label: 'چپ' , value: 'left' },
                                {label: 'راست' , value: 'right' }
                              ]}
                              name={`sidebar_position`}
                              onChange={setFieldValue}
                              onBlur={setFieldTouched}
                              value={values.sidebar_position}
                            />
                          </FormGroup>
                        </Colxx>
                        }{values.show_sidebar &&
                        <Colxx md="4">
                          <FormGroup className="mt-3">
                            <Label>سایدبار</Label>
                            <FormikReactSelect
                              options={options}
                              name={`sidebar_id`}
                              onChange={setFieldValue}
                              onBlur={setFieldTouched}
                              getOptionLabel={option => option.name}
                              getOptionValue={option => option.id}
                              value={values.sidebar_id}
                            />
                          </FormGroup>
                        </Colxx>
                        }*/}
                      </Row>
                    </CardBody>
                  </Card>
                </Colxx>
                <Colxx md = "12 mt-4">
                  <Card>
                    <CardBody>
                      <CardTitle>تنظیمات سئو</CardTitle>
                      <Row>
                        <Colxx md="12">
                          <FormGroup className="mt-4">
                            <Label>عنوان متا</Label>
                            <Field
                              name="meta_title"
                              className="form-control"
                              rows="8"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="6">
                          <FormGroup className="mt-4">
                            <Label>توضیحات متا</Label>
                            <Field
                              name="meta_description"
                              className="form-control"
                              as="textarea"
                              rows="8"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="6">
                          <FormGroup className="mt-4">
                            <Label>کلمات کلیدی متا</Label>
                            <Field
                              name="meta_keywords"
                              className="form-control"
                              as="textarea"
                              rows="8"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="12">
                          <Button className="float-left mt-5" outline type="submit">ثبت</Button>
                        </Colxx>
                      </Row>
                    </CardBody>
                  </Card>
                </Colxx>
              </Row>
            </Form>
          )
        }
      </Formik>
    </React.Fragment>
  )
}

export default BlogPost;

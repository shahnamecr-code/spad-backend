import React, {Suspense, useEffect} from 'react';
import {Field, Form, Formik} from 'formik';
import {Button, Card, CardBody, FormGroup, Label, Row} from 'reactstrap';
import {Colxx, Separator} from '../../../../components/common/CustomBootstrap';
import {adminPathApi} from "../../../../constants/defaultValues";
import axios from "axios";
import {NotificationManager} from "../../../../components/common/react-notifications";
import {FormikReactSelect} from "../../../../containers/form-validations/FormikFields";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import * as Yup from "yup";
import AlertError from "../../../../components/AlertError";
import SearchSelect from "../../../../components/SearchSelect";
import {useHistory} from 'react-router-dom';
import ImageResize from 'quill-image-resize-module-react';
import useAttributesList from '../../../../hooks/search_attribute';
import MediaButton from "../../../../components/MediaLibrary";
import ReactQuill , { Quill } from 'react-quill';
import 'react-quill/dist/quill.snow.css';
import ImageUploader from "quill-image-uploader";


const CategorySchema = Yup.object().shape({
  name: Yup.string()
    .min(3, 'Too Short!')
    .required('Please enter category name'),
  slug: Yup.string()
    .min(2, 'Too Short!')
    .required('Please enter category slug'),
});

const Category = ({match, edit = false}) => {
  const history = useHistory();

  const [loading, setLoading] = React.useState(false);
  const [show_content, setShowContent] = React.useState(!edit);
  const [validationErrors, setValidationErrors] = React.useState({});
  const [init, setInit] = React.useState({description:''})
  const [tags, setTags] = React.useState([])

  Quill.register("modules/imageUploader", ImageUploader);
  Quill.register('modules/imageResize', ImageResize);
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

  const onSubmit = async (values,{setFieldError,setErrors}) => {
    setLoading(true)
    let route = edit ? `/${match.params.id}/edit` : ''
    await axios.post(`${adminPathApi}/category${route}`, {...values,parent:values.parent?(values.parent.id):null})
      .then((response) => {
        if(!edit){
          history.replace(`edit/${response.data.item.id}`)
        }
        NotificationManager.success(response.data.message,null,1000,null,null,'filled');
        syncCat(response.data)
      }).catch((error)=>{
        errorFormSubmit(error)
      }).finally(()=>setLoading(false));
  };

  const [exceptAttr,setExceptAttr,search,setSearch,options] = useAttributesList();

  const onAttrSelectChange = (name,values,setField) => {
    setExceptAttr(values);
    setField(name,values);
  }

  const getAttr = (setloadOptions, search1 = '') => {
    setSearch(search1);
    setloadOptions(options);
  }

  const getFamilies = (setloadOptions, search = '') => {
    axios
      .post(
        `${adminPathApi}/group-attribute/search_group_attributes`,
        {
          page:1,
          per_page:10,
          search:search,
        }
      )
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        const attr = [];
        data.map((attribute) => {
          attr.push({
            ...attribute,
            label: attribute.title,
            value: attribute.id,
            key: attribute.id,
          })
        })
        //setShowAttributeList(false)
        setloadOptions(attr);
      });
  }

  const getCategoriesList = (setloadOptions, search = '') => {
    axios
      .post(
        `${adminPathApi}/category/search_categories`,
        {
          except:[],
          page:1,
          per_page:10,
          search:search,
        }
      )
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        const attr = [];
        data.map((attribute) => {
          attr.push({
            ...attribute,
            key: attribute.id,
          })
        })
        //setShowAttributeList(false)
        setloadOptions(data);
      });
  }

  useEffect(async () => {
    if (edit) {
      await axios
        .get(
          `${adminPathApi}/category/get/${match.params.id}`
        )
        .then((res) => {
          return res.data;
        })
        .then((data) => {
          syncCat(data)
        });
    }
  }, []);

  const syncCat=(data)=>{
    if (data.status) {
      let group_attributes=data.item.group_attributes;
      let attr=[];
      if(group_attributes){
        group_attributes.map((family) => {
          attr.push({
            ...family,
            label: family.title,
            value: family.id,
            key: family.id,
          })
        })
      }
      setInit({
        name: data.item.name,
        slug: data.item.slug,
        parent:data.item.parent,
        order:data.item.order,
        type:data.item.type,
        notification_limit:data.item.notification_limit,
        description:data.item.description,
        icon:data.item.icon,
        shortcut:data.item.shortcut,
        banner:data.item.banner,
        group_attributes:attr,
      });
      setShowContent(true);
      setValidationErrors([]);
    }
  }

  const errorFormSubmit=(error)=>{
    if(error.response.status == 422){
      setValidationErrors(error.response.data.errors)
    }else {
      NotificationManager.error('خطایی در ارسال اطلاعات رخ داده است',null,1000,null,null,'filled');
    }
  }

  if(!show_content){
    return (<div className="loading"/>);
  }

  return (
    <Suspense fallback={<div className="loading"/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={edit?"menu.categories.edit":'menu.categories.create'} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Row className="mb-4">
        <Colxx xxs="12">
          <Card>
            <CardBody>
              <Formik
                initialValues={init}
                onSubmit={onSubmit}
                validationSchema={CategorySchema}
                enableReinitialize
              >
                {({errors, touched,setFieldValue, setFieldTouched, values}) => (
                  <Form className="av-tooltip tooltip-label-right">
                    <AlertError errors={validationErrors}/>

                    <FormGroup>
                      <Label>نام</Label>
                      <Field
                        className="form-control"
                        name="name"
                      />
                      {errors.name && touched.name && (
                        <div className="invalid-feedback d-block">
                          {errors.name}
                        </div>
                      )}
                    </FormGroup>

                    <FormGroup>
                      <Label>نامک</Label>
                      <Field
                        className="form-control"
                        name="slug"
                      />
                      {errors.slug && touched.slug && (
                        <div className="invalid-feedback d-block">
                          {errors.slug}
                        </div>
                      )}
                    </FormGroup>

                    <FormGroup>
                      <Label>دسته والد</Label>
                      <SearchSelect request={getCategoriesList} value={values.parent} name='parent' onChange={setFieldValue}/>
                    </FormGroup>

                    <FormGroup>
                      <label>ترتیب دسته</label>
                      <Field name="order" className="form-control"/>
                    </FormGroup>

                    <FormGroup>
                      <label>حداقل کالای موجود جهت ارسال نوتیفیکیشن</label>
                      <Field name="notification_limit" className="form-control"/>
                    </FormGroup>

                    <FormGroup>
                      <label>نوع دسته بندی</label>
                      <FormikReactSelect
                        name="type"
                        value={values.type}
                        onChange={setFieldValue}
                        onBlur={setFieldTouched}
                        options={[
                          {label: 'اسکرول', value: 0},
                          {label: 'آیکون', value: 1},
                          {label: 'میانبر', value: 2},
                          {label: 'دسته ای', value: 3},]}/>
                    </FormGroup>

                    <FormGroup>
                      <Label>توضیحات</Label>
                      <ReactQuill
                        theme="snow"
                        value={values.description}
                        modules={quillModules}
                        formats={quillFormats}
                        onChange={(val) => {setFieldValue('description',val)}}
                        name={'description'}
                      />
                    </FormGroup>

                    <Row>
                      <Colxx sm="4">
                        <FormGroup>
                          <Label>آیکون</Label>
                          <MediaButton onSubmit={(value)=>setFieldValue('icon',value.files)} default_value={values.icon?values.icon:undefined} name='icon' setFieldValue={setFieldValue}/>
                        </FormGroup>
                      </Colxx>
                      <Colxx sm="4">
                        <FormGroup>
                          <Label>میانبر</Label>
                          <MediaButton onSubmit={(value)=>setFieldValue('shortcut',value.files)} default_value={values.shortcut?values.shortcut:undefined} name='shortcut' setFieldValue={setFieldValue}/>
                        </FormGroup>
                      </Colxx>
                      <Colxx sm="4">
                        <FormGroup>
                          <Label>بنر</Label>
                          <MediaButton onSubmit={(value)=>setFieldValue('banner',value.files)} default_value={values.banner?values.banner:undefined} name='banner' setFieldValue={setFieldValue}/>
                        </FormGroup>
                      </Colxx>
                    </Row>

                    <Button
                      color="primary"
                      className={`btn-shadow btn-multiple-state ${
                        loading ? 'show-spinner' : ''
                      }`}
                      disabled={loading}
                      type="submit"
                    >
                      <span className="spinner d-inline-block">
                        <span className="bounce1"/>
                        <span className="bounce2"/>
                        <span className="bounce3"/>
                      </span>
                      <span className="label">
                        ذخیره
                      </span>
                    </Button>
                  </Form>
                )}
              </Formik>
            </CardBody>
          </Card>
        </Colxx>
      </Row>
    </Suspense>
  )
}


export default Category;

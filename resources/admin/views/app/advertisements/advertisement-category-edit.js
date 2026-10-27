import React, {Suspense, useEffect, useState} from "react";
import {Button, Card, CardBody, FormGroup, FormText, Label, Row} from "reactstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import {Form, Formik,Field} from "formik";
import AlertError from "../../../components/AlertError";
import * as Yup from "yup";
import {AsyncPaginate} from "react-select-async-paginate";
import {adminPathApi} from "../../../constants/defaultValues";
import axios from "axios";
import {NotificationManager} from "../../../components/common/react-notifications";
import MediaButton from "../../../components/MediaLibrary";
import MediaLibraryButton from "../../../components/mediaLibrary/MediaLibrary";


const CategorySchema = Yup.object().shape({
  name: Yup.string()
    .min(3, 'Too Short!')
    .required('نام دسته بندی الزامی است'),
  slug: Yup.string()
    .min(2, 'Too Short!')
    .required('نامک دسته بندی الزامی است'),
});

const AdvertisementCategoryEdit=({match,edit=false,sub=false, history})=>{
  const [validationErrors, setValidationErrors] = React.useState({});
  const [init, setInit] = React.useState({name: '', slug: ''})
  const [loading, setLoading] = React.useState(false);

  const [fileUrls, setFileUrls] = useState([]);

  useEffect(()=>{
    if (edit){
      axios
        .get(`${adminPathApi}/advertisement-category/getEditFormData/${match.params.id}`)
        .then(res=>res.data)
        .then(data=>{
          setInit(data)
          setFileUrls(data.icon)
          if (data.status) {
            NotificationManager.success(data.message, 'عملیات با موفقیت انجام شد.');
            history.push("/spad-admin/advertisements/categories/list");
          }
        }).catch((e)=>{
        if (e.response.status==402){
          window.location.replace(e.response.data.url)
        }
      })
    }
  },[])

  const loadCategories = async (search, prevOptions,{page}) => {
    let source=`${adminPathApi}/advertisement-category/allCategories?page=${page}`
    if (search){
      source+=`&search=${search}`
    }
    let result;

    await axios
      .get(source)
      .then(res=>res.data)
      .then(data=>{
        result=data
      })

    return {
      options: result.data,
      hasMore:result.hasMore,
      additional:{
        page: parseInt(result.page)+1,
      }
    };
  };

  const onSubmit=(values)=>{
    let send={...values}
    setLoading(true)

    if (sub){
      send={...values,parent:match.params.parent}
    }

    if (edit){
      axios
        .post(`${adminPathApi}/advertisement-category/edit/${match.params.id}`,values)
        .then(res=>res.data)
        .then(data=>{
          setLoading(false)
          if (data.status) {
            NotificationManager.success(data.message, 'عملیات با موفقیت انجام شد.');
            history.push("/spad-admin/advertisements/categories/list");
          }
        })
        .catch((error)=>{
          if (error.response.status == 422) {
            setValidationErrors(error.response.data.errors)
          } else {
            NotificationManager.error('خطایی در ارسال اطلاعات رخ داده است', null, 1000, null, null, 'filled');
          }
          setLoading(false)
        })
    }else{
      axios
        .post(`${adminPathApi}/advertisement-category/create`,send)
        .then(res=>res.data)
        .then(data=>{
          setLoading(false)
          if (data.status) {
            NotificationManager.success(data.message, 'عملیات با موفقیت انجام شد.');
            history.push("/spad-admin/advertisements/categories/list");
          }
        })
        .catch((error)=>{
          if (error.response.status == 422) {
            setValidationErrors(error.response.data.errors)
          } else {
            NotificationManager.error('خطایی در ارسال اطلاعات رخ داده است', null, 1000, null, null, 'filled');
          }
          setLoading(false)
        })
    }
  }

  return (
    <Suspense fallback={<div className="loading"/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={"menu.advertisement.categories.edit"} match={match}/>
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
                {({errors, touched, setFieldValue, setFieldTouched, values}) => (
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

                    <Colxx sm="4">
                      <FormGroup>
                        <Label>آیکون</Label>
                        <MediaLibraryButton
                          multiple={false}
                          onSubmit={(values) => setFieldValue('icon', values)}
                          onChange={(values) => setFieldValue('icon', values)}
                          defaultFileIdz={values['icon'] || []}
                          defaultFileUrls={fileUrls ? fileUrls : []}
                          setDefaultFileUrls={setFileUrls}
                        />
                      </FormGroup>
                    </Colxx>
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
  );
}

export default AdvertisementCategoryEdit;

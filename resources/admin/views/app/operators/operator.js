import React, {Suspense, useState} from "react";
import {Button, Card, CardBody, CardTitle, FormGroup, Label, Row} from "reactstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import {Field, FieldArray, Form, Formik} from "formik";
import {FormikReactSelect} from "../../../containers/form-validations/FormikFields";
import {adminPathApi, adminRoot} from "../../../constants/defaultValues";
import {useHistory} from "react-router-dom";
import AlertError from "../../../components/AlertError";
import {NotificationManager} from '../../../components/common/react-notifications';
import * as Yup from 'yup'
import UserCan from "../../../helpers/UserCan";
import {roles,permissionsList} from "../../../constants/operators";
import SearchSelect from "../../../components/SearchSelect";
import axios from "axios";
import MediaButton from "../../../components/MediaLibrary";
import MediaLibraryButton from "../../../components/mediaLibrary/MediaLibrary";

const operatorSchema = Yup.object().shape({
  first_name: Yup.string()
    .min(2 , 'نام باید بیشتر از 2 کاراکتر باشد')
    .max(50 , 'نام باید کمتر از 50 کاراکتر باشد')
    .required('نام اجباری است'),
  last_name: Yup.string()
    .min(2 , 'نام خانوادگی باید بیشتر از 2 کاراکتر باشد')
    .max(50 , 'نام خانوادگی باید کمتر از 50 کاراکتر باشد')
    .required('نام خانوادگی اجباری است'),
  username: Yup.string()
    .min(2 , 'نام کاربری باید بیشتر از 2 کاراکتر باشد')
    .max(50 , 'نام کاربری باید کمتر از 50 کاراکتر باشد')
    .required('نام کاربری اجباری است'),
  email: Yup.string()
    .email('ایمیل معتبر نیست')
    .required('ایمیل اجباری است'),
  phone: Yup.number().typeError('شماره ثابت باید عدد باشد')
    .required('شماره ثابت اجباری است'),
  mobile: Yup.number().typeError('شماره موبایل باید عدد باشد')
    .required('شماره موبایل اجباری است'),
  address: Yup.string()
    .required('آدرس اجباری است'),
  password: Yup.string()
    .required('رمز عبور اجباری است')
});

const Operator = ({match, edit = false, fetchPermission, permissions = [], loading, errors}) => {
  let history = useHistory();
  const [serverError, setServerError] = React.useState({})
  const [cities, setCities] = React.useState(false)
  const id = match.params.id ? match.params.id : null;
  const [initialValues, setInitialValues] = React.useState({
    first_name: '',
    last_name: '',
    status: 1,
    username: '',
    email: '',
    phone: '',
    mobile: '',
    address: ''
  });

  const [fileUrls, setFileUrls] = useState([]);


  React.useEffect(async () => {
    if (id && edit) {
      await axios.get(`${adminPathApi}/operator/${id}`)
        .then(({data}) => {
          setInitialValues({...data,role:data.roles[0].name})
          setFileUrls(data.profile || [])
        })
        .catch((error) => {
          history.push(`${adminRoot}/error`, {
            status: error.response ? error.response.status : 500
          });
        });
    }
  }, []);

  const onSubmit = async (values) => {
    if (edit) {
      await axios.put(`${adminPathApi}/operator/${id}`, values)
        .then(({data}) => {
          NotificationManager.success(data.message, 'موفقیت');
          history.push("/spad-admin/operators/list");
        })
        .catch(({response}) => {
          setServerError(response.data.errors);
          NotificationManager.err(response.data.errors.map((err)=> err[0]), 'خطا');
        });
    } else {
      await axios.post(`${adminPathApi}/operator`, values)
        .then(({data}) => {
          NotificationManager.success(data.message, 'موفقیت');
          history.push("/spad-admin/operators/list");
        })
        .catch(({response}) => {
          setServerError(response.data.errors);
          NotificationManager.error(response.data.errors.map((err)=> err[0]), 'خطا');
        });
    }
  };

  return (
    <>
      <Suspense fallback={<div className={'loading'}/>}>
        <Row>
          <Colxx xss="12">
            <Breadcrumb heading={"menu.operators"} match={match}/>
            <Separator className="mb-5"/>
          </Colxx>
        </Row>
        <Colxx xss={12}>
          <Card className="mb-4">
            <CardBody>
              <CardTitle>اپراتور</CardTitle>
              <AlertError errors={serverError}/>
              <Formik enableReinitialize initialValues={initialValues} onSubmit={onSubmit}
                      validationSchema={operatorSchema}>
                {({errors, touched, setFieldValue, setFieldTouched, values}) => (
                  <Form className="av-tooltip tooltip-label-right">
                    <FormGroup row>
                      <Colxx md={6}>
                        <FormGroup>
                          <Label>نام</Label>
                          <Field name={"first_name"} className={'form-control'}/>
                          {errors.first_name && touched.first_name && (
                            <div className="invalid-feedback d-block">
                              {errors.first_name}
                            </div>
                          )}
                        </FormGroup>
                      </Colxx>
                      <Colxx md={6}>
                        <FormGroup className={'error-l-75'}>
                          <Label>نام خانوادگی</Label>
                          <Field name={"last_name"} className={'form-control'}/>
                          {errors.last_name && touched.last_name && (
                            <div className="invalid-feedback d-block">
                              {errors.last_name}
                            </div>
                          )}
                        </FormGroup>
                      </Colxx>
                      <Colxx md={6}>
                        <FormGroup className={'error-l-75'}>
                          <Label>نام کاربری</Label>
                          <Field name={"username"} className={'form-control'}/>
                          {errors.username && touched.username && (
                            <div className="invalid-feedback d-block">
                              {errors.username}
                            </div>
                          )}
                        </FormGroup>
                      </Colxx>
                      <Colxx md={6}>
                        <FormGroup>
                          <Label>ایمیل</Label>
                          <Field name={"email"} className={'form-control'}/>
                          {errors.email && touched.email && (
                            <div className="invalid-feedback d-block">
                              {errors.email}
                            </div>
                          )}
                        </FormGroup>
                      </Colxx>
                      <Colxx md={6}>
                        <FormGroup className={'error-l-75'}>
                          <Label>شماره موبایل</Label>
                          <Field name={"mobile"} className={'form-control'}/>
                          {errors.mobile && touched.mobile && (
                            <div className="invalid-feedback d-block">
                              {errors.mobile}
                            </div>
                          )}
                        </FormGroup>
                      </Colxx>
                      <Colxx md={6}>
                        <FormGroup className={'error-l-75'}>
                          <Label>شماره ثابت</Label>
                          <Field name={"phone"} className={'form-control'}/>
                          {errors.phone && touched.phone && (
                            <div className="invalid-feedback d-block">
                              {errors.phone}
                            </div>
                          )}
                        </FormGroup>
                      </Colxx>
                      <Colxx md={6}>
                        <FormGroup className={'error-l-75'}>
                          <Label>شماره داخلی</Label>
                          <Field name={"internal_phone"} className={'form-control'}/>
                          {errors.internal_phone && touched.internal_phone && (
                            <div className="invalid-feedback d-block">
                              {errors.internal_phone}
                            </div>
                          )}
                        </FormGroup>
                      </Colxx>
                      <Colxx md={6}>
                        <FormGroup className={'error-l-75'}>
                          <Label>موقعیت شغلی</Label>
                          <Field name={"job_title"} className={'form-control'}/>
                          {errors.job_title && touched.job_title && (
                            <div className="invalid-feedback d-block">
                              {errors.job_title}
                            </div>
                          )}
                        </FormGroup>
                      </Colxx>
                      <Colxx sm="6">
                        <FormGroup>
                          <Label>عکس آواتار</Label>
                          <MediaLibraryButton
                            multiple={false}
                            onSubmit={(values) => setFieldValue('profile', values)}
                            onChange={(values) => setFieldValue('profile', values)}
                            defaultFileIdz={values['profile'] || []}
                            defaultFileUrls={fileUrls ? fileUrls : []}
                            setDefaultFileUrls={setFileUrls}
                          />
                        </FormGroup>
                      </Colxx>
                      <Colxx md={12}>
                        <FormGroup className={'error-l-75'}>
                          <Label>رمز عبور</Label>
                          <Field name={'password'} className={'form-control'} type={'password'}/>
                          {errors.password && touched.password && (
                            <div className="invalid-feedback d-block">
                              {errors.password}
                            </div>
                          )}
                        </FormGroup>
                      </Colxx>
                      <Colxx md={12}>
                        <FormGroup>
                          <Label>آدرس</Label>
                          <Field name={"address"} className={'form-control'}/>
                          {errors.address && touched.address && (
                            <div className="invalid-feedback d-block">
                              {errors.address}
                            </div>
                          )}
                        </FormGroup>
                      </Colxx>
                      <UserCan>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>وضعیت</Label>
                            <FormikReactSelect
                              value={values.status}
                              onChange={setFieldValue}
                              onBlur={setFieldTouched}
                              options={[{label: 'فعال', value: 1}, {label: 'غیر فعال', value: 0}]}
                              name={'status'}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>نقش کاربری</Label>
                            <FormikReactSelect
                              value={values.role}
                              onChange={setFieldValue}
                              onBlur={setFieldTouched}
                              options={roles}
                              name={'role'}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>دسترسی های کاریر</Label>
                            <FormikReactSelect
                              value={values.permissionNames}
                              onChange={setFieldValue}
                              onBlur={setFieldTouched}
                              options={permissionsList}
                              name={'permissionNames'}
                              isMulti/>
                          </FormGroup>
                        </Colxx>
                      </UserCan>
                    </FormGroup>
                    <Button color="primary" type="submit" className="float-left">
                      ثبت
                    </Button>
                  </Form>
                )}
              </Formik>
            </CardBody>
          </Card>
        </Colxx>
      </Suspense>
    </>
  )
}
const mapStateToProps = ({permissionsRoles}) => {
  const {loading, errors, permissions} = permissionsRoles;
  return {permissions, errors, loading};
};

export default Operator;

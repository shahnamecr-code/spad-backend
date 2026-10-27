import React, {Suspense, useEffect} from 'react';
import {ErrorMessage, Field, FieldArray, Form, Formik} from 'formik';

import {Button, Card, CardBody, CardSubtitle, FormGroup, Label, Row} from 'reactstrap';
import {Colxx, Separator} from '../../../../components/common/CustomBootstrap';
import {adminPathApi} from "../../../../constants/defaultValues";
import axios from "axios";
import {NotificationManager} from "../../../../components/common/react-notifications";
import {FormikReactSelect, FormikSwitch} from "../../../../containers/form-validations/FormikFields";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import * as Yup from "yup";
import AlertError from "../../../../components/AlertError";

const AttributeSchema = Yup.object().shape({
  code: Yup.string()
    .min(3, 'Too Short!')
    .max(30, 'Too Long!')
    .required('Please enter your code'),
  name: Yup.string()
    .min(2, 'Too Short!')
    .max(50, 'Too Long!')
    .required('Please enter your name'),
});

const Attribute = ({match, edit = false}) => {
  const onSubmit = async (values,{setFieldError,setErrors}) => {
    setLoading(true)
    let route = edit ? `/${match.params.id}/edit` : ''
    await axios.post(`${adminPathApi}/attribute${route}`, values)
      .then((response) => {
        if(response.data.status){
          if (response.data.options)
            setInit({...init, options: response.data.options})
          NotificationManager.success(response.data.message,null,1000,null,null,'filled');
        }else{
          NotificationManager.error(response.data.message,null,1000,null,null,'filled');
        }
        setValidationErrors({})
      }, (error) => {

        if(error.response.status == 422){
          setValidationErrors(error.response.data.errors)
        }else {
          NotificationManager.error('خطایی در ارسال اطلاعات رخ داده است',null,1000,null,null,'filled');
        }
      }).finally(()=>setLoading(false));
  };

  const options = [
    {label: 'تصویر', value: 'image', key: 0},
    {label: 'لیست کشویی', value: 'select', key: 1},
    {label: 'فیلد متنی', value: 'text', key: 2},
    {label: 'ناحیه متنی', value: 'textarea', key: 3},
    {label: 'منطقی', value: 'boolean', key: 4},
    {label: 'گالری', value: 'gallery', key: 5},
  ];

  const [loading, setLoading] = React.useState(false);
  const [validationErrors, setValidationErrors] = React.useState({});

  const [init, setInit] = React.useState({
    name: '',
    code: '',
    type: 'text',
    is_variable: false,
    can_shop_edit: false,
    options: [],
  })

  useEffect(async () => {
    if (edit) {
      await axios
        .get(
          `${adminPathApi}/attribute/get/${match.params.id}`
        )
        .then((res) => {
          return res.data;
        })
        .then((data) => {
          if (data.status) {
            setInit({
              name: data.item.name,
              code: data.item.code,
              type: data.item.type,
              is_variable: data.item.is_variable == 1 ? true : false,
              rules:data.item.rules?data.item.rules:[],
              options: data.item.options ? data.item.options : [],
            });
          }
        });
    }
  }, []);

  return (
    <Suspense fallback={<div className="loading"/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={edit?"menu.attributes.edit":'menu.attributes.create'} match={match}/>
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
                validationSchema={AttributeSchema}
                enableReinitialize
              >
                {({errors, touched,setFieldValue, setFieldTouched, values}) => (
                  <Form className="av-tooltip tooltip-label-right">
                    <AlertError errors={validationErrors}/>

                    <FormGroup>
                      <Label>کد ویژگی</Label>
                      <Field
                        className="form-control"
                        name="code"
                      />
                      {errors.code && touched.code && (
                        <div className="invalid-feedback d-block">
                          {errors.code}
                        </div>
                      )}
                    </FormGroup>

                    <FormGroup>
                      <Label>عنوان</Label>
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
                      <Label>نوع ویژگی</Label>
                      <FormikReactSelect
                        value={values.type}
                        onChange={setFieldValue}
                        onBlur={setFieldTouched}
                        options={options}
                        name="type"/>
                    </FormGroup>

                    {values.type == 'select' && (
                      <>
                        <FieldArray name="options">
                          {({insert, remove, push}) => (
                            <>
                              <CardSubtitle>مقادیر
                                <Button
                                  color="success"
                                  outline
                                  size={"sm"}
                                  className={"mx-2"}
                                  onClick={() => push({value: ''})}
                                >
                                  افزودن
                                </Button>
                              </CardSubtitle>
                              {values.options && values.options.length > 0 &&
                              values.options.map((option, index) => (
                                <Row key={index} className={"mb-1"}>
                                  <Colxx>
                                    <Field
                                      className="form-control"
                                      name={`options.${index}.value`}
                                      placeholder="عنوان ویژگی"
                                      type="text"
                                    />
                                    <ErrorMessage
                                      name={`options.new.${index}`}
                                      component="div"
                                      className="field-error"
                                    />
                                  </Colxx>
                                  <Colxx>
                                    <Button
                                      color="danger"
                                      outline
                                      size={"sm"}
                                      onClick={() => remove(index)}
                                    >
                                      <span className={"simple-icon-trash"}/>
                                    </Button>
                                  </Colxx>
                                </Row>
                              ))}
                            </>
                          )}
                        </FieldArray>
                      </>
                    )}

{/*                    <FormGroup>
                      <Label>سفارشی سازی توسط فروشنده</Label>
                      <FormikSwitch
                        name="can_shop_edit"
                        className="custom-switch custom-switch-primary"
                        value={values.can_shop_edit}
                        onChange={setFieldValue}
                        onBlur={setFieldTouched}
                      />
                    </FormGroup>*/}

                    <FormGroup>
                      <label>اعتبار سنجی</label>
                      <div role="group" aria-labelledby="checkbox-group">
                        <label>
                          <Field type="checkbox" name="rules" value="required" className="mr-1"/>
                          الزامی
                        </label>
                        <label className="ml-2">
                          <Field type="checkbox" name="rules" value="unique_template_attribute" className="mr-1"/>
                          یکتا
                        </label>
                      </div>
                    </FormGroup>

                    <FormGroup>
                      <Label>استفاده به عنوان متغیر</Label>
                      <FormikSwitch
                        name="is_variable"
                        className="custom-switch custom-switch-primary"
                        value={values.is_variable}
                        onChange={setFieldValue}
                        onBlur={setFieldTouched}
                      />
                    </FormGroup>

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

export default Attribute;

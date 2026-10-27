import React, {Suspense, useState} from "react";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";
import {Button, Card, CardBody, FormGroup, Label, Row} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import AlertError from "../../../components/AlertError";
import {ErrorMessage, Field, FieldArray, Form, Formik} from "formik";
import {FormikReactSelect, FormikSwitch} from "../../../containers/form-validations/FormikFields";
import PropertyRelation from "./components/PropertyRelation";
import * as Yup from "yup";
import {advertisementFieldAccessOptions} from "../../../constants/advertisementConstants";
import { useHistory } from "react-router";
const propertyWrapperSchema = Yup.object().shape({
  title: Yup.string()
    .min(2)
    .max(50)
    .required('وارد کردن عنوان الزامی است.'),
  order: Yup.number()
    .required('وارد کردن اولویت الزامی است.')
});


const AdvertisementCategoryLayoutAdd = ({match, edit = false, fromRoute}) => {
  const [serverError, setServerError] = React.useState({})
  const [properties, setProperties] = useState([]);
  const [initialValues, setInitialValues] = React.useState({
    title: '',
    order: '',
    position: '',
    access:0,
  });
  let history = useHistory();
  React.useEffect(async () => {
    if (edit) {
      await axios.get(`${adminPathApi}/advertisement-category/layout/edit/${match.params.layout}`)
        .then(({data}) => {
          setInitialValues(data.data ?? {})
          setProperties(data.properties ?? [])
        })
        .catch((e)=>{
          if (e.response.status==402){
            window.location.replace(e.response.data.url)
          }
        })
    } else {
      await axios.get(`${adminPathApi}/properties`)
        .then(res => res.data)
        .then(data => {
          setProperties(data)
        }).catch((e)=>{
          if (e.response.status==402){
            window.location.replace(e.response.data.url)
          }
        })
    }
  }, []);

  const onSubmit = async (values) => {
    if (edit) {
      await axios.post(`${adminPathApi}/advertisement-category/layout/edit/${match.params.layout}`, values)
        .then(({data}) => {
          NotificationManager.success(data.message, 'موفقیت');
          history.push("/spad-admin/advertisements/categories/list");
        })
        .catch(({response}) => {
          if (response.status==402){
            window.location.replace(response.data.url)
          }else{
            setServerError(response.data.errors);
            NotificationManager.error(response.data.errors.map((err)=> err[0]), 'خطا');
          }
        });
    } else {
      await axios.post(`${adminPathApi}/advertisement-category/layout/${match.params.id}`, values)
        .then(({data}) => {
          NotificationManager.success(data.message, 'موفقیت');
          history.push("/spad-admin/advertisements/categories/list");
        })
        .catch(({response}) => {
          if (response.status==402){
            window.location.replace(response.data.url)
          }else{
            setServerError(response.data.errors);
            NotificationManager.error(response.data.errors.map((err)=> err[0]), 'خطا');
          }

        });
    }
  };

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={"menu.wrappers"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx xss={12}>
        <Card className="mb-4">
          <CardBody>
            <AlertError errors={serverError}/>
            <Formik enableReinitialize initialValues={initialValues} onSubmit={onSubmit}
                    validationSchema={propertyWrapperSchema}>
              {({errors, touched, setFieldValue, setFieldTouched, values}) => (
                <Form className="av-tooltip tooltip-label-right">
                  <FormGroup row>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>عنوان</Label>
                        <Field name={"title"} className={'form-control'}/>
                        {errors.title && touched.title && (
                          <div className="invalid-feedback d-block">
                            {errors.title}
                          </div>
                        )}
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup className={'error-l-75'}>
                        <Label>ترتیب</Label>
                        <Field type="number" name={"order"} className={'form-control'}/>
                        {errors.order && touched.order && (
                          <div className="invalid-feedback d-block">
                            {errors.order}
                          </div>
                        )}
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>موقعیت</Label>
                        <FormikReactSelect
                          value={values.position}
                          onChange={setFieldValue}
                          onBlur={setFieldTouched}
                          options={[{label: 'normal', value: 'normal'}, {label: 'side', value: 'side'}]}
                          name={'position'}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md="6">
                      <FormGroup>
                        <Label>سطح دسترسی باکس</Label>
                        <FormikReactSelect
                          options={advertisementFieldAccessOptions}
                          name={`access`}
                          value={values.access}
                          onChange={setFieldValue}
                          onBlur={setFieldTouched}
                        />
                        <ErrorMessage
                          name={`access`}
                          component="div"
                          className="field-error"
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md={12} className={"bg-custom-1 py-4 border-theme-1"}>
                      <FieldArray name={'layout'}>
                        {({insert, remove, push}) => (
                          <>
                            {(values.layout && values.layout.length > 0) ? (
                              <>
                                {values.layout.map((item, index) => (
                                  <Card className="mb-3 px-3">
                                    <CardBody>
                                      <Row>
                                        <Colxx md="6">
                                          <FormGroup>
                                            <Label>انتخاب ویژگی</Label>
                                            <FormikReactSelect
                                              options={properties}
                                              name={`layout.${index}.property_id`}
                                              value={values.layout[index].property_id}
                                              onChange={setFieldValue}
                                              onBlur={setFieldTouched}
                                              getOptionLabel={option => option.title}
                                              getOptionValue={option => option.slug}
                                            />
                                            <ErrorMessage
                                              name={`layout.${index}.property_id`}
                                              component="div"
                                              className="field-error"
                                            />
                                          </FormGroup>
                                        </Colxx>
                                        <Colxx md="6">
                                          <FormGroup>
                                            <Label>تعیین کلاس برای ویژگی</Label>
                                            <Field
                                              name={`layout.${index}.class`}
                                              className={"form-control"}
                                            />
                                            <ErrorMessage
                                              name={`layout.${index}.class`}
                                              component="div"
                                              className="field-error"
                                            />
                                          </FormGroup>
                                        </Colxx>
                                        <Colxx md="6">
                                          <FormGroup>
                                            <Label>سطح دسترسی فیلد</Label>
                                            <FormikReactSelect
                                              options={advertisementFieldAccessOptions}
                                              name={`layout.${index}.access`}
                                              value={values.layout[index].access??0}
                                              onChange={setFieldValue}
                                              onBlur={setFieldTouched}
                                            />
                                            <ErrorMessage
                                              name={`layout.${index}.property_id`}
                                              component="div"
                                              className="field-error"
                                            />
                                          </FormGroup>
                                        </Colxx>
                                      </Row>
                                      <PropertyRelation
                                        name={`layout.${index}.dependency`}
                                        values={values.layout[index].dependency}
                                        field={properties.find(field => field.slug == values.layout[index].property_id)}
                                        deleteItem={() => remove(index)}
                                        onBlur={setFieldTouched}
                                        onChange={setFieldValue}
                                        properties={properties}/>
                                    </CardBody>
                                  </Card>
                                ))}
                              </>
                            ) : (
                              <Card className="mb-3">
                                <CardBody>هنوز هیچ ویژگی افزوده نشده است</CardBody>
                              </Card>
                            )
                            }
                             <Button
                              color="primary"
                              outline
                              size={"sm"}
                              className={"mx-2 mb-4 btn-block"}
                              onClick={() => push({})}
                            >
                              افزودن فیلد
                            </Button>
                          </>
                        )}
                      </FieldArray>
                    </Colxx>
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
  )
}

export default AdvertisementCategoryLayoutAdd;

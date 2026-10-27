import React, {Suspense, useEffect, useState} from "react";
import {Button, Card, CardBody, CardFooter, CardSubtitle, FormGroup, Label, Row} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {Formik, Form, FieldArray, ErrorMessage, Field} from "formik";
import {FormikReactSelect, FormikSwitch} from "../../../containers/form-validations/FormikFields";
import {
  advertisementSearchLevels,
  advertisementSearchOperators,
  advertisementSearchTypes
} from "../../../constants/advertisementConstants";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";
import {useHistory} from "react-router-dom";

const AdvertisementCategoryHomeFilter=({match})=>{
  const [properties,setProperties]=useState([]);
  const [init,setInit]=useState({home_filters:[]});
  let history = useHistory();
  useEffect(()=>{
    axios
      .get(`${adminPathApi}/advertisement-category/main-filters/${match.params.id}`)
      .then(res=>res.data)
      .then(data=>{
        setProperties(data.properties)
        if (data.searchItems)
          setInit({home_filters:data.searchItems})
      }).catch((e)=>{
      if (e.response.status==402){
        window.location.replace(e.response.data.url)
      }
    })
  },[])

  const onSubmit=(values)=>{
    axios
      .post(`${adminPathApi}/advertisement-category/main-filters/${match.params.id}`,values)
      .then(res=>res.data)
      .then(data=>{
        NotificationManager.success("با موفقیت دخیره شد");
        history.push("/spad-admin/advertisements/categories/list");
      }).catch((e)=>{
      if (e.response.status==402){
        window.location.replace(e.response.data.url)
      }
    })
  }

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={"menu.advertisement-home-filter"} match={match}/>
          <Separator className="mb-4"/>
        </Colxx>
      </Row>
      <Row className="mb-4">
        <Colxx xxs="12">
          <Formik
            initialValues={init}
            onSubmit={onSubmit}
            enableReinitialize
          >
            {({errors, touched, setFieldValue, setFieldTouched, values,setValues}) => (
              <Card className={"bg-custom-1"}>
                <CardBody>
                <Form className="av-tooltip tooltip-label-right">
                  <Card>
                    <CardBody>
                  <FormGroup>
                    <Label>عنوان بخش اول</Label>
                    <Field className={"form-control"} name={"home_filters.0.title"}/>
                  </FormGroup>
                  <FormGroup>
                    <FieldArray name="home_filters.0.filters">
                      {({insert, remove, push}) => (
                        <>
                          {values.home_filters && values.home_filters[0] &&
                            values.home_filters[0].filters && values.home_filters[0].filters.length>0 &&
                            values.home_filters[0].filters.map((item,index)=>(
                              <Card key={index} className={"mb-2"}>
                                <CardBody>
                                  <Row>
                                    <Colxx md="6">
                                      <FormGroup>
                                        <Label>انتخاب ویژگی</Label>
                                        <FormikReactSelect
                                          options={properties}
                                          name={`home_filters.0.filters.${index}.property_id`}
                                          value={values.home_filters[0].filters[index].property_id}
                                          onChange={setFieldValue}
                                          onBlur={setFieldTouched}
                                          getOptionLabel={option => option.title}
                                          getOptionValue={option => option.slug}
                                        />
                                        <ErrorMessage
                                          name={`home_filters.0.filters.${index}.property_id`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </FormGroup>
                                    </Colxx>
                                    <Colxx md="6">
                                      <FormGroup>
                                        <Label>نوع جستجو</Label>
                                        <FormikReactSelect
                                          options={advertisementSearchTypes}
                                          name={`home_filters.0.filters.${index}.search_type`}
                                          value={values.home_filters[0].filters[index].search_type}
                                          onChange={setFieldValue}
                                          onBlur={setFieldTouched}
                                        />
                                        <ErrorMessage
                                          name={`home_filters.0.filters.${index}.search_type`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </FormGroup>
                                    </Colxx>
                                    <Colxx md={6}>
                                      <FieldArray name={`home_filters.0.filters.${index}.search_enum`}>
                                        {({insert:insert1, remove:remove1, push:push1}) => (
                                          <>
                                            <CardSubtitle>مقادیر
                                              <Button
                                                color="primary"
                                                outline
                                                size={"sm"}
                                                className={"mx-2"}
                                                onClick={() => {
                                                  push1('')
                                                }}
                                              >
                                                افزودن
                                              </Button>
                                            </CardSubtitle>
                                            {values.home_filters && values.home_filters[0] && values.home_filters[0].filters  && values.home_filters[0].filters[index].search_enum && values.home_filters[0].filters[index].search_enum.length > 0 &&
                                              values.home_filters[0].filters[index].search_enum.map((innerItem, number) => (
                                                <Row key={number} className={"mb-1"}>
                                                  <Colxx>
                                                    <Field
                                                      className="form-control"
                                                      name={`home_filters.0.filters.${index}.search_enum.${number}`}
                                                      placeholder="عنوان ویژگی"
                                                      type="text"
                                                    />
                                                    <ErrorMessage
                                                      name={`home_filters.0.filters.${index}.search_enum.${number}`}
                                                      component="div"
                                                      className="field-error"
                                                    />
                                                  </Colxx>
                                                  <Colxx>
                                                    <Button
                                                      color="danger"
                                                      outline
                                                      size={"sm"}
                                                      onClick={() => remove1(number)}
                                                    >
                                                      <span className={"simple-icon-trash"}/>
                                                    </Button>
                                                  </Colxx>
                                                </Row>
                                              ))}
                                          </>
                                        )}
                                      </FieldArray>
                                    </Colxx>
                                    <Colxx md={6}>
                                      <FieldArray name={`home_filters.0.filters.${index}.search_enumNames`}>
                                        {({insert:insert1, remove:remove1, push:push1}) => (
                                          <>
                                            <CardSubtitle>برچسب مقادیر
                                              <Button
                                                color="primary"
                                                outline
                                                size={"sm"}
                                                className={"mx-2"}
                                                onClick={() => {
                                                  push1('')
                                                }}
                                              >
                                                افزودن
                                              </Button>
                                            </CardSubtitle>
                                            {values.home_filters && values.home_filters[0] && values.home_filters[0].filters && values.home_filters[0].filters[index] &&
                                              values.home_filters[0].filters[index].search_enumNames && values.home_filters[0].filters[index].search_enumNames.length > 0 &&
                                              values.home_filters[0].filters && values.home_filters[0].filters[index].search_enumNames.map((innerItem, number) => (
                                                <Row key={number} className={"mb-1"}>
                                                  <Colxx>
                                                    <Field
                                                      className="form-control"
                                                      name={`home_filters.0.filters.${index}.search_enumNames.${number}`}
                                                      placeholder="عنوان ویژگی"
                                                      type="text"
                                                    />
                                                    <ErrorMessage
                                                      name={`home_filters.0.filters.${index}.search_enumNames.${number}`}
                                                      component="div"
                                                      className="field-error"
                                                    />
                                                  </Colxx>
                                                  <Colxx>
                                                    <Button
                                                      color="danger"
                                                      outline
                                                      size={"sm"}
                                                      onClick={() => remove1(number)}
                                                    >
                                                      <span className={"simple-icon-trash"}/>
                                                    </Button>
                                                  </Colxx>
                                                </Row>
                                              ))}
                                          </>
                                        )}
                                      </FieldArray>
                                    </Colxx>
                                    <Colxx md="6">
                                      <FormGroup>
                                        <Label>دقت جستجو</Label>
                                        <FormikReactSelect
                                          options={advertisementSearchLevels}
                                          name={`home_filters.0.filters.${index}.search_level`}
                                          value={values.home_filters[0].filters[index].search_level}
                                          onChange={setFieldValue}
                                          onBlur={setFieldTouched}
                                        />
                                        <ErrorMessage
                                          name={`home_filters.0.filters.${index}.search_level`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </FormGroup>
                                    </Colxx>
                                    <Colxx md={6}>
                                      <FormGroup>
                                        <Label>جستجوی بازه ای</Label>
                                        <FormikSwitch
                                          name={`home_filters.0.filters.${index}.range_search`}
                                          className="custom-switch custom-switch-primary"
                                          value={values.home_filters[0].filters[index].range_search}
                                          onChange={setFieldValue}
                                          onBlur={setFieldTouched}
                                        />
                                      </FormGroup>
                                    </Colxx>
                                    {values.home_filters[0].filters[index].range_search?'':(
                                      <Colxx md="6">
                                        <FormGroup>
                                          <Label>عملگر جستجو</Label>
                                          <FormikReactSelect
                                            options={advertisementSearchOperators}
                                            name={`home_filters.0.filters.${index}.search_operator`}
                                            value={values.home_filters[0].filters[index].search_operator}
                                            onChange={setFieldValue}
                                            onBlur={setFieldTouched}
                                          />
                                          <ErrorMessage
                                            name={`home_filters.0.filters.${index}.search_operator`}
                                            component="div"
                                            className="field-error"
                                          />
                                        </FormGroup>
                                      </Colxx>
                                    )}
                                  </Row>
                                </CardBody>
                                <CardFooter>
                                  <Button onClick={()=>remove(index)}>
                                    <span className="fa fa-trash"/>
                                  </Button>
                                </CardFooter>
                              </Card>
                            ))}
                            <Button
                            color="primary"
                            outline
                            size={"sm"}
                            className={"mx-2 mb-4 btn-block"}
                            onClick={() => push({search_type:'text',search_operator:'eq',search_level:1,range_search:false})}
                          >
                            افزودن فیلد
                          </Button>
                        </>
                      )}
                    </FieldArray>
                  </FormGroup>
                  </CardBody>
                  </Card>
                  <Card className="mt-3">
                    <CardBody>
                  <FormGroup>
                    <Label>عنوان بخش دوم</Label>
                    <Field className={"form-control"} name={"home_filters.1.title"}/>
                  </FormGroup>
                  <FormGroup>
                    <FieldArray name="home_filters.1.filters">
                      {({insert, remove, push}) => (
                        <>
                          {values.home_filters && values.home_filters[1] &&
                            values.home_filters[1].filters && values.home_filters[1].filters.length>0 &&
                            values.home_filters[1].filters.map((item,index)=>(
                              <Card key={index} className={"mb-2"}>
                                <CardBody>
                                  <Row>
                                    <Colxx md="6">
                                      <FormGroup>
                                        <Label>انتخاب ویژگی</Label>
                                        <FormikReactSelect
                                          options={properties}
                                          name={`home_filters.1.filters.${index}.property_id`}
                                          value={values.home_filters[1].filters[index].property_id}
                                          onChange={setFieldValue}
                                          onBlur={setFieldTouched}
                                          getOptionLabel={option => option.title}
                                          getOptionValue={option => option.slug}
                                        />
                                        <ErrorMessage
                                          name={`home_filters.1.filters.${index}.property_id`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </FormGroup>
                                    </Colxx>
                                    <Colxx md="6">
                                      <FormGroup>
                                        <Label>نوع جستجو</Label>
                                        <FormikReactSelect
                                          options={advertisementSearchTypes}
                                          name={`home_filters.1.filters.${index}.search_type`}
                                          value={values.home_filters[1].filters[index].search_type}
                                          onChange={setFieldValue}
                                          onBlur={setFieldTouched}
                                        />
                                        <ErrorMessage
                                          name={`home_filters.1.filters.${index}.search_type`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </FormGroup>
                                    </Colxx>
                                    <Colxx md={6}>
                                      <FieldArray name={`home_filters.1.filters.${index}.search_enum`}>
                                        {({insert:insert1, remove:remove1, push:push1}) => (
                                          <>
                                            <CardSubtitle>مقادیر
                                              <Button
                                                color="primary"
                                                outline
                                                size={"sm"}
                                                className={"mx-2"}
                                                onClick={() => {
                                                  push1('')
                                                }}
                                              >
                                                افزودن
                                              </Button>
                                            </CardSubtitle>
                                            {values.home_filters && values.home_filters[1] && values.home_filters[1].filters  && values.home_filters[1].filters[index].search_enum && values.home_filters[1].filters[index].search_enum.length > 0 &&
                                              values.home_filters[1].filters[index].search_enum.map((innerItem, number) => (
                                                <Row key={number} className={"mb-1"}>
                                                  <Colxx>
                                                    <Field
                                                      className="form-control"
                                                      name={`home_filters.1.filters.${index}.search_enum.${number}`}
                                                      placeholder="عنوان ویژگی"
                                                      type="text"
                                                    />
                                                    <ErrorMessage
                                                      name={`home_filters.1.filters.${index}.search_enum.${number}`}
                                                      component="div"
                                                      className="field-error"
                                                    />
                                                  </Colxx>
                                                  <Colxx>
                                                    <Button
                                                      color="danger"
                                                      outline
                                                      size={"sm"}
                                                      onClick={() => remove1(number)}
                                                    >
                                                      <span className={"simple-icon-trash"}/>
                                                    </Button>
                                                  </Colxx>
                                                </Row>
                                              ))}
                                          </>
                                        )}
                                      </FieldArray>
                                    </Colxx>
                                    <Colxx md={6}>
                                      <FieldArray name={`home_filters.1.filters.${index}.search_enumNames`}>
                                        {({insert:insert1, remove:remove1, push:push1}) => (
                                          <>
                                            <CardSubtitle>برچسب مقادیر
                                              <Button
                                                color="primary"
                                                outline
                                                size={"sm"}
                                                className={"mx-2"}
                                                onClick={() => {
                                                  push1('')
                                                }}
                                              >
                                                افزودن
                                              </Button>
                                            </CardSubtitle>
                                            {values.home_filters && values.home_filters[1] && values.home_filters[1].filters && values.home_filters[1].filters[index] &&
                                              values.home_filters[1].filters[index].search_enumNames && values.home_filters[1].filters[index].search_enumNames.length > 0 &&
                                              values.home_filters[1].filters[index].search_enumNames.map((innerItem, number) => (
                                                <Row key={number} className={"mb-1"}>
                                                  <Colxx>
                                                    <Field
                                                      className="form-control"
                                                      name={`home_filters.1.filters.${index}.search_enumNames.${number}`}
                                                      placeholder="عنوان ویژگی"
                                                      type="text"
                                                    />
                                                    <ErrorMessage
                                                      name={`home_filters.1.filters.${index}.search_enumNames.${number}`}
                                                      component="div"
                                                      className="field-error"
                                                    />
                                                  </Colxx>
                                                  <Colxx>
                                                    <Button
                                                      color="danger"
                                                      outline
                                                      size={"sm"}
                                                      onClick={() => remove1(number)}
                                                    >
                                                      <span className={"simple-icon-trash"}/>
                                                    </Button>
                                                  </Colxx>
                                                </Row>
                                              ))}
                                          </>
                                        )}
                                      </FieldArray>
                                    </Colxx>
                                    <Colxx md="6">
                                      <FormGroup>
                                        <Label>دقت جستجو</Label>
                                        <FormikReactSelect
                                          options={advertisementSearchLevels}
                                          name={`home_filters.1.filters.${index}.search_level`}
                                          value={values.home_filters[1].filters[index].search_level}
                                          onChange={setFieldValue}
                                          onBlur={setFieldTouched}
                                        />
                                        <ErrorMessage
                                          name={`home_filters.1.filters.${index}.search_level`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </FormGroup>
                                    </Colxx>
                                    <Colxx md={6}>
                                      <FormGroup>
                                        <Label>جستجوی بازه ای</Label>
                                        <FormikSwitch
                                          name={`home_filters.1.filters.${index}.range_search`}
                                          className="custom-switch custom-switch-primary"
                                          value={values.home_filters[1].filters[index].range_search}
                                          onChange={setFieldValue}
                                          onBlur={setFieldTouched}
                                        />
                                      </FormGroup>
                                    </Colxx>
                                    {values.home_filters[1].filters[index].range_search?'':(
                                      <Colxx md="6">
                                        <FormGroup>
                                          <Label>عملگر جستجو</Label>
                                          <FormikReactSelect
                                            options={advertisementSearchOperators}
                                            name={`home_filters.1.filters.${index}.search_operator`}
                                            value={values.home_filters[1].filters[index].search_operator}
                                            onChange={setFieldValue}
                                            onBlur={setFieldTouched}
                                          />
                                          <ErrorMessage
                                            name={`home_filters.1.filters.${index}.search_operator`}
                                            component="div"
                                            className="field-error"
                                          />
                                        </FormGroup>
                                      </Colxx>
                                    )}
                                  </Row>
                                </CardBody>
                                <CardFooter>
                                  <Button onClick={()=>remove(index)}>
                                    <span className="fa fa-trash"/>
                                  </Button>
                                </CardFooter>
                              </Card>
                            ))}
                            <Button
                            color="primary"
                            outline
                            size={"sm"}
                            className={"mx-2 mb-4 btn-block"}
                            onClick={() => push({search_type:'text',search_operator:'eq',search_level:1,range_search:false})}
                          >
                            افزودن فیلد
                          </Button>
                        </>
                      )}
                    </FieldArray>
                  </FormGroup>
                  </CardBody>
                  </Card>
                  <Button className="mt-2" type={"submit"}>ذخیره</Button>
                </Form>
                </CardBody>
              </Card>
              )}
          </Formik>
        </Colxx>
      </Row>
    </Suspense>
  );
}

export default AdvertisementCategoryHomeFilter;

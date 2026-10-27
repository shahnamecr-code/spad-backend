import React, {Suspense, useEffect, useState} from "react";
import {Button, ButtonGroup, Card, CardBody, CardFooter, CardSubtitle, FormGroup, Label, Row} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import {Formik, Form, FieldArray, ErrorMessage, Field} from "formik";
import {FormikReactSelect, FormikSwitch} from "../../../containers/form-validations/FormikFields";
import {advertisementSearchTypes,advertisementSearchOperators,advertisementSearchLevels} from "../../../constants/advertisementConstants";
import MediaButton from "../../../components/MediaLibrary";
import {NotificationManager} from "../../../components/common/react-notifications";
import { useHistory } from "react-router";

const AdvertisementCategoryCrmLayout=({match})=>{
  const [properties,setProperties]=useState([]);
  const [init,setInit]=useState({
    items: [
      {icon: '' , label : '' , filters:[] }
    ]
  });
  const history = useHistory()
  const onSubmit=(values)=>{
    axios
      .post(`${adminPathApi}/advertisement-category/crm-layout/${match.params.id}`,values)
      .then(res=>res.data)
      .then(data=>{
        NotificationManager.success(data.message, 'با موفقیت ذخیره شد');
        history.push('/spad-admin/advertisements/categories/list');
      }).catch((e)=>{
      if (e.response.status==402){
        window.location.replace(e.response.data.url)
      }
    })
  }

  useEffect(()=>{
    axios
      .get(`${adminPathApi}/advertisement-category/crm-layout/${match.params.id}`)
      .then(res=>res.data)
      .then(data=>{
        setProperties(data.properties)
        if (data.searchItems)
          setInit({items:data.searchItems})
      }).catch((e)=>{
      if (e.response.status==402){
        window.location.replace(e.response.data.url)
      }
    })
  },[])

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={"menu.advertisement-crm-layout"} match={match}/>
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
                <Form className="av-tooltip tooltip-label-right">
                  <CardBody>
                    <ButtonGroup className={"mb-2"}>
                      <Button color={"secondary"} outline onClick={() => {navigator.clipboard.writeText(JSON.stringify(values))}}>
                        کپی کردن مقادیر
                      </Button>
                      <Button color={"secondary"} outline onClick={()=>navigator.clipboard.readText()
                        .then(text => {
                          try{
                            setValues(JSON.parse(text))
                          }catch (e){
                            console.log('محتوای وارد شده معتبر نیست')
                          }
                        })
                        .catch(err => {
                          console.error('Failed to read clipboard contents: ', err);
                        })}>
                        وارد کردن مقادیر
                      </Button>
                    </ButtonGroup>
                    <Card>
                      <CardBody>
                      <FieldArray name="items">
                      {({insert, remove, push}) => (
                        <>

                      {values.items && values.items.length > 0 &&
                      values.items.map((item,index1)=>(
                        <Card  className="mb-3">

                      <CardBody>
                      <Colxx md={12} className="d-flex flex-wrap ">
                        <Colxx md={6} xs={12} className="mb-3 d-flex align-items-center">
                          <Field
                            className="form-control"
                            name={`items.${index1}.label`}
                            placeholder="عنوان"
                            type="text"
                          />
                        </Colxx>
                        <Colxx md={6} xs={12} className="mb-3 d-flex align-items-center justify-content-center">
                          <Label>
                            آیکون
                          </Label>
                        <MediaButton className="m-auto" onSubmit={(value) => setFieldValue(`items.${index1}.icon`, value.files)} default_value={values.items[index1].icon} name={`items.${index1}.icon`}
                        setFieldValue={setFieldValue} multiple/>
                        </Colxx>
                      </Colxx>
                    <p className="h6 text-center border mt-4 pb-2 pt-2 mb-3 bg-light">فیلتر</p>
                    <FieldArray name={`items[${index1}].filters`}>
                      {({insert, remove, push}) => (
                        <>
                          {values.items[index1].filters && values.items[index1].filters.length > 0 &&
                            values.items[index1].filters.map((item,index)=>(
                              <Card key={index} className={"mb-4"}>
                                <CardBody>
                                  <Row>
                                    <Colxx md="6">
                                      <FormGroup>
                                        <Label>انتخاب ویژگی</Label>
                                        <FormikReactSelect
                                          options={properties}
                                          name={`items.${index1}.filters.${index}.property_id`}
                                          value={values.items[index1].filters[index].property_id}
                                          onChange={setFieldValue}
                                          onBlur={setFieldTouched}
                                          getOptionLabel={option => option.title}
                                          getOptionValue={option => option.slug}
                                        />
                                        <ErrorMessage
                                          name={`items.${index1}.filters.${index}.property_id`}
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
                                          name={`items.${index1}.filters.${index}.search_type`}
                                          value={values.items[index1].filters[index].search_type}
                                          onChange={setFieldValue}
                                          onBlur={setFieldTouched}
                                        />
                                        <ErrorMessage
                                          name={`items.${index1}.filters.${index}.search_type`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </FormGroup>
                                    </Colxx>
                                    <Colxx md={6}>
                                      <FieldArray name={`items.${index1}.filters.${index}.search_enum`}>
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
                                            {values.items[index1].filters[index].search_enum && values.items[index1].filters[index].search_enum.length > 0 &&
                                              values.items[index1].filters[index].search_enum.map((innerItem, number) => (
                                                <Row key={number} className={"mb-1"}>
                                                  <Colxx>
                                                    <Field
                                                      className="form-control"
                                                      name={`items.${index1}.filters.${index}.search_enum.${number}`}
                                                      placeholder="عنوان ویژگی"
                                                      type="text"
                                                    />
                                                    <ErrorMessage
                                                      name={`items.${index1}.filters.${index}.search_enum.${number}`}
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
                                      <FieldArray name={`items.${index1}.filters.${index}.search_enumNames`}>
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
                                            {values.items[index1].filters[index].search_enumNames && values.items[index1].filters[index].search_enumNames.length > 0 &&
                                              values.items[index1].filters[index].search_enumNames.map((innerItem, number) => (
                                                <Row key={number} className={"mb-1"}>
                                                  <Colxx>
                                                    <Field
                                                      className="form-control"
                                                      name={`items.${index1}.filters.${index}.search_enumNames.${number}`}
                                                      placeholder="عنوان ویژگی"
                                                      type="text"
                                                    />
                                                    <ErrorMessage
                                                      name={`items.${index1}.filters.${index}.search_enumNames.${number}`}
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
                                          name={`items.${index1}.filters.${index}.search_level`}
                                          value={values.items[index1].filters[index].search_level}
                                          onChange={setFieldValue}
                                          onBlur={setFieldTouched}
                                        />
                                        <ErrorMessage
                                          name={`items.${index1}.filters.${index}.search_level`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </FormGroup>
                                    </Colxx>
                                    {values.items[index1].filters[index].search_type !== "switch" &&
                                      values.items[index1].filters[index].search_type !== "geo" &&
                                      <Colxx md={3}>
                                        <FormGroup>
                                          <Label>جستجوی بازه ای</Label>
                                          <FormikSwitch
                                            name={`items.${index1}.filters.${index}.range_search`}
                                            className="custom-switch custom-switch-primary"
                                            value={values.items[index1].filters[index].range_search}
                                            onChange={setFieldValue}
                                            onBlur={setFieldTouched}
                                          />
                                        </FormGroup>
                                      </Colxx>
                                    }
                                    {values.items[index1].filters[index].search_type == "select" &&
                                      <Colxx md={3} className="d-flex align-items-center">
                                        <FormGroup>
                                          <Label>تکمیل کننده خودکار</Label>
                                          <FormikSwitch
                                            name={`items.${index1}.filters.${index}.auto_complete`}
                                            className="custom-switch custom-switch-primary"
                                            value={values.items[index1].filters[index].auto_complete}
                                            onChange={setFieldValue}
                                            onBlur={setFieldTouched}
                                          />
                                        </FormGroup>
                                      </Colxx>
                                    }
                                    {values.items[index1].filters[index].range_search?'':(
                                      <Colxx md="6">
                                        <FormGroup>
                                          <Label>عملگر جستجو</Label>
                                          <FormikReactSelect
                                            options={advertisementSearchOperators}
                                            name={`items.${index1}.filters.${index}.search_operator`}
                                            value={values.items[index1].filters[index].search_operator}
                                            onChange={setFieldValue}
                                            onBlur={setFieldTouched}
                                          />
                                          <ErrorMessage
                                            name={`items.${index1}.filters.${index}.search_operator`}
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
                            افزودن فیلتر
                          </Button>
                        </>
                      )}
                    </FieldArray>
                      </CardBody>
                    </Card>
                            ))}
                        <Button
                        color="primary"
                        outline
                        size={"sm"}
                        className={"mx-2 mb-4 btn-block"}
                        onClick={() => push({icon: '' , label: '' , filters: []})}
                      >
                        افزودن +
                      </Button>
                        </>
                      )}
                    </FieldArray>
                      </CardBody>
                    </Card>
                  </CardBody>
                  {values.items && values.items.length>0 && (
                    <CardFooter>
                      <Button type={"submit"}>ذخیره</Button>
                    </CardFooter>
                  )}
                </Form>

        {console.log(values)}
              </Card>


            )}
          </Formik>
        </Colxx>
      </Row>
    </Suspense>
  );
}

export default AdvertisementCategoryCrmLayout;

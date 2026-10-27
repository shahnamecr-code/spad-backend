import React, {Suspense,useEffect, useState} from "react";
import {adminPathApi} from "../../../constants/defaultValues";
import {Button, ButtonGroup, Card, CardBody, CardHeader, CardTitle, FormGroup, Label, Row} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import {Formik, Form, FieldArray, ErrorMessage} from "formik";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {FormikReactSelect} from "../../../containers/form-validations/FormikFields";
import {Link} from "react-router-dom";
import {NotificationManager} from "../../../components/common/react-notifications";
import {useHistory} from "react-router-dom";

const AdvertisementListShowInPanel=({match})=>{
  const [loading,setLoading]=useState(true);
  const [init, setInit] = useState({
    front_custom_main_list: [],
    front_custom_secondary_list: [],
    panel_custom_main_list: [],
    panel_custom_secondary_list: [],
  })
  const [properties,setProperties]=useState([]);
  let history = useHistory();
  useEffect(()=>{
    axios
      .get(`${adminPathApi}/advertisement-category/panelAdvertisementList/${match.params.id}`)
      .then(res=>res.data)
      .then(data=>{
        setLoading(false)
        setProperties(data.properties)
        setInit(data.ListSetting??{})
      }).catch((e)=>{
      if (e.response.status==402){
        window.location.replace(e.response.data.url)
      }
    })
  },[])

  const onSubmit=(values)=>{
    axios
      .post(`${adminPathApi}/advertisement-category/panelAdvertisementList/${match.params.id}`,values)
      .then(res=>res.data)
      .then(data=>{
            NotificationManager.success("با موفقیت ذخیره شد");
            history.push("/spad-admin/advertisements/categories/list");
      }).catch((e)=>{
      if (e.response.status==402){
        window.location.replace(e.response.data.url)
      }
    })
  }

  if (loading){
    return <div className="loading"/>
  }

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={"menu.advertisement-list-show"} match={match}/>
          <Separator className="mb-4"/>
        </Colxx>
      </Row>
      <Row className="mb-4">
        <Colxx xxs="12">
          <Card>
            <CardBody className={"bg-custom-1"}>
              <Formik
                initialValues={init}
                onSubmit={onSubmit}
                enableReinitialize
              >
                {({errors, touched, setFieldValue, setFieldTouched, values,setValues}) => (
                  <Form className="av-tooltip tooltip-label-right">
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
                    <FieldArray name={'front_custom_main_list'}>
                      {({insert, remove, push}) => (
                        <>
                          <Button
                            color="primary"
                            outline
                            size={"sm"}
                            className={"mx-2 mb-4 btn-block"}
                            onClick={() => push({slug: ''})}
                          >
                            <span className="fa fa-plus mr-2"/>
                            ویژگی های اصلی آگهی جهت نمایش در صفحات دسته بندی
                          </Button>
                          {(values.front_custom_main_list && values.front_custom_main_list.length > 0) ? (
                            <>
                              {values.front_custom_main_list.map((item, index) => (
                                <Card className="mb-3 px-3">
                                  <CardBody>
                                    <Colxx md="6">
                                      <FormGroup>
                                        <Label>
                                          انتخاب ویژگی
                                          <Button onClick={()=>remove(index)} className={"ml-2"} color={"danger"} size={"xs"} outline>
                                            <span className={"fa fa-times"}/>
                                          </Button>
                                        </Label>
                                        <FormikReactSelect
                                          options={properties}
                                          name={`front_custom_main_list.${index}.slug`}
                                          value={values.front_custom_main_list[index].slug}
                                          onChange={setFieldValue}
                                          onBlur={setFieldTouched}
                                          getOptionLabel={option => option.title}
                                          getOptionValue={option => option.slug}
                                        />
                                        <ErrorMessage
                                          name={`front_custom_main_list.${index}.slug`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </FormGroup>
                                    </Colxx>
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
                        </>
                      )}
                    </FieldArray>
                    <FieldArray name={'front_custom_secondary_list'}>
                      {({insert, remove, push}) => (
                        <>
                          <Button
                            color="primary"
                            outline
                            size={"sm"}
                            className={"mx-2 mb-4 btn-block"}
                            onClick={() => push({slug: ''})}
                          >
                            <span className="fa fa-plus mr-2"/>
                            ویژگی های فرعی آگهی جهت نمایش در صفحات دسته بندی
                          </Button>
                          {(values.front_custom_secondary_list && values.front_custom_secondary_list.length > 0) ? (
                            <>
                              {values.front_custom_secondary_list.map((item, index) => (
                                <Card className="mb-3 px-3">
                                  <CardBody>
                                    <Colxx md="6">
                                      <FormGroup>
                                        <Label>
                                          انتخاب ویژگی
                                          <Button onClick={()=>remove(index)} className={"ml-2"} color={"danger"} size={"xs"} outline>
                                            <span className={"fa fa-times"}/>
                                          </Button>
                                        </Label>
                                        <FormikReactSelect
                                          options={properties}
                                          name={`front_custom_secondary_list.${index}.slug`}
                                          value={values.front_custom_secondary_list[index].slug}
                                          onChange={setFieldValue}
                                          onBlur={setFieldTouched}
                                          getOptionLabel={option => option.title}
                                          getOptionValue={option => option.slug}
                                        />
                                        <ErrorMessage
                                          name={`front_custom_secondary_list.${index}.slug`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </FormGroup>
                                    </Colxx>
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
                        </>
                      )}
                    </FieldArray>
                    <FieldArray name={'panel_custom_main_list'}>
                      {({insert, remove, push}) => (
                        <>
                          <Button
                            color="primary"
                            outline
                            size={"sm"}
                            className={"mx-2 mb-4 btn-block"}
                            onClick={() => push({})}
                          >
                            <span className="fa fa-plus mr-2"/>
                            ویژگی های اصلی آگهی جهت نمایش در لیست های پنل
                          </Button>
                          {(values.panel_custom_main_list && values.panel_custom_main_list.length > 0) ? (
                            <>
                              {values.panel_custom_main_list.map((item, index) => (
                                <Card className="mb-3 px-3">
                                  <CardBody>
                                    <Colxx md="6">
                                      <FormGroup>
                                        <Label>
                                          انتخاب ویژگی
                                          <Button onClick={()=>remove(index)} className={"ml-2"} color={"danger"} size={"xs"} outline>
                                            <span className={"fa fa-times"}/>
                                          </Button>
                                        </Label>
                                        <FormikReactSelect
                                          options={properties}
                                          name={`panel_custom_main_list.${index}.slug`}
                                          value={values.panel_custom_main_list[index].slug}
                                          onChange={setFieldValue}
                                          onBlur={setFieldTouched}
                                          getOptionLabel={option => option.title}
                                          getOptionValue={option => option.slug}
                                        />
                                        <ErrorMessage
                                          name={`panel_custom_main_list.${index}.slug`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </FormGroup>
                                    </Colxx>
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
                        </>
                      )}
                    </FieldArray>
                    <FieldArray name={'panel_custom_secondary_list'}>
                      {({insert, remove, push}) => (
                        <>
                          <Button
                            color="primary"
                            outline
                            size={"sm"}
                            className={"mx-2 mb-4 btn-block"}
                            onClick={() => push({})}
                          >
                            <span className="fa fa-plus mr-2"/>
                            ویژگی های فرعی آگهی جهت نمایش در لیست های پنل
                          </Button>
                          {(values.panel_custom_secondary_list && values.panel_custom_secondary_list.length > 0) ? (
                            <>
                              {values.panel_custom_secondary_list.map((item, index) => (
                                <Card className="mb-3 px-3">
                                  <CardBody>
                                    <Colxx md="6">
                                      <FormGroup>
                                        <Label>
                                          انتخاب ویژگی
                                          <Button onClick={()=>remove(index)} className={"ml-2"} color={"danger"} size={"xs"} outline>
                                            <span className={"fa fa-times"}/>
                                          </Button>
                                        </Label>
                                        <FormikReactSelect
                                          options={properties}
                                          name={`panel_custom_secondary_list.${index}.slug`}
                                          value={values.panel_custom_secondary_list[index].slug}
                                          onChange={setFieldValue}
                                          onBlur={setFieldTouched}
                                          getOptionLabel={option => option.title}
                                          getOptionValue={option => option.slug}
                                        />
                                        <ErrorMessage
                                          name={`panel_custom_secondary_list.${index}.slug`}
                                          component="div"
                                          className="field-error"
                                        />
                                      </FormGroup>
                                    </Colxx>
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
                        </>
                      )}
                    </FieldArray>
                    <Button type={"submit"}>
                      ذخیره
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

export default AdvertisementListShowInPanel;

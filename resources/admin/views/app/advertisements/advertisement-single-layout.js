import React, {Suspense, useEffect, useState} from "react";
import {Button, ButtonGroup, Card, CardBody, FormGroup, Label, Row} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {ErrorMessage, Field, FieldArray, Form, Formik} from "formik";
import {FormikReactSelect} from "../../../containers/form-validations/FormikFields";
import {adminPathApi} from "../../../constants/defaultValues";
import {advertisementSingleGroupLayouts} from "../../../constants/advertisementConstants";
import {NotificationManager} from "../../../components/common/react-notifications";
import {useHistory} from "react-router-dom";

const AdvertisementSingleLayout = ({match}) => {
  const [loading, setLoading] = useState(true);
  const [init, setInit] = useState({});
  const [properties, setProperties] = useState([]);
  const widgetsTypes = [
    {label: 'پیش فرض', value: 'default'},
    {label: 'ردیف افقی', value: 'horizontal'},
    {label: 'ردیف عمودی', value: 'vertical'},
    {label: 'فیلد گروهی', value: 'group'},
  ];
  let history = useHistory();
  useEffect(() => {
    axios
      .get(`${adminPathApi}/advertisement-category/single-layout/${match.params.id}`)
      .then(res => res.data)
      .then(data => {
        setLoading(false)
        setProperties(data.properties ?? [])
        setInit({single_layout: data.singleLayout ?? false})
      }).catch((e)=>{
      if (e.response.status==402){
        window.location.replace(e.response.data.url)
      }
    })
  }, [])

  const onSubmit = (values) => {
    axios
      .post(`${adminPathApi}/advertisement-category/single-layout/${match.params.id}`, values)
      .then(res => res.data)
      .then(data => {
        NotificationManager.success("با موفقیت ذخیره شد");
        history.push("/spad-admin/advertisements/categories/list");
      }).catch((e)=>{
      if (e.response.status==402){
        window.location.replace(e.response.data.url)
      }
    })
  }

  if (loading) {
    return <div className={"loading"}/>
  }

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={"menu.advertisement.single-page-layout"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Row className="mb-4">
        <Colxx xss={12}>
          <Card className="mb-4">
            <CardBody className={"bg-custom-1"}>
              <Formik enableReinitialize initialValues={init} onSubmit={onSubmit}>
                {({errors, touched, setFieldValue, setFieldTouched, values, setValues}) => (
                  <Form className="av-tooltip tooltip-label-right">
                    <ButtonGroup className={"mb-2"}>
                      <Button color={"secondary"} outline onClick={() => {
                        navigator.clipboard.writeText(JSON.stringify(values))
                      }}>
                        کپی کردن مقادیر
                      </Button>
                      <Button color={"secondary"} outline onClick={() => navigator.clipboard.readText()
                        .then(text => {
                          try {
                            setValues(JSON.parse(text))
                          } catch (e) {
                            console.log('محتوای وارد شده معتبر نیست')
                          }
                        })
                        .catch(err => {
                          console.error('Failed to read clipboard contents: ', err);
                        })}>
                        وارد کردن مقادیر
                      </Button>
                    </ButtonGroup>
                    <FieldArray name={'single_layout'}>
                      {({insert, remove, push}) => (
                        <>
                          <Button
                            color="primary"
                            outline
                            size={"sm"}
                            className={"mx-2 mb-4 btn-block"}
                            onClick={() => push({widget_type: 'default'})}
                          >
                            <span className="fa fa-plus mr-2"/>
                            افزودن
                          </Button>
                          {(values.single_layout && values.single_layout.length > 0) ? (
                            <>
                              {values.single_layout.map((item, index) => (
                                <Card className="mb-3 px-3">
                                  <CardBody>
                                    <Row>
                                      <Colxx md="6">
                                        <FormGroup>
                                          <Label>نوع نمایش</Label>
                                          <FormikReactSelect
                                            name={`single_layout.${index}.widget_type`}
                                            value={values.single_layout[index].widget_type}
                                            options={widgetsTypes}
                                            onChange={setFieldValue}
                                            onBlur={setFieldTouched}/>
                                        </FormGroup>
                                      </Colxx>
                                      {values.single_layout[index].widget_type == 'default' ? (
                                        <Colxx md="6">
                                          <FormGroup>
                                            <Label>
                                              انتخاب ویژگی
                                              <Button onClick={() => remove(index)} className={"ml-2"} color={"danger"}
                                                      size={"xs"} outline>
                                                <span className={"fa fa-times"}/>
                                              </Button>
                                            </Label>
                                            <FormikReactSelect
                                              options={properties}
                                              name={`single_layout.${index}.slug`}
                                              value={values.single_layout[index].slug}
                                              onChange={setFieldValue}
                                              onBlur={setFieldTouched}
                                              getOptionLabel={option => option.title}
                                              getOptionValue={option => option.slug}
                                            />
                                            <ErrorMessage
                                              name={`single_layout.${index}.slug`}
                                              component="div"
                                              className="field-error"
                                            />
                                          </FormGroup>
                                        </Colxx>
                                      ) : ''}
                                      {values.single_layout[index].widget_type != 'default' ? (
                                        <Colxx md="6">
                                          <FormGroup>
                                            <Label>عنوان</Label>
                                            <Field
                                              className={"form-control"}
                                              name={`single_layout.${index}.title`}/>
                                          </FormGroup>
                                        </Colxx>
                                      ) : ''}
                                      {values.single_layout[index].widget_type == 'group' ? (
                                        <Colxx md="12">
                                          <FormGroup>
                                            <Label>چیدمان اطلاعات گروه</Label>
                                            <FormikReactSelect
                                              name={`single_layout.${index}.front_layout`}
                                              onChange={setFieldValue}
                                              onBlur={setFieldTouched}
                                              options={advertisementSingleGroupLayouts}
                                            />
                                          </FormGroup>
                                        </Colxx>
                                      ) : ''}
                                      {values.single_layout[index].widget_type != 'default' ? (
                                        <>
                                          <FieldArray name={`single_layout.${index}.items`}>
                                            {({insert: group_insert, remove: group_remove, push: group_push}) => (
                                              <>
                                                <Button
                                                  color="primary"
                                                  outline
                                                  size={"sm"}
                                                  className={"mx-2 mb-4 btn-block"}
                                                  onClick={() => group_push({slug: ''})}
                                                >
                                                  <span className="fa fa-plus mr-2"/>
                                                  افزودن
                                                </Button>
                                                {(values.single_layout[index].items && values.single_layout[index].items.length > 0) ? (
                                                  <>
                                                    {values.single_layout[index].items.map((group, number) => (
                                                      <>
                                                        <Colxx md="6">
                                                          <FormGroup>
                                                            <Label>
                                                              انتخاب ویژگی
                                                              <Button onClick={() => group_remove(number)}
                                                                      className={"ml-2"} color={"danger"}
                                                                      size={"xs"} outline>
                                                                <span className={"fa fa-times"}/>
                                                              </Button>
                                                            </Label>
                                                            <FormikReactSelect
                                                              options={properties}
                                                              name={`single_layout.${index}.items.${number}.slug`}
                                                              value={values.single_layout[index].items[number].slug}
                                                              onChange={setFieldValue}
                                                              onBlur={setFieldTouched}
                                                              getOptionLabel={option => option.title}
                                                              getOptionValue={option => option.slug}
                                                            />
                                                          </FormGroup>
                                                        </Colxx>
                                                      </>
                                                    ))}
                                                  </>
                                                ) : ''}
                                              </>
                                            )}
                                          </FieldArray>
                                        </>
                                      ) : ''}
                                    </Row>
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
                    <Button type={"submit"}>ذخیره</Button>
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

export default AdvertisementSingleLayout;

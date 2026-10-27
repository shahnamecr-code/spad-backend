import { Field, Formik , FieldArray, Form } from "formik";
import { Suspense, useState } from "react";
import { Button, Card, CardBody, CardTitle, FormGroup, Label, Row } from "reactstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import AlertError from "../../../../components/AlertError";
import { Colxx, Separator } from "../../../../components/common/CustomBootstrap";
import {FormikReactSelect} from "../../../../containers/form-validations/FormikFields";
import axios from "axios";
import { NotificationManager } from "../../../../components/common/react-notifications";
import { useHistory } from "react-router";
import { adminPathApi } from "../../../../constants/defaultValues";
import MediaButton from "../../../../components/MediaLibrary";

const Sidebar = ({match}) =>{
    const [initialValues , setInitialValues] = useState({
        name: '',
        content: [{
            title: '',
            type: '',
        }]
    });
    const options = [
        {value: 'latestBlogPosts' , label: 'آخرین پست های بلاگ' },
        {value: 'latestAds' , label: 'آخرین آگهی ها'},
        {value: 'advertising', label: 'تبلیغات'},
        {value: 'BlogCategories', label: 'دسته بندی های وبلاگ'}
    ];
    const history = useHistory();
    const onSubmit = (values) =>{
    axios
      .post(`${adminPathApi}/settings/sidebar/create`, values)
      .then(res=>{
        NotificationManager.success(res.message, ' موفق  ');
        history.push('/spad-admin/settings/sidebar-list');
      }).catch((response) => {
        NotificationManager.error(response.message, 'ناموفق');
      })
    }
    return(
        <Suspense fallback={<div className={'loading'}/>}>
        <Row>
          <Colxx xss="12">
            <Breadcrumb heading="menu.sidebar_create" match={match}/>
            <Separator className="mb-5"/>
          </Colxx>
          <Colxx md="12">
            <Card>
              <CardBody>
                <CardTitle>ایجاد سایدبار</CardTitle>
                <Formik enableReinitialize initialValues={initialValues} onSubmit={onSubmit}>
                  {({errors, touched, setFieldValue, setFieldTouched, values}) => (
                    <Form>
                        <Colxx md="6">
                          <FormGroup>
                            <Label>نام سایدبار</Label>
                            <Field
                              name="name"
                              className="form-control"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="12">
                          <FormGroup>
                            <FieldArray name={`content`}>
                                {({insert:insert, remove:remove, push:push}) => (
                                    <>
                                        {
                                            values.content.length > 0 && 
                                            values.content.map((innerItem ,index)=>(
                                                <Card className="mb-3">
                                                    <CardBody>
                                                        <Row className="mb-2 justify-content-around align-items-end">
                                                            <Colxx md={values.content[index].type == 'advertising' ? '6' : '5'}>
                                                                <Label>عنوان سایدبار</Label>
                                                                <Field
                                                                    name={`content[${index}].title`}
                                                                    className="form-control"
                                                                />
                                                            </Colxx>
                                                            <Colxx md={values.content[index].type == 'advertising' ? '6' : '5'}>
                                                                <Label>نوع سایدبار</Label>
                                                                <FormikReactSelect
                                                                    options={options}
                                                                    name={`content[${index}].type`}
                                                                    onChange={setFieldValue}
                                                                    onBlur={setFieldTouched}
                                                                />
                                                            </Colxx>
                                                            {values.content[index].type == 'advertising'  && 
                                                                <Colxx md="2" className="mt-3">
                                                                    <Label>عکس</Label>
                                                                    <MediaButton onSubmit={(value) => setFieldValue(`content[${index}].src`, value.files)}
                                                                        default_value={values.content[index].src} name={`content[${index}].src`} setFieldValue={setFieldValue}
                                                                    />
                                                                </Colxx>
                                                            }
                                                            {values.content[index].type == 'advertising'  && 
                                                                <Colxx md="4" className="mt-3">
                                                                    <Label>نوشته پیش فرض عکس</Label>
                                                                    <Field
                                                                        name={`content[${index}].alt`}
                                                                        className="form-control"
                                                                    />
                                                                </Colxx>
                                                            }
                                                            {values.content[index].type == 'advertising'  && 
                                                                <Colxx md="4" className="mt-3">
                                                                    <Label>لینک عکس</Label>
                                                                    <Field
                                                                        name={`content[${index}].link`}
                                                                        className="form-control"
                                                                    />
                                                                </Colxx>
                                                            }
                                                            <Button
                                                                color="danger"
                                                                outline
                                                                size={"sm"}
                                                                onClick={() => remove(index)}
                                                            >
                                                                <span className={"simple-icon-trash"}/>
                                                            </Button>
                                                        </Row>
                                                    </CardBody>
                                                </Card>
                                            ))
                                        }
                                        <div className="w-100 d-flex justify-content-center">
                                            <Button
                                                color="primary"
                                                outline
                                                size={"sm"}
                                                className={"w-85 mt-2"}
                                                onClick={() => {
                                                    push('')
                                                }}
                                            >
                                                افزودن
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </FieldArray>
                          </FormGroup>
                        </Colxx>
                        <Button type="submit">ذخیره</Button>
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
export default Sidebar;
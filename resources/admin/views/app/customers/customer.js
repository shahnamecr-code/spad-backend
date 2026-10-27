import React, {Suspense, useState} from 'react';
import {Button, Card, CardBody, CardTitle, FormGroup, Label, Row} from 'reactstrap';
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {Field, Form, Formik} from "formik";
import {FormikReactSelect, FormikSwitch} from "../../../containers/form-validations/FormikFields";
import DatePicker, {utils} from 'react-modern-calendar-datepicker';
import {adminPathApi, adminRoot} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";
import {useHistory} from "react-router-dom";
import AlertError from "../../../components/AlertError";

const Customer = ({match, edit = false, location}) => {
  let history = useHistory();
  const [serverError, setServerError] = React.useState({});
  const [loading, setLoading] = React.useState(true);
  const [customerGroupList, setCustomerGroupList] = React.useState([]);
  const id = match.params.id ? match.params.id : null;
  const [initialValues, setInitialValues] = useState({
    username: "",
    password: "",
    first_name: "",
    last_name: "",
    code_melli: "",
    birthday: "",
    customer_groups: [],
    status: true,
    black_list: false,
    description: "",
    photo: "",
    email: "",
    mobile: "",
    phone_number: "",
    height: "",
    weight: "",
    eye_color: "",
    blood_type: "",
    gender: "",
  });

  React.useEffect(async () => {
    if (id && edit) {
      await axios.get(`${adminPathApi}/customer/${id}`)
        .then(({data}) => {
          setInitialValues(data);
          setLoading(false);
        })
        .catch((error) => {
          history.push(`${adminRoot}/error`, {
            status: error.response ? error.response.status : 500
          });
        });
    }
  }, []);

  // React.useEffect(async () => {
  //   await axios.get(`${adminPathApi}/getCustomerGroup`)
  //     .then((res) => {
  //       setCustomerGroupList(res.data);
  //     })
  //     .catch(error => console.log(error));
  // }, []);

  const onSubmit = async (values) => {
    if (edit) {
      await axios.put(`${adminPathApi}/customer/${id}`, values)
        .then(({data}) => {
          NotificationManager.success(data.message, 'موفقیت');
          if (location.state && location.state.from) {
            history.push(location.state.from);
          } else {
            history.push(`${adminRoot}/customers/list`);
          }
        })
        .catch(({response}) => {
          setServerError(response.data.errors);
        });
    } else {
      await axios.post(`${adminPathApi}/customer`, values)
        .then(({data}) => {
          NotificationManager.success(data.message, 'موفقیت');
          history.push(`${adminRoot}/customers/list`);
        })
        .catch(({response}) => {
          setServerError(response.data.errors);
        });
    }

  };

  if (edit && loading) {
    return <div className={'loading'}/>
  }

  return (
    <>
      <Suspense fallback={<div className={'loading'}/>}>
        <Row>
          <Colxx xss="12">
            <Breadcrumb heading="menu.customers" match={match}/>
            <Separator className="mb-5"/>
          </Colxx>
        </Row>
        <Colxx md="12">
          <Card>
            <CardBody>
              <CardTitle>افزودن مشتری</CardTitle>
              <AlertError errors={serverError}/>
              <Formik enableReinitialize initialValues={initialValues} onSubmit={onSubmit}>
                {
                  ({errors, touched, setFieldValue, setFieldTouched, values}) => (
                    <Form>
                      <Row>
                        {/* <Colxx md="6">
                          <FormGroup>
                            <Label>نام کاربری</Label>
                            <Field
                              name="username"
                              className="form-control"
                            />
                          </FormGroup>
                        </Colxx> */}

                        <Colxx md="6">
                          <FormGroup>
                            <Label>نام</Label>
                            <Field
                              name="first_name"
                              className="form-control"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="6">
                          <FormGroup>
                            <Label>نام خانوادگی</Label>
                            <Field
                              name="last_name"
                              className="form-control"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="6">
                          <FormGroup>
                            <Label>تلفن همراه</Label>
                            <Field
                              className="form-control"
                              name="mobile"
                              placeholder="09"
                            />
                          </FormGroup>
                        </Colxx>
{/*                        <Colxx md="6">
                          <FormGroup>
                            <Label>ایمیل</Label>
                            <Field
                              className="form-control"
                              name="email"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="6">
                          <FormGroup>
                            <Label>رمز عبور</Label>
                            <Field
                              name="password"
                              className="form-control"
                              type="password"
                            />
                          </FormGroup>
                        </Colxx>*/}

                        {/* <Colxx md="6">
                          <FormGroup>
                            <Label>گروه مشتری</Label>
                            <FormikReactSelect
                              name={'customer_groups'}
                              isMulti
                              onChange={setFieldValue}
                              onBlur={setFieldTouched}
                              options={customerGroupList && customerGroupList}
                              value={values.customer_groups}
                            />
                          </FormGroup>
                        </Colxx> */}
                        {/* <Colxx md="6">
                          <FormGroup>
                            <Label>تاریخ تولد</Label>
                            <DatePicker
                              inputClassName={'form-control'}
                              locale={'fa'}
                              onChange={(value) => setFieldValue('birthday', value)}
                              value={values.birthday}
                              shouldHighlightWeekends
                              maximumDate={utils('fa').getToday()}
                            />
                          </FormGroup>
                        </Colxx> */}
                        <Colxx md="3">
                          <FormGroup>
                            <Label>فعال</Label>
                            <FormikSwitch
                              name="status"
                              className={'custom-switch custom-switch-primary'}
                              onChange={setFieldValue}
                              onBlur={setFieldTouched}
                              value={values.status}
                            />
                          </FormGroup>
                        </Colxx>
                        {/* <Colxx md="3">
                          <FormGroup>
                            <Label>لیست سیاه</Label>
                            <FormikSwitch
                              className={'custom-switch custom-switch-primary'}
                              name="black_list"
                              onChange={setFieldValue}
                              onBlur={setFieldTouched}
                              value={values.black_list}
                            />
                          </FormGroup>
                        </Colxx> */}
                        {/* <Colxx md="6">
                          <FormGroup>
                            <Label>توضیحات</Label>
                            <Field
                              as="textarea"
                              name="description"
                              className="form-control"
                              rows="10"
                            />
                          </FormGroup>
                        </Colxx> */}
                      </Row>
                      {/* <Separator className={"my-4"}/>
                      <CardTitle>اطلاعات تماس</CardTitle>
                      <Row>

                        <Colxx md="6">
                          <FormGroup>
                            <Label>تلفن همراه</Label>
                            <Field
                              className="form-control"
                              name="mobile"
                              placeholder="09"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="6">
                          <FormGroup>
                            <Label>تلفن ثابت</Label>
                            <Field
                              className="form-control"
                              name="phone_number"
                              placeholder="3634 0000"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="6">
                          <FormGroup>
                            <Label>کد ملی</Label>
                            <Field
                              name="code_melli"
                              className="form-control"
                            />
                          </FormGroup>
                        </Colxx>
                      </Row>
                      <Separator className={"my-4"}/>
                      <CardTitle>سایر اطلاعات</CardTitle>
                      <Row>
                        <Colxx md="3">
                          <FormGroup className="mt-2">
                            <Label>قد (‌سانتی متر)</Label>
                            <Field
                              name="height"
                              className="form-control"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="3">
                          <FormGroup className="mt-2">
                            <Label>وزن (کیلوگرم)</Label>
                            <Field
                              name="weight"
                              className="form-control"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="3">
                          <FormGroup className="mt-2">
                            <Label>رنگ چشم</Label>
                            <Field
                              name="eye_color"
                              className="form-control"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="3">
                          <FormGroup className="mt-2">
                            <Label>گروه خونی</Label>
                            <Field
                              name="blood_type"
                              className="form-control"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="3">
                          <FormGroup className="mt-2">
                            <Label>جنسیت</Label>
                            <Field
                              name="gender"
                              className="form-control"
                            />
                          </FormGroup>
                        </Colxx>
                      </Row> */}
                      <Row>
                        <Colxx md="12" className={'mt-2'}>
                          <Button type="submit" color={'primary'}>ذخیره</Button>
                        </Colxx>
                      </Row>
                    </Form>
                  )
                }
              </Formik>
            </CardBody>
          </Card>
        </Colxx>
      </Suspense>
    </>
  )
}

export default Customer;

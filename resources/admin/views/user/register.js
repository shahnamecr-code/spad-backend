import React, {Suspense, useRef} from 'react';
import {Card, CardBody, CardTitle, CustomInput, FormGroup, Label, Row,} from 'reactstrap';
import {NavLink} from 'react-router-dom';
import {registerUser} from '../../redux/actions';
import {Field, Form, Formik} from "formik";
import {Step, Steps, Wizard} from 'react-albus';
import IntlMessages from '../../helpers/IntlMessages';
import {Colxx} from '../../components/common/CustomBootstrap';
import {adminPathApi, companyOwnership} from '../../constants/defaultValues';
import axios from "axios";
import AlertError from "../../components/AlertError";
import {connect} from "react-redux";
import BottomNavigation from "./registerShop/BottomNavigation";
import {Actual, Legal} from "./registerShop/StoreType";
import ContactInfo from "./registerShop/ContactInfo";
import UploadDocument from "./registerShop/UploadDocument";
import EmailVerification from "./registerShop/EmailVerification";
import MobileVerification from "./registerShop/MobileVerification";

const Register = ({history, step, location}) => {
  const [pervLabel, setPervLabel] = React.useState(false);
  const [btnLoading, setBtnLoading] = React.useState(false);
  const [errors, setErrors] = React.useState({});
  const [initVerifyForm, setInitVerifyForm] = React.useState({verify_code: '', email: ''});
  const [initVerifyMobileForm, setInitVerifyMobileForm] = React.useState({verify_code: '', mobile: ''});
  const wizard = useRef();

  const forms = {
    register: useRef(),
    verifyEmail: useRef(),
    verifyMobile: useRef(),
    completeRegister: useRef(),
    contactInfo: useRef(),
    businessInfo: useRef(),
    uploadDocument: useRef(),
  };

  const onClickNext = (goToNext, steps, step, push) => {
    const form = forms[step.id].current;
    setBtnLoading(true);
    step.isDone = true;
    setErrors({});
    form.submitForm();
    /*switch (step.id) {
      case 'register':
        form.submitForm();
        break;
      case 'completeRegister':
        form.submitForm();
        break;
      case 'contactInfo':
        form.submitForm();
        break;
      case 'businessInfo':
        form.submitForm();
        break;
    }*/
  };

  const onHandleSubmitRegister = async (values, wizard) => {
    await axios.post(`${adminPathApi}/registerShop`, values)
      .then(res => {
        setBtnLoading(false);
        if (res.data.status) {
          setInitVerifyForm((prevInit) => ({...prevInit, email: values.email}));
          wizard.next();
        }
      })
      .catch(error => {
        setBtnLoading(false);
        setErrors(error.response.data.errors);
      });
  }

  const onHandleVerifyEmail = async (values, wizard, newCode = false) => {
    await axios.post(`${adminPathApi}/verifyEmail`, values)
      .then(res => {
        setBtnLoading(false);
        if (res.data.status) {
          wizard.next();
        }
      })
      .catch(error => {
        setBtnLoading(false);
        setErrors(error.response.data.errors);
      });
  }

  const onHandleVerifyMobile = async (values, wizard) => {
    await axios.post(`${adminPathApi}/verifyMobile`, values)
      .then(res => {
        setBtnLoading(false);
        if (res.data.status) {
          setInitVerifyMobileForm((prevInit) => ({...prevInit, mobile: values.mobile}));
          wizard.next();
        }
      })
      .catch(error => {
        setBtnLoading(false);
        setErrors(error.response.data.errors);
      });
  }

  const onHandleSubmitRegisterComplete = async (values, wizard) => {
    await axios.post(`${adminPathApi}/completeRegister`, values)
      .then(res => {
        setBtnLoading(false);
        if (res.data.status) {
          wizard.next();
        }
      })
      .catch(error => {
        setBtnLoading(false);
        setErrors(error.response.data.errors);
      });
  }

  const onHandleSubmitContactInfo = async (values, wizard) => {
    await axios.post(`${adminPathApi}/contactInfo`, values)
      .then(res => {
        setBtnLoading(false);
        if (res.data.status) {
          wizard.next();
        }
      })
      .catch(error => {
        setBtnLoading(false);
        setErrors(error.response.data.errors);
      });
  }

  const onHandleSubmitBusinessInfo = async (values, wizard) => {
    await axios.post(`${adminPathApi}/businessInfo`, values)
      .then(res => {
        setBtnLoading(false);
        if (res.data.status) {
          wizard.next();
        }
      })
      .catch(error => {
        setBtnLoading(false);
        setErrors(error.response.data.errors);
      });
  }

  const onHandleUploadDocument = async (values, wizard) => {
    const formData = new FormData();
    for (let value in values) {
      formData.append(value, values[value]);
    }
    // Update the formData object
    /*formData.append(
      "myFile",
      values.file
    );*/
    await axios.post(`${adminPathApi}/uploadDocument`, formData)
      .then(res => {
        setBtnLoading(false);
        if (res.data.status) {
          history.push('/spad-admin')
        }
      })
      .catch(error => {
        setBtnLoading(false);
        setErrors(error.response.data.errors);
      });
  }

  const ActualOrLegal = (props) => {
    switch (props.values.type) {
      case 'actual':
        return (<Actual {...props}/>);
      case 'legal':
        return (<Legal {...props}/>);
      default:
        return (<></>);
    }
  };

  React.useEffect(() => {
    if (step) {
      wizard.current.push(step);
    }
  }, []);

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row className="h-100">
        <Colxx xxs="12" md="10" className="mx-auto my-auto">
          <Wizard ref={wizard}>
            {(wizard) => {
              return (
                <Card className="auth-card">
                  <div className="position-relative image-side ">
                    <p className="text-white h2">سامانه مدیریت املاک مشاوران ملک</p>
                    <p className="white mb-0">
                      Please use this form to register. <br/>
                      If you are a member, please{' '}
                      <NavLink to="/spad-login/login" className="white">
                        login
                      </NavLink>
                      .
                      {/*<SideNavigation disableNav/>*/}
                    </p>
                  </div>
                  <div className="form-side">
                    {companyOwnership?(
                      <NavLink to="/" className="white">
                        <span className="logo-single"/>
                      </NavLink>
                    ):''}
                    <CardBody className={'wizard wizard-default'}>
                      {/*<TopNavigation className="justify-content-center" disableNav/>*/}
                      <Steps>
                        <Step id="register" name={'ثبت نام'}
                              desc={''} label={'ثبت نام'}>
                          <Formik
                            initialValues={{
                              first_name: '',
                              last_name: '',
                              email: '',
                              password: '',
                              password_confirmation: ''
                            }}
                            onSubmit={(values) => onHandleSubmitRegister(values, wizard)}
                            innerRef={forms.register}>
                            {({values}) => (
                              <Form>
                                <CardTitle className="mb-4">
                                  ثبت نام فروشنده
                                </CardTitle>
                                <AlertError errors={errors}/>
                                <FormGroup row>
                                  <Colxx xss={6}>
                                    <FormGroup>
                                      <FormGroup className="form-group has-float-label">
                                        <Label>
                                          نام
                                        </Label>
                                        <Field type="email" className="form-control" name={'first_name'}/>
                                      </FormGroup>
                                    </FormGroup>
                                  </Colxx>
                                  <Colxx xss={6}>
                                    <FormGroup>
                                      <FormGroup className="form-group has-float-label">
                                        <Label>
                                          نام خانوادگی
                                        </Label>
                                        <Field type="email" className="form-control" name={'last_name'}/>
                                      </FormGroup>
                                    </FormGroup>
                                  </Colxx>
                                </FormGroup>
                                <FormGroup className="form-group has-float-label  mb-4">
                                  <Label>
                                    <IntlMessages id="user.email"/>
                                  </Label>
                                  <Field type="email" className="form-control" name={'email'}/>
                                </FormGroup>
                                <FormGroup className="form-group has-float-label  mb-4">
                                  <Label>
                                    <IntlMessages id="user.password"/>
                                  </Label>
                                  <Field type="password" className={'form-control'} name={'password'}/>
                                </FormGroup>
                                <FormGroup className="form-group has-float-label  mb-4">
                                  <Label>
                                    تکرار رمز عبور
                                  </Label>
                                  <Field type="password" className={'form-control'} name={'password_confirmation'}/>
                                </FormGroup>
                              </Form>
                            )}
                          </Formik>
                        </Step>
                        <Step id={"verifyEmail"} name={'تایید ایمیل'} label={'ادامه'}>
                          <Formik initialValues={initVerifyForm}
                                  onSubmit={(values) => onHandleVerifyEmail(values, wizard)}
                                  innerRef={forms.verifyEmail}
                                  enableReinitialize>
                            {() => (
                              <Form>
                                <CardTitle>تایید ایمیل</CardTitle>
                                <AlertError errors={errors}/>
                                <EmailVerification location={location}/>
                              </Form>
                            )}
                          </Formik>
                        </Step>
                        <Step id="completeRegister"
                              name={'اطلاعات فروشنده'} label={'ادامه'}>
                          <Formik
                            initialValues={{
                              type: '',
                              company_name: 'امیر',
                              company_type: '0',
                              register_number: 1362747653,
                              identity_number: 1362747653,
                              economic_number: 1362747653,
                            }}
                            onSubmit={(values) => onHandleSubmitRegisterComplete(values, wizard)}
                            innerRef={forms.completeRegister}>
                            {({values, setFieldValue, setTouched}) => (
                              <Form>
                                <CardTitle className="mb-4">
                                  اطلاعات فروشنده
                                </CardTitle>
                                <AlertError errors={errors}/>
                                <FormGroup className="mb-4">
                                  <Label className={'ml-1'}>چه نوع فروشنده ای هستید؟</Label>
                                  <div>
                                    <CustomInput key={'actual'} type="radio" id={'actual'} label={"حقیقی"}
                                                 name={'typeStore'}
                                                 onChange={(val) => setFieldValue('type', 1)}
                                                 onBlur={setTouched}
                                                 inline
                                    />
                                    <CustomInput key={'legal'} type="radio" id={'legal'} label={"حقوقی"}
                                                 name={'typeStore'}
                                                 onChange={(val) => setFieldValue('type', 2)}
                                                 onBlur={setTouched}
                                                 inline
                                    />
                                  </div>
                                </FormGroup>
                                {}
                                <ActualOrLegal values={values} setFieldValue={setFieldValue}
                                               setTouched={setFieldValue}/>
                              </Form>
                            )}
                          </Formik>
                        </Step>
                        <Step id="contactInfo" name={'اطلاعات تماس'} label={'ادامه'}>
                          <Formik
                            initialValues={{
                              state_id: '',
                              city_id: '',
                              address: '',
                              post_code: '',
                              phone: '',
                              mobile: '',
                              lat_and_long: '',
                            }}
                            onSubmit={(values) => onHandleSubmitContactInfo(values, wizard)}
                            innerRef={forms.contactInfo}>
                            {() => (
                              <Form>
                                <CardTitle className="mb-4">
                                  اطلاعات تماس
                                </CardTitle>
                                <AlertError errors={errors}/>
                                <ContactInfo/>
                              </Form>
                            )}
                          </Formik>
                        </Step>
                        <Step id={"verifyMobile"} name={'تایید شماره موبایل'} label={'ادامه'}>
                          <Formik initialValues={initVerifyMobileForm}
                                  onSubmit={(values) => onHandleVerifyMobile(values, wizard)}
                                  innerRef={forms.verifyMobile}
                                  enableReinitialize>
                            {() => (
                              <Form>
                                <CardTitle>تایید شماره موبایل</CardTitle>
                                <AlertError errors={errors}/>
                                <MobileVerification location={location}/>
                              </Form>
                            )}
                          </Formik>
                        </Step>
                        <Step id="businessInfo" name={'اطلاعات تجاری'} label={'ثبت'}>
                          <Formik initialValues={{}} onSubmit={(values) => onHandleSubmitBusinessInfo(values, wizard)}
                                  innerRef={forms.businessInfo}>
                            {() => (
                              <Form>
                                <CardTitle className="mb-4">
                                  اطلاعات تجاری
                                </CardTitle>
                                <AlertError errors={errors}/>
                                <FormGroup className={'has-float-label mb-4'}>
                                  <Label>نام فروشگاه</Label>
                                  <Field className={'form-control'} name={'name'} maxLength={255}/>
                                </FormGroup>
                                <FormGroup className={'has-float-label mb-4'}>
                                  <Label>شماره شبا</Label>
                                  <Field className={'form-control'} name={'shaba_number'} maxLength={24}/>
                                </FormGroup>
                              </Form>
                            )}
                          </Formik>
                        </Step>
                        <Step id="uploadDocument" name={'مدارک'} label={'ثبت'}>
                          <Formik initialValues={{
                            maliyat: true,
                          }}
                                  onSubmit={(values) => onHandleUploadDocument(values, wizard)}
                                  innerRef={forms.uploadDocument}>
                            {({setFieldValue, values}) => (
                              <Form>
                                <AlertError errors={errors}/>
                                <UploadDocument setFieldValue={setFieldValue} values={values}/>
                              </Form>
                            )}
                          </Formik>
                        </Step>
                      </Steps>
                      <BottomNavigation
                        onClickNext={onClickNext}
                        onClickPrev={({...data}) => console.log(data)}
                        className={'d-flex justify-content-end align-items-center'}
                        prevLabel={pervLabel}
                        btnLoading={btnLoading}
                      />
                    </CardBody>
                  </div>
                </Card>
              )
            }}
          </Wizard>
        </Colxx>
      </Row>
    </Suspense>
  );
};
const mapStateToProps = ({authUser: {step}}) => {
  return {step};
};

export default connect(mapStateToProps, {
  registerUserAction: registerUser,
})(Register);

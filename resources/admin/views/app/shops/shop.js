import React, {createRef, Suspense, useState} from 'react';
import {Alert, Button, Card, CardBody, CardTitle, CustomInput, FormGroup, Label, Row} from 'reactstrap';
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import ActualForm from './form-actual';
import LegalForm from './form-legal';
import {Field, Form, Formik} from "formik";
import {FormikReactSelect, FormikSwitch} from "../../../containers/form-validations/FormikFields";
import AlertError from "../../../components/AlertError";
import {adminPathApi, adminRoot} from "../../../constants/defaultValues";
import {useLocation} from 'react-router-dom';
import MapModal from "../../../components/map/MapModal";
import UserCan from "../../../helpers/UserCan";

const Shop = ({match, edit = false, history}) => {
  const location = useLocation();
  const shopData = location.state ? location.state.shopData : undefined;
  const [serverError, setServerError] = React.useState({});
  const [loading, setLoading] = React.useState(true);
  const [locationModal, setLocationModal] = React.useState(false);
  const id = match.params.id ? match.params.id : (shopData ? shopData.id : null);
  const locationRef = createRef();
  const [initialValues, setInitialValues] = useState({

    // type of store
    type: 1,

    // actual information
    /*first_name: "",
    last_name: "",
    birthday: "",
    gender: "male",
    identity_card_number: "",
    national_identity_number: "",


    // legal infromation
    company_name: "",
    company_type: "",
    register_number: "",
    identity_number: "",
    economic_number: "",


    // general information
    state_id: "1",
    city_id: "1",
    address: "",
    post_code: "",
    lat_and_long: [],
    phone: "",
    mobile: "",

    name: "",
    shaba_number: "",*/
    chain_store: false,
    admin_status: true,
    shop_status: true,
  });


  React.useEffect(async () => {
    if (id && edit && !shopData) {
      await axios.get(`${adminPathApi}/shop/${id}`)
        .then(({data}) => {
          setInitialValues(data)
          setLoading(false);
        })
        .catch((error) => {
          /*history.push(`${adminRoot}/error`, {
            status: error.response ? error.response.status : 500
          });*/
        });
    } else if (shopData) {
      setLoading(false);
      setInitialValues(shopData);
    }
  }, []);

  const ActualOrLegal = (props) => {
    switch (props.values.type) {
      case 1:
        return (<ActualForm {...props}/>);
      case 2:
        return (<LegalForm {...props}/>);
      default:
        return (<></>);
    }
  };

  const submitForm = async (values) => {
    if (id && edit) {
      await axios.put(`${adminPathApi}/shop/${id}`, values)
        .then(() => {
          //NotificationManager.success();
          //history.push(`${adminRoot}/shops/list`);
        })
        .catch(({response}) => {
          setServerError(response.data.errors);
        });
    } else {
      await axios.post(`${adminPathApi}/shop`, values)
        .then(() => {
          history.push(`${adminRoot}/shops/list`);
        })
        .catch(({response}) => {
          setServerError(response.data.errors);
        });
    }
  }
  if (edit && loading) {
    return (<div className={'loading'}/>);
  }

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={"menu.shops"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx xss={12}>
        {location.state && location.state.reason ?
          <Alert color="danger" className={'rounded-sm'}>
            <h5>علت رد فروشگاه</h5>
            <p>{location.state.reason}</p>
          </Alert>
          :
          <></>
        }
        {location.state && location.state.step === 'processing' ?
          <Alert color="warning" className={'rounded-sm'}>
            <h5>فروشگاه شما در حال بررسی می باشد.</h5>
            <h6>لطفا منتظر بمانید.</h6>
          </Alert>
          :
          <></>
        }
        <Card className="mb-4">
          <CardBody>
            <CardTitle>{edit ? `ویرایش فروشگاه ${initialValues.name}` : 'ایجاد فروشگاه جدید'}</CardTitle>
            <AlertError errors={serverError}/>
            <Formik enableReinitialize initialValues={initialValues} onSubmit={submitForm}>
              {(formHelper) => (
                <Form>
                  <CardTitle>اطلاعات کاربر</CardTitle>
                  <Colxx xss={12}>
                    <FormGroup row>
                      <Colxx md={6}>
                        <FormGroup>
                          <Label>نام</Label>
                          <Field name={'user_first_name'} className={'form-control'}/>
                        </FormGroup>
                      </Colxx>
                      <Colxx md={6}>
                        <FormGroup>
                          <Label>نام خانوادگی</Label>
                          <Field name={'user_last_name'} className={'form-control'}/>
                        </FormGroup>
                      </Colxx>
                      <Colxx md={6}>
                        <FormGroup>
                          <Label>ایمیل</Label>
                          <Field name={'email'} className={'form-control'} type={'email'}/>
                        </FormGroup>
                      </Colxx>
                      <Colxx md={6}>
                        <FormGroup>
                          <Label>رمز عبور</Label>
                          <Field
                            name={'password'}
                            className={'form-control'}
                            type={'password'}
                            autoComplete={'new-password'}/>
                        </FormGroup>
                      </Colxx>
                      <UserCan to={['operators.edit']}>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>وضعیت ادمین</Label>
                            <FormikSwitch
                              className={'custom-switch custom-switch-primary'}
                              onChange={formHelper.setFieldValue}
                              onBlur={formHelper.setFieldTouched}
                              name={'admin_status'}
                              value={formHelper.values.admin_status}
                            />
                          </FormGroup>
                        </Colxx>
                      </UserCan>
                    </FormGroup>
                  </Colxx>
                  <Separator className={'mb-4'}/>
                  <Colxx md={6}>
                    <FormGroup className="mt-3">
                      <Label>نوع فروشگاه</Label>
                      <CustomInput key={'actual'} type="radio" id={'actual'} label={"حقیقی"}
                                   onChange={(val) => formHelper.setFieldValue('type', 1)}
                                   onBlur={formHelper.setTouched}
                                   inline
                                   checked={formHelper.values.type === 1}
                      />
                      <CustomInput key={'legal'} type="radio" id={'legal'} label={"حقوقی"}
                                   onChange={(val) => formHelper.setFieldValue('type', 2)}
                                   onBlur={formHelper.setTouched}
                                   inline
                                   checked={formHelper.values.type === 2}
                      />

                    </FormGroup>
                  </Colxx>
                  <Colxx md={12}>
                    <ActualOrLegal {...formHelper}/>
                  </Colxx>
                  <Separator className={'mb-4'}/>
                  <CardTitle tag="h3" className="mt-5">اطلاعات تماس</CardTitle>
                  <FormGroup row>
                    <Colxx md="4">
                      <FormGroup>
                        <Label>استان</Label>
                        <FormikReactSelect
                          className=""
                          name="state_id"
                          options={[{label: "ostan1", value: "1"}, {label: "ostan2", value: "2"}]}
                          value={formHelper.values.state_id}
                          onChange={formHelper.setFieldValue}
                          onBlur={formHelper.setFieldTouched}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md="4">
                      <FormGroup>
                        <Label>شهر</Label>
                        <FormikReactSelect
                          name="city_id"
                          options={[{label: "city1", value: "1"}, {label: "city2", value: "2"}]}
                          value={formHelper.values.city_id}
                          onChange={formHelper.setFieldValue}
                          onBlur={formHelper.setFieldTouched}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md="4">
                      <FormGroup>
                        <Label>کد پستی</Label>
                        <Field
                          className="form-control"
                          name="post_code"
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md="4">
                      <FormGroup>
                        <Label>
                          موقیت مکانی
                        </Label>
                        <Field
                          innerRef={locationRef}
                          className="form-control"
                          value={formHelper.values.lat_and_long && "ثبت شد"}
                          onFocus={() => locationRef.current.blur()}
                          onClick={() => {
                            locationRef.current.blur();
                            setLocationModal(true);
                          }}
                          style={{cursor: 'pointer'}}
                        />
                        <MapModal modal={locationModal} setFieldValue={formHelper.setFieldValue}
                                  setModal={setLocationModal}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md="4">
                      <FormGroup>
                        <Label>تلفن ثابت</Label>
                        <Field
                          style={{direction: "ltr"}}
                          className="form-control"
                          name="phone"
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md="4">
                      <FormGroup>
                        <Label>تلفن همراه</Label>
                        <Field
                          className="form-control"
                          name="mobile"
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md="12">
                      <FormGroup>
                        <Label>آدرس کامل</Label>
                        <Field
                          className="form-control"
                          name="address"
                        />
                      </FormGroup>
                    </Colxx>
                  </FormGroup>
                  <Separator className={'mb-4'}/>
                  <CardTitle tag="h5" className="mt-5">اطلاعات تجاری</CardTitle>
                  <FormGroup row>
                    <Colxx md="6">
                      <FormGroup>
                        <Label>نام فروشگاه تجاری</Label>
                        <Field
                          className="form-control"
                          type="text"
                          name="name"
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md="6">
                      <FormGroup>
                        <Label>شماره شبا (به نام شخص یا شرکت ثبت نام کننده)</Label>
                        <Field
                          className="form-control"
                          type="text"
                          name="shaba_number"
                        />
                      </FormGroup>
                    </Colxx>
                    <UserCan to={['shops.edit']}>
                      <Colxx md={3}>
                        <FormGroup>
                          <Label>وضعیت فروشگاه</Label>
                          <FormikSwitch
                            className={'custom-switch custom-switch-primary'}
                            onChange={formHelper.setFieldValue}
                            onBlur={formHelper.setFieldTouched}
                            name={'shop_status'}
                            value={formHelper.values.shop_status}
                          />
                        </FormGroup>
                      </Colxx>
                    </UserCan>
                    <UserCan to={['shops.edit']}>
                      <Colxx md={3}>
                        <FormGroup>
                          <Label>فروشگاه زنجیره ای</Label>
                          <FormikSwitch
                            className={'custom-switch custom-switch-primary'}
                            onChange={formHelper.setFieldValue}
                            onBlur={formHelper.setFieldTouched}
                            name={'chain_store'}
                            value={formHelper.values.chain_store}
                          />
                        </FormGroup>
                      </Colxx>
                    </UserCan>
                  </FormGroup>
                  <Button type="submit" color="primary" size={'lg'} className={'d-flex float-right'}>ثبت</Button>
                </Form>
              )}
            </Formik>
          </CardBody>
        </Card>
      </Colxx>
    </Suspense>
  )
}

export default Shop;

import React, {Suspense, useState} from 'react';
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import {
  Button,
  ButtonGroup,
  Card,
  CardBody,
  CardTitle,
  FormGroup,
  Input,
  InputGroup,
  InputGroupAddon,
  Label,
  Row,
  Table
} from "reactstrap";
import AlertError from "../../../../components/AlertError";
import {Field, FieldArray, Form, Formik} from "formik";
import DatePicker, {utils} from "react-modern-calendar-datepicker";
import {adminPathApi, adminRoot} from "../../../../constants/defaultValues";
import NotificationManager from "../../../../components/common/react-notifications/NotificationManager";
import axios from "axios";
import {ItemModal, SearchUserModal} from './utils';
import PaymentForm from "../payments/utils";
import * as Yup from "yup";
import {Link} from "react-router-dom";

const BuyFactor = ({match, edit = false, history}) => {
  const [serverError, setServerError] = React.useState({});
  const id = match.params.id ? match.params.id : null;
  const [user, setUser] = React.useState(false);
  const [itemModal, setItemModal] = React.useState(false);
  const [item, setItem] = React.useState(null);
  const [itemToEdit, setItemToEdit] = React.useState(false);
  const [searchUserModal, setSearchUserModal] = React.useState(false);
  const [paymentFormModal, setPaymentFormModal] = React.useState(false);
  const [payment_to_edit, setPaymentToEdit] = React.useState(false);
  const [initialValues, setInitialValues] = useState({
    items: [],
    created_at: utils('fa').getToday(),
    user_id: '',
    description: '',
    discount_under_factor: 0,
    amount_payable: 0,
    charges: '',
    payment_id: ''
  });

  const [payment,setPayment]=useState(null);

  React.useEffect(async () => {
    if (id && edit) {
      await axios.get(`${adminPathApi}/accounting/factor/${id}`)
        .then(({data}) => {
          setInitialValues(data);
          setUser(data.user);
          if (data.payment_id) {
            setPaymentToEdit(data.payment_id)
          }
          if (data.payment && data.payment.items_sum_price){
            setPayment(data.payment.items_sum_price)
          }
        })
        .catch((error) => {
          /*history.push(`${adminRoot}/error`, {
            status: error.response ? error.response.status : 500
          });*/
        });
    }
  }, [edit]);

  const onUserInputKeyDown = async (e) => {
    if (e.target.value.length > 0 && event.keyCode === 9 || event.keyCode === 13) {
      e.preventDefault();
      let form = event.target.form;
      let index = Array.prototype.indexOf.call(form, event.target);
      await getUserInfo(e.target.value)
        .then((res) => {
          form.elements[index + 5].focus();
          setUser(res.data);
        })
        .catch((error) => {
          setUser(false);
          NotificationManager.error('کاربری با این کد یافت نشد.');
        });
    }
  }

  const getUserInfo = async (id = null) =>
    await axios.get(`${adminPathApi}/customer/${id ? id : ''}`);

  const total = (values) => {
    let sum = 0;
    values.items.map((item) => {
        sum += item.total;
      }
    );
    return sum;
  };

  const total_discount = (values) => {
    let sum = 0;
    values.items.map((item) => {
        sum += item.total_discount;
      }
    );
    return sum;
  };

  const total_amount_payable = (values) => {
    let sum = 0;
    sum += total(values);
    sum -= Number(values.discount_under_factor);
    return sum;
  }

  const onSubmit = async (values) => {
    if (values.items.length > 0) {
      if (edit) {
        await axios.put(`${adminPathApi}/accounting/factor/buy/${id}`, values)
          .then(({data}) => {
            if (data.status) {
              NotificationManager.success(data.message);
              //history.push(`${adminRoot}/accounting/factor/buy-list`);
            } else {
              NotificationManager.error(data.message);
            }
          })
          .catch(({response}) => {
            setServerError(response.data.errors);
          });
      } else {
        await axios.post(`${adminPathApi}/accounting/factor/buy`, values)
          .then(({data}) => {
            if (data.status) {
              NotificationManager.success(data.message);
              history.push(`${adminRoot}/accounting/factor/buy/${data.id}`);
            }
          })
          .catch(({response}) => {
            setServerError(response.data.errors);
          });
      }
    } else {
      NotificationManager.error("این فاکتور هیچ آیتمی برای ثبت ندارد");
    }
  };

  const FactorSchema = Yup.object().shape({
    user_id: Yup.string()
      .required('طرف حساب انتخاب نشده است'),
  });

  return (
    <Suspense fallback={<div className="loading"/>}>
      <Row>
        <Colxx xss="12">
          <div className="text-zero top-right-button-container">
            <Link
              to={`${adminRoot}/accounting/factor/print/${id}`}
              className={"top-right-button btn btn-secondary btn-lg"}
              target="_blank"
              rel="noopener noreferrer">
              <i className="simple-icon-printer"/>
              چاپ
            </Link>
          </div>
          <Breadcrumb heading="menu.factor" match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Formik enableReinitialize initialValues={initialValues} onSubmit={onSubmit} validationSchema={FactorSchema}>
        {
          ({values, setFieldValue, errors, touched}) => (
            <Form>
              <Row className="mb-4">
                <Colxx md="12">
                  <Card>
                    <CardBody>
                      <CardTitle>فاکتور خرید</CardTitle>
                      <AlertError errors={serverError}/>
                      <Row>
                        <Colxx md="2">
                          <FormGroup>
                            <Label>سریال برگه</Label>
                            <Field
                              name="id"
                              className="form-control"
                              disabled
                              autoComplete={'off'}
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="2">
                          <FormGroup>
                            <Label>تاریخ</Label>
                            <DatePicker
                              inputClassName={'form-control'}
                              locale={'fa'}
                              onChange={(value) => setFieldValue('created_at', value)}
                              value={values.created_at}
                              shouldHighlightWeekends
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="3">
                          <FormGroup>
                            <Label>طرف حساب</Label>
                            <InputGroup className="mb-3">
                              <Field
                                name="user_id"
                                className="form-control"
                                onKeyDown={onUserInputKeyDown}
                                autoComplete={'off'}
                                autoFocus
                              />
                              <InputGroupAddon addonType="append">
                                <Button color="secondary" onClick={() => setSearchUserModal(true)}><i
                                  className={'fa fa-search'}/></Button>
                                <Button color="secondary"><i className={'fas fa-chart-bar'}/></Button>
                              </InputGroupAddon>
                            </InputGroup>
                            {errors.user_id && touched.user_id && (
                              <div className="invalid-feedback d-block">
                                {errors.user_id}
                              </div>
                            )}
                          </FormGroup>
                        </Colxx>
                        <Colxx md="3">
                          <FormGroup>
                            <Label>نام و نام خانوادگی</Label>
                            <Input disabled readOnly value={user ? user.first_name + ' ' + user.last_name : ''}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md="2">
                          <FormGroup>
                            <Label>شماره تماس</Label>
                            <Input disabled readOnly value={(user && user.mobile) ? user.mobile : ''}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md="12">
                          <FormGroup>
                            <Label>شرح</Label>
                            <Field
                              name="description"
                              className="form-control"
                              autoComplete={'off'}
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md={12}>
                          <FieldArray name={'items'}>
                            {(arrayHelpers) => (
                              <>
                                <ItemModal
                                  isOpen={itemModal}
                                  toggle={() => setItemModal(!itemModal)}
                                  arrayHelper={arrayHelpers}
                                  editItem={item}
                                  itemToEdit={itemToEdit}
                                  action={'buy'}
                                />
                                <ButtonGroup className={'float-right pb-2'}>
                                  <Button onClick={() => {
                                    setItemToEdit(false);
                                    setItemModal(true)
                                  }}>
                                    <i className={'fa fa-plus'}/> افزودن ردیف
                                  </Button>
                                </ButtonGroup>
                                <Table responsive bordered striped>
                                  <thead>
                                  <tr>
                                    <th className={'text-center'}>کد</th>
                                    <th className={'text-center'}>شرح آیتم</th>
                                    <th className={'text-center'}>عنوان آیتم</th>
                                    <th className={'text-center'}>مقدار</th>
                                    <th className={'text-center'}>واحد اصلی</th>
                                    <th className={'text-center'}>فی</th>
                                    <th className={'text-center'}>تخفیف</th>
                                    <th className={'text-center'}>جمع</th>
                                    <th className={'text-center'}>مبلغ</th>
                                    <th className={'text-center'}>مبلغ کل</th>
                                    <th className={'text-center'}>مبلغ کل</th>
                                  </tr>
                                  </thead>
                                  <tbody>
                                  {values.items.length > 0 && values.items.map((item, index) => (
                                    <tr key={index}>
                                      <td>{item.product_id}</td>
                                      <td>{item.description}</td>
                                      <td>{item.name}</td>
                                      <td>{item.quantity * item.unit.quantity}</td>
                                      <td>{item.unit.label}</td>
                                      <td>{item.price}</td>
                                      <td>{item.total_discount}</td>
                                      <td>{item.total_quantity_price}</td>
                                      <td>{item.total}</td>
                                      <td>{item.total}</td>
                                      <td className={'text-center'}>
                                        <ButtonGroup>
                                          <Button color="danger" size="xs"
                                                  onClick={() => arrayHelpers.remove(index)}>
                                            <span className="fa fa-trash"/>
                                          </Button>
                                          {/*<Button color="secondary" size="xs" onClick={() => {
                                            setItem(item);
                                            setItemToEdit(index);
                                            setItemModal(true);
                                          }}>
                                            <span className="fa fa-pencil-alt"/>
                                          </Button>*/}
                                        </ButtonGroup>
                                      </td>
                                    </tr>
                                  ))}
                                  </tbody>
                                </Table>
                              </>
                            )}
                          </FieldArray>
                        </Colxx>
                      </Row>
                    </CardBody>
                  </Card>
                </Colxx>
              </Row>
              <Row>
                <Colxx md={12}>
                  <Card>
                    <CardBody>
                      <Row>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>تخفیف کل</Label>
                            <Input disabled readOnly value={total_discount(values)}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>جمع</Label>
                            <Input readOnly disabled value={total(values)}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>تخفیف زیر فاکتور</Label>
                            <Field
                              name="discount_under_factor"
                              className="form-control"
                              autoComplete={'off'}
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>م.ا.ا. + عوارض</Label>
                            <Input readOnly disabled/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>مجموع اقلام</Label>
                            <Input readOnly disabled value={values.items.length}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>جمع قابل پرداخت</Label>
                            <Input readOnly disabled value={total_amount_payable(values)}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>مجموع مبلغ پرداختی</Label>
                            <Input readOnly disabled value={payment??'---'}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>بدهی فاکتور</Label>
                            <Input readOnly disabled value={total_amount_payable(values)-parseInt(payment)}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>{`حساب قبلی(${values.previous_payed>0?'بدهکار':'بستانکار'})`}</Label>
                            <Input readOnly disabled value={values.previous_payed}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>{`مانده حساب تا این تاریخ(${(total_amount_payable(values)-parseInt(payment)-values.previous_payed)?'بدهکار':'بستانکار'})`}</Label>
                            <Input readOnly disabled value={Math.abs(total_amount_payable(values)-parseInt(payment)-values.previous_payed)}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <Button color={'secondary'} className={'mr-2'} onClick={(e) => {
                            e.preventDefault();
                            if (user) {
                              setPaymentFormModal(true);
                            } else {
                              NotificationManager.error('لطفا کاربر مورد نظر خود را انتخاب کنید.');
                            }
                          }}>پرداخت <i
                            className="fas fa-calculator"/></Button>
                          <Button type={'submit'} color={'primary'}>ذخیره</Button>
                        </Colxx>
                      </Row>
                    </CardBody>
                  </Card>
                </Colxx>
              </Row>
              <SearchUserModal
                isOpen={searchUserModal}
                toggle={() => setSearchUserModal(!searchUserModal)}
                setUserData={(data) => {
                  setFieldValue('user_id', data.id);
                  setUser(data);
                }}
              />
              <PaymentForm
                action={'sell'}
                show_modal={paymentFormModal}
                setShowModal={setPaymentFormModal}
                payment_to_edit={payment_to_edit}
                setPaymentToEdit={setPaymentToEdit}
                isFactor
                userInfo={user}
                amountPayable={total_amount_payable(values)}
                setData={(values, item) => {
                  setPaymentToEdit(item.id);
                  setFieldValue('payment_id', item.id);
                }}
              setPayment={setPayment}/>
            </Form>
          )
        }
      </Formik>
    </Suspense>
  )
}

export default BuyFactor;

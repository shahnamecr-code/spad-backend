import React, {Suspense} from "react";
import axios from "axios";
import {adminPathApi} from "../../../../constants/defaultValues";
import {
  Button,
  FormGroup,
  Input,
  InputGroup, InputGroupAddon,
  Label,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Row,
  Table
} from "reactstrap";
import {Field, FieldArray, Form, Formik} from "formik";
import {FormikReactSelect} from "../../../../containers/form-validations/FormikFields";
import SearchSelect from "../../../../components/SearchSelect";
import DatePicker, {utils} from "react-modern-calendar-datepicker";
import {banks} from "../../../../data/factor";
import {NotificationManager} from "../../../../components/common/react-notifications";
import AlertError from "../../../../components/AlertError";
import {date_convertor} from "../../../../helpers/date";
import {Colxx} from "../../../../components/common/CustomBootstrap";
import {SearchUserModal} from "../factors/utils";
import TableAjax from "../../../../components/TableAjax";

const SearchDocModal = ({isOpen, toggle, setUserData,docType}) => {
  const [data, setData] = React.useState([]);
  const [loading, setLoading] = React.useState(false);
  const [pageCount, setPageCount] = React.useState(0);
  const [selected, setSelected] = React.useState(null);

  const cols = React.useMemo(
    () => [
      {
        Header: 'شماره سریال',
        accessor: 'serial_number',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'مبلغ',
        accessor: 'price',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'صاحب حساب',
        accessor: 'account_owner',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'شماره حساب',
        accessor: 'account_number',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
    ],
    []
  );
  //const tableInstance = useTable({ columns, data })
  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/payments/document?page=${pageIndex + 1}&per_page=${pageSize}&type=${docType}`)
        .then((res) => {
          setData(res.data)
          setPageCount(res.data.last_page)
          return res
        })
        .catch((error) => error)
    }
    , [docType]);

  const onSubmit = () => {
    setUserData(selected);
    toggle();
  };

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Modal isOpen={isOpen} toggle={toggle} size={'lg'}>
        <ModalHeader>
          لیست اسناد
        </ModalHeader>
        <ModalBody>
          <TableAjax
            columns={cols}
            data={data}
            fetchData={fetchData}
            loading={loading}
            pageCount={pageCount}
            striped={false}
            rowsProps={(props, rowInfo) => (
              {
                onClick: e => {
                  setSelected(rowInfo.row.original);
                },
                style: {
                  backgroundColor: selected ? (rowInfo.row.original.id == selected.id ? "#9bdfff" : undefined) : undefined
                }
              }
            )}
          />
          <Button type={'submit'} color={'primary'} onClick={onSubmit}>انتخاب</Button>
        </ModalBody>
      </Modal>
    </Suspense>
  );
}


const PaymentItem = ({action, show, setShow, payment_types, arrayHelpers, item, item_to_edit}) => {
  const defaultInit = {
    type : 1,
    account:'',
    account_id:'',
    price:'',
    description:'',
    serial_number:'',
    pay_date:utils('fa').getToday(),
    account_owner:'',
    account_bank:'',
    account_number:'',
    document_id:'',

  }
  const [init, setInit] = React.useState(item);
  const [searchDocModal,setSearchDocModal]=React.useState(false)
  const [doc, setDoc] = React.useState(false);

  const getShopAccountsList = (setloadOptions, search = '') => {
    axios
      .get(`${adminPathApi}/accounts?page=1&per_page=10&search=${search}`)
      .then(res => res.data)
      .then(data => {
        const items = []
        data.data.map(account => {
          items.push({label: account.account_code, value: account.id})
        })
        setloadOptions(items)
      })
  }
  const getBanksList = (setloadOptions, search = '') => {
    axios
      .get(
        `${adminPathApi}/banks?page=1&per_page=10&search=${search}`,
      )
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        setloadOptions(data.data);
      });
  }
  const save = (values) => {
    if (item_to_edit === false) {
      arrayHelpers.push(values)
    } else {
      arrayHelpers.replace(item_to_edit, values)
    }
    setShow(false)
  }

  const getDocInfo = async (id = null) =>
    await axios.get(`${adminPathApi}/customer/${id ? id : ''}`);

  const onDocInputKeyDown=async (e)=>{
    if (e.target.value.length > 0 && event.keyCode === 9 || event.keyCode === 13) {
      e.preventDefault();
      let form = event.target.form;
      console.log(form)
      let index = Array.prototype.indexOf.call(form, event.target);
      await getDocInfo(e.target.value)
        .then((res) => {
          form.elements[index + 9].focus();
          setDoc(res.data);
        })
        .catch((error) => {
          setDoc(false);
          NotificationManager.error('سندی با این کد یافت نشد.');
        });
    }
  }

  return (
    <Modal isOpen={show} toggle={() => setShow(!show)}>
      <Formik
        initialValues={{...init,pay_date:init.pay_date??utils('fa').getToday()}}
        onSubmit={save}
        enableReinitialize
      >
        {({errors, touched, setFieldValue, setFieldTouched, values,setValues}) => (
          <Form className="av-tooltip tooltip-label-right">
            <ModalBody>
              <FormGroup>
                <label>نوع وجه</label>
                <FormikReactSelect
                  name="type"
                  options={payment_types}
                  value={values.type}
                  onChange={(name,value) => {
                    setValues({...defaultInit,[name]:value})
                  }}
                  onBlur={setFieldTouched}/>
              </FormGroup>
              {(action == 'sell' && (values.type==1 || values.type==2 || values.type==4))?(
                <FormGroup>
                  <Label>انتخاب سند</Label>
                  <InputGroup className="mb-3">
                    <Field
                      name="document_id"
                      className="form-control"
                      onKeyDown={onDocInputKeyDown}
                      autoComplete={'off'}
                      autoFocus
                      value={values.document_id??undefined}
                    />
                    <InputGroupAddon addonType="append">
                      <Button color="secondary" onClick={() => setSearchDocModal(true)}><i
                        className={'fa fa-search'}/></Button>
                      <Button color="secondary"><i className={'fas fa-chart-bar'}/></Button>
                    </InputGroupAddon>
                  </InputGroup>
                </FormGroup>
              ):('')}
              <FormGroup>
                <label>{`${(action == 'buy') ? 'به حساب' : ((action == 'sell') ? 'از حساب' : 'عملیات نامشخض')}`}</label>
                <SearchSelect request={getShopAccountsList} setData={(account) => {
                  setFieldValue('account', account)
                  setFieldValue('account_id', account ? account.value : undefined)
                }} value={values.account} disabled={(action == 'sell' && [1,2,4].includes(values.type))}/>
              </FormGroup>
              <FormGroup>
                <label>مبلغ</label>
                <Field name="price" className="form-control" disabled={(action == 'sell' && [1,2,4].includes(values.type))}/>
              </FormGroup>
              <FormGroup>
                <label>شرح ردیف</label>
                <Field as="textarea" name="description" className="form-control"/>
              </FormGroup>
              <FormGroup>
                <label>شماره سریال</label>
                <Field name="serial_number" className="form-control" disabled={(action == 'sell' && [1,2,4].includes(values.type))}/>
              </FormGroup>
              <FormGroup>
                <label>تاریخ</label>
                <DatePicker
                  inputClassName={'form-control'}
                  locale={'fa'}
                  onChange={(value) => setFieldValue('pay_date', value)}
                  value={values.pay_date?(values.pay_date):(utils('fa').getToday())}
                  shouldHighlightWeekends
                  disabled={(action == 'sell' && [1,2,4].includes(values.type))}
                />
              </FormGroup>
              <FormGroup>
                <label>صاحب حساب</label>
                <Field name="account_owner" className="form-control" disabled={(action == 'sell' && [1,2,4].includes(values.type))}/>
              </FormGroup>
              <FormGroup>
                <label>بانک</label>
                <SearchSelect
                  request={getBanksList}
                  getOptionLabel={option=>option.name}
                  getOptionValue={option=>option.id}
                  setData={(bank) => {
                    setFieldValue('account_bank', bank ? bank : undefined)
                  }}
                  value={values.account_bank ? values.account_bank : undefined}
                  disabled={(action == 'sell' && [1,2,4].includes(values.type))}
                />
              </FormGroup>
              <FormGroup>
                <label>شماره حساب</label>
                <Field name="account_number" className="form-control" disabled={(action == 'sell' && [1,2,4].includes(values.type))}/>
              </FormGroup>
              <FormGroup>
                <label>شرح سند</label>
                <Field as="textarea" name="doc_description" className="form-control"/>
              </FormGroup>
              {[1,2,4].includes(values.type)?(
                <SearchDocModal
                  isOpen={searchDocModal}
                  toggle={() => setSearchDocModal(!searchDocModal)}
                  setUserData={(data) => {
                    setFieldValue('document_id', data.id);
                    setFieldValue('account_owner', data.account_owner);
                    setFieldValue('account_number', data.account_number);
                    setFieldValue('account_bank', data.bank);
                    setFieldValue('serial_number', data.serial_number);
                    setFieldValue('price', data.price);
                    setFieldValue('description', data.description??undefined);
                    setFieldValue('account', data.account??undefined);
                    setFieldValue('account_id', data.account_id??undefined);
                    setFieldValue('pay_date', data.pay_date??utils('fa').getToday());
                    setDoc(data);
                  }}
                  docType={values.type}
                />
              ):('')}
            </ModalBody>
            <ModalFooter>
              <Button onClick={() => setShow(false)}>لغو</Button>
              <Button>ذخیره</Button>
            </ModalFooter>
          </Form>
        )}
      </Formik>
    </Modal>
  );
}

const PaymentForm = ({
                       action,
                       setShowModal,
                       show_modal,
                       payment_to_edit,
                       setPaymentToEdit,
                       setData,
                       isFactor = false,
                       userInfo,
                       amountPayable,
                       ...props
                     }) => {
  const [validationErrors, setvalidationErrors] = React.useState([]);
  const [loading, setLoading] = React.useState(true);
  const [init, setInit] = React.useState({
    user_id: isFactor ? userInfo.id : ''
  });
  const [item_modal, setItemModal] = React.useState(false);
  const payment_types = [
    {label: 'نقد', value: 0},
    {label: 'چک اشخاص', value: 1},
    {label: 'چک شرکتی', value: 2},
    {label: 'کارتخوان', value: 3},
    {label: 'انواع حواله', value: 4},
  ];
  const [item, setItem] = React.useState({});
  const [item_to_edit, setItemToEdit] = React.useState(false);
  const [changed,setChanged]=React.useState(false)

  const resetItem = () => {
    setItem({});
  }

  const getCustomersList = (setloadOptions, search = '') => {
    axios
      .get(
        `${adminPathApi}/customer?page=1&per_page=10&search=${search}`,
      )
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        const attr = [];
        data.data.map((user) => {
          attr.push({
            label: user.first_name + ' ' + user.last_name,
            value: user.id,
            key: user.id,
          })
        })
        //setShowAttributeList(false)
        setloadOptions(attr);
      });
  }

  React.useEffect(() => {
    if (payment_to_edit) {
      axios
        .get(`${adminPathApi}/payments/${payment_to_edit}`)
        .then(res => res.data)
        .then(data => {
          setInit(data)
          setLoading(false)
        })
        .catch(error => {
          NotificationManager.error('خطایی در برقراری ارتباط رخ داده است', null, 1000, null, null, 'filled');
          setLoading(false)
        })
    } else {
      setLoading(false);
      setInit({});
    }
  }, [payment_to_edit,changed]);

  const save = (values) => {
    let sent = {...values, user_id: isFactor ? userInfo.id : values.user_id, action: action};
    let req_source;
    if (payment_to_edit) {
      req_source = `${adminPathApi}/payments/${payment_to_edit}/edit`
    } else {
      req_source = `${adminPathApi}/payments/create`
    }

    axios
      .post(req_source, sent)
      .then(res => res.data)
      .then(data => {
        if (data.status) {
          if (isFactor) {
            setData(values, data.item);
            setChanged(prev=>!prev)
          } else {
            if (payment_to_edit) {
              setData(prev => {
                let info = [...prev.data]
                let index = info.findIndex(item => item.id == data.item.id)
                info[index] = data.item
                return {...prev,data:info};
              })
            } else {
              setData(prev => {
                return {...prev,data:[...prev.data,data.item]};
              })
            }
          }
          if (props.setPayment){
            props.setPayment(values.items.reduce((prev, cur)=> {
              return prev + parseFloat(cur.price);
            }, 0))
          }
          setShowModal(false)
        }
      });

  }
  if (loading) {
    return (<div className="loading"/>)
  }

  return (
    <Modal isOpen={show_modal} toggle={() => setShowModal(!show_modal)} size="xl" backdrop>
      <Formik
        initialValues={init}
        onSubmit={save}
        enableReinitialize
      >
        {({errors, touched, setFieldValue, setFieldTouched, values}) => (
          <Form className="av-tooltip tooltip-label-right">
            <ModalHeader>
              {payment_to_edit ? 'ویرایش برگه' : 'افزودن برگه'}
            </ModalHeader>
            <ModalBody>
              <AlertError errors={validationErrors}/>
              <Row>
                <Colxx md={6}>
                  <FormGroup>
                    <label>{`${(action == 'buy') ? 'دریافت از' : ((action == 'sell') ? 'پرداخت به' : 'عملیات نامشخض')}`}</label>
                    {isFactor ?
                      (<Input disabled readOnly value={userInfo.first_name + ' ' + userInfo.last_name}/>)
                      :
                      (<SearchSelect request={getCustomersList} setData={(user) => {
                        setFieldValue('user_id', user ? user.value : undefined)
                      }} value={values.user ? {
                        label: `${values.user.first_name} ${values.user.last_name}`,
                        value: values.user.id
                      } : undefined}/>)}
                  </FormGroup>
                </Colxx>
                {isFactor ? (
                  <Colxx md={6}>
                    <FormGroup>
                      <Label>مبلغ قابل پرداخت</Label>
                      <Input disabled readOnly value={amountPayable}/>
                    </FormGroup>
                  </Colxx>
                ) : ''}
                <Colxx md={12}>
                  <FormGroup>
                    <label>توضیحات</label>
                    <Field as="textarea" className="form-control" name="description"/>
                  </FormGroup>
                </Colxx>
                <Colxx md={12}>
                  <FieldArray
                    name="items"
                    render={arrayHelpers => (
                      <>
                        <Button onClick={() => {
                          resetItem()
                          setItemToEdit(false)
                          setItemModal(true)
                        }}>افزودن آیتم</Button>
                        {item_modal ? (
                          <PaymentItem action={action} show={item_modal} setShow={setItemModal}
                                       payment_types={payment_types} item={item} item_to_edit={item_to_edit}
                                       arrayHelpers={arrayHelpers}/>
                        ) : ('')}
                        <Table color="info" className="my-2" bordered striped>
                          <thead>
                          <tr>
                            <th>نوع وجه</th>
                            <th>مبلغ</th>
                            <th>{`${(action == 'buy') ? 'به حساب' : ((action == 'sell') ? 'از حساب' : 'عملیات نامشخض')}`}</th>
                            <th>شرح ردیف</th>
                            <th>شماره سریال</th>
                            <th>تاریخ</th>
                            <th>صاحب حساب</th>
                            <th>بانک</th>
                            <th>شماره حساب</th>
                            <th>شرح سند</th>
                            <th>عملیات</th>
                          </tr>
                          </thead>
                          <tbody>
                          {values.items && values.items.length > 0 ? (
                              <>
                                {values.items.map((item, index) => (
                                  <tr key={index}>
                                    <td>{payment_types.find(type => type.value == item.type).label}</td>
                                    <td>{item.price}</td>
                                    <td>{item.account ? item.account.label : '---'}</td>
                                    <td>{item.description}</td>
                                    <td>{item.serial_number}</td>
                                    <td>{item.pay_date ? date_convertor(item.pay_date) : '---'}</td>
                                    <td>{item.account_owner}</td>
                                    <td>{item.account_bank?item.account_bank.name:''}</td>
                                    <td>{item.account_number}</td>
                                    <td>{item.doc_description}</td>
                                    <td>
                                      <Button color="danger" outline size="xs" onClick={() => arrayHelpers.remove(index)}>
                                        <span className="fa fa-trash"/>
                                      </Button>
                                      <Button color="secondary" outline size="xs" onClick={() => {
                                        setItem(item);
                                        setItemToEdit(index)
                                        setItemModal(true);
                                      }}>
                                        <span className="fa fa-pencil-alt"/>
                                      </Button>
                                    </td>
                                  </tr>
                                ))}
                              </>
                            )
                            :
                            (
                              <tr>
                                <td colSpan="11">موردی انتخاب نشده است</td>
                              </tr>
                            )
                          }
                          </tbody>
                          {values.items && values.items.length > 0 ?(
                            <tfoot>
                              <tr>
                                <td className="text-center" colSpan={5}>جمع کل</td>
                                <td className="text-center" colSpan={6}>{values.items.reduce((prev, cur)=> {
                                  return prev + parseFloat(cur.price);
                                }, 0)}</td>
                              </tr>
                            </tfoot>
                          ):('')}
                        </Table>
                      </>
                    )}/>
                </Colxx>
              </Row>
            </ModalBody>
            <ModalFooter>
              <Button color="primary" onClick={() => setShowModal(false)}>لغو</Button>
              <Button color="info" type="submit">ذخیره</Button>
            </ModalFooter>
          </Form>
        )}
      </Formik>
    </Modal>
  );
}

export {PaymentItem};
export default PaymentForm;

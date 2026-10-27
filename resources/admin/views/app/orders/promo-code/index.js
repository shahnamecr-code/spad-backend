import React, {Suspense} from 'react';
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import {
  Button,
  Card,
  CardBody,
  CardTitle,
  CustomInput,
  DropdownItem,
  DropdownMenu,
  DropdownToggle,
  FormGroup,
  Input,
  Label,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Row,
  UncontrolledButtonDropdown
} from "reactstrap";
import TableAjax from "../../../../components/TableAjax";
import axios from "axios";
import {adminPathApi} from "../../../../constants/defaultValues";
import {Field, Form, Formik} from "formik";
import {FormikSwitch} from "../../../../containers/form-validations/FormikFields";
//import DatePicker, {utils} from "react-modern-calendar-datepicker";
import DatePicker from "react-multi-date-picker";
import persian from "react-date-object/calendars/persian";
import persian_fa from "react-date-object/locales/persian_fa";
import weekends from "react-multi-date-picker/plugins/highlight_weekends";
import TimePicker from "react-multi-date-picker/plugins/time_picker";
import Toolbar from "react-multi-date-picker/plugins/toolbar";
import SearchMultiSelect from "../../../../components/SearchMultiSelect";
import AlertError from "../../../../components/AlertError";
import NotificationManager from "../../../../components/common/react-notifications/NotificationManager";
import DateObject from "react-date-object";

const PromoCodes = ({match}) => {
    const [data, setData] = React.useState([]);
    const [loading, setLoading] = React.useState(false);
    const [pageCount, setPageCount] = React.useState(0);
    const [serverError, setServerError] = React.useState(null);
    const [modalShow, setModalShow] = React.useState(false);
    const [modalLoading, setModalLoading] = React.useState(false);
    const defaultInitValues = {
      auto: false,
      code: '',
      start_date: '',
      end_date: '',
      type: 2,
      amount: 0,
      min_price: '',
      max_price: '',
      usage_count: '',
      cart_item_qty: '',
      category: [],
      category_except: [],
      product: [],
      product_except: [],
      customer: [],
      customer_except: [],
      customer_group: [],
      customer_group_except: [],
    }
    const [initialValues, setInitialValues] = React.useState(defaultInitValues);
    const [editId, setEditId] = React.useState(null);

    const Actions = ({id}) => {
      return (
        <UncontrolledButtonDropdown>
          <DropdownToggle color="secondary" outline>
            <i className={'simple-icon-options-vertical'}/>
          </DropdownToggle>
          <DropdownMenu>
            <DropdownItem onClick={() => onEditClick(id)}>ویرایش</DropdownItem>
          </DropdownMenu>
        </UncontrolledButtonDropdown>
      );
    };

    const cols = React.useMemo(
      () => [
        {
          Header: 'کد تخفیف',
          accessor: 'code',
          cellClass: 'list-item-heading',
          Cell: (props) => <>{props.value}</>,
        },
        {
          Header: 'مقدار',
          accessor: 'amount',
          cellClass: 'text-muted',
          Cell: (props) => <>{props.value}</>,
        },
        {
          Header: 'نوع',
          accessor: 'type',
          cellClass: 'text-muted',
          Cell: (props) => <>{props.value === 2 ? 'درصد' : 'مبلغ'}</>,
        },
        {
          Header: 'تعداد قابل استفاده',
          accessor: 'usage_count',
          cellClass: 'text-muted',
          Cell: (props) => <>{props.value ?? 0}</>,
        },
        {
          Header: 'گزینه ها',
          accessor: 'id',
          cellClass: 'text-muted text-center',
          Cell: (props) => (<Actions id={props.value}/>),
        },
      ],
      []
    );
    //const tableInstance = useTable({ columns, data })
    const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
        await axios.get(`${adminPathApi}/promo-code?page=${pageIndex + 1}&per_page=${pageSize}`)
          .then((res) => {
            setData(res.data)
            setPageCount(res.data.last_page)
            return res
          })
          .catch((error) => error)
      }
      , []);

    const getCategory = async (setOption, search) => {
      await axios
        .post(
          `${adminPathApi}/category/search_categories`,
          {
            except: [],
            page: 1,
            per_page: 10,
            search: search,
          }
        )
        .then((res) => {
          return res.data;
        })
        .then((data) => {
          setOption(data);
        });
    };

    const getProduct = async (setOption, search = '') => {
      await axios.get(`${adminPathApi}/product?page=1&per_page=10&search=${search}`)
        .then((res) => {
          setOption(res.data.data);
        })
        .catch((error) => error)
    };

    const getCustomer = async (setOption, search = '') => {
      await axios.get(
        `${adminPathApi}/customer?page=1&per_page=10&search=${search}`,
      )
        .then((res) => {
          return res.data;
        })
        .then((data) => {
          setOption(data.data);
        });
    }

    const getCustomerGroup = async (setOption, search = '') => {
      await axios.get(
        `${adminPathApi}/customer-group?page=1&per_page=10&search=${search}`,
      )
        .then((res) => {
          return res.data;
        })
        .then((data) => {
          setOption(data.data);
        });
    }

    const onEditClick = async (id) => {
      setModalLoading(true);
      await axios.get(`${adminPathApi}/promo-code/${id}`)
        .then((res) => {
          setEditId(id);
          setInitialValues({
            ...res.data,
            start_date: res.data.start_date ? new DateObject({
              ...res.data.start_date,
              locale: persian_fa,
              calendar: persian
            }) : '',
            end_date: res.data.end_date ? new DateObject({
              ...res.data.end_date,
              locale: persian_fa,
              calendar: persian
            }) : '',
          });
          setModalLoading(false);
          setModalShow(true);
        })
        .catch((error) => console.log(error));
    };

    const onSubmit = async (values) => {
      let data = {
        ...values,
        start_date: values.start_date.unix,
        end_date: values.end_date.unix
      };

      if (editId !== null) {
        await axios.put(`${adminPathApi}/promo-code/${editId}`, data)
          .then((res) => {
            if (res.data.status) {
              NotificationManager.success(res.data.message);
              setModalShow(false);
              fetchData({pageIndex: 0, pageSize: 10});
            } else {
              NotificationManager.error(res.data.message);
            }
          })
          .catch(error => {
            setServerError(error.response.data.errors);
          });
      } else {
        await axios.post(`${adminPathApi}/promo-code`, data)
          .then((res) => {
            if (res.data.status) {
              NotificationManager.success(res.data.message);
              setModalShow(false);
              fetchData({pageIndex: 0, pageSize: 10});
            } else {
              NotificationManager.error(res.data.message);
            }
          })
          .catch(error => {
            setServerError(error.response.data.errors);
          });
      }
      setEditId(null);
    }

    return (
      <Suspense fallback={<div className="loading"/>}>
        <Row>
          <Colxx xss="12">
            <div className="text-zero top-right-button-container">
              <Button
                color="primary"
                size="lg"
                className="top-right-button"
                onClick={() => {
                  setEditId(null);
                  setInitialValues(defaultInitValues);
                  setModalShow(true);
                }}
              >
                افزودن
              </Button>
            </div>
            <Breadcrumb heading={"menu.promo-codes"} match={match}/>
            <Separator className="mb-5"/>
          </Colxx>
        </Row>
        <Colxx xss={12}>
          <Card>
            <CardBody>
              <CardTitle>کد تخفیف</CardTitle>
              <TableAjax columns={cols} data={data} fetchData={fetchData} loading={loading} pageCount={pageCount}/>
            </CardBody>
          </Card>
        </Colxx>
        <Modal
          backdrop={'static'}
          isOpen={modalShow}
          toggle={() => {
            setModalShow((prev) => !prev)
          }}
          size={'lg'}
        >
          <ModalHeader>افزودن کد تخفیف</ModalHeader>
          <Formik enableReinitialize initialValues={initialValues} onSubmit={onSubmit}>
            {({values, setFieldValue, setFieldTouched}) => (
              <Form>
                <ModalBody>
                  <AlertError errors={serverError}/>
                  <Row>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>کد اتوماتیک</Label>
                        <FormikSwitch className={'custom-switch custom-switch-primary'} name={'auto'}
                                      onChange={setFieldValue} onBlur={setFieldTouched} value={values.auto}/>
                      </FormGroup>
                    </Colxx>
                    {values.auto !== true ? (
                      <Colxx md={6}>
                        <FormGroup>
                          <Label>کد تخفیف</Label>
                          <Field className={'form-control'} name={'code'}/>
                        </FormGroup>
                      </Colxx>
                    ) : ''}
                  </Row>
                  <Separator className={"my-4"}/>
                  <Row>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>تاریخ شروع</Label>
                        <DatePicker
                          value={values.start_date}
                          format="YYYY/MM/DD HH:mm"
                          calendarPosition="bottom-right"
                          locale={persian_fa}
                          calendar={persian}
                          onChange={(values) => {
                            setFieldValue('start_date', values || '');
                          }}
                          plugins={[weekends(),
                            <TimePicker position={'bottom'} hideSeconds={true}/>, <Toolbar position="bottom"/>]}
                          render={(value, openCalendar, handleValueChange) => {
                            return (
                              <Input onFocus={openCalendar} value={value} onChange={handleValueChange}/>
                            )
                          }}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>تاریخ پایان</Label>
                        <DatePicker
                          value={values.end_date}
                          format="YYYY/MM/DD HH:mm"
                          calendarPosition="bottom-right"
                          locale={persian_fa}
                          calendar={persian}
                          onChange={(values) => {
                            setFieldValue('end_date', values || '');
                          }}
                          plugins={[weekends(),
                            <TimePicker position={'bottom'} hideSeconds={true}/>, <Toolbar position="bottom"/>]}
                          render={(value, openCalendar, handleValueChange) => {
                            return (
                              <Input onFocus={openCalendar} value={value} onChange={handleValueChange}/>
                            )
                          }}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup className={'mt-4'}>
                        <Label>نوع تخفیف</Label>
                        <CustomInput
                          type="radio"
                          id={'price'}
                          label={"مبلغ"}
                          inline
                          onChange={() => setFieldValue('type', 1)}
                          checked={values.type === 1}
                        />
                        <CustomInput
                          type="radio"
                          id={'percent'}
                          label={"درصد"}
                          inline
                          onChange={() => setFieldValue('type', 2)}
                          checked={values.type === 2}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>مبلغ</Label>
                        <Field name={'amount'} className={'form-control'}/>
                      </FormGroup>
                    </Colxx>
                  </Row>
                  <Separator className={'my-4'}/>
                  <Row>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>حداقل هزینه</Label>
                        <Field className={'form-control'} name={'min_price'}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>حداکثر هزینه</Label>
                        <Field className={'form-control'} name={'max_price'}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>تعداد دفعات مجاز</Label>
                        <Field className={'form-control'} name={'usage_count'}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>تعداد آیتم های سبد خرید</Label>
                        <Field className={'form-control'} name={'cart_item_qty'}/>
                      </FormGroup>
                    </Colxx>
                  </Row>
                  <Separator className={'my-4'}/>
                  <Row>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>شامل دسته بندی های</Label>
                        <SearchMultiSelect
                          request={getCategory}
                          name={'category'}
                          value={values.category}
                          onChange={setFieldValue}
                          getOptionLabel={(option) => option.name}
                          getOptionValue={(option) => option.id}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>به جز دسته بندی های</Label>
                        <SearchMultiSelect
                          request={getCategory}
                          name={'category_except'}
                          value={values.category_except}
                          onChange={setFieldValue}
                          getOptionLabel={(option) => option.name}
                          getOptionValue={(option) => option.id}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>شامل محصولات</Label>
                        <SearchMultiSelect
                          request={getProduct}
                          name={'product'}
                          value={values.product}
                          onChange={setFieldValue}
                          getOptionLabel={(option) => option.name ? option.name : option.template.title}
                          getOptionValue={(option) => option.id}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>به جز محصولات</Label>
                        <SearchMultiSelect
                          request={getProduct}
                          name={'product_except'}
                          value={values.product_except}
                          onChange={setFieldValue}
                          getOptionLabel={(option) => option.name ? option.name : option.template.title}
                          getOptionValue={(option) => option.id}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>شامل مشتریان</Label>
                        <SearchMultiSelect
                          request={getCustomer}
                          name={'customer'}
                          value={values.customer}
                          onChange={setFieldValue}
                          getOptionLabel={(option) => option.first_name + ' ' + option.last_name}
                          getOptionValue={(option) => option.id}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>به جز مشتریان</Label>
                        <SearchMultiSelect
                          request={getCustomer}
                          name={'customer_except'}
                          value={values.customer_except}
                          onChange={setFieldValue}
                          getOptionLabel={(option) => option.first_name + ' ' + option.last_name}
                          getOptionValue={(option) => option.id}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>شامل گروه مشتریان</Label>
                        <SearchMultiSelect
                          request={getCustomerGroup}
                          name={'customer_group'}
                          value={values.customer_group}
                          onChange={setFieldValue}
                          getOptionLabel={(option) => option.name}
                          getOptionValue={(option) => option.id}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>به جز گروه مشتریان</Label>
                        <SearchMultiSelect
                          request={getCustomerGroup}
                          name={'customer_group_except'}
                          value={values.customer_group_except}
                          onChange={setFieldValue}
                          getOptionLabel={(option) => option.name}
                          getOptionValue={(option) => option.id}
                        />
                      </FormGroup>
                    </Colxx>
                  </Row>
                </ModalBody>
                <ModalFooter>
                  <Button
                    color="primary"
                    outline
                    onClick={() => setModalShow(false)}
                  >
                    بستن
                  </Button>
                  <Button
                    type={"submit"}
                    color="primary"
                  >
                    ثبت
                  </Button>
                </ModalFooter>
              </Form>
            )}
          </Formik>
        </Modal>
      </Suspense>
    );
  }
;

export default PromoCodes;

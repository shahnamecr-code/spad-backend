import React, {Suspense} from "react";
import NotificationManager from "../../../../components/common/react-notifications/NotificationManager";
import axios from "axios";
import {adminPathApi} from "../../../../constants/defaultValues";
import {
  Button,
  Card,
  CardBody,
  FormGroup,
  Input,
  InputGroup,
  InputGroupAddon,
  Label,
  Modal,
  ModalBody,
  ModalHeader,
  Row
} from "reactstrap";
import {Field, Form, Formik} from "formik";
import {Colxx} from "../../../../components/common/CustomBootstrap";
import {FormikReactSelect} from "../../../../containers/form-validations/FormikFields";
import TableAjax from "../../../../components/TableAjax";
import * as Yup from "yup";

const ItemModal = ({isOpen, toggle, arrayHelper, editItem, itemToEdit = false,action}) => {
  const [searchInventoryModal, setSearchInventoryModal] = React.useState(false);
  const [searchProductModal, setSearchProductModal] = React.useState(false);
  //const [inventory, setInventory] = React.useState(false);
  const [productUnit, setProductUnit] = React.useState([{value: '', label: 'تکی', quantity: 1}]);
  const defaultValues = {
    name: '',
    product_id: '',
    product_sale_info_id: '',
    description: '',
    quantity: 1,
    price: 0,
    cost: 0,
    product_unit: '',
    percent_discount: 0,
    discount: 0,
    total_quantity_price: 0,
    total: 0,
  };
  const [initialValues, setInitialValues] = React.useState(defaultValues);

  React.useEffect(() => {
    if (itemToEdit !== false) {
      setInitialValues(editItem);
    } else {
      setInitialValues(defaultValues);
    }
  }, [itemToEdit, editItem]);

  const onItemInputKeyDown = async (e, setValues) => {
    if (e.target.value.length > 0 && event.keyCode === 9 || event.keyCode === 13) {
      e.preventDefault();
      let form = event.target.form;
      let index = Array.prototype.indexOf.call(form, event.target);
      await getProductInfo(e.target.value)
        .then((res) => {
          if (res.data.units.length > 0) {
            setProductUnit([...res.data.units, {value: '', label: 'تکی', quantity: 1}]);
          }
          setValues((prevInit) => ({...prevInit, name: res.data.name}));
          form.elements[index + 3].focus();
        })
        .catch((error) => {

          NotificationManager.error('محصولی با این کد یافت نشد.');
        });
    }
  };

  const getProductInfo = async (id = null) =>
    await axios.get(`${adminPathApi}/accounting/productInfo/${id ? id : ''}`);

  const total_quantity_price = (values) => {
    const {price, quantity, product_unit} = values;
    let sum = 0;
    if (price && quantity) {
      sum = Number(price) * Number(quantity);
    }
    if (product_unit) {
      let unit = productUnit.find(option => option.value === product_unit);
      sum = Number(sum) * Number(unit.quantity);
    }
    return sum;
  };

  const total_discount = (total_quantity_price1, percent_discount, discount) => {
    return (Number(total_quantity_price1) * Number(percent_discount) / 100) + Number(discount)
  };

  const total_price = (values) => {
    const {percent_discount, discount, cost} = values;
    const total_quantity_price1 = total_quantity_price(values);
    let sum = Number(total_quantity_price1) - total_discount(total_quantity_price1, percent_discount, discount);
    sum = Number(sum) + Number(cost);
    return sum;
  };

  /*const onInventoryInputKeyDown = async (e) => {
    if (e.target.value.length > 0 && event.keyCode === 9 || event.keyCode === 13) {
      e.preventDefault();
      let form = event.target.form;
      let index = Array.prototype.indexOf.call(form, event.target);
      await getInventory(e.target.value)
        .then((res) => {
          form.elements[index + 3].focus();
          setInventory(res.data);
        })
        .catch((error) => {
          setInventory(false);
          NotificationManager.error('انباری با این کد یافت نشد.');
        });
    }
  }*/

  /*const getInventory = async (id = null) =>
    await axios.get(`${adminPathApi}/inventory/${id ? id : ''}`);*/

  const onSubmit = (values) => {
    const {percent_discount, discount} = values;
    const total_quantity_price1 = total_quantity_price(values);
    let unit = productUnit.find(option => option.value === values.product_unit);
    let data = {
      ...values,
      total_discount: total_discount(total_quantity_price1, percent_discount, discount),
      total: total_price(values),
      total_quantity_price: total_quantity_price1,
      unit
    }
    if (itemToEdit === false) {
      arrayHelper.push(data);
    } else {
      arrayHelper.replace(itemToEdit, data);
    }
    toggle();
  };

  let validState={
    product_sale_info_id: Yup.string()
      .required('انبار انتخاب نشده است'),
    product_id: Yup.string()
      .required('محصولی انتخاب نشده است'),
  }
  
  if(action=='sell'){
    validState.buy_price=Yup.number().required('قیمت خرید مشخض نشده است')
    validState.price=Yup.number().when(['buy_price'],(buy_price)=>{
      return Yup.number().min(buy_price,'قیمت فروش کمتر از قیمت خرید انتخاب شده است')
    })
  }
  const FactorItemSchema = Yup.object().shape(validState);

  return (
    <Modal isOpen={isOpen} toggle={toggle} size={'lg'}>
      <ModalHeader>
        اطلاعات ردیف
      </ModalHeader>
      <ModalBody>
        <Formik initialValues={initialValues} onSubmit={onSubmit} enableReinitialize validationSchema={FactorItemSchema}>
          {({values, setFieldValue, setFieldTouched, setValues, handleChange,errors,touched}) => (
            <Form>
              <Row>
                <Colxx md={12} className={'mb-2'}>
                  <Card>
                    <CardBody>
                      <Row>
                        <Colxx md="6">
                          <FormGroup>
                            <Label>کد آیتم</Label>
                            <InputGroup className="mb-3">
                              <Field
                                autoFocus
                                name="product_id"
                                className="form-control"
                                onKeyDown={(e) => onItemInputKeyDown(e, setValues)}
                                autoComplete={'off'}
                              />
                              <InputGroupAddon addonType="append">
                                <Button color="secondary" onClick={() => setSearchProductModal(true)}><i
                                  className={'fa fa-search'}/></Button>
                              </InputGroupAddon>
                            </InputGroup>
                            {errors.product_id && touched.product_id && (
                              <div className="invalid-feedback d-block">
                                {errors.product_id}
                              </div>
                            )}
                          </FormGroup>
                        </Colxx>
                        <Colxx md="6">
                          <FormGroup>
                            <Label>نام آیتم</Label>
                            <Input disabled readOnly value={values.name} autoComplete={'off'}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md="12">
                          <FormGroup>
                            <Label>شرح ردیف</Label>
                            <Field
                              name="description"
                              className="form-control"
                              autoComplete={'off'}
                            />
                          </FormGroup>
                        </Colxx>
                      </Row>
                    </CardBody>
                  </Card>
                </Colxx>
                <Colxx md={12} className={'mb-2'}>
                  <Card>
                    <CardBody>
                      <Row>
                        <Colxx md={12}>
                          <FormGroup>
                            <Label>انبار</Label>
                            <InputGroup className="mb-3">
                              <Field
                                name="product_sale_info_id"
                                className="form-control"
                                disabled
                                readOnly
                                //onKeyDown={onInventoryInputKeyDown}
                                //autoComplete={'off'}
                              />
                              <InputGroupAddon addonType="append">
                                <Button color="secondary" onClick={() => {
                                  if (values.product_id) {
                                    setSearchInventoryModal(true);
                                  } else {
                                    NotificationManager.error('لطفا محصول را انتخاب کنید.');
                                  }
                                }}><i
                                  className={'fa fa-search'}/></Button>
                              </InputGroupAddon>
                            </InputGroup>
                            {errors.product_sale_info_id && touched.product_sale_info_id && (
                              <div className="invalid-feedback d-block">
                                {errors.product_sale_info_id}
                              </div>
                            )}
                          </FormGroup>
                        </Colxx>
                      </Row>
                    </CardBody>
                  </Card>
                </Colxx>
                <Colxx md={12} className={'mb-2'}>
                  <Card>
                    <CardBody>
                      <Row>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>تعداد</Label>
                            <Field
                              name={'quantity'}
                              className={'form-control'}
                              autoComplete={'off'}
                            />
                          </FormGroup>
                        </Colxx>
                        {productUnit.length > 1 && (
                          <Colxx md={6}>
                            <FormGroup>
                              <Label>واحد</Label>
                              <FormikReactSelect
                                options={productUnit}
                                name={'product_unit'}
                                value={values.product_unit}
                                onChange={setFieldValue}
                                onBlur={setFieldTouched}/>
                            </FormGroup>
                          </Colxx>
                        )}
                      </Row>
                    </CardBody>
                  </Card>
                </Colxx>
                <Colxx md={12} className={'mb-2'}>
                  <Card>
                    <CardBody>
                      <Row>
                        {action=='sell'?(
                          <Colxx md={6}>
                            <FormGroup>
                              <Label>قیمت خرید</Label>
                              <Field name={'buy_price'} className={'form-control'} autoComplete={'off'}/>
                              {errors.buy_price && touched.buy_price && (
                                <div className="invalid-feedback d-block">
                                  {errors.buy_price}
                                </div>
                              )}
                            </FormGroup>
                          </Colxx>
                        ):''}
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>قیمت</Label>
                            <Field name={'price'} className={'form-control'} autoComplete={'off'}/>
                            {errors.price && touched.price && (
                              <div className="invalid-feedback d-block">
                                {errors.price}
                              </div>
                            )}
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>قیمت کل تعداد</Label>
                            <Field
                              name={'total_quantity_price'}
                              className={'form-control'}
                              disabled
                              readOnly
                              value={total_quantity_price(values)}
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md={6}>
                          <FormGroup>
                            <Label>هزینه ثابت</Label>
                            <Field name={'cost'} className={'form-control'} autoComplete={'off'}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={2}>
                          <FormGroup>
                            <Label>درصد تخفیف</Label>
                            <Field name={'percent_discount'} className={'form-control'} autoComplete={'off'}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={4}>
                          <FormGroup>
                            <Label>مقدار تخفیف</Label>
                            <Field name={'discount'} className={'form-control'} autoComplete={'off'}/>
                          </FormGroup>
                        </Colxx>
                        <Colxx md={12}>
                          <FormGroup>
                            <Label>جمع کل</Label>
                            <Field
                              name={'total'}
                              className={'form-control'}
                              disabled
                              readOnly
                              value={total_price(values)}
                            />
                          </FormGroup>
                        </Colxx>
                      </Row>
                    </CardBody>
                  </Card>
                </Colxx>
                <Colxx md={12}>
                  <Button type={'submit'} color={'primary'} className={'float-left'}>ثبت</Button>
                </Colxx>
              </Row>
              <SearchProductModal
                isOpen={searchProductModal}
                toggle={() => setSearchProductModal(!searchProductModal)}
                setProductData={(data) => {
                  setFieldValue('product_id', data.id);
                  setFieldValue('name', data.name ? data.name : data.template.title);
                  getProductInfo(data.id).then((res) => {
                    if (res.data.units.length > 0) {
                      setProductUnit([...res.data.units, {value: '', label: 'تکی', quantity: 1}]);
                    }
                    let default_unit=res.data.units.filter(item=>item.default)
                    setFieldValue('product_unit',default_unit?default_unit[0].value:'');
                    setFieldValue('product_sale_info_id',res.data.inventory?res.data.inventory.id:'');
                    if(action=='sell'){
                      setFieldValue('buy_price', res.data.inventory?res.data.inventory.buy_price:'');
                    }
                    if(action=='buy'){
                      setFieldValue('price', res.data.inventory?res.data.inventory.buy_price:0);
                    }else{
                      setFieldValue('price', res.data.inventory?res.data.inventory.store_price:0);
                    }
                  })
                    .catch((error) => {
                      NotificationManager.error('محصولی با این کد یافت نشد.');
                    });
                }}
              />
              <SearchInventoryModal
                isOpen={searchInventoryModal}
                toggle={() => setSearchInventoryModal(!searchInventoryModal)}
                setInventoryData={(data) => {
                  if(action=='sell'){
                    setFieldValue('buy_price', data.buy_price);
                  }
                  setFieldValue('product_sale_info_id', data.id);
                  if(action=='buy'){
                    setFieldValue('price', data.buy_price ?? 0);
                  }else{
                    setFieldValue('price', data.store_price ?? 0);
                  }
                }}
                product_id={values.product_id}
              />
            </Form>
          )}
        </Formik>
      </ModalBody>
    </Modal>
  )
}

const SearchUserModal = ({isOpen, toggle, setUserData}) => {
  const [data, setData] = React.useState([]);
  const [loading, setLoading] = React.useState(false);
  const [pageCount, setPageCount] = React.useState(0);
  const [selected, setSelected] = React.useState(null);
  const [pageIndex, setPageIndex] = React.useState(1);
  const [pageSize, setPageSize] = React.useState(10);
  const [query,setQuery]=React.useState('')

  const cols = React.useMemo(
    () => [
      {
        Header: 'کد',
        accessor: 'id',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'نام و نام خانوادگی',
        cellClass: 'list-item-heading',
        Cell: ({row}) => row.original.first_name + ' ' + row.original.last_name,
      },
      {
        Header: 'نام کاربری',
        accessor: 'username',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'ایمیل',
        accessor: 'email',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'شماره موبایل',
        accessor: 'mobile',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
    ],
    []
  );
  //const tableInstance = useTable({ columns, data })
  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/customer?page=${pageIndex + 1}&per_page=${pageSize}&search=${query}`)
        .then((res) => {
          setData(res.data)
          setPageCount(res.data.last_page)
          return res
        })
        .catch((error) => error)
    }
    , [query]);

  const onSubmit = () => {
    setUserData(selected);
    toggle();
  };

  const searchQuery = (e) => {
    setQuery(e.target.value)
  }

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Modal isOpen={isOpen} toggle={toggle} size={'lg'}>
        <ModalHeader>
          مشتریان
        </ModalHeader>
        <ModalBody>
          <Input value={query} className="form-control mb-2" onChange={searchQuery} placeholder="جستجو کنید..."/>
          <TableAjax
            columns={cols}
            data={data}
            fetchData={fetchData}
            loading={loading}
            pageCount={pageCount}
            striped={false}
            setPageSizeFor={setPageSize}
            setPageIndexFor={setPageIndex}
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

const SearchInventoryModal = ({isOpen, toggle, setInventoryData, product_id}) => {
  const [data, setData] = React.useState([]);
  const [loading, setLoading] = React.useState(false);
  const [pageCount, setPageCount] = React.useState(0);
  const [selected, setSelected] = React.useState(null);

  const cols = React.useMemo(
    () => [
      {
        Header: 'کد',
        accessor: 'id',
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'قیمت خرید',
        accessor: 'buy_price',
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'قیمت فروش',
        accessor: 'price',
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'موجودی',
        accessor: 'quantity',
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
    ],
    []
  );
  //const tableInstance = useTable({ columns, data })
  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/accounting/productSaleInfo/${product_id}?page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData(res.data)
          setPageCount(res.data.last_page)
          return res
        })
        .catch((error) => error)
    }
    , [product_id]);

  const onSubmit = () => {
    setInventoryData(selected);
    toggle();
  };

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Modal isOpen={isOpen} toggle={toggle} size={'lg'}>
        <ModalHeader>
          انبار ها
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

const SearchProductModal = ({isOpen, toggle, setProductData}) => {
  const [data, setData] = React.useState([]);
  const [loading, setLoading] = React.useState(false);
  const [pageCount, setPageCount] = React.useState(0);
  const [selected, setSelected] = React.useState(null);
  const [pageIndex, setPageIndex] = React.useState(1);
  const [pageSize, setPageSize] = React.useState(10);
  const [query,setQuery]=React.useState('')

  const cols = React.useMemo(
    () => [
      {
        Header: 'کد',
        accessor: 'id',
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
      {
        id: 'name',
        Header: 'نام محصول',
        accessor: (row) => row.name ? row.name : row.template.title,
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'دسته بندی',
        accessor: 'category',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'کل موجودی',
        accessor: 'quantity',
        Cell: (props) => <>{props.value}</>,
      },
    ],
    []
  );
//const tableInstance = useTable({ columns, data })
  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/product?page=${pageIndex + 1}&per_page=${pageSize}&search=${query}`)
        .then((res) => {
          setData(res.data)
          setPageCount(res.data.last_page)
          return res
        })
        .catch((error) => error)
    }
    , [query]);

  const onSubmit = () => {
    setProductData(selected);
    toggle();
  };

  const searchQuery = (e) => {
    setQuery(e.target.value)
  }

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Modal isOpen={isOpen} toggle={toggle} size={'lg'}>
        <ModalHeader>
          محصولات
        </ModalHeader>
        <ModalBody>
          <Input value={query} className="form-control mb-2" onChange={searchQuery} placeholder="جستجو کنید..."/>
          <TableAjax
            columns={cols}
            data={data}
            fetchData={fetchData}
            loading={loading}
            pageCount={pageCount}
            striped={false}
            setPageSizeFor={setPageSize}
            setPageIndexFor={setPageIndex}
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

export {ItemModal, SearchUserModal, SearchInventoryModal, SearchProductModal};

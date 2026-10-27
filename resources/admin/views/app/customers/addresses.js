import React, {Suspense} from "react";
import {
  Button,
  Card,
  CardBody,
  CardTitle,
  DropdownItem,
  DropdownMenu,
  DropdownToggle,
  FormGroup,
  Label,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Row,
  UncontrolledButtonDropdown,
} from "reactstrap";
import {Field, Form, Formik} from "formik";
import {useHistory} from "react-router-dom";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import TableAjax from "../../../components/TableAjax";
import {adminPathApi} from "../../../constants/defaultValues";
import {FormikCustomCheckbox} from "../../../containers/form-validations/FormikFields";
import {NotificationManager} from "../../../components/common/react-notifications";
import MapModal from "../../../components/map/MapModal";

const Addresses = ({match}) => {
  let history = useHistory();
  const id = match.params.id ? match.params.id : null;
  const [data, setData] = React.useState([]);
  const [loading, setLoading] = React.useState(false);
  const [pageCount, setPageCount] = React.useState(0);
  const [addressModal, setAddressModal] = React.useState(false);
  const [locationModal, setLocationModal] = React.useState(false);
  const [selectId, setSelectedId] = React.useState(null);
  const [user, setUser] = React.useState({});
  const [initialValues, setInitialValues] = React.useState({
    first_name: '',
    last_name: '',
    address: '',
    phone: '',
    mobile: '',
    province: '',
    city: '',
    pelak: '',
    vahed: '',
    post_code: '',
    self_info: false,
  });

  const Actions = ({id, rowData, index}) => {
    return (
      <UncontrolledButtonDropdown tag={'a'}>
        <DropdownToggle color="secondary" outline>
          <i className={'simple-icon-options-vertical'}/>
        </DropdownToggle>
        <DropdownMenu>
          <DropdownItem onClick={() => {
            setInitialValues({
              id: id,
              first_name: rowData.first_name,
              last_name: rowData.last_name,
              address: rowData.address,
              phone: rowData.phone,
              mobile: rowData.mobile,
              province: rowData.province,
              city: rowData.city,
              pelak: rowData.pelak,
              vahed: rowData.vahed,
              post_code: rowData.post_code,
              self_info: rowData.self_info,
            });
            setSelectedId(id);
            setAddressModal(true);
          }}>
            ویرایش
          </DropdownItem>
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const cols = React.useMemo(
    () => [
      {
        Header: 'استان',
        accessor: 'province',
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'شهر',
        accessor: 'city',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'آدرس',
        accessor: 'address',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'شماره موبایل',
        accessor: 'mobile',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'تلفن',
        accessor: 'phone',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'گزینه ها',
        cellClass: 'text-muted text-center',
        Cell: ({row}) => (<Actions id={row.original.id} rowData={row.original} index={row.index}/>),
      },
    ],
    []
  );

  const handleModalAddressSubmit = async (values) => {
    if (selectId) {
      await axios.put(`${adminPathApi}/address/${selectId}`, values)
        .then((res) => {
          if (res.data.status) {
            let items = [...data];
            items[values.id] = values;
            setAddressModal(false);
            setSelectedId(null);
            setData(items);
            NotificationManager.success(res.data.message);
          }
        })
        .catch(error => console.log(error));
    } else {
      await axios.post(`${adminPathApi}/address/${id}`, values)
        .then((res) => {
          if (res.data.status) {
            setData((data) => ([...data, values]));
            setAddressModal(false);
            setSelectedId(null);
            NotificationManager.success(res.data.message);
          }
        })
        .catch(error => console.log(error));
    }
  };

  const onChangeSellInfo = (name, value, setValues) => {
    if (value) {
      setValues((values) => (
        {
          ...values,
          first_name: user.first_name,
          last_name: user.last_name,
          phone: user.phone_number,
          mobile: user.mobile,
          self_info: value
        }
      ));
    } else {
      setValues((values) => (
        {
          ...values,
          first_name: '',
          last_name: '',
          phone: '',
          mobile: '',
          self_info: value
        }
      ));
    }
  }

  //const tableInstance = useTable({columns, data})
  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/address/${id}?page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData(res.data.addresses.data);
          setPageCount(res.data.addresses.last_page);
          setUser(res.data.user);
          return res
        })
        .catch((error) => error)
    }
    , []);


  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <div className="text-zero top-right-button-container">
            <Button
              color="primary"
              size="lg"
              className="top-right-button"
              onClick={() => setAddressModal(true)}
            >
              افزودن
            </Button>
          </div>
          <Breadcrumb heading={"menu.addresses.list"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx>
        <Card className="mb-4">
          <CardBody>
            <CardTitle>لیست آدرس های {`${user.first_name} ${user.last_name}`}</CardTitle>
            <TableAjax columns={cols} data={data} fetchData={fetchData} loading={loading} pageCount={pageCount}/>
          </CardBody>
        </Card>
      </Colxx>
      <Modal
        isOpen={addressModal}
        size={'lg'}
      >
        <ModalHeader>
          جزئیات آدرس
        </ModalHeader>
        <Formik initialValues={initialValues} onSubmit={handleModalAddressSubmit} enableReinitialize>
          {
            ({errors, touched, setFieldValue, setFieldTouched, values, setValues}) => (
              <Form>
                <ModalBody>
                  <Row>
                    <Colxx md="6">
                      <FormGroup>
                        <Label>استان</Label>
                        <Field name={'province'} className={'form-control'}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md="6">
                      <FormGroup>
                        <Label>شهر</Label>
                        <Field name={'city'} className={'form-control'}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md={"3"}>
                      <FormGroup>
                        <Label>پلاک</Label>
                        <Field name={'pelak'} className={'form-control'}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md={"3"}>
                      <FormGroup>
                        <Label>واحد</Label>
                        <Field name={'vahed'} className={'form-control'}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md={"6"}>
                      <FormGroup>
                        <Label>کد پستی</Label>
                        <Field name={'post_code'} className={'form-control'}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md={"12"}>
                      <FormGroup>
                        <Label>نشانی پستی</Label>
                        <Field name={'address'} className={'form-control'}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md="12">
                      <Label>
                        موقیت مکانی
                      </Label>
                      <FormGroup>
                        <Field
                          className="form-control"
                          value={values.lat_and_long && "ثبت شد"}
                          onFocus={(e) => e.target.blur()}
                          onClick={(e) => {
                            e.target.blur();
                            setLocationModal(true);
                          }}
                          style={{cursor: 'pointer'}}
                        />
                        <MapModal setFieldValue={setFieldValue} modal={locationModal} setModal={setLocationModal}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md={12} className={"my-3"}>
                      <Separator/>
                    </Colxx>
                    <Colxx md="12">
                      <FormGroup>
                        <FormikCustomCheckbox
                          name="self_info"
                          value={values.self_info}
                          label="استفاده از نام و نام خانوادگی کاربر"
                          onChange={(name, value) => onChangeSellInfo(name, value, setValues)}
                          onBlur={setFieldTouched}
                        />
                      </FormGroup>
                    </Colxx>
                    <Colxx md="6">
                      <FormGroup>
                        <Label>نام گیرنده</Label>
                        <Field name={'first_name'} className={'form-control'} disabled={values.self_info}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md="6">
                      <FormGroup>
                        <Label>نام خانوادگی گیرنده</Label>
                        <Field name={'last_name'} className={'form-control'} disabled={values.self_info}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md="6">
                      <FormGroup>
                        <Label>موبایل گیرنده</Label>
                        <Field name={'mobile'} className={'form-control'} disabled={values.self_info}/>
                      </FormGroup>
                    </Colxx>
                    <Colxx md="6">
                      <FormGroup>
                        <Label>تلفن گیرنده</Label>
                        <Field name={'phone'} className={'form-control'} disabled={values.self_info}/>
                      </FormGroup>
                    </Colxx>
                  </Row>
                </ModalBody>
                <ModalFooter>
                  <Button
                    type={"submit"}
                    color="danger"
                  >
                    ثبت
                  </Button>
                  <Button
                    color="secondary"
                    onClick={() => {
                      setAddressModal(false);
                    }}
                  >
                    بستن
                  </Button>
                </ModalFooter>
              </Form>
            )}
        </Formik>
      </Modal>
    </Suspense>
  )
};


export default Addresses;

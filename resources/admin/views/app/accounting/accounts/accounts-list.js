import React, {Suspense} from "react";
import {
  Button,
  Card,
  CardBody,
  DropdownItem,
  DropdownMenu,
  DropdownToggle,
  FormGroup,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Row,
  UncontrolledButtonDropdown
} from "reactstrap";
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import {useHistory} from "react-router-dom";
import TableAjax from "../../../../components/TableAjax";
import axios from "axios";
import {adminPathApi} from "../../../../constants/defaultValues";
import {banks} from "../../../../data/factor";
import AlertError from "../../../../components/AlertError";
import {Field, Form, Formik} from "formik";
import {NotificationManager} from "../../../../components/common/react-notifications";
import SearchSelect from "../../../../components/SearchSelect";

const AccountForm=({setShowModal,show_modal,account_to_edit,setAccountToEdit,setData,...props})=>{
  const [validationErrors,setvalidationErrors]=React.useState([]);
  const [loading,setLoading]=React.useState(show_modal);
  const [init,setInit]=React.useState({});

  if (loading){
    return (<div className="loading"/>)
  }

  React.useEffect(()=>{
    if (account_to_edit){
      axios
        .get(`${adminPathApi}/accounts/${account_to_edit}`)
        .then(res => res.data)
        .then(data => {
            setInit(data)
        })
        .catch(error => {
          NotificationManager.error('خطایی در برقراری ارتباط رخ داده است', null, 1000, null, null, 'filled');
        })
    }else {
      setLoading(false);
      setInit({});
    }
  },[account_to_edit]);

  const save=(values)=>{
    let req_source;
    if(account_to_edit){
      req_source=`${adminPathApi}/accounts/${account_to_edit}/edit`
    }else{
      req_source=`${adminPathApi}/accounts/create`
    }
    axios
      .post(req_source,values)
      .then(res=>res.data)
      .then(data=>{
        if(data.status){
          if (account_to_edit){
            setData(prev => {
              let info = [...prev.data]
              let index = info.findIndex(item => item.id == data.item.id)
              info[index] = data.item
              return {...prev, data: info};
            });
          } else {
            setData(prev => ({...prev, data: [data.item, ...prev.data]}));
          }
          setShowModal(false)
        }
      })
  };
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
  };

  return (
    <Modal isOpen={show_modal} toggle={() => setShowModal(!show_modal)} backdrop>
      <Formik
        initialValues={init}
        onSubmit={save}
        enableReinitialize
      >
        {({errors, touched, setFieldValue, setFieldTouched, values}) => (
          <Form className="av-tooltip tooltip-label-right">
            <ModalHeader>
              حساب بانکی
            </ModalHeader>
            <ModalBody>
              <AlertError errors={validationErrors}/>
              <FormGroup>
                <label>کد حساب</label>
                <Field className="form-control" name="account_code"/>
              </FormGroup>
              <FormGroup>
                <label>نام حساب</label>
                <Field className="form-control" name="account_name"/>
              </FormGroup>
              <FormGroup>
                <label>شماره حساب</label>
                <Field className="form-control" name="account_number"/>
              </FormGroup>
              <FormGroup>
                <label>انتخاب بانک</label>
                <SearchSelect
                  request={getBanksList}
                  getOptionLabel={option => option.name}
                  getOptionValue={option => option.id}
                  name={'account_bank'}
                  onChange={setFieldValue}
                  value={values.account_bank ? values.account_bank : undefined}
                />
              </FormGroup>
            </ModalBody>
            <ModalFooter>
              <Button color="primary" onClick={()=>setShowModal(false)}>لغو</Button>
              <Button color="info" type="submit">ذخیره</Button>
            </ModalFooter>
          </Form>
        )}
      </Formik>
    </Modal>
  );
}

const AccountsList=({match,...props})=>{
  let history = useHistory()
  const [data, setData] = React.useState([])
  const [loading, setLoading] = React.useState(false)
  const [pageCount, setPageCount] = React.useState(0)
  const [show_modal,setShowModal]=React.useState(false)
  const [account_to_edit,setAccountToEdit]=React.useState(false)

  const Actions = ({id}) => {
    return (
      <UncontrolledButtonDropdown tag={'a'}>
        <DropdownToggle color="secondary" outline>
          <i className={'simple-icon-options-vertical'} />
        </DropdownToggle>
        <DropdownMenu>
          <DropdownItem onClick={() => {setShowModal(true);setAccountToEdit(id)}}>
            ویرایش
          </DropdownItem>
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const cols = React.useMemo(
    () => [
      {
        Header: 'کد حساب',
        accessor: 'account_code',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'نام حساب',
        accessor: 'account_name',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'شماره حساب',
        accessor: 'account_number',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'نام بانک',
        accessor: 'account_bank',
        cellClass: 'text-muted',
        Cell: (props) => <>{banks.find(bank=>bank.value== props.value).label}</>,
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

  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/accounts?page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData(res.data)
          setPageCount(res.data.last_page)
          return res
        })
        .catch((error) => error)
    }
    ,[]);


  return (
    <Suspense fallback={<div className={'loading'}/> }>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={"menu.accounting.accounts.list"} match={match}/>
          <Button className={"mx-1"} onClick={()=> {
            setAccountToEdit(false)
            setShowModal(true)
          }}>
            افزودن حساب
          </Button>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx>
        <Card className="mb-4">
          <CardBody>
            <TableAjax columns={cols} data={data} fetchData={fetchData} loading={loading} pageCount={pageCount}/>
          </CardBody>
        </Card>
      </Colxx>
      <AccountForm setShowModal={setShowModal} show_modal={show_modal} account_to_edit={account_to_edit} setAccountToEdit={setAccountToEdit} setData={setData}/>
    </Suspense>
  );
}

export default AccountsList;

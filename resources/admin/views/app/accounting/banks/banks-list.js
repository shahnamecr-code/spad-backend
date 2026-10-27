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
import AlertError from "../../../../components/AlertError";
import {Field, Form, Formik} from "formik";
import {NotificationManager} from "../../../../components/common/react-notifications";

const BankForm = ({setShowModal, show_modal, bank_to_edit, setBankToEdit, setData, ...props}) => {
  const [validationErrors, setValidationErrors] = React.useState([]);
  const [loading, setLoading] = React.useState(show_modal);
  const [init, setInit] = React.useState({});

  if (loading) {
    return (<div className="loading"/>)
  }

  React.useEffect(()=>{
    if (bank_to_edit){
      axios
        .get(`${adminPathApi}/banks/${bank_to_edit}`)
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
  },[bank_to_edit]);


  const save=(values)=>{
    let req_source;
    if(bank_to_edit){
      req_source=`${adminPathApi}/banks/${bank_to_edit}/edit`
    }else{
      req_source=`${adminPathApi}/banks/create`
    }
    axios
      .post(req_source,values)
      .then(res=>res.data)
      .then(data=>{
        setValidationErrors({})
        if(data.status){
          if (bank_to_edit){
            setData(prev=>{
              let info=[...prev.data]
              let index=info.findIndex(item=>item.id==data.item.id)
              info[index]=data.item
              return {...prev,data:info};
            })
          }else{
            setData(prev=>{
              let info=[...prev.data,data.item]
              return {...prev,data:info};
            })
          }
          setBankToEdit(false)
          setShowModal(false)
        }
      }).catch(error=>{
      if(error.response.status == 422){
          setValidationErrors(error.response.data.errors)
        }else {
          NotificationManager.error('خطایی در ارسال اطلاعات رخ داده است',null,1000,null,null,'filled');
        }
    })
  }

  return (
    <Modal isOpen={show_modal} toggle={()=>setShowModal(!show_modal)} backdrop>
      <Formik
        initialValues={init}
        onSubmit={save}
        enableReinitialize
      >
        {({errors, touched,setFieldValue, setFieldTouched, values}) => (
          <Form className="av-tooltip tooltip-label-right">
            <ModalHeader>
              حساب بانکی
            </ModalHeader>
            <ModalBody>
              <AlertError errors={validationErrors}/>
              <FormGroup>
                <label>نام بانک</label>
                <Field className="form-control" name="name"/>
              </FormGroup>
              <FormGroup>
                <label>توضیحات</label>
                <Field as="textarea" className="form-control" name="description"/>
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

const BanksList=({match,...props})=>{
  let history = useHistory()
  const [data, setData] = React.useState([])
  const [loading, setLoading] = React.useState(false)
  const [pageCount, setPageCount] = React.useState(0)
  const [show_modal,setShowModal]=React.useState(false)
  const [bank_to_edit,setBankToEdit]=React.useState(false)

  const Actions = ({id}) => {
    return (
      <UncontrolledButtonDropdown tag={'a'}>
        <DropdownToggle color="secondary" outline>
          <i className={'simple-icon-options-vertical'} />
        </DropdownToggle>
        <DropdownMenu>
          <DropdownItem onClick={() => {setShowModal(true);setBankToEdit(id)}}>
            ویرایش
          </DropdownItem>
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const cols = React.useMemo(
    () => [
      {
        Header: 'نام',
        accessor: 'name',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'توضیحات',
        accessor: 'description',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value}</>,
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
      await axios.get(`${adminPathApi}/banks?page=${pageIndex + 1}&per_page=${pageSize}`)
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
          <Breadcrumb heading={"menu.banks.list"} match={match}/>
          <Button className={"mx-1"} onClick={()=> {
            setBankToEdit(false)
            setShowModal(true)
          }}>
            افزودن بانک
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
      <BankForm setShowModal={setShowModal} show_modal={show_modal} bank_to_edit={bank_to_edit} setBankToEdit={setBankToEdit} setData={setData}/>
    </Suspense>
  );
}

export default BanksList;

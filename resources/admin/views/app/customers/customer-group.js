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
import {Link, useHistory} from "react-router-dom";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import TableAjax from "../../../components/TableAjax";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";

const CustomerGroup = ({match}) => {
  let history = useHistory();
  const [data, setData] = React.useState({});
  const [loading, setLoading] = React.useState(false);
  const [pageCount, setPageCount] = React.useState(0);
  const [modalShow, setModalShow] = React.useState(false);
  const [selectId, setSelectedId] = React.useState(null);
  const [initialValues, setInitialValues] = React.useState({
    id: '',
    name: '',
  });

  const Actions = ({id, rowData, index}) => {
    return (
      <UncontrolledButtonDropdown>
        <DropdownToggle color="secondary" outline>
          <i className={'simple-icon-options-vertical'}/>
        </DropdownToggle>
        <DropdownMenu>
          <DropdownItem onClick={() => {
            setInitialValues({
              id: index,
              name: rowData.name,
            });
            setSelectedId(id);
            setModalShow(true);
          }}>
            ویرایش
          </DropdownItem>
          <Link to={{
            pathname: "list",
            search: "?customer-group=" + id,
          }} className={"dropdown-item"}>نمایش لیست مشتریان</Link>
          {/*<DropdownItem onClick={() => onDeleteButtonClick(id,index)}>
            حذف
          </DropdownItem>*/}
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const cols = React.useMemo(
    () => [
      {
        Header: 'نام',
        accessor: 'name',
        cellClass: 'list-item-heading',
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

  const onDeleteButtonClick = async (id, index) => {
    console.log(data);
    /*await axios.delete(`${adminPathApi}/customer-group/${id}`)
      .then((res) => {
        console.log(data);
        if (res.data.status) {
          let items = [...data];
          items.splice(index,1);
          console.log(items,index);
          //setData(items);
          setSelectedId(null);
          NotificationManager.success(res.data.message);
        }
      })
      .catch(error => console.log(error));*/
  };

  const handleModalSubmit = async (values) => {
    if (selectId) {
      await axios.put(`${adminPathApi}/customer-group/${selectId}`, values)
        .then((res) => {
          if (res.data.status) {
            //let items = [...data.data];
            //items[values.id] = values;
            setModalShow(false);
            setSelectedId(null);
            //setData({data:items});
            fetchData({pageIndex: 0, pageSize: 10});
            NotificationManager.success(res.data.message);
          }
        })
        .catch(error => console.log(error));
    } else {
      await axios.post(`${adminPathApi}/customer-group`, values)
        .then((res) => {
          if (res.data.status) {
            NotificationManager.success(res.data.message);
            //setData((data) => ({data:[...data.data,values]}));
            setModalShow(false);
            setSelectedId(null);
            fetchData({pageIndex: 0, pageSize: 10});
          }

        })
        .catch(error => console.log(error));
    }
  };

  //const tableInstance = useTable({columns, data})
  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/customer-group?page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData(res.data);
          setPageCount(res.data.last_page);
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
              onClick={() => {
                setInitialValues({
                  id: '',
                  name: '',
                });
                setModalShow(true)
              }}
            >
              افزودن
            </Button>
          </div>
          <Breadcrumb heading={"menu.customer-group"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx>
        <Card className="mb-4">
          <CardBody>
            <CardTitle>لیست گروه های مشتری</CardTitle>
            <TableAjax columns={cols} data={data} fetchData={fetchData} loading={loading} pageCount={pageCount}/>
          </CardBody>
        </Card>
      </Colxx>
      <Modal
        isOpen={modalShow}
      >
        <ModalHeader>
          گروه مشتری
        </ModalHeader>
        <Formik initialValues={initialValues} onSubmit={handleModalSubmit} enableReinitialize>
          {
            ({errors, touched, setFieldValue, setFieldTouched, values, setValues}) => (
              <Form>
                <ModalBody>
                  <Row>
                    <Colxx md="12">
                      <FormGroup>
                        <Label>نام گروه</Label>
                        <Field name={'name'} className={'form-control'}/>
                      </FormGroup>
                    </Colxx>
                  </Row>
                </ModalBody>
                <ModalFooter>
                  <Button
                    type={"submit"}
                    color="primary"
                  >
                    ثبت
                  </Button>
                  <Button
                    color="secondary"
                    onClick={() => {
                      setModalShow(false);
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


export default CustomerGroup;

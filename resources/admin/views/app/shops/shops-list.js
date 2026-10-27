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
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import TableAjax from "../../../components/TableAjax";
import axios from "axios";
import {adminPathApi} from "../../../constants/defaultValues";
import {Link, useHistory} from "react-router-dom";
import {Field, Form, Formik} from "formik";

const ShopsList = ({match}) => {
  let history = useHistory();
  const [data, setData] = React.useState([]);
  const [loading, setLoading] = React.useState(false);
  const [pageCount, setPageCount] = React.useState(0);
  const [notApproveModal, setNotApproveModal] = React.useState(false);
  const [selectId, setSelectedId] = React.useState(null);


  const Actions = ({id}) => {
    return (
      <UncontrolledButtonDropdown>
        <DropdownToggle color="secondary" outline>
          <i className={'simple-icon-options-vertical'}/>
        </DropdownToggle>
        <DropdownMenu>
          <Link to={`edit/${id}`} className={"dropdown-item"}>ویرایش</Link>
          <DropdownItem onClick={() => {
          }}>
            تایید فروشگاه
          </DropdownItem>
          <DropdownItem onClick={() => {
            setSelectedId(id);
            setNotApproveModal(true);
          }}>
            رد فروشگاه
          </DropdownItem>
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const cols = React.useMemo(
    () => [
      {
        Header: 'نام فروشگاه',
        accessor: 'name',
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'نام صاحب فروشگاه',
        cellClass: 'text-muted',
        Cell: ({row}) => row.original.user_first_name + ' ' + row.original.user_last_name,
      },
      {
        Header: 'نوع فروشگاه',
        accessor: 'type',
        cellClass: 'text-muted',
        Cell: (props) => <>{props.value === 1 ? 'حقوقی' : 'حقیقی'}</>,
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

  const handleModalNotApproveSubmit = async (values) => {
    await axios.post(`${adminPathApi}/shop/changeStatus/${selectId}`,values)
      .then(({data}) => {
        console.log(data);
      })
      .catch(error => console.log(error));
  };
  //const tableInstance = useTable({ columns, data })
  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/shop?page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData({data: res.data.data, ...res.data.meta});
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
          <Breadcrumb heading={"menu.shops.list"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx>
        <Card className="mb-4">
          <CardBody>
            <CardTitle>فروشگاه ها</CardTitle>
            <TableAjax columns={cols} data={data} fetchData={fetchData} loading={loading} pageCount={pageCount}/>
          </CardBody>
        </Card>
      </Colxx>
      <Modal
        isOpen={notApproveModal}
      >
        <ModalHeader>
          رد فروشگاه
        </ModalHeader>
        <Formik initialValues={{type:'deny_shop'}} onSubmit={handleModalNotApproveSubmit}>
          <Form>
            <ModalBody>
              <FormGroup>
                <Label>علت رد فروشگاه</Label>
                <Field as={'textarea'} name={'reason'} className={'form-control'}/>
              </FormGroup>
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
                  setNotApproveModal(false);
                }}
              >
                بستن
              </Button>
            </ModalFooter>
          </Form>
        </Formik>
      </Modal>
    </Suspense>
  )
};


export default ShopsList;

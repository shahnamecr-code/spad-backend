import React, {Suspense} from "react";
import {
  Button,
  Card,
  CardBody,
  DropdownItem,
  DropdownMenu,
  DropdownToggle,
  FormGroup,
  Label,
  Modal,
  ModalBody,
  ModalHeader,
  Row,
  UncontrolledButtonDropdown
} from "reactstrap";
import {Field, Form, Formik} from "formik";
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import TableAjax from "../../../../components/TableAjax";
import axios from "axios";
import {adminPathApi} from "../../../../constants/defaultValues";
import SearchMultiSelect from "../../../../components/SearchMultiSelect";

const GroupAttributeForm = (props) => {
  const save = (values) => {
    let src_extra = (props.init && props.init.id) ? `/${props.init.id}/edit` : '';
    axios
      .post(
        `${adminPathApi}/group-attribute${src_extra}`,
        values
      )
      .then((res) => {
        return res.data;
      })
      .then((res) => {
        if (res.status) {
          if (props.setData) {
            if (props.init && props.init.id) {
              props.setData((prevdata) =>
                prevdata.map((value, index) => {
                  if (value.id == props.init.id) {
                    let d = res.data;
                    return {...value, ...d};
                  }
                  return value;
                })
              )
            } else {
              props.setData((prevdata) => [res.data, ...prevdata])
            }
            if (props.setShowModal) {
              props.setShowModal(false);
            }
            // props.setData((prevdata) => [res.data,...prevdata])
          }

        }
      });

  }

  return (
    <Formik
      onSubmit={save}
      initialValues={{
        title: props.init ? props.init.title : '',
      }}
    >
      {({errors, touched, setFieldValue, setFieldTouched, values}) => (
        <Form className="av-tooltip tooltip-label-right">
          <FormGroup>
            <Label>عنوان ویژگی</Label>
            <Field
              className="form-control"
              name="title"
            />
            {errors.title && touched.title && (
              <div className="invalid-feedback d-block">
                {errors.title}
              </div>
            )}
          </FormGroup>
          <Button color='primary' type="submit">
            ذخیره
          </Button>
        </Form>
      )}
    </Formik>
  );
}

const GroupAttributeSelect = (props) => {
  const [init, setInit] = React.useState({});
  const [loading, setLoading] = React.useState(true);

  const save = (values) => {
    axios
      .post(
        `${adminPathApi}/group-attribute/${props.init.id}/sync_attribute`,
        values
      )
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        if (props.setShowModal) {
          props.setShowModal(false);
        }
      });
  }

  const getProperties = (setloadOptions, search = '') => {
    axios
      .post(
        `${adminPathApi}/attribute/search_attributes`,
        {
          page: 1,
          per_page: 10,
          search: search,
        }
      )
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        const attr = [];
        data.map((attribute) => {
          attr.push({
            ...attribute,
            options1: attribute.options,
            options: undefined,
            label: attribute.name,
            value: attribute.code,
            key: attribute.code,
          })
        })
        //setShowAttributeList(false)
        setloadOptions(attr);
      });
  }

  React.useEffect(async () => {
    await axios
      .get(
        `${adminPathApi}/group-attribute/${props.init.id}/properties`,
      )
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        let attr = [];
        data.map((attribute) => {
          attr.push({
            ...attribute,
            options1: attribute.options,
            options: undefined,
            label: attribute.name,
            value: attribute.code,
            key: attribute.code,
          })
        })
        setInit({...init, attributes: attr});
        setLoading(false);
      });
  }, []);

  if (loading) {
    return <div className={'loading'}/>;
  }

  return (
    <Formik
      onSubmit={save}
      initialValues={init}
      enableReinitialize
    >
      {({errors, touched, setFieldValue, setFieldTouched, values}) => (
        <Form className="av-tooltip tooltip-label-right">
          <FormGroup>
            <Label>انتخاب ویژگی ها</Label>
            <SearchMultiSelect value={values.attributes} name="attributes" onChange={setFieldValue}
                               request={getProperties}/>
          </FormGroup>
          <Button color='primary' type="submit">
            ذخیره
          </Button>
        </Form>
      )}
    </Formik>
  );
}

const GroupAttributesList = ({match}) => {
  const [show_modal, setShowModal] = React.useState(false);
  const [attributes_modal, setAttributesModal] = React.useState(false);
  const [edit, setEdit] = React.useState(null);
  const [attributes_select, setAttributesSelect] = React.useState(null);

  const [data, setData] = React.useState([]);
  const [loading, setLoading] = React.useState(false);
  const [pageCount, setPageCount] = React.useState(0);

  const Actions = ({id, row}) => {
    return (
      <UncontrolledButtonDropdown tag={'a'}>
        <DropdownToggle color="secondary" outline>
          <i className={'simple-icon-options-vertical'}/>
        </DropdownToggle>
        <DropdownMenu>
          <DropdownItem onClick={() => {
            setEdit(row.original);
            setShowModal(true);
          }}>
            ویرایش
          </DropdownItem>
          <DropdownItem onClick={() => {
            setAttributesSelect(row.original);
            setAttributesModal(true);
          }}>
            مدیریت ویژگی ها
          </DropdownItem>
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const cols = React.useMemo(
    () => [
      {
        Header: 'عنوان',
        accessor: 'title',
        cellClass: 'list-item-heading',
        Cell: (props) => <>{props.value}</>,
      },
      {
        Header: 'گزینه ها',
        accessor: 'id',
        cellClass: 'text-muted text-center',
        Cell: (props) => (<Actions id={props.value} row={props.row}/>),
      },
    ],
    []
  );

  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/group-attribute?page=${pageIndex + 1}&per_page=${pageSize}`)
        .then((res) => {
          setData(res.data)
          setPageCount(res.data.last_page)
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
              className="top-right-button"
              onClick={() => {
                setEdit(null);
                setShowModal(true)
              }}
            >
              <i className={"fa fa-plus mr-1"}/>
              افزودن
            </Button>
          </div>
          <Breadcrumb heading={"menu.group-attributes.list"} match={match}/>
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
      <Modal
        isOpen={show_modal}
        size="md"
        toggle={() => setShowModal(!show_modal)}>
        <ModalHeader>{`${edit ? 'ویرایش ویژگی' : 'افزودن ویژگی'}`}</ModalHeader>
        <ModalBody>
          <GroupAttributeForm init={edit} setData={setData} setShowModal={setShowModal}/>
        </ModalBody>
      </Modal>
      <Modal
        isOpen={attributes_modal}
        size="md"
        toggle={() => setAttributesModal(!attributes_modal)}>
        <ModalHeader>مدیریت ویژگی ها</ModalHeader>
        <ModalBody>
          <GroupAttributeSelect init={attributes_select} setShowModal={setAttributesModal}/>
        </ModalBody>
      </Modal>
    </Suspense>
  );
}

export default GroupAttributesList;

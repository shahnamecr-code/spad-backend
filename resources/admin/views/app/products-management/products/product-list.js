import React, {Suspense, useState} from "react";
import {
  Badge, Button,
  Card,
  CardBody,
  DropdownItem,
  DropdownMenu,
  DropdownToggle, FormGroup, Label, Modal, ModalBody,
  Row,
  UncontrolledButtonDropdown, UncontrolledDropdown,
} from "reactstrap";
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import axios from "axios";
import {adminPathApi, defaultMainImage, uploadPath} from "../../../../constants/defaultValues";
import {Link, useHistory} from "react-router-dom";
import {Formik,Form,Field} from "formik";
import Select from "react-select";
import {
  FormikCheckbox,
  FormikRadioButtonGroup,
  FormikReactSelect
} from "../../../../containers/form-validations/FormikFields";
import {GetDefaultQueryString,GetQueryStringTextMode} from "../../../../helpers/getDefaultQueryString";
import {useLocation} from "react-router-dom";
import IntlMessages from "../../../../helpers/IntlMessages";
import * as querystring from "querystring";
import CustomTableAjax from "../../../../components/CustomTableAjax";


const ProductList = ({match,location,...props}) => {
  let history = useHistory()
  const [queryPairs,setQueryPairs]=React.useState({})
  const [initialFilters,setInitialFilters]=React.useState({})
  const [data, setData] = React.useState([])
  const [loading, setLoading] = React.useState(false)
  const [pageCount, setPageCount] = React.useState(0)
  const [pageSizeFor, setPageSizeFor] = React.useState(1);
  const [pageIndexFor, setPageIndexFor] = React.useState(1);
  const [show_modal,setShowModal]=React.useState(false)
  const orderOptions = [
    { column: 'created', label: 'جدیدترین محصولات' },
    { column: 'status', label: 'وضعیت' },
    { column: 'product_code', label: 'شناسه محصول' },
  ];
  const [selectedOrderOption, setSelectedOrderOption] = useState({
    column: 'created',
    label:
      'جدیدترین محصولات'
  });
  let params=['price','status'];

  React.useEffect(()=>{
    let queryParameters=new URLSearchParams(location.search)
    let query={}
    for (const [key, value] of queryParameters.entries()) {
      query[key]=value
    }
    setQueryPairs(query)
    setSelectedOrderOption(
      orderOptions.find(option=>option.column==query['orderBy'])??orderOptions[0]
    )
  },[location.search])

  React.useEffect(()=>{
    let qp=new URLSearchParams(location.search)
    let query={}
    for (const [key, value] of qp.entries()) {
      query[key]=value
    }
    let filter=new URLSearchParams('')
    Object.keys(query).map((index)=>{
      if(params.includes(index)){
        filter.set(index,query[index])
      }
    })
    setInitialFilters(GetDefaultQueryString(filter.toString()))
  },[])

  const changeOrderBy=(column) => {
    let queryParameters=new URLSearchParams(location.search)
    queryParameters.set('orderBy',column)
    queryParameters.set('page',1)
    history.push('?'+queryParameters.toString())
  }

  const onSearchKey=(e) => {
    if (e.key === 'Enter') {
      let queryParameters=new URLSearchParams(location.search)
      queryParameters.set('search',e.target.value)
      queryParameters.set('page',1)
      history.push('?'+queryParameters.toString())
    }
    // setQueryParameters({...queryParameters,search:e.target.value})
  }

  const Actions = ({id,row}) => {
    return (
      <UncontrolledButtonDropdown>
        <DropdownToggle color="secondary" outline>
          <i className={'simple-icon-options-vertical'}/>
        </DropdownToggle>
        <DropdownMenu>
          <Link to={`edit/${id}`} className={"dropdown-item"}>ویرایش</Link>
          {row.status != 0 && (
            <DropdownItem onClick={() => awaited(id)}>
              در انتظار تأیید
            </DropdownItem>
          )}
          {row.status!=1 && (
            <DropdownItem onClick={() => approve(id)}>
              تأیید
            </DropdownItem>
          )}
          {row.status!=2 && (
            <DropdownItem onClick={() => reject(id)}>
              رد کردن
            </DropdownItem>
          )}
          {row.productsCount>0 && (
            <Link to={`products/${id}`} className={"dropdown-item"}>
              مشاهده فروشندگان <Badge color="info" pill>{row.productsCount}</Badge>
            </Link>
          )}
          <DropdownItem onClick={() => deleteItem(id)}>
            حذف محصول
          </DropdownItem>
        </DropdownMenu>
      </UncontrolledButtonDropdown>
    );
  };

  const reject=(id)=>{
    swal({
      title: 'علت رد محصول را بنویسید',
      icon: "warning",
      content: {
        element: "input",
        attributes: {
          type: "text",
        },
      },
      buttons: ['لغو', 'ثبت و رد محصول'],
    }).then(async (result)=> {
      if (result !== null) {
        axios
          .post(`${adminPathApi}/template/change-status/${id}`,{action:'reject',reject_reason:result})
          .then(res=>res.data)
          .then(data=>{
            if(data.status){
              setData((items)=>{
                let res=[...items.data];
                let cnt=res.findIndex(item => item.id == id);
                if(cnt){
                  res[cnt].status=2
                }
                return {...items,data:res};
              })
              swal(data.message,{icon:'success',timer:3000,buttons:false})
            }
          })
      }
    })
  }

  const approve=(id)=>{
    axios
      .post(`${adminPathApi}/template/change-status/${id}`,{action:'approve'})
      .then(res=>res.data)
      .then(data=>{
        if(data.status){
          setData((items)=>{
            let res=[...items.data];
            let cnt=res.findIndex(item => item.id == id);
            if(cnt){
              res[cnt].status=1
            }
            return {...items,data:res};
          })
          swal(data.message,{icon:'success',timer:3000,buttons:false})
        }
      })
  }

  const awaited=(id)=>{
    axios
      .post(`${adminPathApi}/template/change-status/${id}`,{action:'await'})
      .then(res=>res.data)
      .then(data=>{
        if(data.status){
          setData((items)=>{
            let res=[...items.data];
            let cnt=res.findIndex(item => item.id == id);
            if(cnt){
              res[cnt].status=0
            }
            return {...items,data:res};
          })
          swal(data.message,{icon:'success',timer:3000,buttons:false})
        }
      })
  }

  const deleteItem=(id)=>{
    swal("آیا از حذف محصول اطمینان دارید؟", {
      icon: "warning",
      buttons: ['نه', 'بله'],
    })
      .then(res => {
        if (res) {
          axios
            .delete(`${adminPathApi}/template/delete/${id}`)
            .then(res=>res.data)
            .then(info=>{
              if (info.status) {
                setData(prev => {
                  return {...prev, data: prev.data.filter(item => item.id != id)}
                })
                swal(info.message, {icon: 'success', timer: 1500, buttons: false})
              } else {
                if (info.message)
                  swal(info.message, {icon: 'warning', timer: 1500, buttons: false})
              }
            })
        }
      })
  }

  const cols = React.useMemo(
    () => [
      {
        Header: 'نام',
        cellClass: 'list-item-heading',
        Cell: (props) => {
          let img=props.row.original.properties.find(item=>item.property && item.property.code=='main_image')
          let img_src;
          if(img && img.value){
            img_src=`${uploadPath}/${img.value}`
          }else{
            img_src=`${defaultMainImage}`
          }
          return (
            <div className="product-name-box">
              <div className="img-wrapper">
                <img className="product-image" src={img_src}/>
              </div>
              <div className="name-wrapper">
                <p className="product-title">{props.row.original.title}</p>
              </div>
            </div>
          );
        },
      },
      {
        Header: 'اطلاعات ثبت محصول',
        cellClass: 'list-item-heading',
        Cell: (props) => <ul className="product-register">
          <li><span className="list-name">اپراتور: </span><span className="list-value">{props.row.original.admin.first_name} {props.row.original.admin.last_name}</span></li>
          <li><span className="list-name">فروشگاه: </span><span className="list-value">{props.row.original.shop?props.row.original.shop.name:'فروشگاه اصلی'}</span></li>
          <li><span className="list-name">دسته بندی محصول: </span><span className="list-value">{props.row.original.main_category.name}</span></li>
        </ul>,
      },
      {
        Header: 'وضعیت',
        cellClass: 'list-item-heading',
        Cell: (props) => (
          <>
            {props.row.original.status == 0 && (
              <Badge color="secondary">در انتظار تأیید</Badge>
            )}
            {props.row.original.status == 1 && (
              <Badge color="success">تأیید شده</Badge>
            )}
            {props.row.original.status == 2 && (
              <Badge color="danger">رد شده</Badge>
            )}
          </>
        ),
      },
      {
        Header: 'تغییرات محصول',
        cellClass: 'list-item-heading',
        Cell: (props) => <div className="product-variations">
          {props.row.original.superAttributesTitle.length>0?props.row.original.superAttributesTitle.join(' | '):'---' }
        </div>,
      },
      {
        Header: 'گزینه ها',
        accessor: 'id',
        cellClass: 'text-muted text-center',
        Cell: (props) => (<Actions id={props.value} row={props.row.original}/>),
      },
    ],
    []
  );
  //const tableInstance = useTable({ columns, data })
  const fetchData = React.useCallback(async ({pageSize, pageIndex}) => {
      await axios.get(`${adminPathApi}/template${location.search}`)
        .then((res) => {
          setData(res.data)
          setPageCount(res.data.last_page)
          return res
        })
        .catch((error) => error)
    }
    ,[location.search]);

  return (
    <Suspense fallback={<div className={'loading'}/> }>
      <Row>
        <Colxx md="12">
          <div className="text-zero top-right-button-container">
            <Link to={`create`} className={"btn btn-primary top-right-button"}>
              <i className={"fa fa-plus mr-1"}/>
              افزودن
            </Link>
            <Button className={"btn btn-primary top-right-button"} onClick={(e)=>setShowModal(true)}>
              <i className={"iconsminds-filter-2"}/>
              جستجوی پیشرفته
            </Button>
          </div>
          <Breadcrumb heading={"menu.templates.list"} match={match}/>
          <div className="d-block d-md-inline-block pt-1">
            <UncontrolledDropdown className="mr-1 float-md-left btn-group mb-1">
              <DropdownToggle caret color="outline-dark" size="xs">
                <IntlMessages id="pages.orderby" />
                {selectedOrderOption.label}
              </DropdownToggle>
              <DropdownMenu>
                {orderOptions.map((order, index) => {
                  return (
                    <DropdownItem
                      key={index}
                      onClick={() => changeOrderBy(order.column)}
                    >
                      {order.label}
                    </DropdownItem>
                  );
                })}
              </DropdownMenu>
            </UncontrolledDropdown>
            <div className="search-sm d-inline-block float-md-left mr-1 mb-1 align-top">
              <input
                type="text"
                name="keyword"
                id="search"
                placeholder={'جستجو کنید...'}
                onKeyPress={(e) => onSearchKey(e)}
                defaultValue={queryPairs.search??''}
              />
            </div>
          </div>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx>
        <Card className="mb-4">
          <CardBody>
            <CustomTableAjax columns={cols} data={data} fetchData={fetchData} loading={loading} pageCount={pageCount}
                       setPageIndexFor={setPageIndexFor} setPageSizeFor={setPageSizeFor}/>
          </CardBody>
        </Card>
      </Colxx>
      <Modal isOpen={show_modal} toggle={() => {
        setShowModal(!show_modal)
      }} backdrop wrapClassName="modal-right">
        <ModalBody>
          <Formik initialValues={initialFilters} enableReinitialize onSubmit={(values)=> {
            let form_query=GetQueryStringTextMode(values)
            let other_query={}
            Object.keys(queryPairs).map(index=>{
              if (!params.includes(index)){
                other_query[index]=queryPairs[index]
              }
            })
            let new_query={...other_query,...form_query,page:1}
            let queryObject=new URLSearchParams(new_query)
            history.push('?'+queryObject.toString())
          }}>
            {({errors, touched, setFieldValue, setFieldTouched, values}) => (
              <Form className="av-tooltip tooltip-label-right">
                <FormGroup>
                  <Label>
                    قیمت از
                  </Label>
                  <FormikReactSelect
                    class="form-control"
                    onChange={setFieldValue}
                    onBlur={setFieldTouched}
                    name="price.min"
                    value={(values.price && values.price.min)?values.price.min:undefined}
                    options={[
                      {label:'a',value:1000},
                      {label:'b',value:2000},
                    ]}
                  />
                </FormGroup>
                <FormGroup>
                  <Label>
                    تا
                  </Label>
                  <FormikReactSelect
                    class="form-control"
                    onChange={setFieldValue}
                    onBlur={setFieldTouched}
                    name="price.max"
                    value={(values.price && values.price.max)?values.price.max:undefined}
                    options={[
                      {label:'a',value:1000},
                      {label:'b',value:2000},
                    ]}
                  />
                </FormGroup>
                <FormGroup>
                  <Label>وضعیت</Label>
                  <FormikRadioButtonGroup
                  onChange={setFieldValue}
                  onBlur={setFieldTouched}
                  options={[
                    {label:'همه',value:undefined},
                    {label:'در انتظار تایید',value:'awaiting'},
                    {label:'تایید شده',value:'approved'},
                    {label:'رد شده',value:'rejected'},
                  ]}
                  inline
                  name="status"
                  value={values.status}/>
                </FormGroup>
                <Button type="submit">
                  پیدا کن
                </Button>
              </Form>
              )}
          </Formik>
        </ModalBody>
      </Modal>
    </Suspense>
  )
};

export default ProductList;


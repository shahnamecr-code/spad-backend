import React, {Suspense, useEffect, useState} from "react";
import {adminPathApi, hasCrm} from "../../../constants/defaultValues";
import axios from "axios";
import {Card, CardBody, CardTitle, DropdownMenu, DropdownToggle, Row, UncontrolledButtonDropdown} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import {Link} from "react-router-dom";
import Breadcrumb from "../../../containers/navs/Breadcrumb";

const AdvertisementCategoryList=({match,fromRoute,sub=false})=>{
  const [categories,setCategories]=useState([]);

  useEffect(()=>{
    let source=`${adminPathApi}/advertisement-category/list`
    if (sub){
      source+=`?parent=${match.params.parent}`
    }
    axios
      .get(source)
      .then(res=>res.data)
      .then(data=>{
        setCategories(data)
      }).catch((e)=>{
      if (e.response.status==402){
        window.location.replace(e.response.data.url)
      }
    })
  },[match.params.parent])

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <div className="text-zero top-right-button-container">
            <Link to={sub?`add/${match.params.parent}`:`add`} className={"btn btn-primary top-right-button"}>
              <i className={"fa fa-plus mr-1"}/>
              افزودن
            </Link>
          </div>
          <Breadcrumb heading={"menu.land-types"} match={match}/>
          <Separator className="mb-4"/>
        </Colxx>
      </Row>
      <Colxx xss={12}>
        {categories.map(category=>(
          <Card key={category.id} className="mb-2">
            <CardBody className="d-flex align-items-center justify-content-between">
              <CardTitle className="mb-0">
                {category.is_last ?
                    <span>{category.name}</span>
                  :
                    <Link to={sub?`${category.id}`:`subCategories/${category.id}`}>{category.name}</Link>
                }
              </CardTitle>
              <UncontrolledButtonDropdown>
                <DropdownToggle color="secondary" outline>
                  <i className={'simple-icon-options-vertical'}/>
                </DropdownToggle>
                <DropdownMenu>
                  <Link to={`${fromRoute}/edit/${category.id}`} className={"dropdown-item"}>ویرایش</Link>
                  {!sub?(
                    <Link to={`${fromRoute}/home-filters/${category.id}`} className={"dropdown-item"}>فیلترهای صفحه اصلی</Link>
                  ):''}
                  <Link to={`${fromRoute}/layout/${category.id}`} className={"dropdown-item"}>چیدمان صفحه ثبت آگهی</Link>
                  <Link to={`${fromRoute}/panel-list/${category.id}`} className={"dropdown-item"}>اطلاعات لیست آگهی</Link>
                  <Link to={`${fromRoute}/filters/${category.id}`} className={"dropdown-item"}>چیدمان فیلتر آگهی</Link>
                  <Link to={`${fromRoute}/show/${category.id}`} className={"dropdown-item"}>چیدمان صفحه آگهی</Link>
                  {hasCrm?(
                    <Link to={`${fromRoute}/crm-layout/${category.id}`} className={"dropdown-item"}>چیدمان درخواست ملک</Link>
                  ):''}
                </DropdownMenu>
              </UncontrolledButtonDropdown>
            </CardBody>
          </Card>
        ))}
      </Colxx>
    </Suspense>
  );
}

export default AdvertisementCategoryList;

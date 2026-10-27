import React, {Suspense, useEffect, useState} from "react";
import {adminPathApi} from "../../../constants/defaultValues";
import {Button, Card, CardBody, CardSubtitle, FormGroup, Label, Row} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import PropertyWrapperContent from "./components/PropertyWrapperContent";
import {Link} from "react-router-dom";


const PropertyWrapperList=({match,history})=>{
  const [wrappers,setWrappers]=useState([]);

  useEffect(()=>{
    axios
      .get(`${adminPathApi}/wrappers`)
      .then(res=>res.data)
      .then(data=>setWrappers(data))
  },[])

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <div className="text-zero top-right-button-container">
            <Link to={`add`} className={"btn btn-primary top-right-button"}>
              <i className={"fa fa-plus mr-1"}/>
              افزودن
            </Link>
          </div>
          <Breadcrumb heading={"menu.wrappers"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx xss={12}>
        <Row>
          <Colxx md={8}>
            {wrappers.normal && wrappers.normal.map(wrapper=>(
              <PropertyWrapperContent wrapper={wrapper} history={history}/>
            ))}
          </Colxx>
          <Colxx md={4}>
            {wrappers.side && wrappers.side.map(wrapper=>(
              <PropertyWrapperContent wrapper={wrapper} history={history}/>
            ))}
          </Colxx>
        </Row>
      </Colxx>
    </Suspense>
  );
}

export default PropertyWrapperList;

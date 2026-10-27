import React, {Suspense, useEffect, useState} from "react";
import {Card, CardBody, Row} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {adminPathApi,adminRoot} from "../../../constants/defaultValues";
import {Link} from "react-router-dom";

const propertyList=({match})=>{
  const [properties,setProperties]=useState([])

  useEffect(()=>{
    axios
      .get(`${adminPathApi}/properties`)
      .then(res=>res.data)
      .then(data=>{
        if (data){
          setProperties(data)
        }
      })
      .catch((e)=>{
        if (e.response.status==402){
          window.location.replace(e.response.data.url)
        }
      })
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
          <Breadcrumb heading={"menu.properties"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx xss={12}>
        <Row>
        {properties.map(property=>(
          <Colxx md={6}>
            <Card className="mb-2">
              <CardBody>
                <Link to={`edit/${property.id}`}>{property.title}</Link>
              </CardBody>
            </Card>
          </Colxx>
        ))}
        </Row>
      </Colxx>
    </Suspense>
  );
}

export default propertyList;

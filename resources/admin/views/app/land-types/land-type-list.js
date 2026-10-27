import React, {Suspense,useEffect, useState} from "react";
import {adminPathApi} from "../../../constants/defaultValues";
import {Card, CardBody, CardTitle, DropdownMenu, DropdownToggle, Row, UncontrolledButtonDropdown} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {Link} from "react-router-dom";

const LandTypeList=({match})=>{
  const [loading,setLoading]=useState(false)
  const [landTypes,setLandTypes]=useState([])

  useEffect(()=>{
    axios
      .get(`${adminPathApi}/land-types`)
      .then(res=>res.data)
      .then(data=>{
        setLandTypes(data)
      })
  },[])

  if (loading){
    return <div className="loading"/>
  }

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
          <Breadcrumb heading={"menu.land-types"} match={match}/>
          <Separator className="mb-4"/>
        </Colxx>
      </Row>
      <Colxx xss={12}>
        {landTypes.map(landType=>(
          <Card key={landType.id} className="mb-2">
            <CardBody className="d-flex align-items-center justify-content-between">
              <CardTitle className="mb-0">{landType.name}</CardTitle>
              <UncontrolledButtonDropdown>
                <DropdownToggle color="secondary" outline>
                  <i className={'simple-icon-options-vertical'}/>
                </DropdownToggle>
                <DropdownMenu>
                  <Link to={`edit/${landType.id}`} className={"dropdown-item"}>ویرایش</Link>
                </DropdownMenu>
              </UncontrolledButtonDropdown>
            </CardBody>
          </Card>
        ))}
      </Colxx>
    </Suspense>
  );
}
export default LandTypeList;

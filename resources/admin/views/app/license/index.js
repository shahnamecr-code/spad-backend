import React, {Suspense, useEffect, useState} from "react";
import {Card, CardBody, CardTitle, DropdownMenu, DropdownToggle, Row, UncontrolledButtonDropdown} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import {Link} from "react-router-dom";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {adminPathApi} from "../../../constants/defaultValues";

const License=()=>{
  const [loading,setLoading]=useState(true)
  const [hasLicense,setHasLicense]=useState(false)

  useEffect(()=>{
    axios
      .get(`/api/has_license`)
      .then(res=>res.data)
      .then(data=>{
        if (data.license)
          setHasLicense(data.license)
      })
      .finally(()=>{
        setLoading(false)
      })
  },[])

  return(
    <Suspense fallback={<div className={'loading'}/>}>
      <Colxx xss={12}>
        {hasLicense?(
          <h5>لایسنس برای شما فعال شده است</h5>
        ):(
          <h5>جهت فعال سازی لایسنس <a style={{color:"#a86767",textDecoration:"underline"}} href={"/buy_script"}>کلیک</a> کنید</h5>
        )}
      </Colxx>
    </Suspense>
  );
}

export default License;

import React, {Suspense, useEffect, useState} from "react";
import {Button, ButtonGroup, FormGroup, FormText, Label, Row} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {adminPathApi} from "../../../constants/defaultValues";
import {Link} from "react-router-dom";
import PropertyWrapperContent from "./components/PropertyWrapperContent";
import SearchSelect from "../../../components/SearchSelect";
import {AsyncPaginate} from "react-select-async-paginate";
import axios from "axios";

const AdvertisementCategoryLayout=({match,history,fromRoute})=>{
  const [categoryToImport,setCategoryToImport]=useState(null);
  const [showCategories,setShowCategories]=useState(false);
  const [layout,setLayout]=useState({
    side:[],
    normal:[],
  });

  const loadCategories = async (search, prevOptions,{page}) => {
    let source=`${adminPathApi}/advertisement-category/allCategories?page=${page}`
    if (search){
      source+=`&search=${search}`
    }
    let result;

    await axios
      .get(source)
      .then(res=>res.data)
      .then(data=>{
        result=data
      }).catch((e)=>{
        if (e.response.status==402){
          window.location.replace(e.response.data.url)
        }
      })

    return {
      options: result.data,
      hasMore:result.hasMore,
      additional:{
        page: parseInt(result.page)+1,
      }
    };
  };

  const importLayouts=(category)=>{
    axios
      .post(`${adminPathApi}/advertisement-category/import-layout/${match.params.id}`,{
        "from":categoryToImport.id
      })
      .then(res=>res.data)
      .then(data=>{
        if (data.status)
          setLayout(data.data)
      }).catch((e)=>{
      if (e.response.status==402){
        window.location.replace(e.response.data.url)
      }
    })
  }

  useEffect(()=>{
    axios
      .get(`${adminPathApi}/advertisement-category/layout/${match.params.id}`)
      .then(res=>res.data)
      .then(data=>{
        setLayout(data)
      }).catch((e)=>{
      if (e.response.status==402){
        window.location.replace(e.response.data.url)
      }
    })
  },[])

  return (
    <Suspense fallback={<div className="loading"/>}>
      <Row>
        <Colxx xss="12">
          <div className="text-zero top-right-button-container">
            <Link to={`${match.params.id}/add`} className={"btn btn-primary"}>
              <i className={"fa fa-plus mr-1"}/>
              افزودن
            </Link>
            <Button onClick={()=>setShowCategories(!showCategories)}>
              کپی از دسته بندی های موجود
            </Button>
          </div>
          <Breadcrumb heading={"menu.advertisement.categories.edit"} match={match}/>
          <div className={`mb-2 ${showCategories?'d-flex':'d-none'}`}>
            <div className={"flex-grow-1"}>
              <AsyncPaginate
                value={categoryToImport}
                loadOptions={loadCategories}
                onChange={(item) => setCategoryToImport(item)}
                additional={{
                  page: 1,
                }}
                getOptionLabel={option => option.name}
                getOptionValue={option => option.id}
              />
            </div>
            <Button disabled={categoryToImport?false:true} className={"ml-1"} onClick={()=>importLayouts(categoryToImport)}>
              <span className={"fa fa-check"}/>
            </Button>
          </div>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Row className="mb-4">
        <Colxx xxs="12">
          <Row>
            <Colxx md={8}>
              {layout.normal && layout.normal.map(wrapper=>(
                <PropertyWrapperContent wrapper={wrapper} history={history}/>
              ))}
            </Colxx>
            <Colxx md={4}>
              {layout.side && layout.side.map(wrapper=>(
                <PropertyWrapperContent wrapper={wrapper} history={history}/>
              ))}
            </Colxx>
          </Row>
        </Colxx>
      </Row>
    </Suspense>
  );
}

export default AdvertisementCategoryLayout;

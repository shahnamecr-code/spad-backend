import React, {useEffect, useState} from "react";
import {Field, useFormikContext} from "formik";
import {Col, FormGroup, FormText, Label, Row} from "reactstrap";
import num2persian from "num2persian";
import {Colxx} from "./common/CustomBootstrap";
import {FormikReactSelect, FormikSwitch} from "../containers/form-validations/FormikFields";
import {adminPathApi} from "../constants/defaultValues";
import axios from "axios";
import {AsyncPaginate} from "react-select-async-paginate";
import Select from "react-select";

const AdvertisementPosition=({name,value={}})=>{
  let {values,setFieldValue,setFieldTouched} = useFormikContext();
  const [regions,setRegions]=useState([]);

  const loadCities = async (search, prevOptions,{page}) => {
    let source=`${adminPathApi}/location/allCities?page=${page}`
    if (search){
      source+=`&search=${search}`
    }
    let result;

    await axios
      .get(source)
      .then(res=>res.data)
      .then(data=>{
        result=data
      })

    return {
      options: result.data,
      hasMore:result.hasMore,
      additional:{
        page: parseInt(result.page)+1,
      }
    };
  };

  useEffect(()=>{
    if (value.city){
      axios
        .get(`${adminPathApi}/location/cityAllRegions/${value.city.id}`)
        .then(res=>res.data)
        .then(data=>{
          setRegions(data)
        })
    }
  },[value.city])

  const loadRegions = async (search, prevOptions,{page}) => {
    let source=`${adminPathApi}/location/allRegions?page=${page}`
    if (search){
      source+=`&search=${search}`
    }
    let result;

    await axios
      .get(source)
      .then(res=>res.data)
      .then(data=>{
        result=data
      })

    return {
      options: result.data,
      hasMore:result.hasMore,
      additional:{
        page: parseInt(result.page)+1,
      }
    };
  };

  return (
    <>
      <FormGroup className={"col-md-6"}>
        <Label>شهر</Label>
        <AsyncPaginate
          value={value.city}
          loadOptions={loadCities}
          onChange={(item) => setFieldValue(name, {...value,city:item,region:undefined})}
          additional={{
            page: 1,
          }}
          getOptionLabel={option => option.name}
          getOptionValue={option => option.id}
        />
      </FormGroup>
      {value.city && value.city.regions_count>0 && (
        <FormGroup className={"col-md-6"}>
          <Label>خیابان</Label>
          <Select
            options={regions}
            value={value.region}
            name={`${name}.region`}
            onChange={(item) => setFieldValue(name, {...value,region:item})}
            onBlur={()=>setFieldTouched(`${name}.region`,true)}
            isMulti
            getOptionLabel={option => option.name}
            getOptionValue={option => option.id}
          />
        </FormGroup>
      )}
    </>
  );
}


const CrmFilterItem=({field})=>{
  const {values,setFieldValue,setFieldTouched}=useFormikContext();
  let output='';
    if (field.property_type =='geo' ){
      output=(
        <AdvertisementPosition name={field.code} value={values[field.code]}/>
      );
    } else if (field.range_search){
      switch (field.search_type){
        case 'text':
          output=(
            <FormGroup className={"col-md-6"}>
              <Label>{field.label}</Label>
              <Row>
                <Colxx md={6}>
                  <Field name={`${field.code}.from`} className={"form-control"} placeholder={"از"}/>
                  {field.property_type=='integer' && values[field.code] && values[field.code]['from'] && (
                    <FormText>{num2persian(values[field.code]['from'])}</FormText>
                  )}
                </Colxx>
                <Colxx md={6}>
                  <Field name={`${field.code}.to`} className={"form-control"} placeholder={"تا"}/>
                  {field.property_type=='integer' && values[field.code] && values[field.code]['to'] && (
                    <FormText>{num2persian(values[field.code]['to'])}</FormText>
                  )}
                </Colxx>
              </Row>
            </FormGroup>
          );
          break;
        case 'select':
          output=(
            <FormGroup className={"col-md-6"}>
              <Label>{field.label}</Label>
              <Row>
                <Colxx md={6}>
                  {console.log(values)}
                  <FormikReactSelect value={values[field.code] && values[field.code]['from'] && values[field.code]['from']} name={`${field.code}.from`} options={field.options??[]} onChange={setFieldValue} onBlur={setFieldTouched}/>
                  {field.property_type=='integer' && values[field.code] && values[field.code]['from'] && (
                    <FormText>{num2persian(values[field.code]['from'])}</FormText>
                  )}
                </Colxx>
                <Colxx md={6}>
                  <FormikReactSelect value={values[field.code] && values[field.code]['to'] && values[field.code]['to']} name={`${field.code}.to`} options={field.options??[]} onChange={setFieldValue} onBlur={setFieldTouched}/>
                  {field.property_type=='integer' && values[field.code] && values[field.code]['to'] && (
                    <FormText>{num2persian(values[field.code]['to'])}</FormText>
                  )}
                </Colxx>
              </Row>
            </FormGroup>
          );
          break;
      }
    }else{
      switch (field.search_type){
        case 'text':
          output=(
            <FormGroup className={"col-md-6"}>
              <Label>{field.label}</Label>
              <Field name={field.code} className={"form-control"}/>
            </FormGroup>
          );
          break;
        case 'multi-select':
          output=(
            <FormGroup className={"col-md-6"}>
              <Label>{field.label}</Label>
              <FormikReactSelect isMulti name={field.code} options={field.options??[]} onChange={setFieldValue} onBlur={setFieldTouched} value={values[field.code]}/>
            </FormGroup>
          );
          break;
        case 'switch':
          output=(
            <FormGroup className={"col-xs-4 col-sm-3 col-md-1"}>
              <Label>{field.label}</Label>
              <FormikSwitch
                name={field.code}
                className="custom-switch custom-switch-primary"
                value={values[field.code]}
                onChange={setFieldValue}
                onBlur={setFieldTouched}
              />
            </FormGroup>
          );
          break;
      }
    }
  return output;
}
export default CrmFilterItem;

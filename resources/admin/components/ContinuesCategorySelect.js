import React, {useState} from "react";
import ReactSelect from "react-select";
import {Colxx} from "./common/CustomBootstrap";
import {Label} from "reactstrap";

const ContinuesCategorySelect=({categories,value='',setSelectedCategory})=>{
  let current_category=categories.find(category=>category.slug==value)
  let this_options;

  if (value){
    this_options=categories.filter(category=>category.parent==current_category.parent)
  }else{
    this_options=categories.filter(category=>category.parent==null)
  }

  return (
    <>
      {(current_category && current_category.parent)?(
        <ContinuesCategorySelect categories={categories} value={current_category.parent} setSelectedCategory={setSelectedCategory}/>
      ):''}
      <Colxx md={4}>
        <ReactSelect
          options={this_options}
          value={current_category}
          getOptionLabel={option=>option.name}
          getOptionValue={option=>option.slug}
          onChange={(value)=>setSelectedCategory(value.slug)}
        />
      </Colxx>
    </>
  );
}

export default ContinuesCategorySelect;

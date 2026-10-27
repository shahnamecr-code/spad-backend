import axios from "axios";
import {adminPathApi} from "../constants/defaultValues";
import React from "react";

const getAttributesList = () => {
  const [except,setExcept]=React.useState([]);
  const [search,setSearch]=React.useState('');
  const [options,setLoadOptions]=React.useState('');

  React.useEffect(()=>{
    axios
      .post(
        `${adminPathApi}/attribute/search_attributes`,
        {
          attributes:except,
          page:1,
          per_page:10,
          search:search,
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
            options1:attribute.options,
            options: undefined,
            label: attribute.name,
            value: attribute.code,
            key: attribute.code,
          })
        })
        //setShowAttributeList(false)
        setLoadOptions(attr);
      });
  },[search,except])

  return [except,setExcept,search,setSearch,options,setLoadOptions];
}

export default getAttributesList;

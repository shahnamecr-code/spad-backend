/* eslint-disable react/no-array-index-key */
import React, {useEffect} from 'react';
import {adminPathApi} from "../../../../constants/defaultValues";
import axios from "axios";

const Product = ({ match , location,history }) => {
  const url = new URLSearchParams(location.search);
  const [attributes,setAttributes]=React.useState([]);

  if(!url.has('template'))
    history.push('/');

  useEffect(async () => {
    await axios
      .get(
        `${adminPathApi}/template/attributes/${url.get('template')}`
      )
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        console.log(data)
      });
  }, []);

  return (
    <>
      {
        url.has('template') && (
          <p>Product Add {`${url.get('template')}`}</p>
        )
      }
    </>
  );

};

export default Product;

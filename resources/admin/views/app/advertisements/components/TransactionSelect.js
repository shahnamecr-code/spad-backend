import React, {useEffect, useState} from "react";
import {adminPathApi} from "../../../../constants/defaultValues";
import {FormikReactSelect} from "../../../../containers/form-validations/FormikFields";
import {useFormikContext} from "formik";

const TransactionSelect = ({name,isMulti=false}) => {
  let {values,setFieldValue,setFieldTouched} = useFormikContext();
  let value = values[name];
  const [transactions, setTransactions] = useState([]);

  useEffect(()=>{
    axios
      .get(`${adminPathApi}/transactions`)
      .then(res=>res.data)
      .then(data=>setTransactions(data))
  },[])

  return (
    <FormikReactSelect
      options={transactions}
      name={name}
      value={value}
      onChange={setFieldValue}
      onBlur={setFieldTouched}
      getOptionLabel={option => option.name}
      getOptionValue={option => option.id}
      isMulti={isMulti}/>
  );
}

export default TransactionSelect;

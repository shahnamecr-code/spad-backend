import React, {useEffect, useState} from "react";
import {adminPathApi} from "../../../../constants/defaultValues";
import {FormikReactSelect} from "../../../../containers/form-validations/FormikFields";
import {useFormikContext} from "formik";

const LandTypeSelect = ({name,isMulti=false}) => {
  let {values,setFieldValue,setFieldTouched} = useFormikContext();
  let value = values[name];
  let transaction=values['transaction'];
  const [land_types, setLandTypes] = useState([]);

  useEffect(()=>{
    axios
      .get(`${adminPathApi}/land-types?transaction=${transaction}`)
      .then(res=>res.data)
      .then(data=>setLandTypes(data))
  },[])

  return (
    <FormikReactSelect
      options={land_types}
      name={name}
      value={value}
      onChange={setFieldValue}
      onBlur={setFieldTouched}
      getOptionLabel={option => option.name}
      getOptionValue={option => option.id}
      isMulti={isMulti}/>
  );
}

export default LandTypeSelect;

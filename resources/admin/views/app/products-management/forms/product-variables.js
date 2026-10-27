import React from "react";
import {Form, Formik} from "formik";
import AlertError from "../../../../components/AlertError";
import {Button,} from "reactstrap";
import SearchMultiSelect from "../../../../components/SearchMultiSelect";
import axios from "axios";
import {adminPathApi} from "../../../../constants/defaultValues";

const ProductVariables=({wizardHelper,...props})=>{
  const [init,setInit]=React.useState({});
  const [loading,setLoading]=React.useState(true);
  const [validationErrors,setvalidationErrors]=React.useState({});

  const save=(values)=>{
      axios
        .post(
          `${adminPathApi}/template/${props.id}/variable_attributes`,
          values
        )
        .then((res) => {
          return res.data;
        })
        .then(data=>{
          wizardHelper.step.isDone=true
          wizardHelper.next()
        })
  }

  const getVariableAttributesList = (setloadOptions, search = '') => {
    if(props.id){
      axios
        .post(
          `${adminPathApi}/attribute/search_attributes`,
          {
            template:props.id,
            step:'variant',
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
          setloadOptions(attr);
        });
    }
  }

  React.useEffect(async ()=>{
    axios
      .get(`${adminPathApi}/template/${props.id}/variable_attributes`)
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        let attr=[];
        if(data){
          data.map((attribute)=>{
            attr.push({
              label:attribute.name,
              id:attribute.id,
            })
          })
        }
        setInit({...init,variable:attr});
        setLoading(false)
      })
  },[])

  if(loading){
    return <div className="loading"/>
  }

  return (
    <Formik
      initialValues={init}
      onSubmit={save}
      enableReinitialize
    >
      {({errors, touched, setFieldValue, setFieldTouched, values}) => (
        <Form className="av-tooltip tooltip-label-right">
          <AlertError errors={validationErrors}/>
          <SearchMultiSelect request={getVariableAttributesList} onChange={setFieldValue} name='variable' value={init.variable?init.variable:[]}/>

          <Button type={'submit'} color={'primary'} className="mt-2">ثبت</Button>
        </Form>
      )}
    </Formik>
  );
}
export default ProductVariables;

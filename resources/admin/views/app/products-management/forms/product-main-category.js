import React from "react";
import {Form, Formik} from "formik";
import TreeSelect from "rc-tree-select";
import {Button, FormGroup} from "reactstrap";
import * as Yup from "yup";

const MainCategorySchema = Yup.object().shape({
  mainCat: Yup.string()
    .required('Please select main category'),
});

const ProductMainCategory=({wizardHelper, ...props})=>{
  const save=(values)=>{
    props.setMainCat((cat)=>values.mainCat)
    wizardHelper.step.isDone=true
    wizardHelper.next()
  }
  return (
    <Formik
      initialValues={{mainCat:props.mainCat?props.mainCat:''}}
      onSubmit={save}
      enableReinitialize
      validationSchema={MainCategorySchema}
    >
      {({errors, touched, setFieldValue, setFieldTouched, values}) => (
        <Form className="av-tooltip tooltip-label-right">
          <FormGroup>
            <TreeSelect
              style={{width: 200}}
              onChange={(val) => setFieldValue('mainCat', val)}
              name="mainCat"
              value={values.mainCat}
              treeData={props.list?props.list:[]}
            />
            {errors.mainCat && touched.mainCat && (
              <div className="invalid-feedback d-block">
                {errors.mainCat}
              </div>
            )}
          </FormGroup>
          <Button type={'submit'} color={'primary'}>ادامه</Button>
        </Form>
      )}
    </Formik>
  );
}
export default ProductMainCategory;

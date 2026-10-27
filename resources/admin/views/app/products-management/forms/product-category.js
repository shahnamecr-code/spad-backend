import React, {useEffect} from "react";
import {Form, Formik} from "formik";
import {Button, FormGroup} from "reactstrap";
import {adminPathApi} from "../../../../constants/defaultValues";
import axios from "axios";
import TreeSelect from "rc-tree-select";
import 'rc-tree-select/assets/index.less';


const ProductCategory = ({wizardHelper, ...props}) => {
  const [init, setInit] = React.useState({});
  const [category_options, setCategoryOptions] = React.useState([]);
  const [validationErrors, setValidationErrors] = React.useState({});

  useEffect(async () => {
    let get_attributes_src = `${adminPathApi}/category/get_categories`;
    await axios
      .get(
        get_attributes_src,
      )
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        setCategoryOptions(data)
      });
    await axios
      .get(
        `${adminPathApi}/template/categories/${props.id}`
      ).then((res) => {
        return res.data
      })
      .then((data) => {
        setInit({...init,cat:data})
      })
  }, []);

  const save = async (values) => {
    await axios
      .post(
        `${adminPathApi}/template/categories/${props.id}`,
        values
      ).then((res) => {
        return res.data
      })
      .then((data) => {
        wizardHelper.step.isDone=true
        wizardHelper.next();
      })
  }

  return (
    <Formik
      initialValues={init}
      onSubmit={save}
      enableReinitialize
    >
      {({errors, touched, setFieldValue, setFieldTouched, values}) => (
        <Form className="av-tooltip tooltip-label-right">
          <FormGroup>
            <TreeSelect
              style={{width: 200}}
              multiple
              onChange={(val) => setFieldValue('cat', val)}
              name="cat"
              value={values.cat}
              treeData={category_options}
            />
          </FormGroup>
          <Button type={'submit'} color={'primary'}>ادامه</Button>
        </Form>
      )}
    </Formik>
  );
}
export default ProductCategory;

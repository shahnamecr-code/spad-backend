import React, {useEffect} from "react";
import {Form, Formik} from "formik";
import AlertError from "../../../../components/AlertError";
import {Button, FormGroup, Label} from "reactstrap";
import CustomAttribute from "../../../../components/CustomAttribute";
import {adminPathApi} from "../../../../constants/defaultValues";
import axios from "axios";
import * as Yup from "yup";
import {NotificationManager} from "../../../../components/common/react-notifications";
import {useHistory} from 'react-router-dom';
import TreeSelect from "rc-tree-select";

const AttributeSchema = Yup.object().shape({
  // name: Yup.string()
  //   .min(3, 'Too Short!')
  //   .max(30, 'Too Long!')
  //   .required('Please enter name'),
});

const DefaultAttribute=({wizardHelper,...props})=>{
  const history = useHistory();
  let get_attributes_src;
  const [show_attribute_list,setShowAttributeList]=React.useState(false);
  const [init,setInit]=React.useState({});
  const [attributes,setAttributes]=React.useState([]);
  const [validationErrors, setValidationErrors] = React.useState({});
  useEffect(async () => {
    if(props.id){
      get_attributes_src=`${adminPathApi}/template/default_attributes/${props.id}`;
    }else{
      get_attributes_src=`${adminPathApi}/template/default_attributes`;
    }
    await axios
      .get(
        get_attributes_src,
      )
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        setInit(data.values ? data.values : {})
        setAttributes(data.default?data.default:{})
        wizardHelper.step.isDone=true
      });
  }, []);

  const saveTemplate = async (values,x) => {
    let route;
    if(props.id){
      route=`${adminPathApi}/template/${props.id}/edit`;
    }else{
      route=`${adminPathApi}/template`;
    }

    await axios.post(route, values)
      .then((response) => {
        if(response.data.status){
          if (!props.id){
            history.replace(`edit/${response.data.item.id}`);
          }
          wizardHelper.next();
        }else{
          NotificationManager.error(response.data.message,null,1000,null,null,'filled');
        }
        setValidationErrors({})
      }, (error) => {
        if(error.response.status == 422){
          setValidationErrors(error.response.data.errors)
        }else {
          NotificationManager.error('خطایی در ارسال اطلاعات رخ داده است',null,1000,null,null,'filled');
        }

      });
  }

  return (
    <Formik
      initialValues={init}
      onSubmit={saveTemplate}
      enableReinitialize
      validationSchema={AttributeSchema}
    >
      {({errors, touched, setFieldValue, setFieldTouched, values}) => (
        <Form className="av-tooltip tooltip-label-right">
          <AlertError errors={validationErrors}/>
          {attributes && attributes.map((attribute, index) => (
            <CustomAttribute key={attribute.code} errors={errors} touched={touched} setFieldValue={setFieldValue}
                             setFieldTouched={setFieldTouched} values={values} attribute={attribute}/>
          ))}

          <FormGroup>
            <Label>دسته بندی اصلی محصول</Label>
            <TreeSelect
              style={{width: 200,marginRight:10}}
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

          <Button type={'submit'} color={'primary'}>ثبت</Button>
        </Form>
      )}
    </Formik>
  );
}
export default DefaultAttribute;

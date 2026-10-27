import React, {useEffect} from "react";
import {Form, Formik} from "formik";
import AlertError from "../../../../components/AlertError";
import {Button, FormGroup, Modal, ModalBody, ModalHeader} from "reactstrap";
import CustomAttribute from "../../../../components/CustomAttribute";
import SearchSelect from "../../../../components/SearchSelect";
import {adminPathApi} from "../../../../constants/defaultValues";
import axios from "axios";
import {NotificationManager} from "../../../../components/common/react-notifications";
import {useHistory} from 'react-router-dom';


const ProductAttribute=({wizardHelper,...props})=>{
  const history = useHistory();
  let get_attributes_src;
  const [show_attribute_list,setShowAttributeList]=React.useState(false);
  const [init,setInit]=React.useState({});
  const [attributes,setAttributes]=React.useState([]);
  const [variable_attributes,setVariableAttributes]=React.useState([]);
  const [validationErrors, setValidationErrors] = React.useState({});
  const [except_attr, setExceptAttr] = React.useState({});
  const [loading,setLoading]=React.useState(true);

  useEffect(async () => {
    if(props.id){
      get_attributes_src=`${adminPathApi}/template/${props.id}/attributes`;
      await axios
        .get(
          get_attributes_src,
        )
        .then((res) => {
          return res.data;
        })
        .then((data) => {
          setAttributes(data.attributes)
          setInit(data.values ? data.values : {})
          setExceptAttr(data.except)
          wizardHelper.step.isDone=true
          setLoading(false);
        });
    }
  }, []);

  const saveTemplate = async (form_data) => {
    let route;
    if(props.id){
      route=`${adminPathApi}/template/${props.id}/custom_attributes`;
      await axios.post(route, form_data)
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
  }

  const getAttributesList = (setloadOptions, search = '') => {
    let ids=[];
    if(attributes.length>0){
      attributes.map(attribute=>{
        ids.push(attribute.id)
      })
    }
    axios
      .post(
        `${adminPathApi}/attribute/search_attributes`,
        {
          attributes:ids,
          template:props.id,
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

  const deleteAttribute = (code) => {
    const attr = attributes.filter(attribute => attribute.code != code)
    setAttributes(attr)
  }

  if(loading){
    return <div className='loading'/>
  }

  return (
    <Formik
      initialValues={init}
      onSubmit={saveTemplate}
      enableReinitialize
    >
      {({errors, touched, setFieldValue, setFieldTouched, values}) => (
        <Form className="av-tooltip tooltip-label-right">
          <AlertError errors={validationErrors}/>
          {attributes && attributes.map((attribute,index) => (
            <CustomAttribute key={attribute.code} errors={errors} touched={touched} setFieldValue={setFieldValue}
                             setFieldTouched={setFieldTouched} values={values} attribute={attribute}>
              {!attribute.keep && (
                <Button color="danger" outline size="xs" className="mx-1" onClick={()=>{setFieldValue(attribute.code,undefined);deleteAttribute(attribute.code)}}>
                  حذف ویژگی
                </Button>
              )}
            </CustomAttribute>
          ))}

          <FormGroup>
            {show_attribute_list && (
              <Modal
                isOpen={show_attribute_list}
                size="sm"
                toggle={() => setShowAttributeList(!show_attribute_list)}
              >
                <ModalHeader>انتخاب کنید</ModalHeader>
                <ModalBody>
                  <SearchSelect request={getAttributesList} showModal={setShowAttributeList} setList={setAttributes} list={attributes}/>
                </ModalBody>
              </Modal>

            )}
          </FormGroup>

          {!show_attribute_list && (
            <Button color="info" onClick={(e) => {
              e.preventDefault();
              setShowAttributeList(true);
            }}>افزودن ویژگی</Button>
          )}

          <Button type={'submit'} color={'primary'}>ثبت</Button>
        </Form>
      )}
    </Formik>
  );
}
export default ProductAttribute;

import React from "react";
import {adminPathApi} from "../../../../constants/defaultValues";
import {Button, CardTitle, Row} from "reactstrap";
import {Form, Formik} from "formik";
import {Colxx} from "../../../../components/common/CustomBootstrap";
import CustomAttribute from "../../../../components/CustomAttribute";
import {NotificationManager} from "../../../../components/common/react-notifications";
import AlertError from "../../../../components/AlertError";


const ProductFeature = ({id, wizardHelper, ...props}) => {
  const [groups, setGroups] = React.useState([]);
  const [features, setFeatures] = React.useState({});
  const [loading, setLoading] = React.useState(true);
  const [validationErrors, setValidationErrors] = React.useState({});

  React.useEffect(async () => {
    await axios
      .get(`${adminPathApi}/template/${id}/group_attributes`)
      .then(response => response.data)
      .then(data => {
        if (data.status) {
          setGroups(data.attributes);
          setFeatures(data.features?data.features:{});
        }
      })
      .finally(() => {
        setLoading(false)
      })
  }, []);

  const onSubmit = (values) => {
    console.log(values)
    axios
      .post(`${adminPathApi}/template/${id}/group_attributes`,values)
      .then(response=>response.data)
      .then(data=>{
        if(data.status){
          wizardHelper.next();
        }
      })
      .catch(error=>{
        if(error.response.status == 422){
          setValidationErrors(error.response.data.errors)
        }else {
          NotificationManager.error('خطایی در ارسال اطلاعات رخ داده است',null,1000,null,null,'filled');
        }
      })

  }

  if (loading) {
    return (<div className="loading"/>);
  }

  return (
    <Formik onSubmit={onSubmit} initialValues={features} enableReinitialize>
      {({errors, touched, setFieldValue, setFieldTouched, values}) => (
        <Form className="av-tooltip tooltip-label-right">
          <AlertError errors={validationErrors}/>
          {groups.map(group => (
            <>
              <CardTitle>{group.title}</CardTitle>
              <Row>
              {group.attributes && group.attributes.map(attribute => (
                <Colxx sm="6">
                  <CustomAttribute key={attribute.code} errors={errors} touched={touched} setFieldValue={setFieldValue}
                                   setFieldTouched={setFieldTouched} values={values} attribute={attribute}/>
                </Colxx>
              ))}
              </Row>
            </>
            ))}
            <Button type="submit">ثبت و ادامه</Button>
        </Form>
      )}
    </Formik>
  );
}
export default ProductFeature;

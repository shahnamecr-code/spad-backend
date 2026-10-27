import React, {Suspense, useState} from "react";
import {Button, Card, CardBody, CardSubtitle, FormGroup, Label, Row} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import AlertError from "../../../components/AlertError";
import {ErrorMessage, Field, FieldArray, Form, Formik} from "formik";
import {FormikReactSelect, FormikSwitch} from "../../../containers/form-validations/FormikFields";
import * as Yup from "yup";
import {advertisementPropertyTypes,advertisementSearchTypes,advertisementSearchLevels} from "../../../constants/advertisementConstants";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";

const propertySchema = Yup.object().shape({
  name: Yup.string()
    .min(2)
    .max(50)
    .required('وارد کردن عنوان الزامی است.'),
});

const LandTypeEdit=({match,history,prevMatch,edit=false})=>{
  const [serverError, setServerError] = useState({})
  const [loading,setLoading]=useState(true)
  const [initialValues, setInitialValues] = useState({
    name: '',
    transactions:[]
  });
  const [transactions,setTransactions]=useState([])

  React.useEffect(()=>{
    if(edit){
      const req1=axios.get(`${adminPathApi}/land-types/show/${match.params.id}`)
      const req2=axios.get(`${adminPathApi}/transactions`)
      axios
        .all([req1,req2])
        .then(axios.spread((...responses) =>{
          setInitialValues(responses[0].data)
          setTransactions(responses[1].data)
          setLoading(false)
        }))
        .catch(errors => {
          // react on errors.
        })
    }else{
      axios
        .get(`${adminPathApi}/transactions`)
        .then(res=>{
          setTransactions(res.data)
          setLoading(false)
        })
    }
  },[])

  const onSubmit=(values)=>{
    if (edit){
      axios
        .post(`${adminPathApi}/land-types/edit/${match.params.id}`,values)
        .then(res=>res.data)
        .then(data=>{
          history.push(`${prevMatch.url}/list`)
          NotificationManager.success(data.message, 'موفقیت');
        })
        .catch(({response}) => {
          setServerError(response.data.errors);
        })
    }else{
      axios
        .post(`${adminPathApi}/land-types/add`,values)
        .then(res=>res.data)
        .then(data=>{
          history.push(`${prevMatch.url}/list`)
          NotificationManager.success(data.message, 'موفقیت');
        })
        .catch(({response}) => {
          setServerError(response.data.errors);
        })
    }
  }

  if (loading){
    return (
      <div className="loading"/>
    );
  }

  return (
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={"menu.transactions"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx xss={12}>
        <Card className="mb-4">
          <CardBody>
            <AlertError errors={serverError}/>
            <Formik enableReinitialize initialValues={initialValues} onSubmit={onSubmit}
                    validationSchema={propertySchema}>
              {({errors, touched, setFieldValue, setFieldTouched, values}) => (
                <Form className="av-tooltip tooltip-label-right">
                  <FormGroup row>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>عنوان</Label>
                        <Field name={"name"} className={'form-control'}/>
                        {errors.name && touched.name && (
                          <div className="invalid-feedback d-block">
                            {errors.name}
                          </div>
                        )}
                      </FormGroup>
                    </Colxx>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>معاملات متناظر</Label>
                        <FormikReactSelect
                          isMulti
                          options={transactions}
                          getOptionLabel={option => option.name}
                          getOptionValue={option => option.id}
                          name={"transactions"}
                          value={values.transactions??[]}
                          onChange={setFieldValue}
                          onBlur={setFieldTouched}/>
                        {errors.land_types && touched.land_types && (
                          <div className="invalid-feedback d-block">
                            {errors.land_types}
                          </div>
                        )}
                      </FormGroup>
                    </Colxx>
                  </FormGroup>
                  <Button color="primary" type="submit">
                    ثبت
                  </Button>
                </Form>
              )}
            </Formik>
          </CardBody>
        </Card>
      </Colxx>
    </Suspense>
  );
}

export default LandTypeEdit;

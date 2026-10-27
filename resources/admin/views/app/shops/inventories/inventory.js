import React, {Suspense} from "react";
import {Button, Card, CardBody, CardTitle, FormGroup, Label, Row} from "reactstrap";
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import AlertError from "../../../../components/AlertError";
import {Field, Form, Formik} from "formik";
import * as Yup from "yup";
import {adminPathApi, adminRoot} from "../../../../constants/defaultValues";
import {useHistory} from "react-router-dom";

const inventorySchema = Yup.object().shape({
  name: Yup.string()
    .min(2)
    .required(),
});

const Inventory = ({match, edit = false}) => {
  let history = useHistory();
  const [serverError, setServerError] = React.useState({});
  const id = match.params.id ? match.params.id : null;
  const [loading, setLoading] = React.useState(true);
  const [initialValues, setInitialValues] = React.useState({
    name: ''
  });


  React.useEffect(async () => {
    if (id && edit) {
      await axios.get(`${adminPathApi}/inventory/${id}`)
        .then(({data}) => {
          setInitialValues(data);
          setLoading(false);
        })
        .catch((error) => {
          history.push(`${adminRoot}/error`, {
            status: error.response ? error.response.status : 500
          });
        });
    }
  }, []);

  const onSubmit = async (values) => {
    if (id && edit) {
      await axios.put(`${adminPathApi}/inventory/${id}`, values)
        .then(() => {
          history.push(`${adminRoot}/shops/inventory/list`);
        })
        .catch(({response}) => {
          setServerError(response.data.errors);
        });
    } else {
      await axios.post(`${adminPathApi}/inventory`, values)
        .then(() => {
          history.push(`${adminRoot}/shops/inventory/list`);
        })
        .catch(({response}) => {
          setServerError(response.data.errors);
        });
    }
  };

  if (edit && loading) {
    return (<div className={'loading'}/>);
  }


  return <>
    <Suspense fallback={<div className={'loading'}/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={"menu.inventory"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Colxx xss={12}>
        <Card className="mb-4">
          <CardBody>
            <CardTitle>انبار</CardTitle>
            <AlertError errors={serverError}/>
            <Formik enableReinitialize initialValues={initialValues} onSubmit={onSubmit}
                    validationSchema={inventorySchema}>
              {({errors, touched, setFieldValue, setFieldTouched, values}) => (
                <Form className="av-tooltip tooltip-label-right">
                  <FormGroup row>
                    <Colxx md={6}>
                      <FormGroup>
                        <Label>نام انبار</Label>
                        <Field name={"name"} className={'form-control'}/>
                        {errors.name && touched.name && (
                          <div className="invalid-feedback d-block">
                            {errors.name}
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
  </>
}

export default Inventory;

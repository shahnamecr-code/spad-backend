import React, {Suspense, useState} from "react";
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import {Button, Card, CardBody, CardTitle, FormGroup, Label, Row} from "reactstrap";
import AlertError from "../../../../components/AlertError";
import {Field, Form, Formik} from "formik";
import {adminPathApi, adminRoot} from "../../../../constants/defaultValues";
import {NotificationManager} from "../../../../components/common/react-notifications";
import AreaSelectMap from "../../../../components/map/AreaSelectMap";
import LocationSelectMap from "../../../../components/map/LocationSelectMap";


const Province = ({match, edit = false, history}) => {
  const [serverError, setServerError] = React.useState({});
  const [loading, setLoading] = React.useState(true);
  const id = match.params.id ? match.params.id : null;
  const [initialValues, setInitialValues] = useState({});

  const onSubmit = async (values) => {
    if (edit) {
      await axios.put(`${adminPathApi}/location/province/${id}`, values)
        .then(({data}) => {
          NotificationManager.success(data.message, 'موفقیت');
          history.push(`${adminRoot}/settings/provinces`);
        })
        .catch(({response}) => {
          setServerError(response.data.errors);
        });
    } else {
      await axios.post(`${adminPathApi}/location/province`, values)
        .then(({data}) => {
          NotificationManager.success(data.message, 'موفقیت');
          history.push(`${adminRoot}/settings/provinces`);
        })
        .catch(({response}) => {
          setServerError(response.data.errors);
        });
    }

  };

  React.useEffect(async () => {
    if (id && edit) {
      await axios.get(`${adminPathApi}/location/province/${id}`)
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

  if (edit && loading) {
    return <div className={'loading'}/>
  }


  return (
    <>
      <Suspense fallback={<div className={'loading'}/>}>
        <Row>
          <Colxx xss="12">
            <Breadcrumb heading="menu.provinces" match={match}/>
            <Separator className="mb-5"/>
          </Colxx>
          <Colxx md="12">
            <Card>
              <CardBody>
                <CardTitle>{edit ? 'ویرایش' : 'افزودن'} استان{''}</CardTitle>
                <AlertError errors={serverError}/>
                <Formik enableReinitialize initialValues={initialValues} onSubmit={onSubmit}>
                  {({errors, touched, setFieldValue, setFieldTouched, values}) => (
                    <Form>
                      <Row>
                        <Colxx md="6">
                          <FormGroup>
                            <Label>نام</Label>
                            <Field
                              name="name"
                              className="form-control"
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx className={"d-flex"} md={12}>
                          <div>
                            <FormGroup>
                              <LocationSelectMap
                                label={'انتخاب مرکز استان'}
                                value={(values.lng && values.lat) ? ([values.lng, values.lat]) : undefined}
                                onChange={(data) => {
                                  setFieldValue('lng', data[0]);
                                  setFieldValue('lat', data[1]);
                                }}
                              />
                            </FormGroup>
                          </div>
                          <div className="mx-2">
                            <FormGroup>
                              <AreaSelectMap
                                center={[values.lng, values.lat]}
                                value={values.area}
                                onChange={(data) => {
                                  setFieldValue('area', data.geometry);
                                }}
                              />
                            </FormGroup>
                          </div>
                        </Colxx>
                      </Row>
                      <Row>
                        <Colxx md="12" className={'mt-2'}>
                          <Button type="submit" color={'primary'} className="float-left">ذخیره</Button>
                        </Colxx>
                      </Row>
                    </Form>
                  )}
                </Formik>
              </CardBody>
            </Card>
          </Colxx>
        </Row>
      </Suspense>
    </>
  )
}

export default Province;

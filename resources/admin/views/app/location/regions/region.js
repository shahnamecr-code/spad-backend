import React, {Suspense, useState} from "react";
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import {Button, Card, CardBody, CardTitle, FormGroup, Label, Row} from "reactstrap";
import AlertError from "../../../../components/AlertError";
import {Field, Form, Formik} from "formik";
import {adminPathApi, adminRoot} from "../../../../constants/defaultValues";
import {NotificationManager} from "../../../../components/common/react-notifications";
import axios from "axios";
import LocationSelectMap from "../../../../components/map/LocationSelectMap";
import AreaSelectMap from "../../../../components/map/AreaSelectMap";
import ProvinceSelect from "../../../../components/selects/ProvinceSelect";
import CitySelect from "../../../../components/selects/CitySelect";

const Region = ({match, edit = false, history}) => {
  const [serverError, setServerError] = React.useState({});
  const [loading, setLoading] = React.useState(true);
  const id = match.params.id ? match.params.id : null;
  const [initialValues, setInitialValues] = useState({
    name: '',
    lat: '',
    lng: '',
    province_id: '',
    province: '',
    city_id: '',
    city: '',
  });

  const onSubmit = async (values) => {
    if (edit) {
      await axios.put(`${adminPathApi}/location/region/${id}`, values)
        .then(({data}) => {
          NotificationManager.success(data.message, 'موفقیت');
          history.push(`${adminRoot}/settings/regions`);
        })
        .catch(({response}) => {
          setServerError(response.data.errors);
        });
    } else {
      await axios.post(`${adminPathApi}/location/region`, values)
        .then(({data}) => {
          NotificationManager.success(data.message, 'موفقیت');
          history.push(`${adminRoot}/settings/regions`);
        })
        .catch(({response}) => {
          setServerError(response.data.errors);
        });
    }

  };

  React.useEffect(async () => {
    if (id && edit) {
      await axios.get(`${adminPathApi}/location/region/${id}`)
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
            <Breadcrumb heading="menu.regions" match={match}/>
            <Separator className="mb-5"/>
          </Colxx>
          <Colxx md="12">
            <Card>
              <CardBody>
                <CardTitle>{edit ? 'ویرایش' : 'افزودن'} منطقه{''}</CardTitle>
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
                        <Colxx md={3}>
                          <ProvinceSelect
                            name={'province'}
                            //field_name={'province_id'}
                            value={values.province}
                            setFieldValue={setFieldValue}
                          />
                        </Colxx>
                        <Colxx md={3}>
                          <CitySelect
                            name={'city'}
                            field_name={'city_id'}
                            value={values.city}
                            setFieldValue={setFieldValue}
                            province_id={values.province?.id}
                          />
                        </Colxx>
                        <Colxx md="12">
                          <FormGroup>
                            <LocationSelectMap
                              label={'انتخاب مرکز محله'}
                              value={(values.lng && values.lat) ?
                                ([values.lng, values.lat]) : (values.city ? [values.city.lng, values.city.lat] : undefined)}
                              onChange={(data) => {
                                setFieldValue('lng', data[0]);
                                setFieldValue('lat', data[1]);
                              }}
                            />
                          </FormGroup>
                        </Colxx>
                        <Colxx md="12">
                          <FormGroup>
                            <AreaSelectMap
                              center={[values.lng, values.lat]}
                              value={values.area}
                              onChange={(data) => {
                                setFieldValue('area', data);
                              }}
                            />
                          </FormGroup>
                        </Colxx>
                      </Row>
                      <Row>
                        <Colxx md="12" className={'mt-2'}>
                          <Button type="submit" color={'primary'}>ذخیره</Button>
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

export default Region;

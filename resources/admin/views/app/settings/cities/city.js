import React, { Suspense, useState } from "react";
import { Colxx, Separator } from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import { Button, Card, CardBody, CardTitle, FormGroup, Label, Row } from "reactstrap";
import AlertError from "../../../../components/AlertError";
import { Field, Form, Formik } from "formik";
import { adminPathApi, adminRoot } from "../../../../constants/defaultValues";
import { NotificationManager } from "../../../../components/common/react-notifications";
import axios from "axios";
import LocationSelectMap from "../../../../components/map/LocationSelectMap";
import AreaSelectMap from "../../../../components/map/AreaSelectMap";

const City = ({ match, edit = false, history }) => {
  const [serverError, setServerError] = React.useState({});
  const [loading, setLoading] = React.useState(true);
  const id = match.params.id || null;
  const [initialValues, setInitialValues] = useState({
    name: "شیراز",
    lat: "",
    lng: "",
    area: null,
  });

  const onSubmit = async (values) => {
    if (!edit || !id) return;
    await axios.put(`${adminPathApi}/location/city/${id}`, { ...values, name: "شیراز" })
      .then(({ data }) => {
        NotificationManager.success(data.message, "موفقیت");
        history.push(`${adminRoot}/settings/cities`);
      })
      .catch(({ response }) => setServerError(response?.data?.errors || {}));
  };

  React.useEffect(() => {
    if (id && edit) {
      axios.get(`${adminPathApi}/location/city/${id}`)
        .then(({ data }) => {
          setInitialValues({ ...data, name: "شیراز" });
          setLoading(false);
        })
        .catch((error) => {
          history.push(`${adminRoot}/error`, { status: error.response ? error.response.status : 500 });
        });
    } else {
      history.replace(`${adminRoot}/settings/cities`);
    }
  }, []);

  if (edit && loading) return <div className="loading" />;

  return (
    <Suspense fallback={<div className="loading" />}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading="menu.cities" match={match} />
          <Separator className="mb-5" />
        </Colxx>
        <Colxx md="12">
          <Card>
            <CardBody>
              <CardTitle>تنظیمات شهر شیراز</CardTitle>
              <AlertError errors={serverError} />
              <Formik enableReinitialize initialValues={initialValues} onSubmit={onSubmit}>
                {({ setFieldValue, values }) => (
                  <Form>
                    <Row>
                      <Colxx md="6">
                        <FormGroup>
                          <Label>شهر</Label>
                          <Field name="name" value="شیراز" disabled className="form-control" />
                          <small className="text-muted">شهر سایت ثابت است و قابل تغییر نیست.</small>
                        </FormGroup>
                      </Colxx>
                      <div>
                        <FormGroup>
                          <LocationSelectMap
                            label="انتخاب مرکز شهر شیراز"
                            value={(values.lng && values.lat) ? [values.lng, values.lat] : undefined}
                            onChange={(data) => {
                              setFieldValue("lng", data[0]);
                              setFieldValue("lat", data[1]);
                            }}
                          />
                        </FormGroup>
                      </div>
                      <div className="mx-2">
                        <FormGroup>
                          <AreaSelectMap
                            center={[values.lng, values.lat]}
                            value={values.area}
                            onChange={(data) => setFieldValue("area", data)}
                          />
                        </FormGroup>
                      </div>
                    </Row>
                    <Row>
                      <Colxx md="12" className="mt-2">
                        <Button type="submit" color="primary" className="float-left">ذخیره</Button>
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
  );
};

export default City;

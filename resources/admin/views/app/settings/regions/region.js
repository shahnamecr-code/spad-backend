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

const Region = ({ match, edit = false, history }) => {
  const [serverError, setServerError] = React.useState({});
  const [loading, setLoading] = React.useState(edit);
  const id = match.params.id || null;
  const [initialValues, setInitialValues] = useState({ name: "", lat: "", lng: "", area: null });

  const onSubmit = async (values) => {
    const request = edit
      ? axios.put(`${adminPathApi}/location/region/${id}`, values)
      : axios.post(`${adminPathApi}/location/region`, values);

    await request
      .then(({ data }) => {
        NotificationManager.success(data.message, "موفقیت");
        history.push(`${adminRoot}/settings/regions`);
      })
      .catch(({ response }) => setServerError(response?.data?.errors || {}));
  };

  React.useEffect(() => {
    if (id && edit) {
      axios.get(`${adminPathApi}/location/region/${id}`)
        .then(({ data }) => {
          setInitialValues(data);
          setLoading(false);
        })
        .catch((error) => history.push(`${adminRoot}/error`, { status: error.response ? error.response.status : 500 }));
    }
  }, []);

  if (loading) return <div className="loading" />;

  return (
    <Suspense fallback={<div className="loading" />}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading="menu.regions" match={match} />
          <Separator className="mb-5" />
        </Colxx>
        <Colxx md="12">
          <Card>
            <CardBody>
              <CardTitle>{edit ? "ویرایش خیابان" : "افزودن خیابان جدید در شیراز"}</CardTitle>
              <AlertError errors={serverError} />
              <Formik enableReinitialize initialValues={initialValues} onSubmit={onSubmit}>
                {({ setFieldValue, values }) => (
                  <Form>
                    <Row>
                      <Colxx md="6">
                        <FormGroup>
                          <Label>نام خیابان</Label>
                          <Field name="name" className="form-control" placeholder="مثلاً عفیف‌آباد" />
                        </FormGroup>
                      </Colxx>
                      <Colxx md="6">
                        <FormGroup>
                          <Label>شهر</Label>
                          <input className="form-control" value="شیراز" disabled readOnly />
                        </FormGroup>
                      </Colxx>
                      <div>
                        <FormGroup>
                          <LocationSelectMap
                            label="انتخاب مرکز خیابان"
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

export default Region;

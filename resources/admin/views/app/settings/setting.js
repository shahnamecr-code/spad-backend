import React, { Suspense, useState } from "react";
import {
  TabContent,
  TabPane,
  Nav,
  NavItem,
  NavLink,
  Row,
  Card,
  CardBody,
  CardTitle,
  FormGroup,
  Label,
  Input,
  CustomInput,
  CardSubtitle,
  CardText,
  Button,
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
} from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { Field, Form, Formik } from "formik";
import {
  FormikCheckboxGroup,
  FormikReactSelect,
  FormikSwitch,
  FormikRadioButtonGroup,
} from "../../../containers/form-validations/FormikFields";
import tabs from "./setting_tab";

import classnames from "classnames";
import CustomAttribute from "../../../components/CustomAttribute";
import { adminPathApi } from "../../../constants/defaultValues";
import { NotificationManager } from "../../../components/common/react-notifications";

const Subs = ({ subs, group, init }) => {
  const save = (values) => {
    axios
      .post(`${adminPathApi}/settings/${group}`, values)
      .then((res) => res.data)
      .then((data) => {
        if (data.status) {
          NotificationManager.success(
            data.message,
            null,
            1000,
            null,
            null,
            "filled"
          );
        } else {
          NotificationManager.error(
            data.message,
            null,
            1000,
            null,
            null,
            "filled"
          );
        }
      })
      .catch((error) => {
        NotificationManager.error(
          "خطایی در ارسال اطلاعات رخ داده است",
          null,
          1000,
          null,
          null,
          "filled"
        );
      });
  };

  return (
    <Formik enableReinitialize initialValues={init} onSubmit={save}>
      {({
        errors,
        touched,
        setFieldValue,
        setFieldTouched,
        values,
        getFieldMeta,
      }) => (
        <Form>
          {subs.map((sub, i) => {
            return (
              <CustomAttribute
                key={i}
                attribute={sub}
                errors={errors}
                touched={touched}
                setFieldValue={setFieldValue}
                setFieldTouched={setFieldTouched}
                values={values}
                getFieldMeta={getFieldMeta}
              />
            );
          })}
          <Button type={"submit"} className="float-left">
            ذخیره تنظیمات
          </Button>
        </Form>
      )}
    </Formik>
  );
};

const Settings = ({ match }) => {
  const [activeTab, setActiveTab] = useState(false);
  const [tabs, setTabs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [init, setInit] = useState({});

  const toggle = (tabid) => {
    if (activeTab !== tabid) setActiveTab(tabid);
  };

  React.useEffect(() => {
    axios
      .get(`${adminPathApi}/settings/all-setting-tabs`)
      .then((res) => res.data)
      .then((data) => {
        setTabs(data);
        setActiveTab(Object.keys(data)[0]);
        setLoading(false);
      });
  }, []);

  React.useEffect(() => {
    if (activeTab) {
      axios
        .get(`${adminPathApi}/settings/${activeTab}`)
        .then((res) => res.data)
        .then((data) => {
          setInit(data ? data : {});
        });
    }
  }, [activeTab]);

  if (loading) {
    return <div className="loading" />;
  }

  return (
    <React.Fragment>
      <Breadcrumb heading="menu.settings" match={match} />
      <Separator className="mb-5" />
      <Row>
        <Colxx md="12">
          <Card>
            <Nav tabs>
              {Object.keys(tabs).map((index) => (
                <NavItem>
                  <NavLink
                    className={classnames({ active: activeTab === index })}
                    onClick={() => {
                      toggle(index);
                    }}
                  >
                    {tabs[index].name}
                  </NavLink>
                </NavItem>
              ))}
            </Nav>
            <CardBody>
              <TabContent activeTab={activeTab}>
                {Object.keys(tabs).map((index) => (
                  <TabPane tabId={index}>
                    <Subs subs={tabs[index].subs} group={index} init={init} />
                  </TabPane>
                ))}
              </TabContent>
            </CardBody>
          </Card>
        </Colxx>
      </Row>
    </React.Fragment>
  );
};

export default Settings;

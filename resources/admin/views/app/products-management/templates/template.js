import React, {Suspense, useEffect} from "react";
import {
  Button,
  Card,
  CardBody,
  Col,
  FormGroup,
  Label,
  Modal,
  ModalBody,
  ModalHeader,
  Nav,
  NavItem,
  NavLink,
  Row,
  TabContent,
  TabPane
} from "reactstrap";
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import axios from "axios";
import {adminPathApi} from "../../../../constants/defaultValues";
import {NotificationManager} from "../../../../components/common/react-notifications";
import {Field, Form, Formik} from "formik";
import AlertError from "../../../../components/AlertError";
import CustomAttribute from "../../../../components/CustomAttribute";
import SearchSelect from "../../../../components/SearchSelect";
import classnames from 'classnames';

const Template = ({match, edit = false}) => {
  const [loading, setLoading] = React.useState(false);
  const [show_attribute_list, setShowAttributeList] = React.useState(false);

  const [validationErrors, setValidationErrors] = React.useState({});
  const [attributes, setAttributes] = React.useState([]);
  const [default_attributes, setDefaultAttributes] = React.useState([]);
  const [attribute_list, setAttributeList] = React.useState([]);
  const [attribute_main_list, setAttributeMainList] = React.useState([]);
  const [init, setInit] = React.useState({})

  const [activeTab, setActiveTab] = React.useState('1');

  const toggle = tab => {
    if(activeTab !== tab) setActiveTab(tab);
  }

  let get_attributes_src;

  useEffect(async () => {
    if(edit){
      get_attributes_src=`${adminPathApi}/template/attributes/${match.params.id}`;
    }else{
      get_attributes_src=`${adminPathApi}/template/attributes`;
    }
    await axios
      .get(
        get_attributes_src,
      )
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        setInit({...init,attributes:data.attributes.values ? data.attributes.values : {},title:data.title?data.title:''})
        setAttributes([...data.attributes.default, ...data.attributes.extra])
      });
  }, []);

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

  const saveTemplate = async (values) => {
    let route;
    if(edit){
      route=`${adminPathApi}/template/${match.params.id}/edit`;
    }else{
      route=`${adminPathApi}/template`;
    }

    await axios.post(route, values)
      .then((response) => {
        if(response.data.status){
          NotificationManager.success(response.data.message,null,1000,null,null,'filled');
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

      }).finally(()=>setLoading(false));
  }

  return (
    <Suspense fallback={<div className="loading"/>}>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={edit ? 'menu.templates.edit' : 'menu.templates.create'} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Row className="mb-4">
        <Colxx xxs="12">
          <Card>
            <CardBody>
              <Row>
                <Col sm="3">
                  <Nav className="nav-pills flex-column" tabs>
                    <NavItem>
                      <NavLink
                        className={classnames({ active: activeTab === '1' })}
                        onClick={() => { toggle('1'); }}
                      >
                        ویژگی های قالب
                      </NavLink>
                    </NavItem>
                    <NavItem>
                      <NavLink
                        className={classnames({ active: activeTab === '2' })}
                        onClick={() => { toggle('2'); }}
                      >
                        More Tabs
                      </NavLink>
                    </NavItem>
                  </Nav>
                </Col>
                <Col sm="9">
                  <TabContent activeTab={activeTab}>
                    <TabPane tabId="1">
                      <Row>
                        <Col sm="12">
                          <Formik
                            initialValues={init}
                            onSubmit={saveTemplate}
                            enableReinitialize
                          >
                            {({errors, touched, setFieldValue, setFieldTouched, values}) => (
                              <Form className="av-tooltip tooltip-label-right">
                                <AlertError errors={validationErrors}/>
                                <FormGroup>
                                  <Label>عنوان قالب</Label>
                                  <Field
                                    className="form-control"
                                    name="title"
                                  />
                                  {errors.title && touched.title && (
                                    <div className="invalid-feedback d-block">
                                      {errors.title}
                                    </div>
                                  )}
                                </FormGroup>
                                {attributes.map((attribute,index) => (
                                  <CustomAttribute key={attribute.code} errors={errors} touched={touched} setFieldValue={setFieldValue}
                                                   setFieldTouched={setFieldTouched} values={values} attribute={attribute} prefix="attributes">
                                    {!attribute.is_default && (
                                      <Button color="danger" outline size="xs" className="mx-1" onClick={()=>{setFieldValue(`attributes.${attribute.code}`,undefined);deleteAttribute(attribute.code)}}>
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
                                        {/*<FormikReactSelect
                              onChange={(key, value) => {
                                // console.log(key)
                                let new_attribute = attribute_main_list.find((i) => i.code == value);
                                if(new_attribute)
                                  setAttributes((attributes)=> [...attributes,new_attribute] )
                                setShowAttributeList(false)
                              }}
                              options={attribute_list}
                            />*/}
                                      </ModalBody>
                                    </Modal>

                                  )}
                                </FormGroup>

                                {!show_attribute_list && (
                                  <Button color="info" onClick={(e) => {
                                    e.preventDefault();
                                    setShowAttributeList(true);
                                    //getAttributesList()
                                  }}>افزودن ویژگی</Button>
                                )}
                                <Button type="submit">
                                  ذخیره
                                </Button>

                              </Form>
                            )}
                          </Formik>
                        </Col>
                      </Row>
                    </TabPane>
                    <TabPane tabId="2">
                      <Row>
                        <Col sm="12">
                          <h4>Tab 2 Contents</h4>
                        </Col>
                      </Row>
                    </TabPane>
                  </TabContent>
                </Col>
              </Row>
            </CardBody>
          </Card>
        </Colxx>
      </Row>
    </Suspense>
  );
}

export default Template;

import React from 'react';
import {Button, Card, CardBody, CardTitle, FormGroup, FormText, Label, Row} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import {connect} from "react-redux";
import {permissionAll} from "../../../redux/permissions/actions";
import {Form, Formik} from "formik";
import Switch from "rc-switch";
import Select from "react-select";
import CategoryPermission from "./category-permission";
import {adminPathApi} from "../../../constants/defaultValues";
import {NotificationManager} from "../../../components/common/react-notifications";
import axios from "axios";
import {FormikReactSelect} from "../../../containers/form-validations/FormikFields";
import { AsyncPaginate } from "react-select-async-paginate";
import {useHistory} from "react-router-dom";

import SearchMultiSelect from "../../../components/SearchMultiSelect";

import CheckboxTree from 'react-checkbox-tree';
import 'react-checkbox-tree/lib/react-checkbox-tree.css';

const OperatorPermission = ({match, currentUser}) => {
  const id = match.params.id ? match.params.id : null;
  const [loading, setLoading] = React.useState(true);
  const [cities, setCities] = React.useState([]);
  const [regions, setRegions] = React.useState([]);
  const [experts, setExperts] = React.useState([]);
  const [serverError, setServerError] = React.useState({});
  const [initialValues, setInitialValues] = React.useState({
    categories: [],
    experts:[],
    cities:[]
  });
  const [expand_categories,setExpandCategories]=React.useState([]);
  const [category_options,setCategoryOptions]=React.useState([]);
  let history = useHistory();
  const loadCities = async (search, prevOptions,{page}) => {
    let source=`${adminPathApi}/location/allCities?page=${page}`
    if (search){
      source+=`&search=${search}`
    }
    let result;

    await axios
      .get(source)
      .then(res=>res.data)
      .then(data=>{
        result=data
      })

    return {
      options: result.data,
      hasMore:result.hasMore,
      additional:{
        page: parseInt(result.page)+1,
      }
    };
  };

  const loadRegions = async (search, prevOptions,{page}) => {
    let source=`${adminPathApi}/location/allRegions?page=${page}`
    if (search){
      source+=`&search=${search}`
    }
    let result;

    await axios
      .get(source)
      .then(res=>res.data)
      .then(data=>{
        result=data
      })

    return {
      options: result.data,
      hasMore:result.hasMore,
      additional:{
        page: parseInt(result.page)+1,
      }
    };
  };

  const loadExperts = async (search, prevOptions,{page}) => {
    let source=`${adminPathApi}/managers/search_experts?user=${match.params.id}&page=${page}`
    if (search){
      source+=`&search=${search}`
    }
    let result;

    await axios
      .get(source)
      .then(res=>res.data)
      .then(data=>{
        result=data
      })

    return {
      options: result.data,
      hasMore:result.hasMore,
      additional:{
        page: parseInt(result.page)+1,
      }
    };
  };

  React.useEffect(async () => {
    await axios.all([
      axios.get(`${adminPathApi}/advertisement-category/getAllCategoriesWithChildren`),
      axios.get(`${adminPathApi}/location/allCities`),
      axios.get(`${adminPathApi}/location/allRegions`),
      axios.get(`${adminPathApi}/operator/getPermissionable/${match.params.id}`)
    ])
      .then(axios.spread((categories,cities,regions,userInfo) => {

          // setInitialValues({...permissionable.data, operator: undefined});
          // setInventoryOption(inventory.data);
          // setPermissions(rolesAndPermissions.data);
          setCategoryOptions(categories.data)
          setCities(cities.data)
          setRegions(regions.data)
          setInitialValues(userInfo.data)
          setLoading(false);
        })
      )
      .catch(error => console.log(error));

  }, []);

  const onSubmitForm = async (values) => {
    await axios.post(`${adminPathApi}/operator/permissions/${id}`, values)
      .then(({data}) => {
        NotificationManager.success(data.message, 'موفقیت');
        history.push("/spad-admin/operators/list");
      })
      .catch(({response}) => {
        setServerError(response.data.errors);
      });
  }

  if (loading) {
    return <div className={'loading'}/>
  }

  return (
    <Colxx xss={12}>
      <Card>
        <CardBody>
          <CardTitle>
            دسترسی ها
          </CardTitle>
          <Formik initialValues={initialValues} onSubmit={onSubmitForm} enableReinitialize>
            {({setFieldValue, setFieldTouched, values, setValues}) =>
              <Form>
                <Row>
                  <Colxx md={12}>
                    <Label>حیطه کاری کارشناس</Label>
                    <CheckboxTree
                      nodes={category_options}
                      checked={values.categories??[]}
                      expanded={expand_categories}
                      onCheck={(checked)=>setFieldValue('categories',checked)}
                      onExpand={(expanded)=>setExpandCategories(expanded)}
                      direction="rtl"
                      showNodeIcon={false}
                      icons={{
                        check: <span className="fa fa-check-square" />,
                        uncheck: <span className="fas fa-square" />,
                        halfCheck: <span className="fa fa-check-square" />,
                        expandClose: <span className="fa fa-chevron-right" />,
                        expandOpen: <span className="fa fa-chevron-down" />,
                        expandAll: <span className="fa fa-plus-square" />,
                        collapseAll: <span className="fa fa-minus-square" />,
                        parentClose: <span className="fa fa-folder" />,
                        parentOpen: <span className="fa fa-folder-open" />,
                        leaf: <span className="fa fa-file" />,
                      }}
                    />
                  </Colxx>
                  <Colxx md={12}>
                    <FormGroup>
                      <Label>شهرهای منتخب</Label>
                      <AsyncPaginate
                        value={values.cities}
                        loadOptions={loadCities}
                        isMulti
                        onChange={(items) => setFieldValue('cities', items)}
                        additional={{
                          page: 1,
                        }}
                        getOptionLabel={option => option.name}
                        getOptionValue={option => option.id}
                      />
                      <FormText>انتخاب شهر جهت تعیین دسترسی مدیران است</FormText>
                    </FormGroup>
                  </Colxx>
                  <Colxx md={12}>
                    <Label>انتخاب کارشناسان</Label>
                    <AsyncPaginate
                      value={values.experts??[]}
                      loadOptions={loadExperts}
                      isMulti
                      onChange={(items) => setFieldValue('experts', items??[])}
                      additional={{
                        page: 1,
                      }}
                      getOptionLabel={option => option.display_name}
                      getOptionValue={option => option.id}/>
                    <FormText>انتخاب کارشناسان جهت تعیین زیرمجموعه سرپرست است</FormText>
                  </Colxx>
                  <Colxx md={12}>
                    <FormGroup>
                      <Label>خیابان‌های منتخب</Label>
                      <AsyncPaginate
                        value={values.regions}
                        loadOptions={loadRegions}
                        isMulti
                        onChange={(items) => setFieldValue('regions', items)}
                        additional={{
                          page: 1,
                        }}
                      />
                      <FormText>انتخاب خیابان جهت تعیین دسترسی کارشناسان است</FormText>
                    </FormGroup>
                  </Colxx>
                </Row>
                <Button color={'primary'} type={'submit'} className="float-left">ثبت</Button>
              </Form>
            }
          </Formik>
        </CardBody>
      </Card>
    </Colxx>
  )
}


const mapStateToProps = ({permissionsRoles, authUser: {currentUser}}) => {
  /*const {loading, errors, permissions} = permissionsRoles;*/
  return {/*permissions, errors, loading,*/ currentUser};
};


export default connect(mapStateToProps, {
  fetchPermission: permissionAll
})(OperatorPermission);

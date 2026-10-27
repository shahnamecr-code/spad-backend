import React, {useEffect, useState} from "react";
import {adminPathApi} from "../../../constants/defaultValues";
import {Button, Row} from "reactstrap";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import {Link} from "react-router-dom";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import AdvertisementList from "./components/AdvertisementList";
// import {Virtuoso} from 'react-virtuoso'
import {connect} from "react-redux";
import {permissionAll} from "../../../redux/permissions/actions";
import {Field, Form, Formik} from "formik";

const SkillAdvertisementList = ({match, history, location, currentUser}) => {
  const [advertisements, setAdvertisements] = useState([])
  const [link, setLink] = useState(`${adminPathApi}/advertisements/list?type=skill`);
  const [selectedItems, setSelectedItems] = useState([]);
  const [lastChecked, setLastChecked] = useState(null);

  const [loading, setLoading] = useState(false)

  let queryParameters = new URLSearchParams(location.search)
  let query = {}
  for (const [key, value] of queryParameters.entries()) {
    query[key] = value
  }

  const [show_modal, setShowModal] = useState(false)
  const [selectedCategory, setSelectedCategory] = useState(query.category ?? 0)
  const [categoryList, setCategoryList] = useState([])

  const [allCategoryFilter, setAllCategoryFilter] = useState({})
  const [searchItems, setSearchItems] = useState([])

  const [queryParams, setQueryParams] = useState(query)

  useEffect(() => {
    let filters_route = `${adminPathApi}/advertisement-category/displayCategoryFilters`
    if (selectedCategory) {
      filters_route += `?category=${selectedCategory}`
    }
    if (allCategoryFilter[selectedCategory]) {
      setCategoryList(allCategoryFilter[selectedCategory]['categories'])
      setSearchItems(allCategoryFilter[selectedCategory]['searchItems'])
    } else {
      setLoading(true)
      axios
        .get(filters_route)
        .then(res => res.data)
        .then(data => {
          setAllCategoryFilter(prev => {
            return {...prev, [selectedCategory]: data};
          })
          setCategoryList(data.categories)
          setSearchItems(data.searchItems ?? [])
          setLoading(false)
        }).catch((e) => {
        if (e.response.status == 450) {
          window.location.replace(e.response.data.url)
        }
      })
    }
  }, [selectedCategory]);
  const getList = (values) => {
    values.cod = "";
    axios
      .get(link ? link : `${adminPathApi}/advertisements/list?type=skill`)
      .then(data => {
        setAdvertisements(data.data.data)
      }).catch((e) => {
      if (e.response.status == 450) {
        window.location.replace(e.response.data.url)
      }
    })
  }
  useEffect(() => {
    setShowModal(false)
    // let queryParameters = new URLSearchParams(location.search)
    // let query = {}
    // for (const [key, value] of queryParameters.entries()) {
    //   query[key] = value
    // }
    // setQueryParams(query)
    // setSelectedCategory(query.category??0)
  }, [location.search])

  return (
    <>
      <Row>
        <Colxx xss="12">
          <div className="text-zero top-right-button-container d-flex flex-wrap">
            <Formik
              enableReinitialize
              initialValues={{
                cod: ''
              }}
              onSubmit={(value) => {
                if (value.cod !== "") {
                  axios
                    .get(`${adminPathApi}/advertisements/find/${value.cod}`)
                    .then(res => setAdvertisements([res.data]))
                    .catch((response) => NotificationManager.error('آگهی با این کد وجود ندارد'))
                }
              }}
            >
              {({errors, touched, setFieldValue, setFieldTouched, values}) => (
                <Form>
                  <div className="position-relative mr-3">
                    <Field className="form-control rounded" name="cod" placeholder="کد آگهی..."/>
                    {advertisements.length !== 1 ?
                      <button type="submit" className="magnify">
                        <i className={"simple-icon-magnifier "}/>
                      </button>
                      :
                      <div onClick={() => getList(values)} className="magnify cursor">
                        <i className={"simple-icon-close "}/>
                      </div>
                    }
                  </div>
                </Form>
              )}
            </Formik>
            <Link to={`add`} className={"btn btn-primary top-right-button"}>
              <i className={'fa fa-plus mr-1'}/>
              افزودن
            </Link>
            <Button className={"btn btn-primary top-right-button"} onClick={(e) => setShowModal(true)}>
              <i className={"iconsminds-filter-2"}/>
              جستجوی پیشرفته
            </Button>
          </div>
          <Breadcrumb heading={"menu.advertisements.list"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Row>
        <AdvertisementList
          advertisements={advertisements}
          setAdvertisements={setAdvertisements}
          link={link}
          setLink={setLink}
          selectedItems={selectedItems}
          setSelectedItems={setSelectedItems}
          lastChecked={lastChecked}
          setLastChecked={setLastChecked}
          type={"skill"}
          user={currentUser}
          history={history}
          show_modal={show_modal}
          setShowModal={setShowModal}
          selectedCategory={selectedCategory}
          setSelectedCategory={setSelectedCategory}
          allCategoryFilter={allCategoryFilter}
          categoryList={categoryList}
          queryParams={queryParams}
          setQueryParams={setQueryParams}
          loading={loading}
          searchItems={searchItems}
          initQueryString={queryParameters.toString()}
        />
      </Row>
    </>
  );
}

const mapStateToProps = ({permissionsRoles, authUser: {currentUser}}) => {
  /*const {loading, errors, permissions} = permissionsRoles;*/
  return {/*permissions, errors, loading,*/ currentUser};
};

export default connect(mapStateToProps, {
  fetchPermission: permissionAll
})(SkillAdvertisementList);

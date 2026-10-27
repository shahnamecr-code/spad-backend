import React, {useEffect, useMemo, useState} from "react";
import {adminPathApi} from "../../../constants/defaultValues";
import {Button, Row} from "reactstrap";
import {Colxx} from "../../../components/common/CustomBootstrap";
import {Link} from "react-router-dom";
import AdvertisementList from "./components/AdvertisementList";
import {connect} from "react-redux";
import {permissionAll} from "../../../redux/permissions/actions";
import {Formik, Form, Field} from "formik";
import {NotificationManager} from "../../../components/common/react-notifications";

const AllAdvertisementList = ({match, history, location, currentUser}) => {
  const [advertisements, setAdvertisements] = useState([]);
  const [link, setLink] = useState(`${adminPathApi}/advertisements/list?type=all`);
  const [selectedItems, setSelectedItems] = useState([]);
  const [lastChecked, setLastChecked] = useState(null);
  const [loading, setLoading] = useState(false);

  const queryParameters = new URLSearchParams(location.search);
  const query = {};
  for (const [key, value] of queryParameters.entries()) {
    query[key] = value;
  }

  const [show_modal, setShowModal] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState(query.category || 0);
  const [categoryList, setCategoryList] = useState([]);
  const [allCategoryFilter, setAllCategoryFilter] = useState({});
  const [searchItems, setSearchItems] = useState([]);
  const [queryParams, setQueryParams] = useState(query);

  useEffect(() => {
    let filtersRoute = `${adminPathApi}/advertisement-category/displayCategoryFilters`;
    if (selectedCategory) filtersRoute += `?category=${selectedCategory}`;

    if (allCategoryFilter[selectedCategory]) {
      setCategoryList(allCategoryFilter[selectedCategory].categories);
      setSearchItems(allCategoryFilter[selectedCategory].searchItems);
      return;
    }

    setLoading(true);
    axios
      .get(filtersRoute)
      .then((res) => res.data)
      .then((data) => {
        setAllCategoryFilter((prev) => ({...prev, [selectedCategory]: data}));
        setCategoryList(data.categories);
        setSearchItems(data.searchItems || []);
        setLoading(false);
      })
      .catch((e) => {
        setLoading(false);
        if (e.response && e.response.status === 402) {
          window.location.replace(e.response.data.url);
        }
      });
  }, [selectedCategory]);

  const getList = () => {
    axios
      .get(`${adminPathApi}/advertisements/list?type=all`)
      .then((res) => res.data)
      .then((data) => {
        setAdvertisements(data.data || []);
        setLink(data.links ? data.links.next : null);
      })
      .catch((e) => {
        if (e.response && e.response.status === 402) {
          window.location.replace(e.response.data.url);
        }
      });
  };

  useEffect(() => {
    setShowModal(false);
  }, [location.search]);

  const activeFilterCount = useMemo(
    () => Object.keys(queryParams || {}).filter((key) => queryParams[key] !== "" && queryParams[key] !== null && queryParams[key] !== undefined).length,
    [queryParams]
  );

  return (
    <div className="spad-ad-list-page">
      <Row>
        <Colxx xs="12">
          <section className="spad-ad-list-hero">
            <div className="spad-ad-list-hero__copy">
              <span className="spad-ad-list-eyebrow">
                <i className="simple-icon-grid" />
                مدیریت محتوای املاک
              </span>
              <h1>لیست آگهی‌ها</h1>
              <p>مشاهده، ویرایش، تأیید، حذف و نردبان آگهی‌ها از یک نمای یکپارچه و سریع.</p>
            </div>

            <div className="spad-ad-list-hero__stats">
              <div className="spad-ad-list-stat">
                <span className="spad-ad-list-stat__icon spad-ad-list-stat__icon--blue"><i className="simple-icon-layers" /></span>
                <div><strong>{advertisements.length}</strong><small>آگهی بارگذاری‌شده</small></div>
              </div>
              <div className="spad-ad-list-stat">
                <span className="spad-ad-list-stat__icon spad-ad-list-stat__icon--green"><i className="simple-icon-check" /></span>
                <div><strong>{selectedItems.length}</strong><small>انتخاب‌شده</small></div>
              </div>
              <div className="spad-ad-list-stat">
                <span className="spad-ad-list-stat__icon spad-ad-list-stat__icon--violet"><i className="iconsminds-filter-2" /></span>
                <div><strong>{activeFilterCount}</strong><small>فیلتر فعال</small></div>
              </div>
            </div>
          </section>
        </Colxx>
      </Row>

      <Row>
        <Colxx xs="12">
          <section className="spad-ad-list-toolbar">
            <Formik
              enableReinitialize
              initialValues={{cod: ""}}
              onSubmit={(values) => {
                if (!values.cod) return;
                axios
                  .get(`${adminPathApi}/advertisements/find/${values.cod}`)
                  .then((res) => {
                    setAdvertisements([res.data]);
                    setLink(null);
                  })
                  .catch(() => NotificationManager.error("آگهی با این کد وجود ندارد"));
              }}
            >
              {({values, resetForm}) => (
                <Form className="spad-ad-code-search">
                  <i className="simple-icon-magnifier" />
                  <Field name="cod" className="form-control" placeholder="جستجو با کد آگهی..." inputMode="numeric" />
                  {advertisements.length === 1 && values.cod ? (
                    <button
                      type="button"
                      className="spad-ad-code-search__clear"
                      onClick={() => {
                        resetForm();
                        getList();
                      }}
                      title="پاک کردن جستجو"
                    >
                      <i className="simple-icon-close" />
                    </button>
                  ) : (
                    <button type="submit" className="spad-ad-code-search__submit">جستجو</button>
                  )}
                </Form>
              )}
            </Formik>

            <div className="spad-ad-list-toolbar__actions">
              <Button color="light" className="spad-ad-list-filter-btn" onClick={() => setShowModal(true)}>
                <i className="iconsminds-filter-2" />
                <span>فیلتر پیشرفته</span>
                {activeFilterCount > 0 ? <b>{activeFilterCount}</b> : null}
              </Button>
              <Link to="add" className="btn btn-primary spad-ad-list-add-btn">
                <i className="simple-icon-plus" />
                افزودن آگهی
              </Link>
            </div>
          </section>
        </Colxx>
      </Row>

      <Row className="spad-ad-list-content-row">
        <AdvertisementList
          advertisements={advertisements}
          setAdvertisements={setAdvertisements}
          link={link}
          setLink={setLink}
          selectedItems={selectedItems}
          setSelectedItems={setSelectedItems}
          lastChecked={lastChecked}
          setLastChecked={setLastChecked}
          type="all"
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
    </div>
  );
};

const mapStateToProps = ({permissionsRoles, authUser: {currentUser}}) => ({currentUser});

export default connect(mapStateToProps, {
  fetchPermission: permissionAll,
})(AllAdvertisementList);

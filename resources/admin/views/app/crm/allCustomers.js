import React, { useEffect, useState } from "react";
import { adminPathApi, adminRoot } from "../../../constants/defaultValues";
import {
  Button,
  Card,
  CustomInput,
  Modal,
  ModalBody,
  ModalHeader,
  Row,
} from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import { Link, NavLink } from "react-router-dom";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import CrmRequestsList from "./components/CrmRequestsList";
import { connect } from "react-redux";
import { permissionAll } from "../../../redux/permissions/actions";

const allCustomers = ({ match, history, location, currentUser }) => {
  const [requests, setRequests] = useState([]);
  const [link, setLink] = useState(`${adminPathApi}/crm/request-list?type=all`);
  const [selectedItems, setSelectedItems] = useState([]);
  const [lastChecked, setLastChecked] = useState(null);

  const [loading, setLoading] = useState(false);

  let queryParameters = new URLSearchParams(location.search);
  let query = {};
  for (const [key, value] of queryParameters.entries()) {
    query[key] = value;
  }

  const [show_modal, setShowModal] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState(query.category ?? 0);
  const [categoryList, setCategoryList] = useState([]);

  const [allCategoryFilter, setAllCategoryFilter] = useState({});
  const [searchItems, setSearchItems] = useState([]);

  const [queryParams, setQueryParams] = useState(query);

  useEffect(() => {
    let filters_route = `${adminPathApi}/advertisement-category/displayCategoryFilters`;
    if (selectedCategory) {
      filters_route += `?category=${selectedCategory}`;
    }
    if (allCategoryFilter[selectedCategory]) {
      setCategoryList(allCategoryFilter[selectedCategory]["categories"]);
      setSearchItems(allCategoryFilter[selectedCategory]["searchItems"]);
    } else {
      setLoading(true);
      axios
        .get(filters_route)
        .then((res) => res.data)
        .then((data) => {
          setAllCategoryFilter((prev) => {
            return { ...prev, [selectedCategory]: data };
          });
          setCategoryList(data.categories);
          setSearchItems(data.searchItems ?? []);
          setLoading(false);
        });
    }
  }, [selectedCategory]);

  useEffect(() => {
    setShowModal(false);
    // let queryParameters = new URLSearchParams(location.search)
    // let query = {}
    // for (const [key, value] of queryParameters.entries()) {
    //   query[key] = value
    // }
    // setQueryParams(query)
    // setSelectedCategory(query.category??0)
  }, [location.search]);

  return (
    <>
      <Row>
        <Colxx xss="12">
          <div className="text-zero top-right-button-container">
            <Link
              to={`add-customer`}
              className={"btn btn-primary top-right-button"}
            >
              <i className={"fa fa-plus mr-1"} />
              افزودن
            </Link>
            {/*            <Button className={"btn btn-primary top-right-button"} onClick={(e) => setShowModal(true)}>
              <i className={"iconsminds-filter-2"}/>
              جستجوی پیشرفته
            </Button>*/}
          </div>
          <Breadcrumb heading={"menu.all-requests"} match={match} />
          <Separator className="mb-5" />
        </Colxx>
      </Row>
      <Row>
        <CrmRequestsList
          requests={requests}
          setRequests={setRequests}
          link={link}
          setLink={setLink}
          selectedItems={selectedItems}
          setSelectedItems={setSelectedItems}
          lastChecked={lastChecked}
          setLastChecked={setLastChecked}
          type={"all"}
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
};

const mapStateToProps = ({ permissionsRoles, authUser: { currentUser } }) => {
  /*const {loading, errors, permissions} = permissionsRoles;*/
  return { /*permissions, errors, loading,*/ currentUser };
};

export default connect(mapStateToProps, {
  fetchPermission: permissionAll,
})(allCustomers);

import axios from "axios";
import React, { useEffect, useState } from "react";
import { connect } from "react-redux";
import { Row } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { permissionAll } from "../../../redux/permissions/actions";
import AdvertisementList from "./components/AdvertisementList";

const RquestRelatedAdvers = ({ match, history, location, currentUser }) => {
  const [advertisements, setAdvertisements] = useState([]);
  const [selectedItems, setSelectedItems] = useState([]);
  const [link, setLink] = useState(
    `/api/crm/${match.params.requestId}/suggest`
  );

  useEffect(() => {
    axios
      .get(`/api/crm/${match.params.requestId}/suggest`)
      .then((res) => res.data)
      .then((data) => {
        setAdvertisements(data.data);
      });
  }, []);

  return (
    <>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={"menu.requestRelatedAdvers"} match={match} />
          <Separator className="mb-5" />
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
          type={"all"}
          user={currentUser}
          history={history}
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
})(RquestRelatedAdvers);

import React from "react";
import {connect} from "react-redux";

const allowedTo = (perms, permissions) => {
  if (!perms) {
    return false;
  }
  if (typeof perms == "object") {
    return permissions.filter((perm) => perms.includes(perm)).length > 0;
  }
  if (permissions.includes(perms)) {
    return true
  }
  return false;
}

const UserCan = ({to = null, currentUser, children}) => {
  let {permissions, roles} = currentUser;

  if (roles.length > 0 && roles.includes('Super Admin')) {
    return <>{children}</>;
  }

  if (permissions.length > 0 && allowedTo(to, permissions)) {
    return <>{children}</>;
  }

  return null;
}

const mapStateToProps = ({authUser: {currentUser}}) => {
  return {currentUser};
}

export {allowedTo};
export default connect(mapStateToProps)(UserCan);

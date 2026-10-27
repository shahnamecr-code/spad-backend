import React from 'react'
import {connect} from "react-redux";
import {useHistory} from "react-router-dom";
import {leaveUser} from "../redux/auth/actions";

const AxiosInterceptor = ({children, currentUser, shopId, leaveUser}) => {
  const history = useHistory();
  React.useEffect(() => {
    const reqInterceptor = axios.interceptors.request.use(
      (requestConfig) => {
        if (currentUser) {
          if (currentUser.shop_id == null) {
            requestConfig.headers.shopId = shopId.value;
          }
        }
        return requestConfig;
      });
    const resInterceptor = axios.interceptors.response.use(
      (response) => (response),
      (error) => {
        switch (error.response.status) {
          case 403:
            history.push("/spad-admin/error", {status: error.response.status});
            break;
          case 401:
            if (error.response.statusText == "Unauthorized") {
              leaveUser();
              history.push("/spad-login", {timeout: true});
            }
            break;
        }
        return Promise.reject(error);
      });

    return () => {
      axios.interceptors.request.eject(reqInterceptor);
      axios.interceptors.response.eject(resInterceptor);
    }
  }, [currentUser, shopId]);

  return children;
}

const mapStateToProps = ({authUser: {currentUser, shopId}}) => {
  return {currentUser, shopId}
}


export default connect(mapStateToProps, {leaveUser})(AxiosInterceptor);

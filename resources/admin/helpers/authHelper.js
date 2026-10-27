import React from 'react';
import {Redirect, Route, useHistory} from 'react-router-dom';
import {isAuthGuardActive} from '../constants/defaultValues';
import axios from "axios";

const ProtectedRoute = ({component: Component, roles = undefined, currentUser, checkUser, userLoading, ...rest}) => {
  const history = useHistory();
  React.useEffect(() => {
    if (!currentUser) {
      checkUser(history)
    }
  }, []);

  const setComponent = (props) => {
    if (isAuthGuardActive) {
      if (currentUser) {
        if (roles) {
          if (roles.includes(currentUser.role)) {
            return <Component {...props} />;
          }
          return (
            <Redirect
              to={{
                pathname: '/unauthorized',
                state: {from: props.location},
              }}
            />
          );
        }
        return <Component {...props} />;
      }
      return <div className={'loading'}/>;
      /*switch (status.status){
        case 401:
          console.log(status);
          return (
            <Redirect
              to={{
                pathname: '/spad-login/login',
                state: { from: props.location },
              }}
            />
          );
        default:
          return <div className={'loading'}/>
      }
*/
    }
    return <Component {...props} />;
  };

  return <Route {...rest} render={setComponent}/>;
};

// eslint-disable-next-line import/prefer-default-export
export default ProtectedRoute;

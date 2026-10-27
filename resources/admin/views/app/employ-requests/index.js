import React, { Suspense } from 'react';
import { Redirect, Route, Switch } from 'react-router-dom';
// import { ProtectedRoute, UserRole } from '../../../helpers/authHelper';

const AllEmployRequestList = React.lazy(() =>
  import(/* webpackChunkName: "all-employ-request-list" */ './all-list')
);

const Dashboards = ({ match }) => (
  <Suspense fallback={<div className="loading" />}>
    <Switch>
      <Redirect exact from={`${match.url}/`} to={`${match.url}/default`} />
      <Route
        path={`${match.url}/list`}
        render={(props) => <AllEmployRequestList {...props} />}
      />
      <Redirect to="/error" />
    </Switch>
  </Suspense>
);
export default Dashboards;

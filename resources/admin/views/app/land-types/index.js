import React, { Suspense } from 'react';
import { Redirect, Route, Switch } from 'react-router-dom';
// import { ProtectedRoute, UserRole } from '../../../helpers/authHelper';

const LandTypeEdit = React.lazy(() =>
  import(/* webpackChunkName: "dashboard-default" */ './land-type-edit')
);
const LandTypeList = React.lazy(() =>
  import(/* webpackChunkName: "dashboard-default" */ './land-type-list')
);


const Dashboards = ({ match }) => (
  <Suspense fallback={<div className="loading" />}>
    <Switch>
      <Redirect exact from={`${match.url}/`} to={`${match.url}/list`} />
      <Route
        path={`${match.url}/list`}
        render={(props) => <LandTypeList {...props} />}
      />
      <Route
        path={`${match.url}/add`}
        render={(props) => <LandTypeEdit prevMatch={match} {...props} />}
      />
      <Route
        path={`${match.url}/edit/:id`}
        render={(props) => <LandTypeEdit prevMatch={match} edit {...props} />}
      />
      <Redirect to="/error" />
    </Switch>
  </Suspense>
);
export default Dashboards;

import React, { Suspense } from 'react';
import { Redirect, Route, Switch } from 'react-router-dom';

const PropertyWrapperEdit = React.lazy(() =>
  import(/* webpackChunkName: "dashboard-default" */ './property-wrapper-edit')
);

const PropertyWrapperList = React.lazy(() =>
  import(/* webpackChunkName: "dashboard-default" */ './property-wrapper-list')
);

const Dashboards = ({ match }) => (
  <Suspense fallback={<div className="loading" />}>
    <Switch>
      <Redirect exact from={`${match.url}/`} to={`${match.url}/list`} />
      <Route
        path={`${match.url}/list`}
        render={(props) => <PropertyWrapperList {...props} />}
      />
      <Route
        path={`${match.url}/add`}
        render={(props) => <PropertyWrapperEdit {...props} />}
      />
      <Route
        path={`${match.url}/edit/:id`}
        render={(props) => <PropertyWrapperEdit edit {...props} />}
      />
      <Redirect to="/error" />
    </Switch>
  </Suspense>
);
export default Dashboards;

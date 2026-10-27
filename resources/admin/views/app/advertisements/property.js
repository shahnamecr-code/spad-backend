import React, { Suspense } from 'react';
import { Redirect, Route, Switch } from 'react-router-dom';

const PropertyEdit = React.lazy(() =>
  import(/* webpackChunkName: "property-edit" */ './property-edit')
);

const PropertyList = React.lazy(() =>
  import(/* webpackChunkName: "property-list" */ './property-list')
);

const PropertyLayout = React.lazy(() =>
  import(/* webpackChunkName: "property-layout" */ './property-layout')
);

const Dashboards = ({ match }) => (
  <Suspense fallback={<div className="loading" />}>
    <Switch>
      <Redirect exact from={`${match.url}/`} to={`${match.url}/list`} />
      <Route
        path={`${match.url}/list`}
        render={(props) => <PropertyList {...props} />}
      />
      <Route
        path={`${match.url}/layout`}
        render={(props) => <PropertyLayout {...props} />}
      />
      <Route
        path={`${match.url}/add`}
        render={(props) => <PropertyEdit {...props} />}
      />
      <Route
        path={`${match.url}/edit/:id`}
        render={(props) => <PropertyEdit edit {...props} />}
      />
      <Redirect to="/error" />
    </Switch>
  </Suspense>
);
export default Dashboards;

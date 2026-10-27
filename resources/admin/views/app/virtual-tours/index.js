import React, { Suspense } from "react";
import { Redirect, Route, Switch } from "react-router-dom";
import "../../../assets/css/sass/virtual-tours.scss";

const VirtualTourManager = React.lazy(() => import("./virtual-tour-manager"));
const VirtualTourEditor = React.lazy(() => import("./virtual-tour-editor"));

const VirtualTours = ({ match }) => (
  <Suspense fallback={<div className="loading" />}>
    <Switch>
      <Redirect exact from={`${match.url}/`} to={`${match.url}/list`} />
      <Route exact path={`${match.url}/list`} render={(props) => <VirtualTourManager {...props} />} />
      <Route exact path={`${match.url}/create`} render={(props) => <VirtualTourManager {...props} startCreate />} />
      <Route exact path={`${match.url}/:id/edit`} render={(props) => <VirtualTourEditor {...props} />} />
      <Redirect to="/error" />
    </Switch>
  </Suspense>
);

export default VirtualTours;

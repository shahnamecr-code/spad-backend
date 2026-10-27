import React, { Suspense } from "react";
import { Redirect, Route, Switch } from "react-router-dom";
import "../../../assets/css/sass/inquiries-admin.scss";

const InquiryServices = React.lazy(() => import("./inquiry-services"));
const InquiryRequests = React.lazy(() => import("./inquiry-requests"));
const InquiryRequest = React.lazy(() => import("./inquiry-request"));
const InquirySettings = React.lazy(() => import("./inquiry-settings"));

const Inquiries = ({ match }) => (
  <Suspense fallback={<div className="loading" />}>
    <Switch>
      <Redirect exact from={`${match.url}/`} to={`${match.url}/requests`} />
      <Route exact path={`${match.url}/services`} render={(props) => <InquiryServices {...props} />} />
      <Route exact path={`${match.url}/requests`} render={(props) => <InquiryRequests {...props} />} />
      <Route exact path={`${match.url}/requests/:id`} render={(props) => <InquiryRequest {...props} />} />
      <Route exact path={`${match.url}/settings`} render={(props) => <InquirySettings {...props} />} />
      <Redirect to="/error" />
    </Switch>
  </Suspense>
);

export default Inquiries;

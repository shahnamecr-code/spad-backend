import React, { Suspense } from "react";
import { Redirect, Route, Switch } from "react-router-dom";
// import { ProtectedRoute, UserRole } from '../../../helpers/authHelper';

const PropertyWrapper = React.lazy(() =>
  import(/* webpackChunkName: "dashboard-default" */ "./property-wrapper")
);

const Property = React.lazy(() =>
  import(/* webpackChunkName: "dashboard-default" */ "./property")
);

const AdvertisementRegister = React.lazy(() =>
  import(
    /* webpackChunkName: "advertisement-register" */ "./advertisement-register"
  )
);

const AdvertisementEdit = React.lazy(() =>
  import(/* webpackChunkName: "advertisement-edit" */ "./advertisement-edit")
);

const AdvertisementList = React.lazy(() =>
  import(/* webpackChunkName: "advertisement-list" */ "./advertisement-list")
);

const UnapprovedAdvertisementList = React.lazy(() =>
  import(/* webpackChunkName: "advertisement-unapproved-list" */ "./advertisement-unapproved-list")
);

const SkillAdvertisementList = React.lazy(() =>
  import(
    /* webpackChunkName: "skill-advertisement-list" */ "./skill-advertisement-list"
  )
);

const AdvertisementCategory = React.lazy(() =>
  import(
    /* webpackChunkName: "advertisement-category" */ "./advertisement-category"
  )
);

const ReportList = React.lazy(() =>
  import(/* webpackChunkName: "advertisement-category" */ "./report-list")
);

const Dashboards = ({ match }) => (
  <Suspense fallback={<div className="loading" />}>
    <Switch>
      <Redirect exact from={`${match.url}/`} to={`${match.url}/wrappers`} />
      <Route
        path={`${match.url}/wrappers`}
        render={(props) => <PropertyWrapper {...props} />}
      />
      <Route
        path={`${match.url}/properties`}
        render={(props) => <Property {...props} />}
      />
      <Route
        path={`${match.url}/add`}
        render={(props) => <AdvertisementRegister {...props} />}
      />
      <Route
        path={`${match.url}/edit/:id`}
        render={(props) => <AdvertisementEdit {...props} />}
      />
      <Route
        path={`${match.url}/adver-list`}
        render={(props) => <AdvertisementList {...props} />}
      />
      <Route
        path={`${match.url}/unapproved`}
        render={(props) => <UnapprovedAdvertisementList {...props} />}
      />
      <Route
        path={`${match.url}/check-list`}
        render={(props) => <SkillAdvertisementList {...props} />}
      />
      <Route
        path={`${match.url}/categories`}
        render={(props) => <AdvertisementCategory {...props} />}
      />
      <Route
        path={`${match.url}/report-list`}
        render={(props) => <ReportList {...props} />}
      />

      <Redirect to="/error" />
    </Switch>
  </Suspense>
);
export default Dashboards;

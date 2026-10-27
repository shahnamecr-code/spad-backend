import React, { Suspense } from "react";
import { Redirect, Route, Switch } from "react-router-dom";

const AddCustomer = React.lazy(() =>
  import(/* webpackChunkName: "add-customer" */ "./add-customer")
);
const AllCustomers = React.lazy(() =>
  import(/* webpackChunkName: "add-customer" */ "./allCustomers")
);
const MyCustomers = React.lazy(() =>
  import(/* webpackChunkName: "add-customer" */ "./myCustomers")
);
const MyRegionCustomers = React.lazy(() =>
  import(/* webpackChunkName: "add-customer" */ "./allMyAreaCustomer")
);
const VisitRequests = React.lazy(() =>
  import(/* webpackChunkName: "add-customer" */ "./visitRequests")
);
const RequestRelatedAdvers = React.lazy(() =>
  import(/* webpackChunkName: "add-customer" */ "./requestRelatedAdvers")
);

const Crm = ({ match }) => {
  return (
    <Suspense fallback={<div className="loading" />}>
      <Switch>
        <Route
          path={`${match.url}/add-customer`}
          render={(props) => <AddCustomer {...props} />}
        />
        <Route
          path={`${match.url}/all-requests`}
          render={(props) => <AllCustomers {...props} />}
        />
        <Route
          path={`${match.url}/my-requests`}
          render={(props) => <MyCustomers {...props} />}
        />
        <Route
          path={`${match.url}/my-region-requests`}
          render={(props) => <MyRegionCustomers {...props} />}
        />
        <Route
          path={`${match.url}/visit-requests`}
          render={(props) => <VisitRequests {...props} />}
        />
        <Route
          path={`${match.url}/requestRelatedAdvers/:requestId`}
          render={(props) => <RequestRelatedAdvers {...props} />}
        />
      </Switch>
    </Suspense>
  );
};

export default Crm;

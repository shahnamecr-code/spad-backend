import React, { Suspense } from "react";
import { Redirect, Route, Switch } from "react-router-dom";
import AddCustomer from "./addCustomer";
import CheckInformation from "./checkInformation";


const Blog = ({ match }) => {
  return (
    <Suspense fallback={<div className="loading" />}>
      <Switch>
        {/* <Redirect exact from={`${match.url}/`} to={`${match.url}/posts/list`}/> */}
        <Route
          path={`${match.url}/add`}
          render={(props) => <AddCustomer {...props} />}
        />
        <Route
          path={`${match.url}/check`}
          render={(props) => <CheckInformation {...props} />}
        />

      </Switch>
    </Suspense>
  );
};

export default Blog;

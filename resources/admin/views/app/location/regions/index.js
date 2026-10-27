import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';


const RegionsList = React.lazy(() =>
  import(/* webpackChunkName: "region-list" */ './region-list')
);

const Region = React.lazy(() =>
  import(/* webpackChunkName: "regions-region" */ './region')
);

const Cities = ({match}) => {
  return (
    <Suspense fallback={<div className="loading"/>}>
      <Switch>
        <Redirect
          exact
          from={`${match.url}/`}
          to={`${match.url}/list`}
        />
        <Route
          path={`${match.url}/list`}
          render={(props) => <RegionsList {...props} />}
        />
        <Route
          path={`${match.url}/create`}
          render={(props) => <Region {...props} />}
        />
        <Route
          path={`${match.url}/edit/:id`}
          render={(props) => <Region edit {...props} />}
        />
        <Redirect to="/error"/>
      </Switch>
    </Suspense>
  );
};

export default Cities;

import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';


const ProvincesList = React.lazy(() =>
  import(/* webpackChunkName: "province-list" */ './province-list')
);

const Province = React.lazy(() =>
  import(/* webpackChunkName: "provinces_province" */ './province')
);

const ProvinceCitiesList = React.lazy(() =>
  import(/* webpackChunkName: "province_cities_list" */ './cities-list')
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
          render={(props) => <ProvincesList {...props} />}
        />
        <Route
          path={`${match.url}/create`}
          render={(props) => <Province {...props} />}
        />
        <Route
          path={`${match.url}/edit/:id`}
          render={(props) => <Province edit {...props} />}
        />
        <Route
          path={`${match.url}/cities/:id`}
          render={(props) => <ProvinceCitiesList {...props} />}
        />
        <Redirect to="/error"/>
      </Switch>
    </Suspense>
  );
};

export default Cities;

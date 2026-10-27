import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';

// import { ProtectedRoute, UserRole } from '../../helpers/authHelper';
const Regions = React.lazy(() =>
  import(/* webpackChunkName: "regions" */ './regions')
);
const Cities = React.lazy(() =>
  import(/* webpackChunkName: "cities" */ './cities')
);
const Provinces = React.lazy(() =>
  import(/* webpackChunkName: "provinces" */ './provinces')
);


const Settings = ({match}) => {
  return (
    <Suspense fallback={<div className="loading"/>}>
      <Switch>
        <Route
          path={`${match.url}/regions`}
          render={(props) => <Regions {...props} />}
        />
        <Route
          path={`${match.url}/cities`}
          render={(props) => <Cities {...props} />}
        />
        <Route
          path={`${match.url}/provinces`}
          render={(props) => <Provinces {...props}/>}
        />
        <Redirect to="/error"/>
      </Switch>
    </Suspense>
  );
};

export default Settings;

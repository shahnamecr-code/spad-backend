import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';
import SidebarList from './Sidebar/Sidebar-list';

// import { ProtectedRoute, UserRole } from '../../helpers/authHelper';
const Setting = React.lazy(() =>
  import(/* webpackChunkName: "settings-setting" */ './setting')
);
const Regions = React.lazy(() =>
  import(/* webpackChunkName: "regions" */ './regions')
);
const Cities = React.lazy(() =>
  import(/* webpackChunkName: "cities" */ './cities')
);
const Sidebar = React.lazy(() =>
  import(/* webpackChunkName: "provinces" */ './Sidebar/Sidebar')
);


const Settings = ({match}) => {
  return (
    <Suspense fallback={<div className="loading"/>}>
      <Switch>
        <Route
          path={`${match.url}/main`}
          render={(props) => <Setting {...props} />}
        />
        <Route
          path={`${match.url}/regions`}
          render={(props) => <Regions {...props} />}
        />
        <Route
          path={`${match.url}/cities`}
          render={(props) => <Cities {...props} />}
        />
        <Route
          path={`${match.url}/sidebar-list`}
          render={(props) => <SidebarList {...props}/>}
        />
        <Route
          path={`${match.url}/sidebar-post`}
          render={(props) => <Sidebar {...props}/>}
        />
        <Redirect to="/error"/>
      </Switch>
    </Suspense>
  );
};

export default Settings;

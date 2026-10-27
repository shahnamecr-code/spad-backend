import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';

const GroupAttributesList = React.lazy(() =>
  import(/* webpackChunkName: "group-attributes-list" */ './group-attributes-list')
);

const GroupAttributes = ({match}) => (
  <Suspense fallback={<div className="loading"/>}>
    <Switch>
      <Redirect exact from={`${match.url}/`} to={`${match.url}/list`}/>
      <Route
        path={`${match.url}/list`}
        render={(props) => <GroupAttributesList {...props} />}
      />
      <Redirect to="/error" />
    </Switch>
  </Suspense>
);
export default GroupAttributes;

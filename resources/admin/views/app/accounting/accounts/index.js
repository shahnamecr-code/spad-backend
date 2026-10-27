import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';

const AccountsList = React.lazy(() =>
  import(/* webpackChunkName: "accounts-list" */ './accounts-list')
);

const Accounts = ({match}) => {
  return (
    <Suspense fallback={<div className="loading"/>}>
      <Switch>
        <Redirect exact from={`${match.url}/`} to={`${match.url}/list`}/>
        <Route
          path={`${match.url}/list`}
          render={(props) => <AccountsList {...props} />}
        />
      </Switch>
    </Suspense>
  )
}

export default Accounts;

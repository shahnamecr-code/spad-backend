import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';

const PaymentsList = React.lazy(() =>
  import(/* webpackChunkName: "accounts-list" */ './payments-list')
);

const Accounts = ({match, action}) => {
  return (
    <Suspense fallback={<div className="loading"/>}>
      <Switch>
        <Redirect exact from={`${match.url}/`} to={`${match.url}/list`}/>
        <Route
          path={`${match.url}/list`}
          render={(props) => <PaymentsList action={action} {...props} />}
        />
      </Switch>
    </Suspense>
  )
}

export default Accounts;

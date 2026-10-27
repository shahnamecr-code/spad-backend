import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';

const Accounts = React.lazy(() =>
  import(/* webpackChunkName: "accounts" */ './accounts')
);
const Payments = React.lazy(() =>
  import(/* webpackChunkName: "payments" */ './payments')
);
const Factors = React.lazy(() => import(/* webpackChunkName: "factors" */ './factors'));

const Banks = React.lazy(() =>
  import(/* webpackChunkName: "banks" */ './banks')
);

const Accounting = ({match}) => {
  return (
    <Suspense fallback={<div className="loading"/>}>
      <Switch>
        <Redirect exact from={`${match.url}/`} to={`${match.url}/accounts`}/>
        <Route
          path={`${match.url}/accounts`}
          render={(props) => <Accounts {...props} />}
        />
        <Route
          path={`${match.url}/buy-payments`}
          render={(props) => <Payments action="buy" {...props} />}
        />
        <Route
          path={`${match.url}/sell-payments`}
          render={(props) => <Payments action="sell" {...props} />}
        />
        <Route
          path={`${match.url}/factor`}
          render={(props) => <Factors {...props} />}
        />
        <Route
          path={`${match.url}/banks`}
          render={(props) => <Banks {...props} />}
        />
      </Switch>
    </Suspense>
  )
}

export default Accounting;

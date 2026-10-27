import React, { Suspense } from 'react';
import { Redirect, Route, Switch } from 'react-router-dom';
// import { ProtectedRoute, UserRole } from '../../../helpers/authHelper';

const TransactionEdit = React.lazy(() =>
  import(/* webpackChunkName: "dashboard-default" */ './transaction-edit')
);
const TransactionList = React.lazy(() =>
  import(/* webpackChunkName: "dashboard-default" */ './transaction-list')
);


const Dashboards = ({ match }) => (
  <Suspense fallback={<div className="loading" />}>
    <Switch>
      <Redirect exact from={`${match.url}/`} to={`${match.url}/list`} />
      <Route
        path={`${match.url}/list`}
        render={(props) => <TransactionList {...props} />}
      />
      <Route
        path={`${match.url}/add`}
        render={(props) => <TransactionEdit prevMatch={match} {...props} />}
      />
      <Route
        path={`${match.url}/edit/:id`}
        render={(props) => <TransactionEdit prevMatch={match} edit {...props} />}
      />
      <Redirect to="/error" />
    </Switch>
  </Suspense>
);
export default Dashboards;

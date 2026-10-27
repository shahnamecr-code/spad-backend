import React, { Suspense } from "react";
import { Redirect, Route, Switch } from "react-router-dom";

const WalletList = React.lazy(() => import(/* webpackChunkName: "wallet-list" */ "./wallet-list"));
const WalletUser = React.lazy(() => import(/* webpackChunkName: "wallet-user" */ "./wallet-user"));
const WalletTransactions = React.lazy(() => import(/* webpackChunkName: "wallet-transactions" */ "./wallet-transactions"));
const WalletSettings = React.lazy(() => import(/* webpackChunkName: "wallet-settings" */ "./wallet-settings"));

const Wallets = ({ match }) => (
  <Suspense fallback={<div className="loading" />}>
    <Switch>
      <Redirect exact from={`${match.url}/`} to={`${match.url}/list`} />
      <Route path={`${match.url}/list`} render={(props) => <WalletList {...props} />} />
      <Route path={`${match.url}/transactions`} render={(props) => <WalletTransactions {...props} />} />
      <Route path={`${match.url}/settings`} render={(props) => <WalletSettings {...props} />} />
      <Route path={`${match.url}/user/:id`} render={(props) => <WalletUser {...props} />} />
    </Switch>
  </Suspense>
);

export default Wallets;

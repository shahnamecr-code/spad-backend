import React, { Suspense } from "react";
import { Redirect, Route, Switch } from "react-router-dom";

const RaffleList = React.lazy(() => import("./raffle-list"));
const RaffleForm = React.lazy(() => import("./raffle-form"));
const RaffleTickets = React.lazy(() => import("./raffle-tickets"));
const RaffleSettings = React.lazy(() => import("./raffle-settings"));

const Raffles = ({ match }) => (
  <Suspense fallback={<div className="loading" />}>
    <Switch>
      <Redirect exact from={`${match.url}/`} to={`${match.url}/list`} />
      <Route exact path={`${match.url}/list`} render={(props) => <RaffleList {...props} />} />
      <Route exact path={`${match.url}/create`} render={(props) => <RaffleForm {...props} />} />
      <Route exact path={`${match.url}/edit/:id`} render={(props) => <RaffleForm {...props} edit />} />
      <Route exact path={`${match.url}/tickets/:id`} render={(props) => <RaffleTickets {...props} />} />
      <Route exact path={`${match.url}/settings`} render={(props) => <RaffleSettings {...props} />} />
      <Redirect to="/error" />
    </Switch>
  </Suspense>
);

export default Raffles;

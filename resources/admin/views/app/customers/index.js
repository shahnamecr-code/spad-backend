import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from "react-router-dom";

const CustomersList = React.lazy(() => import(/* webpackChunkName: "customers-list" */ "./customers-list"))
const Customer = React.lazy(() => import(/* webpackChunkName: "customer" */ "./customer"))
// const Addresses = React.lazy(() => import(/* webpackChunkName: "addresses" */ "./addresses"))
// const CustomerGroup = React.lazy(() => import(/* webpackChunkName: "customer-group" */ "./customer-group"));

const Customers = ({match}) => {
  return (
    <Suspense fallback={<div className="loading"/>}>
      <Switch>
        <Redirect exact from={`${match.url}/`} to={`${match.url}/list`}/>
        <Route path={`${match.url}/list`} render={(props) => <CustomersList {...props}/>}/>
        <Route path={`${match.url}/create`} render={(props) => <Customer {...props}/>}/>
        <Route path={`${match.url}/edit/:id`} render={(props) => <Customer edit{...props}/>}/>
        // <Route path={`${match.url}/address/:id`} render={(props) => <Addresses {...props}/>}/>
        // <Route path={`${match.url}/customer-group`} render={(props) => <CustomerGroup {...props}/>}/>
      </Switch>
    </Suspense>
  )
}

export default Customers;

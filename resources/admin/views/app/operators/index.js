import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from "react-router-dom";

const OperatorsList = React.lazy(() => import(/* webpackChunkName: "operators-list" */ "./operators-list"))
const Operator = React.lazy(() => import(/* webpackChunkName: "operator" */ "./operator"))
const OperatorPermission = React.lazy(() => import(/* webpackChunkName: "operator-permission" */ "./operator-permission"))

const Operators = ({match}) => {
  return (
    <Suspense fallback={<div className="loading"/>}>
      <Switch>
        <Redirect exact from={`${match.url}/`} to={`${match.url}/list`} />
        <Route path={`${match.url}/list`} render={(props) => <OperatorsList {...props}/>}/>
        <Route path={`${match.url}/create`} render={(props) => <Operator {...props}/>}/>
        <Route path={`${match.url}/permission/:id`} render={(props) => <OperatorPermission {...props}/>}/>
        <Route path={`${match.url}/edit/:id`} render={(props) => <Operator edit {...props}/>}/>
      </Switch>
    </Suspense>
  );
};

export default Operators;

import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from "react-router-dom";

const InventoriesList = React.lazy(() => import(/* webpackChunkName: "inventories-list" */  './inventories-list'))
const Inventory = React.lazy(() => import(/* webpackChunkName: "inventory" */  './inventory'))


const Inventories = ({match}) => {
  return (
    <Suspense fallback={<div className="loading"/>}>
      <Switch>
        <Redirect exact from={`${match.url}/`} to={`${match.url}/list`}/>
        <Route path={`${match.url}/list`} render={(props) => <InventoriesList {...props} />}/>
        <Route path={`${match.url}/create`} render={(props) => <Inventory {...props}/>}/>
        <Route path={`${match.url}/edit/:id`} render={(props) => <Inventory edit {...props}/>}/>
      </Switch>
    </Suspense>
  )
}

export default Inventories;

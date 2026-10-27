import React, {Suspense} from 'react';
import {Route} from 'react-router-dom';
import Shop from "./shop";
import ShopsList from "./shops-list";

const Inventories = React.lazy(() => import(/* webpackChunkName: "inventories" */'./inventories'));


const Shops = ({match}) => {
  return (
    <Suspense fallback={<div className="loading"/>}>
      <Route
        path={`${match.url}/list`}
        render={(props) => <ShopsList {...props} />}
      />
      <Route
        path={`${match.url}/create`}
        render={(props) => <Shop {...props}/>}
      />
      <Route
        path={`${match.url}/edit/:id`}
        render={(props) => <Shop edit {...props}/>}
      />
      <Route
        path={`${match.url}/inventory`}
        render={(props) => <Inventories {...props}/>}
      />
    </Suspense>
  )
}

export default Shops;

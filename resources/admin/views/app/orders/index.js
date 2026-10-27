import React, {Suspense} from 'react';
import {Route, Switch} from "react-router-dom";
import Order from "./order";

const PromoCodes = React.lazy(() => import(/* webpackChunkName: "promo-code" */'./promo-code'));
const Cart = React.lazy(() => import(/* webpackChunkName: "cart" */'./cart'));
const OrdersList = React.lazy(() => import(/* webpackChunkName: "orders-list" */'./orders-list'));

const Orders = ({match}) => {
  return (
    <Suspense fallback={<div className="loading"/>}>
      <Switch>
        <Route
          path={`${match.url}/list`}
          render={(props) => <OrdersList {...props} />}
        />
        <Route
          path={`${match.url}/:id`}
          render={(props) => <Order {...props} />}
        />
        <Route
          path={`${match.url}/promo-codes`}
          render={(props) => <PromoCodes {...props} />}
        />
        <Route
          path={`${match.url}/cart`}
          render={(props) => <Cart {...props} />}
        />
      </Switch>
    </Suspense>
  );
};

export default Orders;

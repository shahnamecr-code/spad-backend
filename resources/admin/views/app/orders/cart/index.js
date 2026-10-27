import React, {Suspense} from 'react';
import {Route, Switch} from "react-router-dom";

const AddToCart = React.lazy(() => import(/* webpackChunkName: "add-to-cart" */'./add-to-cart'));

const Cart = ({match}) => {
  return (
    <Suspense fallback={<div className="loading"/>}>
      <Switch>
        <Route
          path={`${match.url}/addToCart`}
          render={(props) => <AddToCart {...props} />}
        />
      </Switch>
    </Suspense>
  )
};

export default Cart;

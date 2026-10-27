import React, {Suspense} from "react";
import {Route, Switch} from "react-router-dom";

const SellFactor = React.lazy(() => import(/* webpackChunkName: "sellFactor" */ './SellFactor'));
const BuyFactor = React.lazy(() => import(/* webpackChunkName: "buyFactor" */ './BuyFactor'));
const FactorList = React.lazy(() => import(/* webpackChunkName: "buyFactorList" */ './FactorList'));
const FactorPrint = React.lazy(() => import(/* webpackChunkName: "factorPrint" */ './FactorPrint'));

const Factors = ({match}) => {
  return (
    <Suspense fallback={<div className="loading"/>}>
      <Switch>
        <Route
          path={`${match.url}/buy-list`}
          render={(props) => <FactorList type={'buy'} {...props} />}
        />
        <Route
          path={`${match.url}/sell-list`}
          render={(props) => <FactorList type={'sell'} {...props} />}
        />
        <Route
          exact
          path={`${match.url}/buy`}
          render={(props) => <BuyFactor {...props} />}
        />
        <Route
          path={`${match.url}/buy/:id`}
          render={(props) => <BuyFactor edit {...props} />}
        />
        <Route
          exact
          path={`${match.url}/sell`}
          render={(props) => <SellFactor {...props} />}
        />
        <Route
          path={`${match.url}/sell/:id`}
          render={(props) => <SellFactor edit{...props} />}
        />
        <Route
          path={`${match.url}/print/:id`}
          render={(props) => <FactorPrint edit{...props} />}
        />
      </Switch>
    </Suspense>
  )
}

export default Factors;

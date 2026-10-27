import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';

const BanksList = React.lazy(() =>
  import(/* webpackChunkName: "banks-list" */ './banks-list')
);

const Banks = ({match}) => {
  return (
    <Suspense fallback={<div className="loading"/>}>
      <Switch>
        <Redirect exact from={`${match.url}/`} to={`${match.url}/list`}/>
        <Route
          path={`${match.url}/list`}
          render={(props) => <BanksList {...props} />}
        />
      </Switch>
    </Suspense>
  )
}

export default Banks;

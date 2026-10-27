import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';


const CitiesList = React.lazy(() =>
  import(/* webpackChunkName: "cities" */ './city-list')
);

const City = React.lazy(() =>
  import(/* webpackChunkName: "cities-city" */ './city')
);

const Cities = ({match}) => {
  return (
    <Suspense fallback={<div className="loading"/>}>
      <Switch>
        <Redirect
          exact
          from={`${match.url}/`}
          to={`${match.url}/list`}
        />
        <Route
          path={`${match.url}/list`}
          render={(props) => <CitiesList {...props} />}
        />
        <Route
          path={`${match.url}/create`}
          render={(props) => <City {...props} />}
        />
        <Route
          path={`${match.url}/edit/:id`}
          render={(props) => <City edit {...props} />}
        />
        <Redirect to="/error"/>
      </Switch>
    </Suspense>
  );
};

export default Cities;

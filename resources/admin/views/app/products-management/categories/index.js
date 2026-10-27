import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';

const CategoryList = React.lazy(() =>
  import(/* webpackChunkName: "category-list" */ './category-list')
);
const Category = React.lazy(() =>
  import(/* webpackChunkName: "category" */ './category')
);

const Categories = ({match}) => (
  <Suspense fallback={<div className="loading"/>}>
    <Switch>
      <Redirect exact from={`${match.url}/`} to={`${match.url}/list`} />
      <Route
        path={`${match.url}/list`}
        render={(props) => <CategoryList {...props} />}
      />
      <Route
        path={`${match.url}/create`}
        render={(props) => <Category {...props} />}
      />
      <Route
        path={`${match.url}/edit/:id`}
        render={(props) => <Category edit {...props} />}
      />
      <Redirect to="/error" />
    </Switch>
  </Suspense>
);
export default Categories;

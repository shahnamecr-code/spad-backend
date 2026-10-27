import React, { Suspense } from 'react';
import { Redirect, Route, Switch } from 'react-router-dom';
import AdvertisementCategoryFilter from "./advertisement-category-filter";

const AdvertisementCategoryList = React.lazy(() =>
  import(/* webpackChunkName: "advertisement-category-list" */ './advertisement-category-list')
);

const AdvertisementCategoryEdit = React.lazy(() =>
  import(/* webpackChunkName: "advertisement-category-edit" */ './advertisement-category-edit')
);

const AdvertisementCategoryLayout = React.lazy(() =>
  import(/* webpackChunkName: "advertisement-category-layout" */ './advertisement-category-layout')
);

const AdvertisementSingleLayout = React.lazy(() =>
  import(/* webpackChunkName: "advertisement-single-layout" */ './advertisement-single-layout')
);

const AdvertisementCategoryAddLayout = React.lazy(() =>
  import(/* webpackChunkName: "advertisement-category-layout-add" */ './advertisement-category-layout-add')
);

const AdvertisementsListShowInPanel = React.lazy(() =>
  import(/* webpackChunkName: "advertisement-list-show-in-panel" */ './advertisement-list-show-in-panel')
);

const AdvertisementsCategoryFilter = React.lazy(() =>
  import(/* webpackChunkName: "advertisement-category-filter" */ './advertisement-category-filter')
);

const AdvertisementsCategoryHomeFilter = React.lazy(() =>
  import(/* webpackChunkName: "advertisement-category-filter" */ './advertisement-category-home-filter')
);

const AdvertisementsCategoryCrmLayout = React.lazy(() =>
  import(/* webpackChunkName: "advertisement-category-crm-layout" */ './advertisement-category-crm-layout')
);

const Dashboards = ({ match }) => (
  <Suspense fallback={<div className="loading" />}>
    <Switch>
      <Redirect exact from={`${match.url}/`} to={`${match.url}/list`} />
      <Route
        path={`${match.url}/list`}
        render={(props) => <AdvertisementCategoryList fromRoute={match.url} {...props} />}
      />
      <Route
        path={`${match.url}/add`}
        render={(props) => <AdvertisementCategoryEdit fromRoute={match.url} {...props} />}
      />
      <Route
        path={`${match.url}/edit/:id`}
        render={(props) => <AdvertisementCategoryEdit fromRoute={match.url} edit {...props} />}
      />
      <Route
        path={`${match.url}/layout/edit/:layout`}
        render={(props) => <AdvertisementCategoryAddLayout fromRoute={match.url} edit {...props} />}
      />
      <Route
        path={`${match.url}/panel-list/:id`}
        render={(props) => <AdvertisementsListShowInPanel fromRoute={match.url} edit {...props} />}
      />
      <Route
        path={`${match.url}/filters/:id`}
        render={(props) => <AdvertisementCategoryFilter fromRoute={match.url} edit {...props} />}
      />
      <Route
        path={`${match.url}/crm-layout/:id`}
        render={(props) => <AdvertisementsCategoryCrmLayout fromRoute={match.url} edit {...props} />}
      />
      <Route
        path={`${match.url}/home-filters/:id`}
        render={(props) => <AdvertisementsCategoryHomeFilter fromRoute={match.url} edit {...props} />}
      />
      <Route
        path={`${match.url}/layout/:id/add`}
        render={(props) => <AdvertisementCategoryAddLayout fromRoute={match.url} {...props} />}
      />
      <Route
        path={`${match.url}/layout/:id`}
        render={(props) => <AdvertisementCategoryLayout fromRoute={match.url} edit {...props} />}
      />
      <Route
        path={`${match.url}/show/:id`}
        render={(props) => <AdvertisementSingleLayout fromRoute={match.url} edit {...props} />}
      />
      <Route
        path={`${match.url}/subCategories/add/:parent`}
        render={(props) => <AdvertisementCategoryEdit fromRoute={match.url} sub {...props} />}
      />
      <Route
        path={`${match.url}/subCategories/:parent`}
        render={(props) => <AdvertisementCategoryList fromRoute={match.url} sub {...props} />}
      />
      <Redirect to="/error" />
    </Switch>
  </Suspense>
);
export default Dashboards;


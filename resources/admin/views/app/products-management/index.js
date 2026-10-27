import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';

const Attributes = React.lazy(() =>
  import(/* webpackChunkName: "attributes" */ './attributes')
);
const GroupAttributes = React.lazy(() =>
  import(/* webpackChunkName: "attributes" */ './group-attributes')
);
const Products = React.lazy(() =>
  import(/* webpackChunkName: "products" */ './products')
);
const Templates = React.lazy(() =>
    import(/* webpackChunkName: "templates" */ './templates')
);
const Categories = React.lazy(() =>
  import(/* webpackChunkName: "categories" */ './categories')
);

const ProductsManagement = ({ match }) => (
    <Suspense fallback={<div className="loading" />}>
        <Switch>
            <Redirect exact from={`${match.url}/`} to={`${match.url}/products`} />
            <Route
                path={`${match.url}/products`}
                render={(props) => <Products {...props} />}
            />
            <Route
                path={`${match.url}/templates`}
                render={(props) => <Templates {...props} />}
            />
            <Route
                path={`${match.url}/attributes`}
                render={(props) => <Attributes {...props} />}
            />
            <Route
              path={`${match.url}/group-attributes`}
              render={(props) => <GroupAttributes {...props} />}
            />
          <Route
            path={`${match.url}/categories`}
            render={(props) => <Categories {...props} />}
          />
            <Redirect to="/error" />
        </Switch>
    </Suspense>
);
export default ProductsManagement;

import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';

const SelectTemplate = React.lazy(() =>
  import(/* webpackChunkName: "select-template" */ './select-template')
);

const Product = React.lazy(() =>
  import(/* webpackChunkName: "product" */ './product')
);

const ShopProductList = React.lazy(() =>
  import(/* webpackChunkName: "shop-product-list" */ './shop-product-list')
);

const ProductEdit = React.lazy(() =>
  import(/* webpackChunkName: "product-edit" */ './product-edit')
);

const TemplateProducts = React.lazy(() =>
  import(/* webpackChunkName: "template-products" */ './template-products')
);

const ShopProduct = React.lazy(() =>
  import(/* webpackChunkName: "shop-product" */ './shop-product')
);


const Products = ({ match }) => (
    <Suspense fallback={<div className="loading" />}>
        <Switch>
            <Redirect exact from={`${match.url}/`} to={`${match.url}/list`} />
            <Route
                path={`${match.url}/list`}
                render={(props) => <ShopProductList {...props} />}
            />
          <Route
            path={`${match.url}/select-template`}
            render={(props) => <SelectTemplate {...props} />}
          />
          {/*create template*/}
          <Route
            path={`${match.url}/template/create`}
            render={(props) => <ProductEdit {...props} />}
          />
          {/*create template*/}
          <Route
            path={`${match.url}/template/edit/:id`}
            render={(props) => <ProductEdit edit {...props} />}
          />
          {/*edit product*/}
          <Route
            path={`${match.url}/:template?/edit/:id`}
            render={(props) => <ShopProduct {...props}/>}
          />
          {/*edit product*/}
          <Route
            path={`${match.url}/:template/create`}
            render={(props) => <ShopProduct {...props}/>}
          />
          <Redirect to="/error" />
        </Switch>
    </Suspense>
);
export default Products;

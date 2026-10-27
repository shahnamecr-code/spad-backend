import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';

const TemplateList = React.lazy(() =>
  import(/* webpackChunkName: "template-list" */ './template-list')
);

const Template = React.lazy(() =>
  import(/* webpackChunkName: "template" */ './template')
);

const ProductList = React.lazy(() =>
  import(/* webpackChunkName: "product-list" */ '../products/product-list')
);

const ProductTemplateTemplateEdit = React.lazy(() =>
  import(/* webpackChunkName: "product-template-edit" */ '../products/product-edit')
);

const TemplateProducts = React.lazy(() =>
  import(/* webpackChunkName: "template-products" */ '../products/template-products')
);

const Templates = ({match}) => (
  <Suspense fallback={<div className="loading"/>}>
    <Switch>
      <Redirect exact from={`${match.url}/`} to={`${match.url}/list`}/>
      <Route
        path={`${match.url}/list`}
        render={(props) => <ProductList {...props} />}
      />
      <Route
        path={`${match.url}/create`}
        render={(props) => <ProductTemplateTemplateEdit {...props} />}
      />
      <Route
        path={`${match.url}/edit/:id`}
        render={(props) => <ProductTemplateTemplateEdit edit {...props} />}
      />
      <Route
        path={`${match.url}/products/:id`}
        render={(props) => <TemplateProducts {...props} />}
      />
      <Redirect to="/error"/>
    </Switch>
  </Suspense>
);
export default Templates;

import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';

const AttributeList = React.lazy(() =>
  import(/* webpackChunkName: "blog-list" */ './attribute-list')
);
const Attribute = React.lazy(() =>
  import(/* webpackChunkName: "blog-list" */ './attribute')
);

const Attributes = ({match}) => (
  <Suspense fallback={<div className="loading"/>}>
        <Switch>
            <Redirect exact from={`${match.url}/`} to={`${match.url}/list`} />
            <Route
                path={`${match.url}/list`}
                render={(props) => <AttributeList {...props} />}
            />
            <Route
                path={`${match.url}/create`}
                render={(props) => <Attribute {...props} />}
            />
            <Route
              path={`${match.url}/edit/:id`}
              render={(props) => <Attribute edit {...props} />}
            />
            <Redirect to="/error" />
        </Switch>
    </Suspense>
);
export default Attributes;

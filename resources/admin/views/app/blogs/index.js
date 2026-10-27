import React, {Suspense} from 'react';
import {Redirect, Route, Switch} from 'react-router-dom';

const BlogCategoryList = React.lazy(()=>import("./blog-category-list"))
const BlogPostList = React.lazy(()=>import("./blog-post-list"))
const BlogPost = React.lazy(()=>import("./blog-post"))


const Blog = ({match}) =>{
  return(
    <Suspense fallback = {<div className = "loading" />}>
      <Switch>
        <Redirect exact from={`${match.url}/`} to={`${match.url}/posts/list`}/>
        <Route
          path = {`${match.url}/categories/list`}
          render = {(props)=><BlogCategoryList {...props}/>}
        />
        <Route
          path = {`${match.url}/posts/list`}
          render = {(props)=><BlogPostList {...props}/>}
        />
        <Route
          path = {`${match.url}/posts/create`}
          render = {(props)=><BlogPost {...props}/>}
        />
        <Route
          path = {`${match.url}/posts/edit/:id`}
          render = {(props)=><BlogPost edit {...props}/>}
        />
      </Switch>
    </Suspense>
  )
}

export default Blog;

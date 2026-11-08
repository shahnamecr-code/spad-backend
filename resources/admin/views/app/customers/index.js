
import React,{Suspense} from "react";
import {Redirect,Route,Switch} from "react-router-dom";
const SpadCustomers=React.lazy(()=>import(/* webpackChunkName: "spad-customers" */ "./spad-customers"));
export default function Customers({match}){
 return <Suspense fallback={<div className="loading"/>}><Switch>
  <Route exact path={match.url} render={props=><SpadCustomers {...props}/>}/>
  <Redirect to={match.url}/>
 </Switch></Suspense>;
}

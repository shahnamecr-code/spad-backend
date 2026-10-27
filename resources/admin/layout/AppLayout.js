import React from 'react';
import {connect} from 'react-redux';
import {withRouter} from 'react-router-dom';

import TopNav from '../containers/navs/Topnav';
import Sidebar from '../containers/navs/Sidebar';
import Footer from '../containers/navs/Footer';

const AppLayout = ({children, history, step}) => (
  <div id="app-container" className="spad-admin-shell">
    <TopNav history={history}/>
    {step == null ? <Sidebar/> : null}
    <main className="spad-admin-main">
      <div className="container-fluid spad-admin-content">{children}</div>
    </main>
    <Footer/>
  </div>
);

const mapStateToProps = ({authUser: {step}}) => ({step});

export default withRouter(connect(mapStateToProps)(AppLayout));

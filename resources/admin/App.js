import React, {Suspense} from 'react';
import {connect} from 'react-redux';
import {BrowserRouter as Router, Route, Switch,} from 'react-router-dom';
import {IntlProvider} from 'react-intl';
import './helpers/Firebase';
import AppLocale from './lang';
import ColorSwitcher from './components/common/ColorSwitcher';
import {NotificationContainer} from './components/common/react-notifications';
import {adminRoot, isMultiColorActive, UserRole,} from './constants/defaultValues';
import {getDirection} from './helpers/Utils';
import ProtectedRoute from './helpers/authHelper';
import {checkLoginUser} from "./redux/auth/actions";
import {GetPanelSettings} from "./redux/settings/actions";

/*const ViewHome = React.lazy(() =>
  import(/!* webpackChunkName: "views" *!/ './views/home')
);*/
const ViewApp = React.lazy(() =>
  import(/* webpackChunkName: "views-app" */ './views/app')
);
const ViewUser = React.lazy(() =>
  import(/* webpackChunkName: "views-user" */ './views/user')
);
const ViewError = React.lazy(() =>
  import(/* webpackChunkName: "views-error" */ './views/error')
);
const ViewUnauthorized = React.lazy(() =>
  import(/* webpackChunkName: "views-error" */ './views/unauthorized')
);

class App extends React.Component {
  constructor(props) {
    super(props);
    const direction = getDirection();
    if (direction.isRtl) {
      document.body.classList.add('rtl');
      document.body.classList.remove('ltr');
    } else {
      document.body.classList.add('ltr');
      document.body.classList.remove('rtl');
    }
  }

  render() {
    const {locale, currentUser, checkUser} = this.props;
    const currentAppLocale = AppLocale[locale];
    return (
      <div className="h-100">
        <IntlProvider
          locale={currentAppLocale.locale}
          messages={currentAppLocale.messages}
        >
          <>
            <NotificationContainer/>
            {isMultiColorActive && <ColorSwitcher/>}
            <Suspense fallback={<div className="loading"/>}>
              <Router>
                <Switch>
                  <Route
                    path={`${adminRoot}/error`}
                    exact
                    render={(props) => <ViewError {...props} />}
                  />

                  <Route
                    path="/spad-login"
                    render={(props) => <ViewUser {...props} />}
                  />
                  {/*<Route
                    path="/error"
                    exact
                    render={(props) => <ViewError {...props} />}
                  />*/}
                  <Route
                    path={`${adminRoot}/unauthorized`}
                    exact
                    render={(props) => <ViewUnauthorized {...props} />}
                  />

                  <ProtectedRoute
                    userLoading={this.props.loading}
                    currentUser={currentUser}
                    checkUser={checkUser}
                    path={adminRoot}
                    component={ViewApp}
                    roles={[UserRole.Admin, UserRole.Editor]}
                  />
                  {/*<Route
                    path="/"
                    exact
                    render={(props) => <ViewHome {...props} />}
                  />*/}
                  {/*
                  <Redirect exact from="/" to={adminRoot} />
                  */}
                  {/*<Redirect to="/error"/>*/}
                </Switch>
              </Router>
            </Suspense>
          </>
        </IntlProvider>
      </div>
    );
  }
}

const mapStateToProps = ({authUser, settings}) => {
  const {currentUser, loading} = authUser;
  const {locale} = settings;
  return {currentUser, locale, loading};
};

const mapActionsToProps = {
  checkUser: checkLoginUser,
  globalSetting: GetPanelSettings,
};

export default connect(mapStateToProps, mapActionsToProps)(App);

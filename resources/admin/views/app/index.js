import React, { Suspense } from "react";
import { Redirect, Route, Switch, withRouter } from "react-router-dom";
import { connect } from "react-redux";

import AppLayout from "../../layout/AppLayout";
import Shop from "./shops/shop";
import AxiosInterceptor from "../../helpers/AxiosInterceptor";
import "../../assets/css/sass/spad-admin-modern.scss";
import "../../assets/css/sass/spad-admin-modernize-v6.scss";
import "../../assets/css/sass/spad-admin-v7.scss";

// import { ProtectedRoute, UserRole } from '../../helpers/authHelper';

const Dashboards = React.lazy(() =>
  import(/* webpackChunkName: "dashboards" */ "./dashboards")
);
const Advertisements = React.lazy(() =>
  import(/* webpackChunkName: "advertisements" */ "./advertisements")
);
const EmployRequests = React.lazy(() =>
  import(/* webpackChunkName: "employ-requests" */ "./employ-requests")
);
const ExpertRequests = React.lazy(() =>
  import(/* webpackChunkName: "expert-requests" */ "./expert-requests")
);
const Location = React.lazy(() =>
  import(/* webpackChunkName: "location" */ "./location")
);

const Blogs = React.lazy(() => import(/* webpackChunkName: "Blogs" */'./blogs'));
const ContentsList = React.lazy(() => import(/* webpackChunkName: "contact" */'./contact/contact-list'));
const MediaManagement = React.lazy(() => import(/* webpackChunkName: "contact" */'./media-management/Media_management_cp'));

/*const Transactions = React.lazy(() =>
  import(/!* webpackChunkName: "dashboards" *!/ './transactions')
);
const LandTypes = React.lazy(() =>
  import(/!* webpackChunkName: "dashboards" *!/ './land-types')
);*/
/*const Pages = React.lazy(() =>
  import(/!* webpackChunkName: "pages" *!/ './pages')
);
const Accounting = React.lazy(() =>
  import(/!* webpackChunkName: "accounting" *!/ './accounting')
);*/
/*const Applications = React.lazy(() =>
  import(/!* webpackChunkName: "applications" *!/ './applications')
);*/
/*const ProductsManagement = React.lazy(() =>
  import(/!* webpackChunkName: "ProductsMangement" *!/ './products-management')
);

const Ui = React.lazy(() => import(/!* webpackChunkName: "ui" *!/ './ui'));
const Menu = React.lazy(() => import(/!* webpackChunkName: "menu" *!/ './menu'));
const BlankPage = React.lazy(() =>
  import(/!* webpackChunkName: "blank-page" *!/ './blank-page')
);*/
const Customers = React.lazy(() => import(/* webpackChunkName: "customers" */'./customers'));
const Operators = React.lazy(() =>
  import(/* webpackChunkName: "operators" */ "./operators")
);
const Settings = React.lazy(() => import(/* webpackChunkName: "settings" */'./settings'));
const License = React.lazy(() => import(/* webpackChunkName: "license" */'./license'));
const Crm = React.lazy(() => import(/* webpackChunkName: "crm" */'./crm'));
const Raffles = React.lazy(() => import(/* webpackChunkName: "raffles" */'./raffles'));
const Wallets = React.lazy(() => import(/* webpackChunkName: "wallets" */'./wallets'));
const MortgageAdmin = React.lazy(() => import("./mortgage"));
const MaterialsAdmin = React.lazy(() => import("./material-prices"));
const CommunityAdmin = React.lazy(() => import("./community"));
const PropertyAffairsAdmin = React.lazy(() => import("./property-affairs"));
class CommunityPageErrorBoundary extends React.Component {
 constructor(props){super(props);this.state={hasError:false};}
 static getDerivedStateFromError(){return {hasError:true};}
 componentDidCatch(err){console.error("SPAD community panel rendering error",err);}
 render(){
  if(this.state.hasError)return <div dir="rtl" style={{padding:28,margin:24,background:"#f6fbff",border:"1px solid #d9e8f4",borderRadius:15}}>
   <h3 style={{fontSize:20,color:"#146d9c"}}>صفحه مدیریت به‌درستی بارگذاری نشد</h3>
   <p style={{fontSize:13,color:"#53798b"}}>یک خطای موقت در نمایش این بخش رخ داده است. صفحه را دوباره بارگذاری کنید؛ اطلاعات شما تغییری نکرده است.</p>
   <button type="button" style={{background:"#0877b5",color:"white",border:0,borderRadius:8,padding:"10px 18px",cursor:"pointer"}} onClick={()=>window.location.reload()}>بارگذاری مجدد صفحه</button>
  </div>;
  return this.props.children;
 }
}
const Inquiries = React.lazy(() => import(/* webpackChunkName: "inquiries" */'./inquiries'));
const VirtualTours = React.lazy(() => import(/* webpackChunkName: "virtual-tours" */'./virtual-tours'));
const PartnerPanels = React.lazy(() => import(/* webpackChunkName: "partner-panels" */'./partner-panels'));
const InvestorListings = React.lazy(() => import(/* webpackChunkName: "investor-listings" */'./investor-listings'));
/*const Shops = React.lazy(() => import(/!* webpackChunkName: "shops" *!/'./shops'));
const Customers = React.lazy(() => import(/!* webpackChunkName: "customers" *!/'./customers'));
const Blogs = React.lazy(() => import(/!* webpackChunkName: "Blogs" *!/'./blogs'));
const Settings = React.lazy(() => import(/!* webpackChunkName: "settings" *!/'./settings'));
const Orders = React.lazy(() => import(/!* webpackChunkName: "orders" *!/'./orders'));*/

const App = ({ match, step, location }) => {
  return (
    <AxiosInterceptor>
      <AppLayout>
        <div className="dashboard-wrapper spad-admin-modern">
          <Suspense fallback={<div className="loading" />}>
            {step ? (
              <>
                <Shop edit match={match} location={location} />
              </>
            ) : (
              <Switch>
                <Redirect
                  exact
                  from={`${match.url}/`}
                  to={`${match.url}/dashboards`}
                />
                <Route
                  path={`${match.url}/dashboards`}
                  render={(props) => (
                    <Dashboards {...props} />
                  )}
                />
                <Route
                  path={`${match.url}/operators`}
                  render={(props) => <Operators {...props} />}
                />
                <Route
                  path={`${match.url}/customers`}
                  render={(props) => <Customers {...props}/>}
                />
                <Route
                  path={`${match.url}/blogs`}
                  render={(props) => <Blogs {...props}/>}
                />
                <Route
                  path={`${match.url}/advertisements`}
                  render={(props) => <Advertisements {...props} />}
                />
                <Route
                  path={`${match.url}/employ-requests`}
                  render={(props) => <EmployRequests {...props} />}
                />
                <Route
                  path={`${match.url}/expert-requests`}
                  render={(props) => <ExpertRequests {...props} />}
                />
                <Route
                  path={`${match.url}/location`}
                  render={(props) => <Location {...props} />}
                />
                <Route
                  path={`${match.url}/crm`}
                  render={(props) => <Crm {...props} />}
                />
                <Route
                  path={`${match.url}/contacts`}
                  render={(props) => <ContentsList {...props} />}
                />
                <Route
                  path={`${match.url}/media_management`}
                  render={(props) => <MediaManagement {...props} />}
                />
                <Route
                  path={`${match.url}/license`}
                  render={(props) => <License {...props} />}
                />
                {/*                <Route
                  path={`${match.url}/transactions`}
                  render={(props) => <Transactions {...props}/>}
                />
                <Route
                  path={`${match.url}/land-types`}
                  render={(props) => <LandTypes {...props}/>}
                />*/}
                {/* <Route
                  path={`${match.url}/crm`}
                  render={(props) => <Crm {...props} />}
                /> */}
                <Route
                  path={`${match.url}/raffles`}
                  render={(props) => <Raffles {...props} />}
                />
                <Route
                  path={`${match.url}/wallets`}
                  render={(props) => <Wallets {...props} />}
                />
                <Route path={`${match.url}/mortgage`} render={(props) => <MortgageAdmin {...props} />} />
                <Route path={`${match.url}/material-prices`} render={(props) => <MaterialsAdmin {...props} />} />
                <Route path={`${match.url}/building-management`} render={(props) => <CommunityAdmin {...props} mode="buildings" />} />
                <Route path={`${match.url}/top-builders`} render={(props) => <CommunityAdmin {...props} mode="builders" />} />
                <Route path={`${match.url}/property-auctions`} render={(props) => <CommunityPageErrorBoundary><CommunityAdmin {...props} mode="auctions" /></CommunityPageErrorBoundary>} />
                 <Route path={`${match.url}/property-affairs`} render={(props) => <CommunityPageErrorBoundary><PropertyAffairsAdmin {...props} /></CommunityPageErrorBoundary>} />
                <Route
                  path={`${match.url}/inquiries`}
                  render={(props) => <Inquiries {...props} />}
                />
                <Route
                  path={`${match.url}/virtual-tours`}
                  render={(props) => <VirtualTours {...props} />}
                />
                <Route
                  path={`${match.url}/partner-panels/broker-properties`}
                  render={(props) => <InvestorListings {...props} />}
                />
                <Route
                  path={`${match.url}/partner-panels/investor-opportunities`}
                  render={(props) => <InvestorListings {...props} />}
                />
                <Route
                  path={`${match.url}/partner-panels`}
                  render={(props) => <PartnerPanels {...props} />}
                />
                <Route
                  path={`${match.url}/settings`}
                  render={(props) => <Settings {...props}/>}
                />
                <Redirect to="/error" />
              </Switch>
            )}
          </Suspense>
        </div>
      </AppLayout>
    </AxiosInterceptor>
  );
};

const mapStateToProps = ({ menu, authUser: { step } }) => {
  const { containerClassnames } = menu;
  return { containerClassnames, step };
};

export default withRouter(connect(mapStateToProps, {})(App));

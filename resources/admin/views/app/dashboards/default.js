import React, { useEffect, useState } from 'react';
import { injectIntl } from 'react-intl';
import { Row } from 'reactstrap';
import { Colxx, Separator } from '../../../components/common/CustomBootstrap';
import Breadcrumb from '../../../containers/navs/Breadcrumb';
import IconCardsCarousel from '../../../containers/dashboards/IconCardsCarousel';
import RecentOrders from '../../../containers/dashboards/RecentOrders';
import Logs from '../../../containers/dashboards/Logs';
import Tickets from '../../../containers/dashboards/Tickets';
import Calendar from '../../../containers/dashboards/Calendar';
import BestSellers from '../../../containers/dashboards/BestSellers';
import ProfileStatuses from '../../../containers/dashboards/ProfileStatuses';
import GradientCardContainer from '../../../containers/dashboards/GradientCardContainer';
import Cakes from '../../../containers/dashboards/Cakes';
import GradientWithRadialProgressCard from '../../../components/cards/GradientWithRadialProgressCard';
import SortableStaticticsRow from '../../../containers/dashboards/SortableStaticticsRow';
import AdvancedSearch from '../../../containers/dashboards/AdvancedSearch';
import SmallLineCharts from '../../../containers/dashboards/SmallLineCharts';
import SalesChartCard from '../../../containers/dashboards/SalesChartCard';
import ProductCategoriesPolarArea from '../../../containers/dashboards/ProductCategoriesPolarArea';
import WebsiteVisitsChartCard from '../../../containers/dashboards/WebsiteVisitsChartCard';
import ConversionRatesChartCard from '../../../containers/dashboards/ConversionRatesChartCard';
import TopRatedItems from '../../../containers/dashboards/TopRatedItems';
import {adminPathApi} from "../../../constants/defaultValues";
import axios from "axios";
const DefaultDashboard = ({ intl, match }) => {
  const { messages } = intl;
  const [data , setData] = useState(null);
  useEffect(()=>{
    axios
      .get(`${adminPathApi}/dashboard`)
      .then(data=>{
        setData(data)
        console.log(data)
      }).catch(({response})=>{
        alert(response);
      })
  },[])
  return (
    <>
      <Row>
        <Colxx xxs="12">
          <Breadcrumb heading="menu.default" match={match} />
          <Separator className="mb-5" />
        </Colxx>
      </Row>
      <Row>
        <Colxx lg="12" xl="6">
          {data !== null &&
            <IconCardsCarousel data={[
             data.data.all_ads_count ,
             data.data.expert_requests_count ,
             data.data.crm_requests_count
            ]}/>
          }
          <Row>
            <Colxx md="12" className="mb-4">
              {data !== null && <SalesChartCard data={data} setData={setData} />}
            </Colxx>
          </Row>
        </Colxx>
        <Colxx lg="12" xl="6" className="mb-4">
          <RecentOrders data={data} />
        </Colxx>
      </Row>
      <Row>
        <Colxx lg="4" md="12" className="mb-4">
          <ProductCategoriesPolarArea data={data} type={1} chartClass="dashboard-donut-chart" />
        </Colxx>
        <Colxx lg="4" md="12" className="mb-4">
          <ProductCategoriesPolarArea data={data} type={2} chartClass="dashboard-donut-chart" />
        </Colxx>
        <Colxx lg="4" md="12" className="mb-4">
          <ProductCategoriesPolarArea data={data} type={3} chartClass="dashboard-donut-chart" />
        </Colxx>
      </Row>
      {/* <Row>
      <Colxx lg="6" md="6" className="mb-4">
          <Logs />
        </Colxx>
        <Colxx lg="6" md="6" className="mb-4">
          <Tickets />
        </Colxx>
      </Row>
      <Row> */}
        {/* <Colxx xl="6" lg="12" className="mb-4">
          <Calendar />
        </Colxx> */}
        {/* <Colxx xl="6" lg="12" className="mb-4">
          <BestSellers />
        </Colxx>
      </Row> */}
      {/* <Row>
        <Colxx sm="12" lg="4" className="mb-4">
          <ProfileStatuses />
        </Colxx>
        <Colxx md="6" lg="4" className="mb-4">
          <GradientCardContainer />
        </Colxx>
        <Colxx md="6" lg="4" className="mb-4">
          <Cakes />
        </Colxx>
      </Row>
      <SortableStaticticsRow messages={messages} />
      <Row>
        <Colxx sm="12" md="6" className="mb-4">
          <WebsiteVisitsChartCard />
        </Colxx>
        <Colxx sm="12" md="6" className="mb-4">
          <ConversionRatesChartCard />
        </Colxx>
      </Row>
      <Row>
        <Colxx lg="12" md="6" xl="4">
          <Row>
            <Colxx lg="4" xl="12" className="mb-4">
              <GradientWithRadialProgressCard
                icon="iconsminds-clock"
                title={`5 ${messages['dashboards.files']}`}
                detail={messages['dashboards.pending-for-print']}
                percent={(5 * 100) / 12}
                progressText="5/12"
              />
            </Colxx>
            <Colxx lg="4" xl="12" className="mb-4">
              <GradientWithRadialProgressCard
                icon="iconsminds-male"
                title={`4 ${messages['dashboards.orders']}`}
                detail={messages['dashboards.on-approval-process']}
                percent={(4 * 100) / 6}
                progressText="4/6"
              />
            </Colxx>
            <Colxx lg="4" xl="12" className="mb-4">
              <GradientWithRadialProgressCard
                icon="iconsminds-bell"
                title={`8 ${messages['dashboards.alerts']}`}
                detail={messages['dashboards.waiting-for-notice']}
                percent={(8 * 100) / 10}
                progressText="8/10"
              />
            </Colxx>
          </Row>
        </Colxx>
        <Colxx lg="6" md="6" xl="4" sm="12" className="mb-4">
          <AdvancedSearch messages={messages} />
        </Colxx>
        <Colxx lg="6" xl="4" className="mb-4">
          <SmallLineCharts />
          <TopRatedItems />
        </Colxx>
      </Row> */}
    </>
  );
};
export default injectIntl(DefaultDashboard);

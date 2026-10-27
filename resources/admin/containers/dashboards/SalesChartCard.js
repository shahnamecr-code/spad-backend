import React from 'react';
import axios from "axios";
import {
  Card,
  CardBody,
  CardTitle,
  UncontrolledDropdown,
  DropdownItem,
  DropdownToggle,
  DropdownMenu,
} from 'reactstrap';
import IntlMessages from '../../helpers/IntlMessages';
import { LineChart } from '../../components/charts';
import { ThemeColors } from '../../helpers/ThemeColors';
import {adminPathApi} from "../../constants/defaultValues";
// import { lineChartData } from '../../data/charts';

const SalesChartCard = ({data , setData}) => {
  const colors = ThemeColors();
  const lineChartData = {
    labels: data !== null && data.data.crm_requests_by_time.label,
    datasets: [
      {
        label: '',
        data: data !== null && data.data.crm_requests_by_time.value,
        borderColor: colors.themeColor1,
        pointBackgroundColor: colors.foregroundColor,
        pointBorderColor: colors.themeColor1,
        pointHoverBackgroundColor: colors.themeColor1,
        pointHoverBorderColor: colors.foregroundColor,
        pointRadius: 6,
        pointBorderWidth: 2,
        pointHoverRadius: 8,
        fill: false,
      },
    ],
  };
  // const func = (type) =>{
  //   axios
  //     .get(`${adminPathApi}/dashboard/AdsStatistics/${type}`)
  //     .then(res=>{
  //       setData(prev=> {
  //         const pre = prev;
  //         pre.data.ads_by_time = res.data;
  //         return pre
  //       })
  //       console.log(data)
  //     }).catch(({response})=>{
  //       alert(response);
  //     })
  // }
  return (
    <Card>
      <div className="position-absolute card-top-buttons">
        <UncontrolledDropdown>
          <DropdownToggle color="" className="btn btn-header-light icon-button">
            <i className="simple-icon-refresh" />
          </DropdownToggle>
          <DropdownMenu right>
          {/* onClick={()=> func("week")} */}
            <DropdownItem > 
              <IntlMessages id="dashboards.sales" />
            </DropdownItem>
            {/* <DropdownItem onClick={()=> func("month")}>
              <IntlMessages id="dashboards.orders" />
            </DropdownItem>
            <DropdownItem onClick={()=> func("year")}>
              <IntlMessages id="dashboards.refunds" />
            </DropdownItem> */}
          </DropdownMenu>
        </UncontrolledDropdown>
      </div>
      <CardBody>
        <CardTitle>
          <IntlMessages id="dashboards.sales" />
        </CardTitle>
        <div className="dashboard-line-chart">
          <LineChart shadow minMax={data} data={lineChartData} />
        </div>
      </CardBody>
    </Card>
  );
};

export default SalesChartCard;

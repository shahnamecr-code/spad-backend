import React, { useEffect } from 'react';
import { Card, CardBody, CardTitle } from 'reactstrap';
import IntlMessages from '../../helpers/IntlMessages';
import { PolarAreaChart } from '../../components/charts';
import { ThemeColors } from '../../helpers/ThemeColors';
// import { polarAreaChartData } from '../../data/charts';

const ProductCategoriesPolarArea = ({ chartClass = 'chart-container' , data , type}) => {
  const colors = ThemeColors();
  const ColorArray = []
  Object.keys(colors).map(color => {
    ColorArray.push(colors[color])
  })
  const values = [12,20,28,15,25]
  const polarAreaChartData = {
    labels: data !== null && 
      (
        type == 1 ?  data.data.ads_by_category.label :
        type == 2 ?  data.data.crm_requests_by_category.label :
        type == 3 && data.data.ads_by_state.label 
      ),
    datasets: [
      {
        data: data !== null && 
        (
          type == 1 ?  data.data.ads_by_category.value :
          type == 2 ?  data.data.crm_requests_by_category.value :
          type == 3 && data.data.ads_by_state.value 
        ) ,
        borderWidth: 2,
        borderColor: values.map((val,index) => ColorArray[index]),
        backgroundColor: values.map((val,index) => ColorArray[index]),
      },
    ],
  };
  return (
    <Card>
      <CardBody>
        <CardTitle>
          <IntlMessages id={
            type == 1 ? 'dashboards.product-categories1' :
            type == 2 ? 'dashboards.product-categories2' :
            type == 3 && 'dashboards.product-categories3' 
          } />
        </CardTitle>
        <div className={chartClass}>
          <PolarAreaChart shadow data={polarAreaChartData && polarAreaChartData} />
        </div>
      </CardBody>
    </Card>
  );
};

export default ProductCategoriesPolarArea;

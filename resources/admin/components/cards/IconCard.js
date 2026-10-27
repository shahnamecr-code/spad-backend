import React from 'react';
import { Card, CardBody } from 'reactstrap';
import IntlMessages from '../../helpers/IntlMessages';

const IconCard = ({ className = 'mb-4', item , index}) => {
  return (
    <div className={`icon-row-item ${className}`}>
      <Card>
        <CardBody className="text-center">
          <i className={
            index == 0 ? 'iconsminds-check' 
            : index == 1 ? 'iconsminds-arrow-refresh' 
            : index == 2 && 'iconsminds-clock' 
          } />
          <p className="card-text font-weight-semibold mb-0">
            <IntlMessages id={
              index == 0 ? 'dashboards.pending-orders'
              : index == 1 ? 'dashboards.completed-orders'
              : index == 2 && 'dashboards.refund-requests'
            } />
          </p>
          <p className="lead text-center">{item}</p>
        </CardBody>
      </Card>
    </div>
  );
};

export default React.memo(IconCard);

/* eslint-disable react/no-array-index-key */
import React from 'react';
import { NavLink } from 'react-router-dom';
import PerfectScrollbar from 'react-perfect-scrollbar';
import { Card, CardBody, CardTitle, Badge } from 'reactstrap';
import IntlMessages from '../../helpers/IntlMessages';
import { adminRoot } from '../../constants/defaultValues';

const RecentOrders = ({data}) => {
  return (
    <Card>
      <CardBody>
        <CardTitle>
          <IntlMessages id="dashboards.recent-orders" />
        </CardTitle>
        <div className="scroll dashboard-list-with-thumbs">
          <PerfectScrollbar
            options={{ suppressScrollX: true, wheelPropagation: false }}
          >
            {data !== null && data.data.last_ads.map((order, index) => {
              return (
                <div key={index} className="d-flex flex-row mb-3">
                  <NavLink
                    to={`${adminRoot}/advertisements/edit/${order.id}`}
                    className="d-block position-relative"
                  >
                    <img
                      src={order.image}
                      alt={order.title}
                      className="list-thumbnail border-0 w-100px"
                    />
                    <Badge
                      key={index}
                      className="position-absolute badge-top-right"
                      color={order.status_class}
                      pill
                    >
                      {order.status_text}
                    </Badge>
                  </NavLink>

                  <div className="pl-3 pt-2 pr-2 pb-2">
                    <NavLink to={`${adminRoot}/advertisements/edit/${order.id}`}>
                      <p className="list-item-heading">{order.title}</p>
                      <div className="pr-4">
                        {/* <p className="text-muted mb-1 text-small">
                          {order.description}
                        </p> */}
                      </div>
                      <div className="text-primary text-small font-weight-medium d-none d-sm-block">
                        {order.date}
                      </div>
                    </NavLink>
                  </div>
                </div>
              );
            })}
          </PerfectScrollbar>
        </div>
      </CardBody>
    </Card>
  );
};
export default RecentOrders;

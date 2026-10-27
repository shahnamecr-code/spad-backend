import React from "react";
import { Button, Card, CardBody, Input, Row } from "reactstrap";
import {
  Colxx,
  Separator,
} from "../../../../components/common/CustomBootstrap";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
const CheckInformation = ({ match }) => {
  return (
    <>
      <Row>
        <Colxx md="12">
          <Breadcrumb heading="menu.crm.addCustomer" match={match} />
          <Separator className="mb-5" />
        </Colxx>
      </Row>

      <Row>
        <Colxx md="12">
          <Card>
            <CardBody>
              <Row>
                <Colxx md="5">
                  <Input onChange={(e) => console.log(e.target.value)} />
                </Colxx>
                <Colxx md="3">
                  <Button>بررسی اطلاعات</Button>
                </Colxx>
              </Row>
            </CardBody>
          </Card>
        </Colxx>
      </Row>
    </>
  );
};
export default CheckInformation;

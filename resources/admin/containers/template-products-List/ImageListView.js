import React from 'react';
import {
  Row,
  Card,
  CardBody,
  CardSubtitle,
  CardImg,
  CardText,
  CustomInput,
  Badge,
} from 'reactstrap';
import { NavLink } from 'react-router-dom';
import classnames from 'classnames';
import { ContextMenuTrigger } from 'react-contextmenu';
import { Colxx } from '../../components/common/CustomBootstrap';

import {adminRoot, uploadPath} from "../../constants/defaultValues";

const statusColors=['secondary','success','danger'];
const statusText=['در انتظار تأیید','تأیید شده','رد شده'];

const ImageListView = ({ product, isSelect, collect, onCheckItem,getMainImage,getPrice }) => {
  return (
    <Colxx sm="6" lg="4" xl="3" className="mb-3" key={product.id}>
      <ContextMenuTrigger id="menu_id" data={product.id} collect={collect}>
        <Card
          onClick={(event) => onCheckItem(event, product.id)}
          className={classnames({
            active: isSelect,
          })}
        >
          <div className="position-relative">
            <NavLink to={`${adminRoot}/products-management/products/edit/${product.id}`} className="w-40 w-sm-100">
              <CardImg top alt={product.name?product.name:product.template.title} src={getMainImage(product)} />
            </NavLink>
            <Badge
              color={statusColors[product.status]}
              pill
              className="position-absolute badge-top-left"
            >
              {statusText[product.status]}
            </Badge>
          </div>
          <CardBody>
            <Row>
              <Colxx xxs="2">
                <CustomInput
                  className="item-check mb-0"
                  type="checkbox"
                  id={`check_${product.id}`}
                  checked={isSelect}
                  onChange={() => {}}
                  label=""
                />
              </Colxx>
              <Colxx xxs="10" className="mb-3">
                <CardSubtitle>{product.name?product.name:product.template.title}</CardSubtitle>
                <CardText className="text-muted text-small mb-0 font-weight-light">
                  {getPrice(product)}
                </CardText>
              </Colxx>
            </Row>
          </CardBody>
        </Card>
      </ContextMenuTrigger>
    </Colxx>
  );
};

/* React.memo detail : https://reactjs.org/docs/react-api.html#reactpurecomponent  */
export default React.memo(ImageListView);

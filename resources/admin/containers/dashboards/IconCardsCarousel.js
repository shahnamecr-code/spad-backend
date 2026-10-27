/* eslint-disable react/no-array-index-key */
import React from 'react';
import IconCard from '../../components/cards/IconCard';
import GlideComponent from '../../components/carousel/GlideComponent';
import {Colxx} from "../../components/common/CustomBootstrap";

const IconCardsCarousel = ({ className = 'icon-cards-row' , data}) => {
  return (
    <div className={className}>
      {/*<GlideComponent*/}
      {/*  settings={{*/}
      {/*    gap: 5,*/}
      {/*    perView: 3,*/}
      {/*    type: 'carousel',*/}
      {/*    breakpoints: {*/}
      {/*      320: { perView: 1 },*/}
      {/*      576: { perView: 2 },*/}
      {/*      1600: { perView: 3 },*/}
      {/*      1800: { perView: 4 },*/}
      {/*    },*/}
      {/*    hideNav: true,*/}
      {/*  }}*/}
      {/*>*/}
        <div className="d-flex">
          {data !== null && data.map((item, index) => {
            return (
              <Colxx md={4} key={`icon_card_${index}`}>
                <IconCard item={item} index={index} className="mb-4" />
              </Colxx>
            );
          })}
        </div>
      {/*</GlideComponent>*/}
    </div>
  );
};
export default IconCardsCarousel;

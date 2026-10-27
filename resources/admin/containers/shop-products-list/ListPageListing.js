import React from 'react';
import { Row } from 'reactstrap';
import Pagination from './Pagination';
import ContextMenuContainer from './ContextMenuContainer';
import DataListView from './DataListView';
import ImageListView from './ImageListView';
import ThumbListView from './ThumbListView';

function collect(props) {
  return { data: props.data };
}

const ListPageListing = ({
                           items,
                           displayMode,
                           selectedItems,
                           onCheckItem,
                           currentPage,
                           totalPage,
                           onContextMenuClick,
                           onContextMenu,
                           onChangePage,
                           pagination_links,
                           setPaginationLinks,
                           getMainImage,
                           getPrice
                         }) => {
  return (
    <Row>
      {items.map((product) => {
        if (displayMode === 'imagelist') {
          return (
            <ImageListView
              key={product.id}
              product={product}
              isSelect={selectedItems.includes(product.id)}
              collect={collect}
              onCheckItem={onCheckItem}
              getMainImage={getMainImage}
              getPrice={getPrice}
            />
          );
        }
        if (displayMode === 'thumblist') {
          return (
            <ThumbListView
              key={product.id}
              product={product}
              isSelect={selectedItems.includes(product.id)}
              collect={collect}
              onCheckItem={onCheckItem}
              getMainImage={getMainImage}
              getPrice={getPrice}
            />
          );
        }
        return (
          <DataListView
            key={product.id}
            product={product}
            isSelect={selectedItems.includes(product.id)}
            onCheckItem={onCheckItem}
            collect={collect}
            getPrice={getPrice}
          />
        );
      })}
      <Pagination
        currentPage={currentPage}
        totalPage={totalPage}
        onChangePage={(i) => onChangePage(i)}
        pagination_links={pagination_links}
        setPaginationLinks={setPaginationLinks}
      />
      <ContextMenuContainer
        onContextMenuClick={onContextMenuClick}
        onContextMenu={onContextMenu}
      />
    </Row>
  );
};

export default React.memo(ListPageListing);

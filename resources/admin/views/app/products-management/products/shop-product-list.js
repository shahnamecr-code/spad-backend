import React, { useState, useEffect } from 'react';
import List from '../../../../containers/shop-products-list/list'
import axios from "axios";
import {adminPathApi, uploadPath,defaultMainImage} from "../../../../constants/defaultValues";


const ItemsList = ({ match }) => {
  const orderOptions = [
    { column: 'created', label: 'جدیدترین محصولات' },
    { column: 'status', label: 'وضعیت' },
    { column: 'product_code', label: 'شناسه محصول' },
  ];
  const pageSizes = [1,4,8,12,16,20];

  const [isLoaded, setIsLoaded] = useState(false);
  const [displayMode, setDisplayMode] = useState('list');
  const [currentPage, setCurrentPage] = useState(1);
  const [selectedPageSize, setSelectedPageSize] = useState(8);
  const [selectedOrderOption, setSelectedOrderOption] = useState({
    column: 'created',
    label:
      'جدیدترین محصولات'
  });

  const [totalItemCount, setTotalItemCount] = useState(0);
  const [totalPage, setTotalPage] = useState(1);
  const [search, setSearch] = useState('');
  const [selectedItems, setSelectedItems] = useState([]);
  const [items, setItems] = useState([]);
  const [lastChecked, setLastChecked] = useState(null);
  const [pagination_links,setPaginationLinks]=useState(null);

  const fetchData=()=>{
    axios
      .get(
        `${adminPathApi}/product?page=${currentPage}&per_page=${selectedPageSize}&search=${search}&orderBy=${selectedOrderOption.column}`
      )
      .then((res) => {
        return res.data;
      })
      .then((data) => {
        setTotalPage(data.last_page);
        setItems(data.data);
        setSelectedItems([]);
        setTotalItemCount(data.total);
        setPaginationLinks(data.links);
        setIsLoaded(true);
      });
  };

  const onContextMenuClick = (e, data) => {
    switch (data.action){
      case 'reject':
        reject(selectedItems);
        break;
      case 'approve':
        approve(selectedItems);
        break;
      case 'awaited':
        awaited(selectedItems);
        break;
      case 'delete':
        deleteItems(selectedItems)
    }
  };

  const reject=(id)=>{
    swal({
      title: 'علت رد محصول را بنویسید',
      icon: "warning",
      content: {
        element: "input",
        attributes: {
          type: "text",
        },
      },
      buttons: ['لغو', 'ثبت و رد محصول'],
    }).then(async (result)=> {
      if (result !== null) {
        axios
          .post(`${adminPathApi}/product/change-status`,{action:'reject',reject_reason:result,items:id})
          .then(res=>res.data)
          .then(data=>{
            if(data.status){
              fetchData();
              swal(data.message,{icon:'success',timer:3000,buttons:false})
            }
          })
      }
    })
  }

  const approve=(id)=>{
    axios
      .post(`${adminPathApi}/product/change-status`,{action:'approve',items:id})
      .then(res=>res.data)
      .then(data=>{
        if(data.status){
          fetchData();
          swal(data.message,{icon:'success',timer:3000,buttons:false})
        }
      })
  }

  const awaited=(id)=>{
    axios
      .post(`${adminPathApi}/product/change-status`,{action:'await',items:id})
      .then(res=>res.data)
      .then(data=>{
        if(data.status){
          fetchData();
          swal(data.message,{icon:'success',timer:3000,buttons:false})
        }
      })
  }

  const deleteItems=(id)=>{
    swal("آیا از حذف محصولات اطمینان دارید؟", {
      icon: "warning",
      buttons: ['نه', 'بله'],
    })
      .then(res => {
        if (res) {
          axios
            .post(`${adminPathApi}/product/delete`,{items:id})
            .then(res=>res.data)
            .then(info=>{
              if (info.status) {
                setItems(prev => {
                  let output=prev.filter(item => !id.includes(item.id ))
                  return output
                })
                swal(info.message, {icon: 'success', timer: 1500, buttons: false})
              } else {
                if (info.message)
                  swal(info.message, {icon: 'warning', timer: 1500, buttons: false})
              }
            })
        }
      })
  }

  const getMainImage=(pd)=>{
    if(pd.template.properties){
      let img=pd.template.properties.find(item=>item.property && item.property.code=='main_image');
      if(img)
        return `${uploadPath}/${img.value}`;
    }
    return defaultMainImage;
  }

  const getPrice=(pd)=>{
    let quantity='';
    if(pd.inventories_sum_quantity){
      quantity=`(${pd.inventories_sum_quantity})`
    }
    if (pd.inventories_max_price && pd.inventories_min_price)
      return (pd.inventories_max_price==pd.inventories_min_price)?`${pd.inventories_max_price} ${quantity}`:`از ${pd.inventories_min_price} تا ${pd.inventories_max_price} ${quantity}`;
    else if(pd.inventories_sum_quantity)
      return pd.inventories_sum_quantity
    else
      return '---';
  }

  return (
    <List match={match} orderOptions={orderOptions} pageSizes={pageSizes} pageTitle={"menu.products.list"}
          isLoaded={isLoaded} setIsLoaded={setIsLoaded}
          displayMode={displayMode} setDisplayMode={setDisplayMode}
          currentPage={currentPage} setCurrentPage={setCurrentPage}
          selectedPageSize={selectedPageSize} setSelectedPageSize={setSelectedPageSize}
          selectedOrderOption={selectedOrderOption} setSelectedOrderOption={setSelectedOrderOption}
          totalItemCount={totalItemCount} setTotalItemCount={setTotalItemCount}
          totalPage={totalPage} setTotalPage={setTotalPage}
          search={search} setSearch={setSearch}
          selectedItems={selectedItems} setSelectedItems={setSelectedItems}
          items={items} setItems={setItems}
          lastChecked={lastChecked} setLastChecked={setLastChecked}
          pagination_links={pagination_links} setPaginationLinks={setPaginationLinks}
          fetchData={fetchData}
          onContextMenuClick={onContextMenuClick}
          getMainImage={getMainImage}
          getPrice={getPrice}
    />
  );
}
export default ItemsList;

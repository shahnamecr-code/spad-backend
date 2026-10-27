import React, {useEffect, useState} from "react";
import {adminPathApi, adminRoot} from "../../../constants/defaultValues";
import {Colxx, Separator} from "../../../components/common/CustomBootstrap";
import useMousetrap from "../../../hooks/use-mousetrap";
import {ContextMenu, ContextMenuTrigger, MenuItem} from "react-contextmenu";
import {Card, Row} from "reactstrap";
import classnames from "classnames";
import {Link, NavLink} from "react-router-dom";
import IconTooltip from "../advertisements/components/IconTooltip";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import {VirtuosoGrid} from "react-virtuoso";

function collect(props) {
  return {data: props.data};
}

const getIndex = (value, arr, prop) => {
  for (let i = 0; i < arr.length; i += 1) {
    if (arr[i][prop] === value) {
      return i;
    }
  }
  return -1;
};

const AllList=({match})=>{
  const [advertisements,setAdvertisements]=useState([])
  const [link, setLink] = useState(`${adminPathApi}/advertisements/list`);
  const [selectedItems, setSelectedItems] = useState([]);
  const [lastChecked, setLastChecked] = useState(null);

  useEffect(()=>{
    axios
      .get(`${adminPathApi}/expert-request/list`)
      .then(res=>res.data)
      .then(data=>{
        setAdvertisements(data.data)
        setLink(data.links.next)
      })
  },[])

  const ItemContainer = (props) => {
    return <Colxx md={12}  {...props}>{props.children}</Colxx>
  }

  const onContextMenu = (e, data) => {
    const clickedProductId = data.data;
    if (!selectedItems.includes(clickedProductId)) {
      setSelectedItems([clickedProductId]);
    }

    return true;
  };

  const onContextMenuClick = (e, data) => {
    // console.log(data.data)
    switch (data.action) {
      case 'reject':
        reject(selectedItems);
        break;
      case 'approve':
        approve(selectedItems);
        break;

    }
  };

  const onCheckItem = (event, id) => {
    if (
      event.target.tagName === 'A' ||
      (event.target.parentElement && event.target.parentElement.tagName === 'A')
    ) {
      return true;
    }
    if (lastChecked === null) {
      setLastChecked(id);
    }

    let selectedList = [...selectedItems];
    if (selectedList.includes(id)) {
      selectedList = selectedList.filter((x) => x !== id);
    } else {
      selectedList.push(id);
    }
    setSelectedItems(selectedList);

    if (event.shiftKey) {
      let newItems = [...advertisements];
      const start = getIndex(id, newItems, 'id');
      const end = getIndex(lastChecked, newItems, 'id');
      newItems = newItems.slice(Math.min(start, end), Math.max(start, end) + 1);
      selectedItems.push(
        ...newItems.map((item) => {
          return item.id;
        })
      );
      selectedList = Array.from(new Set(selectedItems));
      setSelectedItems(selectedList);
    }
    document.activeElement.blur();
    return false;
  };

  const handleChangeSelectAll = (isToggle) => {
    if (selectedItems.length >= advertisements.length) {
      if (isToggle) {
        setSelectedItems([]);
      }
    } else {
      setSelectedItems(advertisements.map((x) => x.id));
    }
    document.activeElement.blur();
    return false;
  };

  useMousetrap(['ctrl+a', 'command+a'], () => {
    handleChangeSelectAll(false);
  });

  useMousetrap(['ctrl+d', 'command+d'], () => {
    setSelectedItems([]);
    return false;
  });

  const ItemWrapper = ({ads, isSelect, collect, ...props}) => {
    return (
      <ContextMenuTrigger id="menu_id" data={ads.id} collect={collect}>
        <Card
          key={ads.id}
          onClick={(event) => onCheckItem(event, ads.id)}
          className={classnames('d-flex flex-row mb-2', {
            active: isSelect,
          })}
        >
          <div className="pl-2 d-flex flex-grow-1 min-width-zero">
            <div className="card-body align-self-center d-flex flex-column flex-lg-row justify-content-between min-width-zero align-items-lg-center">
              <NavLink to={`edit/${ads.id}`} className="w-40 w-sm-100">
                <p className="list-item-heading mb-1 truncate">
                  {`${ads.first_name} ${ads.last_name} (${ads.mobile})`}
                </p>
              </NavLink>
              <p className="mb-1 text-muted text-small w-15 w-sm-100">
                {`${ads.city || "شیراز"}-${ads.region || ""}-${ads.address || ""}`}
              </p>
              <p className="mb-1 text-muted text-small w-15 w-sm-100">
                {ads.category}
              </p>
              <p className="mb-1 text-muted text-small w-15 w-sm-100">
                {ads.date}
              </p>
            </div>
          </div>
        </Card>
      </ContextMenuTrigger>
    )
  }

  const loadMore = React.useCallback(() => {
    if (link) {
      axios.get(link)
        .then(res => res.data)
        .then(data => {
          setAdvertisements([...advertisements, ...data.data])
          setLink(data.links.next)
        }).catch(error => console.log(error));
    }
  }, [link]);

  return (
    <>
      <Row>
        <Colxx xss="12">
          <Breadcrumb heading={"menu.advertisements.list"} match={match}/>
          <Separator className="mb-5"/>
        </Colxx>
      </Row>
      <Row>
        <Colxx md={12} className={"ads-wrapper"}>
          <VirtuosoGrid
            style={{ height: 400 }}
            totalCount={advertisements.length}
            overscan={200}
            endReached={loadMore}
            components={{
              Item: ItemContainer,
              List: React.forwardRef(({children, ...props}, ref) => {
                return (<div ref={ref} style={{margin: 0, padding: 0}} {...props} className={'row ss'}>
                  {children}
                  <ContextMenu id="menu_id" onShow={(e) => {
                    onContextMenu(e, e.detail.data)
                  }}>
                    <MenuItem onClick={onContextMenuClick} data={{action: 'reject'}}>
                      <i className="simple-icon-docs"/> <span>رد آگهی</span>
                    </MenuItem>
                    <MenuItem onClick={onContextMenuClick} data={{action: 'approve'}}>
                      <i className="simple-icon-drawer"/> <span>تایید</span>
                    </MenuItem>
                  </ContextMenu>
                </div>);
              }),
            }}
            itemContent={(index) => {
              let ads=advertisements[index]
              return (<ItemWrapper ads={ads} collect={collect} isSelect={selectedItems.includes(ads.id)}/>)
            }}
          />
        </Colxx>
      </Row>
    </>
  );

}
export default AllList;

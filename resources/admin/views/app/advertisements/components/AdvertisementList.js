import React, { useEffect, useState } from "react";
import {
  adminPathApi,
  adminRoot,
  currentUser,
} from "../../../../constants/defaultValues";
import classnames from "classnames";
import {Button, ButtonGroup, Card, CardBody, CustomInput, Modal, ModalBody, ModalHeader, Row , UncontrolledButtonDropdown , DropdownToggle , DropdownMenu} from "reactstrap";
import {Colxx, Separator} from "../../../../components/common/CustomBootstrap";
import {Link, NavLink} from "react-router-dom";
import Breadcrumb from "../../../../containers/navs/Breadcrumb";
import {ContextMenu, ContextMenuTrigger, MenuItem} from "react-contextmenu";
import useMousetrap from '../../../../hooks/use-mousetrap';
// import {Virtuoso} from 'react-virtuoso'
import {VirtuosoGrid} from 'react-virtuoso'
import IconTooltip from "../components/IconTooltip";
import UserCan from "../../../../helpers/UserCan";
import TransferAdvertisement from "./TransferAdvertisement";
import {Form, Formik} from "formik";
import AdvertisementCategoryFilter from "./AdvertisementCategoryFilter";
import AdvertisementPreview from "./AdvertisementPreview";
import classes from './css/advertismentList.module.css'
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

const AdvertisementList = ({
  advertisements,
  setAdvertisements,
  link,
  setLink,
  selectedItems,
  setSelectedItems,
  lastChecked,
  setLastChecked,
  type,
  user,
  history,
  show_modal,
  setShowModal,
  selectedCategory,
  setSelectedCategory,
  allCategoryFilter,
  categoryList,
  queryParams,
  setQueryParams,
  loading,
  searchItems,
  initQueryString = "",
}) => {
  const [rejectReasons, setRejectReasons] = useState([]);
  const [showPreviewModal, setShowPreviewModal] = useState(false);
  const [advertisementToPreview, setAdvertisementToPreview] = useState(false);
  const [showTransferModal, setShowTransferModal] = useState(false);
  const [advertisementToTransfer, setAdvertisementToTransfer] = useState(false);
  const [queryString, setQueryString] = useState(initQueryString);

  const reasons = ["اطلاعات ناقص است", "آگهی تکراری است"];

  useEffect(() => {
    let prefix = queryString ? `&${queryString}` : "";
    axios
      .get(`${adminPathApi}/advertisements/list?type=${type}${prefix}`)
      .then((res) => res.data)
      .then((data) => {
        let reject_reasons =
          "<select id='reject_reasons' class='form-control'>";
        reasons.map((reason) => {
          reject_reasons += `<option value="${reason}">${reason}</option>`;
        });
        reject_reasons += "</select>";
        setRejectReasons(reject_reasons);
        setAdvertisements(data.data);
        setLink(data.links.next);
      });
  }, [queryString]);

  const reject = (adsList) => {
    let list = document.createElement("div");
    list.innerHTML = rejectReasons;

    swal({
      content: list.firstChild,
      title: "دلیل رد آگهی را مشخص کنید",
    }).then((value) => {
      let selected = document.getElementById("reject_reasons").value;
      if (selected) {
        axios
          .post(`${adminPathApi}/advertisements/reject`, {
            ads: adsList,
            reason: selected,
          })
          .then((res) => res.data)
          .then((data) => {
            if (data.status) {
              const processedIds = (data.data || []).map((item) => item.id);
              let new_advertisements = [...advertisements];
              if (type === "unapproved") {
                new_advertisements = new_advertisements.filter((ad) => !processedIds.includes(ad.id));
                setSelectedItems((prev) => prev.filter((id) => !processedIds.includes(id)));
              } else {
                new_advertisements.map((ad, i) => {
                  let num = data.data.findIndex((v) => v.id == ad.id);
                  if (num !== -1) {
                    new_advertisements[i] = data.data[num];
                  }
                });
              }
              setAdvertisements(new_advertisements);
              if (data.message) {
                swal(data.message, {
                  icon: "success",
                });
              }
            }
          });
      }
    });
  };

  const approve = (adsList) => {
    axios
      .post(`${adminPathApi}/advertisements/approve`, { ads: adsList })
      .then((res) => res.data)
      .then((data) => {
        if (data.status) {
          const processedIds = (data.data || []).map((item) => item.id);
          let new_advertisements = [...advertisements];
          if (type === "unapproved") {
            new_advertisements = new_advertisements.filter((ad) => !processedIds.includes(ad.id));
            setSelectedItems((prev) => prev.filter((id) => !processedIds.includes(id)));
          } else {
            new_advertisements.map((ad, i) => {
              let num = data.data.findIndex((v) => v.id == ad.id);
              if (num !== -1) {
                new_advertisements[i] = data.data[num];
              }
            });
          }
          setAdvertisements(new_advertisements);
          if (data.message) {
            swal(data.message, {
              icon: "success",
            });
          }
        }
      });
  };

  const priority = (adsList) => {
    axios
      .post(`${adminPathApi}/advertisements/priority`, { ads: adsList })
      .then((res) => res.data)
      .then((data) => {
        if (data.status) {
          let new_advertisements = [...advertisements];
          new_advertisements.map((ad, i) => {
            let num = data.data.findIndex((v) => v.id == ad.id);
            if (num !== -1) {
              new_advertisements[i] = data.data[num];
            }
          });
          setAdvertisements(new_advertisements);
          if (data.message) {
            swal(data.message, {
              icon: "success",
            });
          }
        }
      });
  };

  const handleCardAction = (event, callback) => {
    event.preventDefault();
    event.stopPropagation();
    callback();
  };

  const open_transfer_modal = (ads) => {
    setAdvertisementToTransfer(ads);
    setShowTransferModal(true);
  };

  const open_preview_modal = (ads) => {
    setAdvertisementToPreview(ads);
    setShowPreviewModal(true);
  };

  const ItemContainer = (props) => {
    return (
      <Colxx md={3} {...props}>
        {props.children}
      </Colxx>
    );
  };

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
      case "reject":
        reject(selectedItems);
        break;
      case "approve":
        approve(selectedItems);
        break;
      case "priority":
        priority(selectedItems);
        break;
    }
  };

  const onCheckItem = (event, ads) => {
    open_preview_modal(ads);
    let id = ads.id;
    if (
      event.target.tagName === "A" ||
      (event.target.parentElement && event.target.parentElement.tagName === "A")
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
      const start = getIndex(id, newItems, "id");
      const end = getIndex(lastChecked, newItems, "id");
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

  useMousetrap(["ctrl+a", "command+a"], () => {
    handleChangeSelectAll(false);
  });

  useMousetrap(["ctrl+d", "command+d"], () => {
    setSelectedItems([]);
    return false;
  });

  const ItemWrapper = ({ ads, isSelect, collect, ...props }) => {
    return (
      <ContextMenuTrigger id="menu_id" data={ads.id} collect={collect} >
        <div
          key={ads.id}
          className={classnames(
            "ads-card",
            { active: isSelect },
            classes.mahdi
          )}
          onClick={(event) => type !== "skill" && onCheckItem(event, ads)}
        >
          <Row>
            <Colxx xs={12}>
              <div className={"position-relative"}>
                <div className="d-flex">
                  <img
                    alt={ads.title}
                    src={ads.image}
                    className="ads-card-thumbnail"
                  />
                  <p className={"ads-detail-cate bg-primary"}>
                    <span>{ads.category}</span>
                  </p>
                  <p className={`ads-detail-status bg-${ads.status_class}`}>
                    <span className={`ads-detail-value`}>{ads.status_text}</span>
                  </p>
                  <p className={"ads-detail-cod"}>
                    <span className="ads-detail-title">کد: </span>
                    <span className="ads-detail-value">{ads.id}</span>
                  </p>
                </div>
              </div>
            </Colxx>
            <Colxx xs={12}>
              <div className={`py-3 d-flex flex-column justify-content-center flex-grow-1 min-width-zero ${classes.px_2}`}>
                <div className="d-flex align-items-center justify-content-between">
                  <div style={{fontWeight: 'bold' , fontSize: '14px'}}>
                    {ads.title}
                  </div>
                  <div>
                    {type == 'skill' && (
                      <UncontrolledButtonDropdown>
                        <DropdownToggle style={{padding: '5px 5px 3px 4px'}} color="secondary" outline>
                          <i className={'simple-icon-options-vertical'} />
                        </DropdownToggle>
                        <DropdownMenu>
                          <UserCan to={"transfer.advertisement"}>
                            <span onClick={() => open_transfer_modal(ads)} className={'dropdown-item'}>انتقال آگهی</span>
                          </UserCan>
                          <UserCan to={"transfer.advertisement"}>
                            <span onClick={(event) => onCheckItem(event, ads)} className={'dropdown-item'}>نمایش آگهی</span>
                          </UserCan>
                            <Link className="dropdown-item" size={"xs"} to={`${adminRoot}/advertisements/edit/${ads.id}`} >ویرایش آگهی</Link>
                        </DropdownMenu>
                      </UncontrolledButtonDropdown>
                    )}
                  </div>
                </div>
                {ads.reject_reason ? (
                  <p className={"ads-detail"}>
                    <span className="ads-detail-title">علت رد</span>
                    <span className={`ads-detail-value`}>{ads.reject_reason}</span>
                  </p>
                ) : ''}
                <div className={"ads-main-props"}>
                  {ads.main_props && ads.main_props.map(prop => (
                    <p className={"ads-detail-main-props mb-2 pb-2 d-flex justify-content-between"}>
                      <span className="ads-detail-title">{prop.title}</span>
                      <span className="ads-detail-value text-primary">{`${prop.value} ${prop.unit ?? ''}`}</span>
                    </p>
                  ))}
                </div>
                {/* <div className={"ads-secondary-props"}>
                  {ads.secondary_props && ads.secondary_props.map((prop, index) => (
                    <p className={"ads-detail"}>
                      {prop.icon ? <IconTooltip prop={prop} id={`${ads.id}_secondary_${index}`} placement={"bottom"}/> :
                        <span className="ads-detail-title">{prop.title}</span>}
                      <span className="ads-detail-value">{`${prop.value} ${prop.unit ?? ''}`}</span>
                    </p>
                  ))}
                </div> */}
              </div>
            </Colxx>
            <Colxx md={12} className={(type === "all" || type === "unapproved") ? classes.metaFooter : "adver-list-footer"}>
                <div className="border-topp py-2 w-100 d-flex justify-content-between">
                  <div className={"ads-detail text-muted"}>
                    <span style={{fontSize: '17px'}} className="simple-icon-compass"></span>
                    <span style={{fontSize: '11px' , fontWeight: 'bold' , marginRight: '5px'}} className="ads-detail-value">{`${ads.city} ${ads.region}`}</span>
                  </div>
                  <div className={"ads-detail text-muted"}>
                    <span style={{fontSize: '15px'}} className={"simple-icon-clock"}/>
                    <span style={{fontSize: '11px'}} className={"ml-1"}>{ads.date}</span>
                  </div>
                </div>
            </Colxx>
            {(type === "all" || type === "unapproved") && (
              <Colxx md={12} className={classes.actionBar}>
                <div className={classes.actionGrid}>
                  <Link
                    to={`${adminRoot}/advertisements/edit/${ads.id}`}
                    className={`btn btn-outline-primary btn-sm ${classes.actionButton}`}
                    onClick={(event) => event.stopPropagation()}
                  >
                    <i className="simple-icon-pencil" />
                    <span>ویرایش</span>
                  </Link>

                  <UserCan to={"reject.advertisement"}>
                    <button
                      type="button"
                      className={`btn btn-outline-success btn-sm ${classes.actionButton}`}
                      onClick={(event) => handleCardAction(event, () => approve([ads.id]))}
                    >
                      <i className="simple-icon-check" />
                      <span>تایید</span>
                    </button>
                  </UserCan>

                  <UserCan to={"reject.advertisement"}>
                    <button
                      type="button"
                      className={`btn btn-outline-danger btn-sm ${classes.actionButton}`}
                      onClick={(event) => handleCardAction(event, () => reject([ads.id]))}
                    >
                      <i className="simple-icon-close" />
                      <span>رد</span>
                    </button>
                  </UserCan>

                  <UserCan to={"reject.advertisement"}>
                    <button
                      type="button"
                      className={`btn btn-outline-warning btn-sm ${classes.actionButton}`}
                      onClick={(event) => handleCardAction(event, () => priority([ads.id]))}
                    >
                      <i className="simple-icon-arrow-up-circle" />
                      <span>نردبان</span>
                    </button>
                  </UserCan>
                </div>
              </Colxx>
            )}
          </Row>
        </div>
      </ContextMenuTrigger>
    );
  };

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
    <Colxx md={12} className={"ads-wrapper list-card"}>
      <VirtuosoGrid
        style={{height: 600}}
        totalCount={advertisements.length}
        overscan={300}
        endReached={loadMore}
        components={{
          Item: ItemContainer,
          List: React.forwardRef(({children, ...props}, ref) => {
            return (
            <div ref={ref} style={{margin: 0, padding: 0}} {...props} className={'d-flex flex-wrap ss'}>
              {children}
              <ContextMenu id="menu_id" onShow={(e) => {
                onContextMenu(e, e.detail.data)
              }}>
                <UserCan to={"reject.advertisement"}>
                  <MenuItem onClick={onContextMenuClick} data={{action: 'reject'}}>
                    <i className="simple-icon-docs"/> <span>رد آگهی</span>
                  </MenuItem>
                  <MenuItem onClick={onContextMenuClick} data={{action: 'approve'}}>
                    <i className="simple-icon-drawer"/> <span>تایید</span>
                  </MenuItem>
                  <MenuItem onClick={onContextMenuClick} data={{action: 'priority'}}>
                    <i className="simple-icon-drawer"/> <span>نردبان آگهی</span>
                  </MenuItem>
                </UserCan>
              </ContextMenu>
            </div>
            );
          }),
        }}
        itemContent={(index) => {
          let ads = advertisements[index]
          return (<ItemWrapper ads={ads} collect={collect} isSelect={selectedItems.includes(ads.id)}/>)
        }}
      />
      <Modal isOpen={show_modal} toggle={() => setShowModal(!show_modal)} backdrop wrapClassName="modal-center">
        <ModalHeader>
        <span className="iconsminds-close close-button text-primary" onClick={() => setShowModal(!show_modal)}></span>
          فیلتر کردن آگهی
        </ModalHeader>
        <ModalBody>
          {loading ? <div className={"loading"}/> : (<>
            {(selectedCategory && allCategoryFilter[selectedCategory] && allCategoryFilter[selectedCategory]['current']) ? (
              <p className="btn btn-primary w-100 cursor_pointer" onClick={() => {
                setSelectedCategory(allCategoryFilter[selectedCategory]['current'].id)
              }}>{`بازگشت به ${allCategoryFilter[selectedCategory]['current'].name}`}</p>
            ) : ''}
            {categoryList && categoryList.map(category => (
              <Card className="mb-2">
                <CardBody className="p-3 ">
                  <p className="m-0 cursor_pointer" onClick={() => {
                    setSelectedCategory(category.id)
                  }}>{category.name}</p>
                </CardBody>
              </Card>
            ))}
          </>)}
          <Formik enableReinitialize initialValues={queryParams} onSubmit={(values) => {
            let url = new URLSearchParams({...values,category:selectedCategory})
            setQueryParams({...values,category:selectedCategory})
            setQueryString(url.toString())
            // setLink(`${adminPathApi}/advertisements/list?type=all&${url}`)
            history.push(`?${url}`)
          }}>
            <Form className="av-tooltip tooltip-label-right">
              {searchItems && searchItems.length > 0 && searchItems.map((searchItem) => (
                <Card>
                  <CardBody>
                  <p className="border-bottom">
                    {searchItem.label}
                    {/*{searchItem.icon && <img src={searchItem.icon}/>}*/}
                  </p>
                  <div>
                    {searchItem && searchItem.items && <div>
                      {searchItem.items.map(filter=>(
                        <AdvertisementCategoryFilter item={filter}/>
                      ))}
                    </div>
                    }
                  </div>
                  </CardBody>
                </Card>
              ))}
              <div className="d-flex justify-content-between mt-4">
                <Button color={"primary"} outline type={"submit"}>پیدا کن</Button>
                <Button color={"danger"} onClick={()=>setShowModal(false)}>بی خیال</Button>
              </div>
            </Form>
          </Formik>
        </ModalBody>
      </Modal>
      {showTransferModal ? (
        <TransferAdvertisement
          isOpen={showTransferModal}
          toggle={() => setShowTransferModal(!showTransferModal)}
          ads={advertisementToTransfer}
        />
      ) : ''}
      {showPreviewModal ? (
        <AdvertisementPreview
          isOpen={showPreviewModal}
          toggle={() => setShowPreviewModal(!showPreviewModal)}
          ads={advertisementToPreview}
        />
      ) : ''}
    </Colxx>
  );
}

export default AdvertisementList;

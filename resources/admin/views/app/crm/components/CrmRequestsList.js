import React, { useEffect, useState } from "react";
import { adminPathApi, adminRoot } from "../../../../constants/defaultValues";
import classnames from "classnames";
import { Button, Card, Row } from "reactstrap";
import { Colxx } from "../../../../components/common/CustomBootstrap";
import { ContextMenu, ContextMenuTrigger, MenuItem } from "react-contextmenu";
import useMousetrap from "../../../../hooks/use-mousetrap";
import { VirtuosoGrid } from "react-virtuoso";
import UserCan from "../../../../helpers/UserCan";
import CrmRequestPreview from "./CrmRequestPreview";
import CrmRequestEdit from "./CrmRequestEdit";
import CrmRequestTransfer from "./CrmRequestTransfer";

function collect(props) {
  return { data: props.data };
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
  requests,
  setRequests,
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
  // const history = useHistory();

  const [rejectReasons, setRejectReasons] = useState([]);
  const [showPreviewModal, setShowPreviewModal] = useState(false);
  const [requestToPreview, setRequestToPreview] = useState(false);
  const [showTransferModal, setShowTransferModal] = useState(false);
  const [advertisementToTransfer, setAdvertisementToTransfer] = useState(false);
  const [queryString, setQueryString] = useState(initQueryString);

  const [showEditModal, setShowEditModal] = useState(false);
  const [requestToEdit, setRequestToEdit] = useState(false);
  const [requestToTransfer, setRequestToTransfer] = useState(false);

  const reasons = ["اطلاعات ناقص است", "آگهی تکراری است"];

  useEffect(() => {
    let prefix = queryString ? `&${queryString}` : "";
    axios
      .get(`${adminPathApi}/crm/request-list?type=${type}${prefix}`)
      .then((res) => res.data)
      .then((data) => {
        let reject_reasons =
          "<select id='reject_reasons' class='form-control'>";
        reasons.map((reason) => {
          reject_reasons += `<option value="${reason}">${reason}</option>`;
        });
        reject_reasons += "</select>";
        setRejectReasons(reject_reasons);
        setRequests(data.data);
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
              let new_advertisements = [...advertisements];
              new_advertisements.map((ad, i) => {
                let num = data.data.findIndex((v) => v.id == ad.id);
                if (num !== -1) {
                  new_advertisements[i] = data.data[num];
                }
              });
              setRequests(new_advertisements);
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
          let new_advertisements = [...requests];
          new_advertisements.map((ad, i) => {
            let num = data.data.findIndex((v) => v.id == ad.id);
            if (num !== -1) {
              new_advertisements[i] = data.data[num];
            }
          });
          setRequests(new_advertisements);
          if (data.message) {
            swal(data.message, {
              icon: "success",
            });
          }
        }
      });
  };

  const open_transfer_modal = (ads) => {
    setRequestToTransfer(ads);
    setShowTransferModal(true);
  };

  const open_preview_modal = (ads) => {
    setRequestToPreview(ads);
    setShowPreviewModal(true);
  };

  const open_edit_modal = (ads) => {
    setRequestToEdit(ads);
    setShowEditModal(true);
  };

  const update_request = (new_request) => {
    let new_value = [...requests];
    let i = requests.findIndex((request) => request.id == new_request.id);
    new_value[i] = new_request;
    setRequests(new_value);
  };

  const ItemContainer = (props) => {
    return (
      <Colxx md={12} {...props}>
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
    }
  };

  const onCheckItem = (event, id) => {
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
      let newItems = [...requests];
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
    if (selectedItems.length >= requests.length) {
      if (isToggle) {
        setSelectedItems([]);
      }
    } else {
      setSelectedItems(requests.map((x) => x.id));
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

  const ItemWrapper = ({ req, isSelect, collect, ...props }) => {
    return (
      // <ContextMenuTrigger id="menu_id" data={req.id} collect={collect}>
      <Card
        key={req.id}
        onClick={(event) => onCheckItem(event, req.id)}
        className={classnames("crm-card", {
          // active: isSelect,
        })}
      >
        <Row>
          <Colxx md={2}>
            <span className={"ml-1"}>{req.category.name}</span>
          </Colxx>
          <Colxx md={2}>
            <div className={"d-flex flex-column"}>
              <span className={"ml-1"}>{req.city.name}</span>
              <span>{req.regions.join(" | ")}</span>
            </div>
          </Colxx>
          <Colxx md={1}>
            <span className={"ml-1"}>{req.user.name}</span>
          </Colxx>
          {type != "my-requests" ? (
            <Colxx md={2}>
              {req.admin ? (
                <span>
                  <span className={"ml-1"}>{req.admin.display_name}</span>
                  <span className={"d-inline-block"}>
                    <span>{req.admin.mobile}</span>
                    <span className={"fa fa-phone ml-1"} />
                  </span>
                </span>
              ) : (
                <span className={"ml-1"}>---</span>
              )}
            </Colxx>
          ) : (
            ""
          )}
          <Colxx md={1}>
            <span className={"ml-1"}>{req.date}</span>
          </Colxx>
          <Colxx md={4}>
            <div className={"btn-group"}>
              <Button
                outline
                onClick={() => {
                  open_preview_modal(req);
                }}
              >
                <span className={"fa fa-eye"} />
              </Button>
              {req.can_edit && (
                <Button
                  outline
                  onClick={() => {
                    open_edit_modal(req);
                  }}
                >
                  <span className={"fa fa-edit"} />
                </Button>
              )}
              {req.can_edit && (
                <Button
                  outline
                  onClick={() =>
                    history.push(
                      `${adminRoot}/crm/requestRelatedAdvers/${req.id}`
                    )
                  }
                >
                  <span className="fa fa-list-alt" />
                </Button>
              )}
              {req.can_transfer && (
                <Button
                  outline
                  onClick={() => {
                    open_transfer_modal(req);
                  }}
                >
                  <span className={"simple-icon-refresh"} />
                </Button>
              )}
            </div>
          </Colxx>
        </Row>
      </Card>
      // </ContextMenuTrigger>
    );
  };

  const loadMore = React.useCallback(() => {
    if (link) {
      axios
        .get(link)
        .then((res) => res.data)
        .then((data) => {
          setRequests([...requests, ...data.data]);
          setLink(data.links.next);
        })
        .catch((error) => console.log(error));
    }
  }, [link]);

  return (
    <Colxx md={12} className={"requests-wrapper"}>
      <VirtuosoGrid
        style={{ height: 600, overflowX: "hidden" }}
        totalCount={requests.length}
        overscan={300}
        endReached={loadMore}
        components={{
          Item: ItemContainer,
          List: React.forwardRef(({ children, ...props }, ref) => {
            return (
              <div
                ref={ref}
                style={{ margin: 0, padding: 0, overflowX: "hidden" }}
                {...props}
                className={"row"}
              >
                {/*              <Colxx md={12}>
                <Row className={"crm-card"}>
                  <Colxx md={2}>شهر</Colxx>
                  <Colxx md={2}>دسته بندی</Colxx>
                  <Colxx md={4}>نام مشتری</Colxx>
                </Row>
              </Colxx>*/}
                {children}
                <ContextMenu
                  id="menu_id"
                  onShow={(e) => {
                    onContextMenu(e, e.detail.data);
                  }}
                >
                  <UserCan to={"reject.advertisement"}>
                    <MenuItem
                      onClick={onContextMenuClick}
                      data={{ action: "reject" }}
                    >
                      <i className="simple-icon-docs" /> <span>رد آگهی</span>
                    </MenuItem>
                    <MenuItem
                      onClick={onContextMenuClick}
                      data={{ action: "approve" }}
                    >
                      <i className="simple-icon-drawer" /> <span>تایید</span>
                    </MenuItem>
                  </UserCan>
                </ContextMenu>
              </div>
            );
          }),
        }}
        itemContent={(index) => {
          let req = requests[index];
          return (
            <ItemWrapper
              req={req}
              collect={collect}
              isSelect={selectedItems.includes(req.id)}
            />
          );
        }}
      />
      {/*      <Modal isOpen={show_modal} toggle={() => setShowModal(!show_modal)} backdrop wrapClassName="modal-right">
        <ModalHeader>فیلتر کردن آگهی</ModalHeader>
        <ModalBody>
          {loading ? <div className={"loading"}/> : (<>
            {(selectedCategory && allCategoryFilter[selectedCategory] && allCategoryFilter[selectedCategory]['current']) ? (
              <p onClick={() => {
                setSelectedCategory(allCategoryFilter[selectedCategory]['current'].id)
              }}>{`بازگشت به ${allCategoryFilter[selectedCategory]['current'].name}`}</p>
            ) : ''}
            {categoryList && categoryList.map(category => (
              <p onClick={() => {
                setSelectedCategory(category.id)
              }}>{category.name}</p>
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
                <AdvertisementCategoryFilter item={searchItem}/>
              ))}
              <Button color={"primary"} outline type={"submit"}>پیدا کن</Button>
              <Button color={"danger"} onClick={()=>setShowModal(false)}>بی خیال</Button>
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
      ) : ''}*/}
      {showPreviewModal ? (
        <CrmRequestPreview
          isOpen={showPreviewModal}
          toggle={() => setShowPreviewModal(!showPreviewModal)}
          request={requestToPreview}
        />
      ) : (
        ""
      )}
      {showEditModal ? (
        <CrmRequestEdit
          isOpen={showEditModal}
          toggle={() => setShowEditModal(!showEditModal)}
          request={requestToEdit}
          update_request={update_request}
        />
      ) : (
        ""
      )}
      {showTransferModal ? (
        <CrmRequestTransfer
          isOpen={showTransferModal}
          toggle={() => setShowTransferModal(!showTransferModal)}
          request={requestToTransfer}
          update_request={update_request}
        />
      ) : (
        ""
      )}
    </Colxx>
  );
};

export default AdvertisementList;

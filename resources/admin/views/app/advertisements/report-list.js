import classnames from "classnames";
import React, { useEffect, useState } from "react";
import { connect } from "react-redux";
import { Link } from "react-router-dom";
import { Button, Card, Modal, ModalHeader, Row } from "reactstrap";
import { Colxx, Separator } from "../../../components/common/CustomBootstrap";
import { adminPathApi } from "../../../constants/defaultValues";
import Breadcrumb from "../../../containers/navs/Breadcrumb";
import { permissionAll } from "../../../redux/permissions/actions";
import classes from "./components/css/advertismentList.module.css";

const AllAdvertisementList = ({ match, history, location, currentUser }) => {
  const [advertisements, setAdvertisements] = useState([]);
  const [loading, setLoading] = useState(false);

  const [show_modal, setShowModal] = useState(false);
  const [reporters, setReporters] = useState([]);

  useEffect(() => {
    axios
      .get(`${adminPathApi}/advertisements/all-reports`)
      .then((res) => res.data)
      .then((data) => {
        setAdvertisements(data.data);
        setLoading(false);
      }).catch((e)=>{
      if (e.response.status==402){
        window.location.replace(e.response.data.url)
      }
    });
  }, []);

  const open_preview_modal = (adver) => {
    setShowModal(true);
    axios
      .get(`${adminPathApi}/advertisements/reports/${adver.id}`)
      .then((res) => res.data)
      .then((data) => {
        console.log(data.data);
        setReporters(data.data);
        set;
        setLoading(false);
      }).catch((e)=>{
      if (e.response.status==402){
        window.location.replace(e.response.data.url)
      }
    });
  };

  return (
    <>
      <Row>
        <Colxx xss="12">
          <Breadcrumb
            heading={"menu.advertisements.report-list"}
            match={match}
          />
          <Separator className="mb-5" />
        </Colxx>
      </Row>
      <Row>
        {advertisements.map((ads) => {
          return (
            <Colxx md={3} >
              <div className='react-contextmenu-wrapper' onClick={() => open_preview_modal(ads)}>
                <div
                  key={ads.id}
                  className={classnames("ads-card", "position-relative" , classes.mahdi)}
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
                    <Colxx md={12} className="adver-list-footer-report">
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
                  </Row>
                </div>
              </div>
            </Colxx>
          );
        })}
      </Row>

      <Modal
        isOpen={show_modal}
        toggle={() => setShowModal(false)}
        backdrop
        wrapClassName="modal-center"
      >
        <div className="p-2">
          {reporters.map((reporter) => {
            return (
              <>
                <div className={"group_widget my-3"}>
                  <ModalHeader className="mb-5"><div className={"group_widget_title h6"}>اطلاعات گزارش</div></ModalHeader>
                  <div className={`group_widget_content`}>
                    <div className={"vertical_widget_item"}>
                      <span>عنوان: </span>
                      <span>{reporter.reason}</span>
                    </div>
                    <div className={"vertical_widget_item"}>
                      <span>زمان گزارش: </span>
                      <span>{reporter.created_at}</span>
                    </div>
                    <div className={"vertical_widget_item"}>
                      <span> نام کاربر: </span>
                      <span>{reporter.user.full_name}</span>
                    </div>
                    <div className={"vertical_widget_item"}>
                      <span> شماره موبایل: </span>
                      <span>{reporter.user.mobile}</span>
                    </div>
                    <div className={"vertical_widget_item"}>
                      <span> توضیحات: </span>
                      <span>{reporter.description}</span>
                    </div>
                  </div>
                </div>
              </>
            );
          })}
        </div>
      </Modal>
    </>
  );
};

const mapStateToProps = ({ permissionsRoles, authUser: { currentUser } }) => {
  /*const {loading, errors, permissions} = permissionsRoles;*/
  return { /*permissions, errors, loading,*/ currentUser };
};

export default connect(mapStateToProps, {
  fetchPermission: permissionAll,
})(AllAdvertisementList);

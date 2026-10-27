import React, {useEffect, useState} from "react";
import {adminPathApi} from "../../../../constants/defaultValues";
import {Button, CardTitle, Modal, ModalBody, ModalHeader} from "reactstrap";

import { Swiper, SwiperSlide } from "swiper/react";

// Import Swiper styles
import "swiper/css";
import "swiper/css/free-mode";
import "swiper/css/navigation";
import "swiper/css/thumbs";

import { FreeMode, Navigation, Thumbs } from "swiper";
import {Row} from "reactstrap";

import LightboxGallery from "../../../../components/LightboxGallery";
import AdvertisementInfoWidget from "./AdvertisementInfoWidget";
import { Colxx } from "../../../../components/common/CustomBootstrap";

const AdvertisementPreview=({toggle, isOpen, ads})=>{
  const [loading,setLoading]=useState(true);
  const [detail,setDetail]=useState({});
  const [thumbsSwiper, setThumbsSwiper] = useState(null);
  const [currentImage,setCurrentImage]=useState(null)
  const [showLightbox,setShowLightbox]=useState(false)

  useEffect(()=>{
    axios
      .get(`${adminPathApi}/advertisements/preview/${ads.id}`)
      .then(res=>res.data)
      .then(data=>{
        setLoading(false)
        setDetail(data)
      })
  },[ads.id])

  if (loading){
    return <div className={"loading"}/>;
  }

  return (
    <Modal
      isOpen={isOpen}
      toggle={toggle}
      backdrop
      wrapClassName="modal-center"
      size="lg"
    >
      <div className={"modal-header advertisement-preview-header"}>
      <span className="iconsminds-close close-button text-primary" onClick={toggle}></span>
        {detail['breadcrumb'] && (
          <div className={"d-flex w-100 justify-content-between align-items-center"}>
            <ul className={"breadcrumb flex-grow-1 px-0"} style={{fontSize: '10px'}}>
              {detail['breadcrumb'].map((breadcrumb,index)=>(
                <li key={index} className={`breadcrumb-item ${(index==detail['breadcrumb'].length-1)?'active':''}`}>{breadcrumb['name']}</li>
              ))}
            </ul>
            <div className={"data"}>{detail['date']}</div>
          </div>
        )}
        <h2>{detail['title']}<span>{detail['location']}</span></h2>
        {detail['widgets'] && detail['widgets'].map((widget,index)=>(
          (widget.widget_type=="horizontal" && widget.items)?(
            <Row className={"mb-3 w-100 d-flex"}>
              {widget.items.map((item,index)=>(
                <Colxx key={index} sm={12} xs={12} md={4} className={"horizontal_widget_item d-flex align-items-center py-2"}>
                    <span className="text-muted mr-2">{item.info.title}</span>
                    <span className={"text-center text-primary"}>{item.info.value} {item.info.unit}</span>
                </Colxx>
              ))}
            </Row>
      ):''))}
      </div>
      <ModalBody>
        <Swiper
          style={{
            "--swiper-navigation-color": "#fff",
            "--swiper-pagination-color": "#fff",
          }}
          loop={false}
          spaceBetween={10}
          navigation={true}
          thumbs={{ swiper: thumbsSwiper }}
          modules={[FreeMode, Navigation, Thumbs]}
          className="mySwiper2"
        >
          {detail['gallery'] && detail['gallery'].map((galleryItem,index)=>(
            <SwiperSlide>
              <img src={galleryItem} alt={''} onClick={()=>{
                setCurrentImage(index)
                setShowLightbox(true)
              }}/>
            </SwiperSlide>
          ))}
        </Swiper>
        {detail['gallery'] && detail['gallery'].length>1 &&<Swiper
          onSwiper={setThumbsSwiper}
          loop={false}
          spaceBetween={10}
          slidesPerView={3}
          freeMode={true}
          watchSlidesProgress={true}
          modules={[FreeMode, Navigation, Thumbs]}
          className="mySwiper"
        >
          {detail['gallery'] && detail['gallery'].length>1 && detail['gallery'].map(galleryItem=>(
            <SwiperSlide>
              <img src={galleryItem} alt={''}/>
            </SwiperSlide>
          ))}
        </Swiper>}
        {showLightbox?(
          <LightboxGallery images={detail['gallery']} startIndex={currentImage} onClose={()=>setShowLightbox(false)}/>
        ):''}
        <div className={"mt-2"}>
          {detail['widgets'] && detail['widgets'].map((widget,index)=>(
            <AdvertisementInfoWidget key={index} widget={widget}/>
          ))}
        </div>
        <div className={"group_widget border-bottom-0 mt-2"}>
          <div className={"group_widget_title h6"}>فاصله از مراکز مهم</div>
            <div className={`group_widget_content w-100 m-0 row`}>
              {detail.important_places && detail.important_places.map((info)=>
                <div className={"vertical_widget_item my_vertical_widget_item w-50 border-left border-bottom"}>
                  {info.place && info.place.icon && info.place.label && <span>
                    { info.place.icon && <img src={info.place.icon}/>}
                    { info.place.label}
                  </span>}
                  {info.distance &&<span>
                    {info.distance}
                  </span>}
                </div>
              )}
            </div>
          </div>
          {detail['admins'] && (
          <div className={"group_widget mt-2 p-3"}>
            <div className={"group_widget_title h6"}>اطلاعات تماس</div>
            <div className={`group_widget_content`}>
              {detail['admins']['experts'] && detail['admins']['experts'].map((expert,index)=>(
                <Colxx md={12} lg={4}>
                  <div key={index} className={"contact_vertical_widget_item"}>
                  <span className="d-flex justify-content-center"><img src={expert.avatar} /></span>
                    <div className="d-grid w-100">
                      <p className="text-primary text-center">کارشناس: <span className="text-dark">{expert.display_name}</span></p>
                      <p className="text-muted text-center">تلفن همراه : </p>
                      <span className="w-100 d-flex justify-content-center text-muted">{expert.mobile}</span>
                    </div>
                  </div>
                </Colxx>
              ))}
              {detail['admins']['supervisors'] && detail['admins']['supervisors'].map((expert,index)=>(
                <Colxx md={12} lg={4}>
                  <div key={index} className={"contact_vertical_widget_item"}>
                    <span className="d-flex justify-content-center"><img src={expert.avatar} /></span>
                    <div className="d-grid w-100">
                      <p className="text-primary text-center">سرپرست: <span className="text-dark">{expert.display_name}</span></p>
                      <p className="text-muted text-center">تلفن همراه : </p>
                      <span className="w-100 d-flex justify-content-center text-muted">{expert.mobile}</span>
                    </div>
                  </div>
                </Colxx>
              ))}
              {detail['admins']['managers'] && detail['admins']['managers'].map((expert,index)=>(
                <Colxx md={12} lg={4}>
                  <div key={index} className={"contact_vertical_widget_item"}>
                    <span className="d-flex justify-content-center"><img src={expert.avatar} /></span>
                    <div className="d-grid w-100 align-items-center">
                      <p className="text-primary text-center">مدیر فروش: <span className="text-dark">{expert.display_name}</span></p>
                      <p className="text-muted text-center">تلفن همراه : </p>
                      <span className="w-100 d-flex justify-content-center text-muted">{expert.mobile}</span>
                    </div>
                  </div>
                </Colxx>
              ))}
            </div>
          </div>
        )}
      </ModalBody>
    </Modal>
  );
}

export default AdvertisementPreview;

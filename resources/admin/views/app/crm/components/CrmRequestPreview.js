import React, {useEffect, useState} from "react";
import {adminPathApi} from "../../../../constants/defaultValues";
import {Modal, ModalBody} from "reactstrap";

// Import Swiper styles
import "swiper/css";
import "swiper/css/free-mode";
import "swiper/css/navigation";
import "swiper/css/thumbs";
import CrmRequestPreviewPropertyItem from "./CrmRequestPreviewPropertyItem";

const CrmRequestPreview = ({toggle, isOpen, request}) => {
  const [loading, setLoading] = useState(true);
  const [detail, setDetail] = useState({});
  const [thumbsSwiper, setThumbsSwiper] = useState(null);
  const [currentImage, setCurrentImage] = useState(null)
  const [showLightbox, setShowLightbox] = useState(false)

  useEffect(() => {
    axios
      .get(`${adminPathApi}/crm/preview/${request.id}`)
      .then(res => res.data)
      .then(data => {
        setDetail(data)
        setLoading(false)
      })
  }, [request.id])

  if (loading) {
    return <div className={"loading"}/>;
  }

  return (
    <Modal
      isOpen={isOpen}
      toggle={toggle}
      backdrop
      wrapClassName="modal-right"
    >
      {console.log(detail)}
      <ModalBody>
        <div className={"crm-preview"}>
          <div className="crm-preview-main-info">
            <p>
              <span>دسته بندی: </span>
              <span>{detail.category.name}</span>
            </p>
            <p>
              <span>شهر: </span>
              <span>{detail.city.name}</span>
            </p>
            {detail.regions && detail.regions.length > 0 ? (
              <p>
                <span>خیابان‌های درخواستی: </span>
                <span>{detail.regions.join(' | ')}</span>
              </p>
            ) : ''}
            <p>
              <span>نام مشتری: </span>
              <span>{detail.user.name}</span>
            </p>
            {detail.user.mobile &&
              <p>
                <span>شماره تماس: </span>
                <span>{detail.user.mobile}</span>
              </p>
            }
          </div>
          <div className={"crm-preview-properties-wrapper"}>
            {detail.properties && Object.values(detail.properties).map(property => (
              <CrmRequestPreviewPropertyItem property={property}/>
            ))}
          </div>
        </div>

      </ModalBody>
    </Modal>
  );
}

export default CrmRequestPreview;

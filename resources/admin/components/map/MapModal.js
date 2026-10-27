import React from "react";
import {Button, Modal, ModalBody, ModalFooter} from "reactstrap";
import {MapContainer, TileLayer} from "react-leaflet";
import {MapLocationSelect} from "../../views/user/registerShop/ContactInfo";

const MapModal = ({modal, setFieldValue, setModal}) => {

  return (
    <Modal
      size={'lg'}
      isOpen={modal}
    >
      <ModalBody>
        <MapContainer
          style={{height: "500px", width: "100%"}}
          zoom={12}
          center={[35.699721, 411.338076]}
        >
          <TileLayer
            attribution='&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
            url="https://{s}.google.com/vt/lyrs=m@221097413&x={x}&y={y}&z={z}"
            subdomains={['mt0', 'mt1', 'mt2', 'mt3']}
            maxZoom={20}
            minZoom={2}
          />
          <MapLocationSelect onChange={setFieldValue}/>
        </MapContainer>
      </ModalBody>
      <ModalFooter>
        <Button
          color="primary"
          onClick={() => {
            setModal(false);
          }}>
          ثبت
        </Button>
      </ModalFooter>
    </Modal>
  )
}

export default MapModal

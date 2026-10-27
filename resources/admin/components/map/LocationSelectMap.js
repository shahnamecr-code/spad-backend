import React, {useState} from "react";
import {Button, Modal, ModalBody, ModalFooter, ModalHeader} from "reactstrap";
import MyMap from "./MyMap";
import Marker from "./Marker";

const LocationSelectMap = ({center, value, label, onChange}) => {
  const [open, setOpen] = useState(false);
  const [point, setPoint] = useState(value);

  return (
    <>
      <Button onClick={() => setOpen(true)}>{label ? label : 'انتخاب موقعیت'}</Button>
      <Modal isOpen={open} size={'lg'} toggle={() => setOpen(false)}>
        <ModalHeader toggle={() => setOpen(false)}>{label ? label : 'انتخاب موقعیت'}</ModalHeader>
        <ModalBody>
          <MyMap
            map={Map}
            center={value ? value : [51.41, 35.7575]}
            onClick={(map, event) => {
              setPoint(event.lngLat.toArray());
            }}
          >
            {!!point &&
            <Marker
              coordinates={point}
              anchor="bottom"
            />
            }
          </MyMap>
        </ModalBody>
        <ModalFooter>
          <Button
            color="primary"
            onClick={(e) => {
              e.preventDefault();
              onChange(point);
              setOpen(false);
            }}>
            ثبت
          </Button>
        </ModalFooter>
      </Modal>
    </>
  )
}

export default LocationSelectMap

import DrawControl from "react-mapbox-gl-draw";
import MyMap from "./MyMap";
import React, {useRef, useState} from "react";
import {Button, Modal, ModalBody, ModalFooter, ModalHeader} from "reactstrap";
import {NotificationManager} from "../common/react-notifications";

const AreaSelectMap = ({value, center, onChange}) => {
  const drawRef = useRef();
  const [open, setOpen] = useState(false);

  return (
    <>
      <Button onClick={() => setOpen(true)}>انتخاب محدوده</Button>
      <Modal isOpen={open} size={'lg'} toggle={() => setOpen(false)}>
        <ModalHeader toggle={() => setOpen(false)}>انتخاب محدوده</ModalHeader>
        <ModalBody>
          <MyMap
            map={Map}
            center={center ? center : [51.41, 35.7575]}
            zoom={[6]}
            onStyleLoad={(map) => {
              if (value) {
                drawRef.current.draw.add(value)
              }
            }}
          >
            <DrawControl
              ref={drawRef}
              displayControlsDefault={false}
              controls={{polygon: true, trash: true}}
              onDrawCreate={(event) => {
                //console.log(event, 'create')
              }}
              onDrawUpdate={(event) => {
                //console.log(event, 'update')
              }}
              onDrawDelete={(event) => {
                //console.log(event, 'delete')
              }}
              onDrawModeChange={(event) => {
                //console.log(event);
              }}
            />
          </MyMap>
        </ModalBody>
        <ModalFooter>
          <Button
            color="primary"
            onClick={(e) => {
              e.preventDefault();
              let allf = drawRef.current.draw.getAll().features;
              if (allf.length > 1 || allf.length === 0) {
                NotificationManager.error('محدوده انتخاب شده باید یک عدد باشد');
                return;
              }
              onChange(allf[0]);
              setOpen(false);
            }}>
            ثبت
          </Button>
        </ModalFooter>
      </Modal>
    </>
  )
}

export default AreaSelectMap;

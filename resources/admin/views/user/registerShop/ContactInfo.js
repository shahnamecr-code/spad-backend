import React, {createRef} from "react";
import {MapContainer, Marker, TileLayer, useMapEvents} from "react-leaflet";
import {Field, useFormikContext} from "formik";
import {Button, FormGroup, Label, Modal, ModalBody, ModalFooter} from "reactstrap";
import Select from "react-select";
import {Colxx} from "../../../components/common/CustomBootstrap";

const MapLocationSelect = (props) => {
  const [position, setPosition] = React.useState(false);
  useMapEvents({
    click: (e) => {
      setPosition(e.latlng);
      props.onChange('lat_and_long', [e.latlng.lat, e.latlng.lng]);
    },
  });
  return position ? <Marker position={position}/> : <></>;
};

const ContactInfo = () => {
  const {setFieldValue, setTouched, values} = useFormikContext();
  const [locationModal, setLocationModal] = React.useState(false);
  const locationRef = createRef();

  const stateList = [
    {label: "آذر بایجان شرقی", value: 21},
    {label: "آذر بایجان شرقی", value: 22},
    {label: "آذر بایجان شرقی", value: 23},
    {label: "آذر بایجان شرقی", value: 24},
    {label: "آذر بایجان شرقی", value: 25},
  ];
  return (
    <>
      <FormGroup className="has-float-label  mb-4">
        <Label>
          استان
        </Label>
        <Select
          name={'state_id'}
          options={stateList}
          className={'react-select'}
          classNamePrefix="react-select"
          onChange={(val) => setFieldValue('state_id', val.value)}
          value={stateList ? stateList.find(option => option.value === values.state_id) : ''}
        />
      </FormGroup>
      <FormGroup className="has-float-label  mb-4">
        <Label>
          شهر
        </Label>
        <Select
          name={'city_id'}
          options={stateList}
          className={'react-select'}
          classNamePrefix="react-select"
          onChange={(val) => setFieldValue('city_id', val.value)}
          value={stateList ? stateList.find(option => option.value === values.city_id) : ''}
        />
      </FormGroup>
      <FormGroup className="has-float-label  mb-4">
        <Label>
          آدرس
        </Label>
        <Field className="form-control" name={'address'} maxLength={255}/>
      </FormGroup>
      <FormGroup className="has-float-label  mb-4">
        <Label>
          کد پستی
        </Label>
        <Field className="form-control" name={'post_code'} maxLength={10}/>
      </FormGroup>
      <FormGroup className="has-float-label  mb-4">
        <Label>
          موقیت مکانی
        </Label>
        <Field
          innerRef={locationRef}
          className="form-control"
          value={values.lat_and_long && "ثبت شد"}
          onFocus={() => locationRef.current.blur()}
          onClick={() => {
            locationRef.current.blur();
            setLocationModal(true);
          }}
          style={{cursor: 'pointer'}}
        />

        <Modal
          size={'lg'}
          isOpen={locationModal}
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
                setLocationModal(false);
              }}>
              ثبت
            </Button>
          </ModalFooter>
        </Modal>
      </FormGroup>
      <FormGroup className="has-float-label  mb-4">
        <Label>
          تلفن همراه
        </Label>
        <Field className="form-control" name={'mobile'} maxLength={11}/>
      </FormGroup>
      <FormGroup className="mb-4">
        <FormGroup row>
          <Colxx md={10}>
            <FormGroup className={'has-float-label'}>
              <Label>
                تلفن ثابت
              </Label>
              <Field className="form-control" name={'phone'} maxLength={8}/>
            </FormGroup>
          </Colxx>
          <Colxx md={2} className={'p-0 pr-3'}>
            <FormGroup className={'has-float-label'}>
              <Label>کد شهر</Label>
              <Field className="form-control disabled" name={'city_code'} onChange={undefined}/>
            </FormGroup>
          </Colxx>
        </FormGroup>
      </FormGroup>
    </>
  );
}

export default ContactInfo;
export {MapLocationSelect}

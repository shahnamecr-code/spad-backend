import React, { useState } from "react";
import {
  Button,
  Card,
  CardBody,
  FormGroup,
  Label,
  Row,
} from "reactstrap";
import { Field, FieldArray, useFormikContext } from "formik";
import { Colxx } from "../../../../components/common/CustomBootstrap";
import { FormikReactSelect } from "../../../../containers/form-validations/FormikFields";
import AdvertisementSection from "./AdvertisementSection";
import PropertyToolsFields from "./PropertyToolsFields";

const ImportantPlacesSection = ({ options }) => {
  const [open, setOpen] = useState(true);
  const { values, setFieldValue, setFieldTouched } = useFormikContext();

  return (
    <Card className="spad-ad-section mb-4">
      <div className="spad-ad-section__header">
        <div className="spad-ad-section__title">
          <span className="spad-ad-section__icon spad-ad-section__icon--teal">
            <i className="simple-icon-location-pin" />
          </span>
          <div>
            <h3>فاصله از مراکز مهم</h3>
            <small>دسترسی ملک به نقاط مهم اطراف را ثبت کنید.</small>
          </div>
        </div>
        <Button
          type="button"
          color="link"
          className="spad-ad-collapse-btn"
          onClick={() => setOpen((current) => !current)}
        >
          <i className={open ? "simple-icon-arrow-up" : "simple-icon-arrow-down"} />
        </Button>
      </div>

      {open ? (
        <CardBody className="spad-ad-section__body">
          <FieldArray name="important_places">
            {({ remove, push }) => (
              <div className="spad-ad-important-list">
                {(values.important_places || []).map((field, index) => (
                  <div className="spad-ad-important-row" key={`important-place-${index}`}>
                    <span className="spad-ad-row-number">{index + 1}</span>

                    <FormGroup className="mb-0">
                      <Label>مرکز مهم</Label>
                      <FormikReactSelect
                        options={options || []}
                        name={`important_places.${index}.place`}
                        value={
                          options && field.place
                            ? options.find((opt) =>
                                field.place.label
                                  ? opt.label === field.place.label
                                  : opt.label === field.place
                              )
                            : undefined
                        }
                        onChange={setFieldValue}
                        onBlur={setFieldTouched}
                        getOptionLabel={(option) => option.label}
                        getOptionValue={(option) => option}
                        className="spad-ad-react-select"
                      />
                    </FormGroup>

                    <FormGroup className="mb-0">
                      <Label>فاصله</Label>
                      <div className="spad-ad-distance-input">
                        <Field
                          className="form-control"
                          name={`important_places.${index}.distance`}
                          type="text"
                          placeholder="مثلاً 500 متر"
                        />
                      </div>
                    </FormGroup>

                    <Button
                      type="button"
                      color="danger"
                      outline
                      className="spad-ad-remove-row"
                      disabled={(values.important_places || []).length <= 1}
                      onClick={() => remove(index)}
                      title="حذف این مورد"
                    >
                      <i className="simple-icon-trash" />
                    </Button>
                  </div>
                ))}

                <Button
                  type="button"
                  color="primary"
                  outline
                  className="spad-ad-add-row"
                  onClick={() => push({ distance: "", place: "" })}
                >
                  <i className="simple-icon-plus ml-1" />
                  افزودن مرکز مهم
                </Button>
              </div>
            )}
          </FieldArray>
        </CardBody>
      ) : null}
    </Card>
  );
};

const AdvertisementFormLayout = ({
  mode = "create",
  normalBox = [],
  sideBox = [],
  properties = {},
  options = [],
  fileUrls = [],
  setFileUrls,
  categoryName,
  submitting = false,
  onBack,
  extraContent,
}) => {
  const isEdit = mode === "edit";

  return (
    <div className="spad-ad-form-shell">
      <div className="spad-ad-form-hero">
        <div className="spad-ad-form-hero__content">
          <span className="spad-ad-eyebrow">
            {isEdit ? "SPAD PROPERTY EDITOR" : "SPAD PROPERTY PUBLISHER"}
          </span>
          <h2>{isEdit ? "ویرایش کامل آگهی" : "ثبت آگهی جدید"}</h2>
          <p>
            {isEdit
              ? "تمام اطلاعات، تصاویر، امکانات آنلاین و ویژگی‌های فایل را در یک فرم یکپارچه ویرایش کنید."
              : "اطلاعات فایل، موقعیت، تصاویر و امکانات تکمیلی ملک را در یک فرم یکپارچه ثبت کنید."}
          </p>
        </div>

        <div className="spad-ad-form-hero__meta">
          {categoryName ? (
            <div className="spad-ad-category-badge">
              <i className="simple-icon-tag" />
              <span>دسته‌بندی</span>
              <strong>{categoryName}</strong>
            </div>
          ) : null}
          <div className="spad-ad-mode-badge">
            <i className={isEdit ? "simple-icon-pencil" : "simple-icon-plus"} />
            <span>{isEdit ? "حالت ویرایش" : "آگهی جدید"}</span>
          </div>
        </div>
      </div>

      <Row className="spad-ad-form-columns">
        <Colxx xxs="12" lg="8" className="spad-ad-form-main">
          {normalBox && normalBox.length ? (
            normalBox.map((wrapper, index) => (
              <AdvertisementSection
                key={`normal-${wrapper.id || index}`}
                wrapper={wrapper}
                properties={properties}
                fileUrls={fileUrls}
                setDefaultFileUrls={setFileUrls}
              />
            ))
          ) : (
            <Card className="spad-ad-section mb-4">
              <CardBody className="spad-ad-empty-section">
                <i className="simple-icon-info" />
                <span>برای این دسته‌بندی بخش اصلی تعریف نشده است.</span>
              </CardBody>
            </Card>
          )}
        </Colxx>

        <Colxx xxs="12" lg="4" className="spad-ad-form-side">
          {sideBox &&
            sideBox.map((wrapper, index) => (
              <AdvertisementSection
                key={`side-${wrapper.id || index}`}
                wrapper={wrapper}
                properties={properties}
                fileUrls={fileUrls}
                setDefaultFileUrls={setFileUrls}
              />
            ))}
        </Colxx>
      </Row>

      <ImportantPlacesSection options={options} />

      <PropertyToolsFields />

      {extraContent ? <div className="spad-ad-extra-content">{extraContent}</div> : null}

      <div className="spad-ad-savebar">
        <div className="spad-ad-savebar__info">
          <span className="spad-ad-savebar__icon">
            <i className="simple-icon-check" />
          </span>
          <div>
            <strong>{isEdit ? "ذخیره تغییرات آگهی" : "ثبت و ذخیره آگهی"}</strong>
            <small>قبل از ذخیره، اطلاعات مهم و تصاویر را یک‌بار بررسی کنید.</small>
          </div>
        </div>

        <div className="spad-ad-savebar__actions">
          {onBack ? (
            <Button type="button" color="light" className="spad-ad-secondary-btn" onClick={onBack}>
              بازگشت
            </Button>
          ) : null}
          <Button type="submit" color="primary" className="spad-ad-primary-btn" disabled={submitting}>
            {submitting ? (
              <>
                <span className="spinner-border spinner-border-sm ml-2" role="status" aria-hidden="true" />
                در حال ذخیره...
              </>
            ) : (
              <>
                <i className="simple-icon-check ml-2" />
                {isEdit ? "ذخیره تغییرات" : "ثبت آگهی"}
              </>
            )}
          </Button>
        </div>
      </div>
    </div>
  );
};

export default AdvertisementFormLayout;

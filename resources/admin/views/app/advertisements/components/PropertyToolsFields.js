import React from "react";
import DatePicker, { DateObject } from "react-multi-date-picker";
import persian from "react-date-object/calendars/persian";
import persian_fa from "react-date-object/locales/persian_fa";
import gregorian from "react-date-object/calendars/gregorian";
import Num2persian from "num2persian";
import {
  Card,
  CardBody,
  Col,
  FormGroup,
  FormText,
  Label,
  Row,
} from "reactstrap";
import { Field, useFormikContext } from "formik";
import { FormikSwitch } from "../../../../containers/form-validations/FormikFields";
import MediaLibraryButton from "../../../../components/mediaLibrary/MediaLibrary";

const faNumber = (value) => {
  const number = String(value || "").replace(/[^0-9]/g, "");
  if (!number) return "";
  return `${Number(number).toLocaleString("fa-IR")} تومان`;
};

const faNumberWords = (value) => {
  const digits = String(value || "").replace(/[^0-9]/g, "");
  if (!digits) return "";
  try {
    return `${Num2persian(Number(digits))} تومان`;
  } catch (_) {
    return "";
  }
};

const toPersianDateObject = (value) => {
  if (!value) return null;

  const normalized = String(value).split("T")[0].replace(/\//g, "-");
  if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized)) return null;

  try {
    return new DateObject({
      date: normalized,
      format: "YYYY-MM-DD",
      calendar: gregorian,
    }).convert(persian);
  } catch (_) {
    return null;
  }
};

const toGregorianDateString = (value) => {
  if (!value) return "";
  return new DateObject(value).convert(gregorian).format("YYYY-MM-DD");
};

const MoneyField = ({ name, label, hint, icon = "simple-icon-wallet" }) => {
  const { values } = useFormikContext();
  const parts = name.split(".");
  let value = values;
  parts.forEach((part) => {
    value = value && value[part] !== undefined ? value[part] : "";
  });

  return (
    <FormGroup className="spad-ad-money-field">
      <Label>{label}</Label>
      <div className="spad-ad-input-with-icon">
        <i className={icon} />
        <Field name={name} type="number" min="0" className="form-control" />
      </div>
      {value ? (
        <div className="spad-ad-money-preview" style={{ display: "block", lineHeight: 1.9 }}>
          <div>{faNumber(value)}</div>
          {faNumberWords(value) ? <strong style={{ display: "block", fontWeight: 600 }}>{faNumberWords(value)}</strong> : null}
        </div>
      ) : null}
      {hint ? <FormText color="muted">{hint}</FormText> : null}
    </FormGroup>
  );
};

const SwitchField = ({ name, label }) => {
  const { values, setFieldValue, setFieldTouched } = useFormikContext();
  const parts = name.split(".");
  let value = values;
  parts.forEach((part) => {
    value = value && value[part] !== undefined ? value[part] : false;
  });

  return (
    <div className="spad-ad-feature-switch">
      <div>
        <strong>{label}</strong>
      </div>
      <FormikSwitch
        name={name}
        className="custom-switch custom-switch-primary"
        value={!!value}
        onChange={setFieldValue}
        onBlur={setFieldTouched}
      />
    </div>
  );
};

const PropertyToolsFields = () => {
  const { values, setFieldValue } = useFormikContext();
  const tools = values.property_tools || {};
  const tourFiles = Array.isArray(tools.virtual_tour_files)
    ? tools.virtual_tour_files
    : [];

  return (
    <Card className="spad-ad-tools-card mb-4">
      <div className="spad-ad-tools-header">
        <div className="spad-ad-section__title">
          <span className="spad-ad-section__icon spad-ad-section__icon--purple">
            <i className="simple-icon-grid" />
          </span>
          <div>
            <h3>امکانات آنلاین و اطلاعات مالی ملک</h3>
            <small>قابلیت‌های تکمیلی که در صفحه معرفی ملک به کاربر نمایش داده می‌شوند.</small>
          </div>
        </div>
        <span className="spad-ad-new-feature-badge">امکانات تکمیلی</span>
      </div>

      <CardBody className="spad-ad-tools-body">
        <div className="spad-ad-document-panel">
          <div className="spad-ad-finance-title">
            <span className="spad-ad-tool-icon spad-ad-tool-icon--blue">
              <i className="simple-icon-doc" />
            </span>
            <div>
              <h4>نوع سند ملک</h4>
              <p>نوع سندی که در صفحه معرفی ملک به کاربر نمایش داده می‌شود.</p>
            </div>
          </div>
          <FormGroup className="mb-0">
            <Label>نوع سند</Label>
            <Field as="select" name="property_tools.document_type" className="form-control">
              <option value="">انتخاب نوع سند</option>
              <option value="single_leaf">تک برگ ملکی</option>
              <option value="endowment">اوقافی</option>
              <option value="pre_sale">پیش فروش</option>
            </Field>
          </FormGroup>
        </div>

        <div className="spad-ad-document-panel mb-4">
          <div className="spad-ad-finance-title">
            <span className="spad-ad-tool-icon spad-ad-tool-icon--teal">
              <i className="simple-icon-shield" />
            </span>
            <div>
              <h4>تأییدات مدیریتی آگهی</h4>
              <p>این وضعیت‌ها فقط توسط مدیریت قابل فعال‌سازی هستند و در صفحه معرفی ملک نمایش داده می‌شوند.</p>
            </div>
          </div>

          <Row>
            <Col md={6} className="mb-2">
              <SwitchField
                name="property_tools.owner_identity_verified"
                label="احراز هویت مالک"
              />
            </Col>
            <Col md={6} className="mb-2">
              <SwitchField
                name="property_tools.documents_verified"
                label="تأیید مدارک"
              />
            </Col>
            <Col md={6} className="mb-2">
              <SwitchField
                name="property_tools.below_expert_price"
                label="زیر قیمت کارشناسی"
              />
            </Col>
          </Row>
        </div>

        <div className="spad-ad-info-banner">
          <i className="simple-icon-info" />
          <div>
            <strong>اطلاعات این بخش مستقیماً به صفحه ملک متصل است.</strong>
            <span>
              برای تور ۳۶۰ می‌توانید فایل پانوراما یا لینک تور وارد کنید؛ نقشه سه‌بعدی نیز فقط توسط مدیریت و از طریق لینک Viewer فعال می‌شود.
            </span>
          </div>
        </div>

        <Row>
          <Col md={6} className="mb-4">
            <div className="spad-ad-tool-panel h-100">
              <div className="spad-ad-tool-panel__heading">
                <span className="spad-ad-tool-icon spad-ad-tool-icon--blue">360°</span>
                <div>
                  <h4>بازدید آنلاین ۳۶۰ درجه</h4>
                  <p>تور مجازی یا پانورامای تعاملی ملک</p>
                </div>
              </div>

              <SwitchField
                name="property_tools.virtual_tour_enabled"
                label="نمایش دکمه بازدید آنلاین ۳۶۰ درجه"
              />

              <FormGroup>
                <Label>فایل / تصویر تور مجازی</Label>
                <div className="spad-ad-media-field spad-ad-media-field--large">
                  <MediaLibraryButton
                    multiple={false}
                    defaultFileIdz={tourFiles}
                    onSubmit={(files) =>
                      setFieldValue("property_tools.virtual_tour_files", files || [])
                    }
                    onChange={(files) =>
                      setFieldValue("property_tools.virtual_tour_files", files || [])
                    }
                  />
                </div>
                <FormText color="muted">پیشنهاد: تصویر پانورامای ۲:۱ JPG/WebP یا فایل HTML مستقل.</FormText>
              </FormGroup>

              <FormGroup className="mb-0">
                <Label>لینک تور مجازی (اختیاری)</Label>
                <div className="spad-ad-input-with-icon">
                  <i className="simple-icon-link" />
                  <Field
                    name="property_tools.virtual_tour_url"
                    type="url"
                    className="form-control"
                    placeholder="https://..."
                    dir="ltr"
                  />
                </div>
                <FormText color="muted">اگر لینک وارد شود، نسبت به فایل آپلودی اولویت دارد.</FormText>
              </FormGroup>
            </div>
          </Col>

          <Col md={6} className="mb-4">
            <div className="spad-ad-tool-panel h-100">
              <div className="spad-ad-tool-panel__heading">
                <span className="spad-ad-tool-icon spad-ad-tool-icon--teal">
                  <i className="simple-icon-layers" />
                </span>
                <div>
                  <h4>نقشه و پلان ۳بعدی ملک</h4>
                  <p>نمایش تعاملی مدل سه‌بعدی، پلان معماری یا مدل Embed ملک</p>
                </div>
              </div>

              <SwitchField
                name="property_tools.floorplan_3d_enabled"
                label="فعال‌سازی نقشه و پلان ۳بعدی"
              />

              <FormGroup className="mb-0">
                <Label>لینک نقشه / مدل سه‌بعدی</Label>
                <div className="spad-ad-input-with-icon">
                  <i className="simple-icon-link" />
                  <Field
                    name="property_tools.floorplan_3d_url"
                    type="url"
                    className="form-control"
                    placeholder="https://..."
                    dir="ltr"
                  />
                </div>
                <FormText color="muted">
                  لینک Embed یا نمایش عمومی سرویس‌هایی مانند Sketchfab، Matterport یا هر Viewer سازگار با iframe را وارد کنید.
                </FormText>
              </FormGroup>

              {tools.floorplan_3d_enabled && !tools.floorplan_3d_url ? (
                <div className="spad-ad-note-box mt-3">
                  <i className="simple-icon-info" />
                  <span>برای نمایش دکمه نقشه سه‌بعدی در صفحه ملک، وارد کردن لینک Viewer الزامی است.</span>
                </div>
              ) : null}
            </div>
          </Col>
        </Row>

        <div className="spad-ad-finance-grid">
          <div className="spad-ad-finance-panel">
            <div className="spad-ad-finance-title">
              <span className="spad-ad-tool-icon spad-ad-tool-icon--gold">
                <i className="simple-icon-home" />
              </span>
              <div>
                <h4>وضعیت رهن و تخلیه ملک</h4>
                <p>وضعیت فعلی ملک و تاریخ تخلیه مستاجر را مشخص کنید.</p>
              </div>
            </div>

            <Row>
              <Col md={6}>
                <FormGroup>
                  <Label>وضعیت فعلی ملک</Label>
                  <Field
                    as="select"
                    name="property_tools.rental_occupancy_status"
                    className="form-control"
                    onChange={(event) => {
                      const value = event.target.value;
                      setFieldValue("property_tools.rental_occupancy_status", value);
                      if (value !== "tenant") {
                        setFieldValue("property_tools.rental_evacuation_date", "");
                      }
                    }}
                  >
                    <option value="vacant">تخلیه است</option>
                    <option value="tenant">در حال حاضر در رهن مستاجر است</option>
                  </Field>
                </FormGroup>
              </Col>
              {tools.rental_occupancy_status === "tenant" ? (
                <Col md={6}>
                  <FormGroup>
                    <Label>تاریخ تخلیه مستاجر</Label>
                    <DatePicker
                      calendar={persian}
                      locale={persian_fa}
                      format="YYYY/MM/DD"
                      calendarPosition="bottom-right"
                      containerClassName="w-100"
                      inputClass="form-control"
                      value={toPersianDateObject(tools.rental_evacuation_date)}
                      onChange={(date) => {
                        const selectedDate = Array.isArray(date) ? date[0] : date;
                        setFieldValue(
                          "property_tools.rental_evacuation_date",
                          toGregorianDateString(selectedDate || null)
                        );
                      }}
                      placeholder="تاریخ تخلیه را انتخاب کنید"
                      editable={false}
                      required
                    />
                  </FormGroup>
                </Col>
              ) : null}
            </Row>

            <Row>
              <Col md={6}>
                <MoneyField
                  name="property_tools.rental_deposit_ceiling"
                  label="سقف رهن / ودیعه"
                  hint="در صورت خالی بودن در صفحه ملک نمایش داده نمی‌شود."
                  icon="simple-icon-wallet"
                />
              </Col>
              <Col md={6}>
                <MoneyField
                  name="property_tools.rental_monthly_rent"
                  label="اجاره ماهانه"
                  hint="مبلغ توافقی مدنظر مالک یا فایلینگ."
                  icon="simple-icon-calendar"
                />
              </Col>
            </Row>
          </div>

          <div className="spad-ad-finance-panel">
            <div className="spad-ad-finance-title">
              <span className="spad-ad-tool-icon spad-ad-tool-icon--purple">
                <i className="simple-icon-calculator" />
              </span>
              <div>
                <h4>وام قابل دریافت روی سند ملک</h4>
                <p>کنترل سقف و فعال بودن ماشین حساب وام</p>
              </div>
            </div>

            <SwitchField
              name="property_tools.mortgage_enabled"
              label="فعال‌سازی ماشین حساب وام برای این ملک"
            />

            <MoneyField
              name="property_tools.mortgage_max_amount"
              label="حداکثر وام قابل دریافت روی این سند"
              hint="محاسبات اقساط و هزینه اوراق حداکثر تا این مبلغ انجام می‌شود."
              icon="simple-icon-calculator"
            />
          </div>
        </div>

        <div className="spad-ad-note-box">
          <i className="simple-icon-settings" />
          <span>
            نرخ سود، مدت بازپرداخت، مبلغ پوشش هر برگ و قیمت اوراق از مسیر «تنظیمات ← تنظیمات وام» قابل ویرایش است.
          </span>
        </div>
      </CardBody>
    </Card>
  );
};

export default PropertyToolsFields;

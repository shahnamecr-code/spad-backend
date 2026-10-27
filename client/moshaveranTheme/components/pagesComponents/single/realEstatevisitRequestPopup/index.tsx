import Popup, { IPopupProps } from "../../../general/popup/Popup";
import styles from "./realEstatevisitRequestPopup.module.scss";
import { Formik, Form, Field, FieldArray } from "formik";

// datePicker Imports
import DatePicker from "react-multi-date-picker";
import TimePicker from "react-multi-date-picker/plugins/time_picker";
import persian from "react-date-object/calendars/persian";
import persian_fa from "react-date-object/locales/persian_fa";
import { useEffect, useState } from "react";
import { postMeeting } from "../../../../services/AdsServices";
import { BsFillAlarmFill } from "@react-icons/all-files/bs/BsFillAlarmFill";
import { BsCalendar } from "@react-icons/all-files/bs/BsCalendar";
import Button from "../../../general/button/Button";
import { AiOutlineMinusCircle } from "@react-icons/all-files/ai/AiOutlineMinusCircle";
import { BsPlus } from "@react-icons/all-files/bs/BsPlus";

interface IProps extends IPopupProps {
  advId: number | string;
}

const RealEstatevisitRequestPopup = (props: IProps) => {
  const handleSubmit = (values: any) => {
    postMeeting(String(props.advId), values).then((res) => {});
  };

  return (
    <Popup width={900} overflow="visible" {...props} title="درخواست بازدید">
      <div className={styles.container}>
        <p className={styles.descriptiveParagraph}>
          از طریق این قسمت میتوانید ایام و ساعاتی را برای بازدید ملک مذکور
          درخواست نمایید. کارشناسان آگهی جهت هماهنگی بیشتر با شما تماس خواهند
          گرفت.
        </p>
        <div>
          <Formik
            initialValues={{ times: [new Date().getTime()] }}
            onSubmit={handleSubmit}
            render={({ values, setFieldValue }) => (
              <Form>
                <textarea
                  placeholder="توضیحات خود را وارد نمایید"
                  rows={3}
                  style={{ marginTop: 8 }}
                  onChange={(e) => setFieldValue("description", e.target.value)}
                />
                <FieldArray
                  name="times"
                  render={(arrayHelpers) => (
                    <div>
                      <h2>لیست زمان های بازدید</h2>
                      {values.times.map((_, index) => (
                        <div key={index} className={styles.itemContainer}>
                          <h6>بازدید شماره {index + 1}</h6>
                          <div className={styles["date-item"]}>
                            <div className={styles["date-btn__container"]}>
                              <DatePicker
                                // style={{ position: "absolute" }}
                                className={styles["date-btn__item"]}
                                hideOnScroll
                                calendar={persian}
                                locale={persian_fa}
                                value={values.times[index]}
                                calendarPosition="bottom-right"
                                onChange={(e) => {
                                  setFieldValue(`times.${index}`, e?.valueOf());
                                }}
                              />
                              <div
                                // style={{ width: "40px", overflow: "hidden" }}
                                className={styles["icon-container"]}
                              >
                                <div
                                  className={styles["date-btn-icon__container"]}
                                >
                                  <BsCalendar className={styles.icon} />
                                </div>
                              </div>
                            </div>

                            <div className={styles["date-btn__container"]}>
                              <DatePicker
                                disableDayPicker
                                format="HH:mm A"
                                className={styles["date-btn__item"]}
                                plugins={[<TimePicker key={1} />]}
                                calendar={persian}
                                locale={persian_fa}
                                calendarPosition="bottom-right"
                                value={values.times[index]}
                                disabled={values.times[index] ? false : true}
                                onChange={(e) =>
                                  setFieldValue(`times.${index}`, e?.valueOf())
                                }
                              />
                              <div
                                // style={{ width: "40px", overflow: "hidden" }}
                                className={styles["icon-container"]}
                              >
                                <div
                                  className={styles["date-btn-icon__container"]}
                                >
                                  <BsFillAlarmFill className={styles.icon} />
                                </div>
                              </div>
                            </div>

                            {values.times.length !== index + 1 && (
                              <Button
                                color="gray"
                                width={140}
                                height={35}
                                onClick={() => arrayHelpers.remove(index)} // remove a friend from the list
                              >
                                حذف این تایم
                                <AiOutlineMinusCircle className={styles.icon} />
                              </Button>
                            )}
                          </div>
                        </div>
                      ))}
                      <div className={styles["add-time__btn"]}>
                        <Button
                          color="gray"
                          width={160}
                          onClick={() =>
                            arrayHelpers.push(new Date().getTime())
                          }
                        >
                          تایم بعدی
                          <BsPlus className={styles.icon} />
                        </Button>
                      </div>
                      <div className={styles.submitBtnContainer}>
                        <Button
                          color="primary"
                          width={170}
                          btnType="submit"
                          height={45}
                        >
                          ثبت درخواست
                        </Button>
                      </div>
                    </div>
                  )}
                />
              </Form>
            )}
          />
        </div>
      </div>
    </Popup>
  );
};

export default RealEstatevisitRequestPopup;

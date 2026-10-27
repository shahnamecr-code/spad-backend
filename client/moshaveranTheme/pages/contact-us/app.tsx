import {
  contactItems,
  downloadAppItems,
} from "../../components/layout/footer/Footer";
import MainLayout from "../../components/layout/mainLayout/MainLayout";
import styles from "./contact-us.module.scss";
import { FaTelegramPlane } from "@react-icons/all-files/fa/FaTelegramPlane";
import { FaInstagram } from "@react-icons/all-files/fa/FaInstagram";
import { FaFacebook } from "@react-icons/all-files/fa/FaFacebook";
import { FaWhatsapp } from "@react-icons/all-files/fa/FaWhatsapp";
import Link from "next/link";
import Input from "../../components/general/input/Input";
import Button from "../../components/general/button/Button";
import TextArea from "../../components/general/textArea/TextArea";
import { Form, Formik, FormikHelpers } from "formik";
import { toast } from "react-toastify";
import { useAppContext } from "../../utils/context";
import { postContactUs } from "../../services/generalServices";
import { IContactData } from "../../types/general.types";
import { addCountryCode } from "../../utils";

const ContactUs = () => {
  const context = useAppContext();
  const socialMediaItems = [
    context.options.telegram_account && {
      icon: <FaTelegramPlane />,
      text: "تلگرام",
      url: `https://t.me/${context.options.telegram_account}`,
    },
    context.options.instagram_account && {
      icon: <FaInstagram />,
      text: "اینستاگرام",
      url: `https://www.instagram.com/${context.options.instagram_account}`,
    },
    context.options.facebook_account && {
      icon: <FaFacebook />,
      text: "فیسبوک",
      url: `https://www.facebook.com/${context.options.facebook_account}`,
    },
    context.options.whatsapp && {
      icon: <FaWhatsapp />,
      text: "واتساپ",
      url: `https://web.whatsapp.com/send?phone=${addCountryCode(
        context.options.whatsapp
      )}&text=`,
    },
  ];

  const initialValues: IContactData = {};

  const formSubmit = (
    values: IContactData,
    formikHelpers: FormikHelpers<IContactData>
  ) =>
    postContactUs(values).then((res) => {
      if (res.data) {
        formikHelpers.resetForm();
        toast.success(res.data.message);
      }
    });
  return (
    <MainLayout title="تماس با ما" hideLayout>
      <div className={styles.container}>
        <span className={styles.title}>
          تماس با {context.options.general_site_name || ""}
        </span>
        <div className={styles["description-container"]}>
          <p>
            جهت دریافت اطلاعات بیشتر با مشاورین املاک{" "}
            {context.options.general_site_name || ""} تماس حاصل فرمایید
          </p>
          <p>
            همچنین میتوانید از فرم زیر جهت ارسال هرگونه نظرات و یا انتقادات خود
            استفاده نمایید . همکاران ما در اسرع وقت با شما در ارتباط خواهند بود
          </p>
        </div>
        <div className="flex w-100 xs-flex-col-r">
          <div className={styles["contact-items-container"]}>
            <span className={styles["sub-title"]}>
              راه های ارتباط با {context.options.general_site_name || ""}
            </span>
            <div className="flex-col">
              {contactItems(context).map((item, i) => (
                <div key={i} className="contact-item">
                  {item.icon}
                  <span>{item.text}</span>
                </div>
              ))}
            </div>
            <span className={styles["sub-title"]}>شبکه های اجتماعی</span>
            <div className="flex-col">
              {socialMediaItems.map(
                (item, i) =>
                  item && (
                    <Link href={item.url} key={i}>
                      <a className="contact-item" target="_blank">
                        {item.icon}
                        <span>{item.text}</span>
                      </a>
                    </Link>
                  )
              )}
            </div>
            <span className={styles["sub-title"]}>دانلود اپلیکیشن</span>
            <div className="flex-col">
              {downloadAppItems(context).map(
                (item, i) =>
                  item?.url && (
                    <Link href={item?.url || ""} key={i}>
                      <a className="contact-item" target="_blank">
                        {item?.icon}
                        <span>{item?.name}</span>
                      </a>
                    </Link>
                  )
              )}
            </div>
          </div>
          <Formik initialValues={initialValues} onSubmit={formSubmit}>
            {({ setFieldValue, values }) => (
              <Form className={styles["contact-form-container"]}>
                <span className={styles["sub-title"]}>
                  فرم تماس با {context.options.general_site_name}
                </span>
                <Input
                  label="نام و نام خانوادگی"
                  value={values.name}
                  onChange={(e) => setFieldValue("name", e)}
                />
                <Input
                  label="شماره تماس"
                  value={values.mobile}
                  onChange={(e) => setFieldValue("mobile", e)}
                  type="number"
                />
                <Input
                  label="عنوان پیام"
                  value={values.subject}
                  onChange={(e) => setFieldValue("subject", e)}
                />
                <TextArea
                  rows={3}
                  label="متن پیام"
                  value={values.content}
                  onChange={(e) => setFieldValue("content", e)}
                />
                <Button className={styles["send-btn"]} btnType="submit">
                  ارسال پیام
                </Button>
              </Form>
            )}
          </Formik>
        </div>
      </div>
    </MainLayout>
  );
};

export default ContactUs;

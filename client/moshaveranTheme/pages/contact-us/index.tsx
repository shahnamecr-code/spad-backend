import RenderLinks from "../../components/general/renderLinks/RenderLinks";
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
import { AppContextType, useAppContext } from "../../utils/context";
import {
  getContactUsOtption,
  postContactUs,
} from "../../services/generalServices";
import { IContactData } from "../../types/general.types";
import { addCountryCode } from "../../utils";
import { HiOutlineLocationMarker } from "@react-icons/all-files/hi/HiOutlineLocationMarker";
import { HiOutlineMail } from "@react-icons/all-files/hi/HiOutlineMail";
import { HiOutlinePhone } from "@react-icons/all-files/hi/HiOutlinePhone";
import { useEffect, useState } from "react";

const ContactUs = () => {
  const context = useAppContext();
  const [contactOptions, setContactOptions] = useState<{
    contact_address: string;
    contact_phone: string;
    contact_email: string;
  }>();

  useEffect(() => {
    getContactUsOtption().then((res) => {
      setContactOptions(res.data);
    });
  }, []);
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

  const contactItems = () => [
    {
      icon: <HiOutlineLocationMarker />,
      text: contactOptions?.contact_address || "",
    },
    {
      icon: <HiOutlineMail />,
      text: contactOptions?.contact_email || "",
      url: `mailto:${contactOptions?.contact_email}`,
    },
    {
      icon: <HiOutlinePhone />,
      text: contactOptions?.contact_phone || "",
      url: `tel:${contactOptions?.contact_phone}`,
    },
  ];
  return (
    <MainLayout title="تماس با ما">
      <RenderLinks links={[{ title: "تماس با ما" }]} />
      <div className={styles.container}>
        <h1 className={styles.title}>
          تماس با {context.options.general_site_name || ""}
        </h1>
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
            <h2 className={styles["sub-title"]}>
              راه های ارتباط با {context.options.general_site_name || ""}
            </h2>
            <div className="flex-col">
              {contactItems().map((item, i) => {
                if (item.text && item.text.length) {
                  return (
                    <div key={i} className="contact-item">
                      {item.icon}
                      <a href={item.url}>{item.text}</a>
                    </div>
                  );
                }
              })}
            </div>
            <h2 className={styles["sub-title"]}>شبکه های اجتماعی</h2>
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
            <h2 className={styles["sub-title"]}>دانلود اپلیکیشن</h2>
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
                <h2 className={styles["sub-title"]}>
                  فرم تماس با {context.options.general_site_name}
                </h2>
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

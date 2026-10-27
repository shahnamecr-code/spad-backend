import RenderLinks from "../../components/general/renderLinks/RenderLinks";
import MainLayout from "../../components/layout/mainLayout/MainLayout";
import styles from "./adExpertiseRequest.module.scss";
import { Select } from "../../components/general/select/Select";
import Button from "../../components/general/button/Button";
import { useAdExpertiseRequestDev } from "../../components/pagesComponents/adExpertiseRequest/adExpertiseRequest.dev";
import { useContext, useState } from "react";
import { ICategory } from "../../types/createAd.types";
import { AppContext } from "../../utils/context";
import { Field, Form, Formik } from "formik";

const AdExpertiseRequest = () => {
  const dev = useAdExpertiseRequestDev();
  const { provinces, options } = useContext(AppContext);
  const [levelAndChildren, setLevelAndChildren] = useState<{
    level: number;
    customer_children: any;
  }>();

  return (
    <MainLayout title="درخواست کارشناسی ملک">
      <RenderLinks links={[{ title: "درخواست کارشناسی ملک" }]} />
      <div className={styles.container}>
        <h1 className={styles.title}>درخواست کارشناسی ملک </h1>
        <div className={styles["description-container"]}>
          <p>
            جهت دریافت اطلاعات بیشتر با مشاورین املاک {options.general_site_name} تماس حاصل
            فرمایید
          </p>
          <p>{dev.adExpertiseRequestSettings?.description}</p>
        </div>
        <div className="flex w-100 xs-flex-col-r">
          <Formik
            initialValues={{
              category_id: 0,
              province_id: 0,
              city_id: 0,
              region_id: 0,
              address: "",
            }}
            onSubmit={dev.handleSubmit}
          >
            {({ values, setFieldValue }) => (
              <Form
                style={{ width: "100%" }}
                className={styles["contact-form-container"]}
              >
                <h2 className={styles["sub-title"]}>
                  فرم ثبت درخواست کارشناسی در {options.general_site_name || ""}
                </h2>
                {/* <div className="d-sm-flex">
                  // <Field label="نام " className="col-md-6 col-12" />
                  <Field label="نام خانوادگی" className="col-md-6 col-12" />
                </div> */}
                <div className="d-sm-flex">
                  {dev.category?.levels && dev.category?.levels[0] && (
                    <Select
                      id="transactions"
                      label={dev.category?.levels[0].label}
                      items={dev.category.data}
                      className={`${styles.transaction} col-md-6 col-12`}
                      displayExpr="name"
                      valueKey="id"
                      onChange={(e) => {
                        const customer_children =
                          e as ICategory["customer_children"];
                        const category = e as ICategory;
                        setFieldValue("category_id", category?.id);
                        setLevelAndChildren({
                          level: 0,
                          customer_children,
                        });
                      }}
                    />
                  )}

                  {dev.category?.levels && dev.category?.levels[1] && (
                    <Select
                      id="aoolicationtype"
                      label={dev.category?.levels[1].label}
                      items={
                        levelAndChildren?.level == 0
                          ? levelAndChildren?.customer_children
                              ?.customer_children
                          : []
                      }
                      className={`${styles.transaction} col-md-6 col-12`}
                      displayExpr="name"
                      valueKey="id"
                      onChange={(e) => {
                        const customer_children =
                          e as ICategory["customer_children"];
                        const category = e as ICategory;
                        setFieldValue("category_id", category?.id);
                        setLevelAndChildren({
                          level: 1,
                          customer_children,
                        });
                      }}
                    />
                  )}
                </div>
                <div className="d-sm-flex">
                  { dev.category?.levels && dev.category?.levels[2] && (
                    <Select
                      id="landtype"
                      label={dev.category?.levels[2].label}
                      items={
                        levelAndChildren?.level == 1
                          ? levelAndChildren?.customer_children
                              ?.customer_children
                          : []
                      }
                      className={`${styles.transaction} col-md-6 col-12`}
                      displayExpr="name"
                      valueKey="id"
                      onChange={(e) => {
                        const customer_children =
                          e as ICategory["customer_children"];
                        const category = e as ICategory;
                        setFieldValue("category_id", category?.id);
                        setLevelAndChildren({
                          level: 2,
                          customer_children,
                        });
                      }}
                    />
                  )}

                  <Select
                    id="province"
                    label="استان"
                    items={provinces}
                    className={`${styles.transaction} col-md-6 col-12`}
                    displayExpr="name"
                    valueKey="id"
                    onChange={(e) => {
                      if (!(e instanceof Array)) {
                        setFieldValue("province_id", e?.id);
                        dev.handleChangeProvince(e?.id || 0);
                      }
                    }}
                  />
                </div>
                <div className="d-sm-flex">
                  <Select
                    id="city"
                    label="شهر"
                    items={dev.cities}
                    className={`${styles.transaction} col-md-6 col-12`}
                    displayExpr="name"
                    valueKey="id"
                    onChange={(e) => {
                      if (!(e instanceof Array)) {
                        setFieldValue("city_id", e?.id);
                        dev.handleChangeCity(e?.id || 0);
                      }
                    }}
                  />

                  <Select
                    id="region"
                    label="منطقه"
                    items={dev.region}
                    className={`${styles.transaction} col-md-6 col-12`}
                    displayExpr="name"
                    valueKey="id"
                    onChange={(e) => {
                      if (!(e instanceof Array)) {
                        setFieldValue("region_id", e?.id);
                      }
                    }}
                  />
                </div>
                <div>
                  <div className={`col-12 ${styles["address-field"]}`}>
                    <span className={styles.label}>آدرس دقیق</span>
                    <Field className="form-control" name="address" />
                  </div>
                  <div className="flex justify-content-end">
                    <Button
                      btnType="submit"
                      className={`${styles["send-btn"]} col-12  col-md-3`}
                    >
                      ثبت درخواست
                    </Button>
                  </div>
                </div>
              </Form>
            )}
          </Formik>
        </div>
      </div>
    </MainLayout>
  );
};

export default AdExpertiseRequest;

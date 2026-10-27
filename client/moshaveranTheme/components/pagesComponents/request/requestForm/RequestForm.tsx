import { Form } from "formik";
import React from "react";
import Button from "../../../general/button/Button";
import RenderLinks from "../../../general/renderLinks/RenderLinks";
import { Select } from "../../../general/select/Select";
import Field from "../../search/field/Field";
import { useRequestDev } from "../request.dev";
import styles from "./request.module.scss";

const RequestForm = ({
  errMsgs,
  resetErrMsgs,
}: {
  errMsgs?: {
    [key: string]: string[];
  };
  resetErrMsgs: () => void;
}) => {
  const dev = useRequestDev();

  return (
    <Form className={styles.form}>
      <RenderLinks links={[{ title: "تقاضای ملک" }]} titleH1/>
      <div className={styles.container}>
        <div className={styles.filters}>
          <Select
            id="transactions"
            label={dev.labels[0]?.label}
            items={dev.categories}
            className={`col-md-6 col-12 ${styles.transaction}`}
            displayExpr="name"
            valueKey="id"
            value={dev.transaction}
            onChange={(e) => {
              dev.resetForm();
              dev.setFieldValue("transaction", e);
              resetErrMsgs();
            }}
          />
          {dev.transaction && (
            <>
              <Select
                id="application_type"
                label={dev.labels[1]?.label}
                items={dev.transaction.customer_children}
                className={`col-md-6 col-12 ${styles.transaction}`}
                displayExpr="name"
                valueKey="id"
                value={dev.applicationType}
                onChange={(e) => {
                  dev.setValues({
                    transaction: dev.transaction,
                    application_type: e,
                    land_type: undefined,
                  });
                  resetErrMsgs();
                }}
              />
              {dev.applicationType && (
                <Select
                  id="application_type"
                  label={dev.labels[2]?.label}
                  items={dev.applicationType.customer_children}
                  className={`col-md-6 col-12 ${styles.transaction}`}
                  displayExpr="name"
                  valueKey="id"
                  value={dev.landType}
                  onChange={(e) => {
                    dev.setFieldValue("land_type", e);
                    dev.setValues({
                      transaction: dev.transaction,
                      application_type: dev.applicationType,
                      land_type: e,
                    });
                    resetErrMsgs();
                  }}
                />
              )}
            </>
          )}
          {dev.fields && (
            <>
              {dev.fields.map((item, i) => (
                <div className={styles["fields-box"]} key={i}>
                  <div className={styles["title-container"]}>
                    {/* {item.icon && (
                      <img src={imgUrl(item.icon)} width={20} height={20} />
                    )} */}
                    <h2 className={styles["fields-label"]}>{item.label}</h2>
                  </div>
                  <div className="divider" />
                  <div className={styles["fields-container"]}>
                    {item.items?.map((item, i) =>
                      item.range_search &&
                      item.search_type !== "geo" &&
                      item.search_type !== "textarea" ? (
                        <div
                          className={`col-md-12 ${styles["input-main-container"]}`}
                          key={i}
                        >
                          <span className={styles.label}>{item.label}</span>
                          <div className={styles["input-container"]}>
                            <Field
                              info={{
                                ...item,
                                label: "حداقل",
                                code: `${item.code}[from]`,
                              }}
                              errMsgs={errMsgs}
                            />
                            <Field
                              info={{
                                ...item,
                                label: "حداکثر",
                                code: `${item.code}[to]`,
                              }}
                              errMsgs={errMsgs}
                            />
                          </div>
                        </div>
                      ) : (
                        <Field
                          info={item}
                          key={i}
                          className={
                            item.search_type === "geo"
                              ? "w-100"
                              : "col-md-6 col-12"
                          }
                          errMsgs={errMsgs}
                        />
                      )
                    )}
                  </div>
                </div>
              ))}
              <div className={styles["btn-container"]}>
                <Button width={150} btnType="submit">
                  درخواست
                </Button>
              </div>
            </>
          )}
        </div>
      </div>
    </Form>
  );
};

export default RequestForm;

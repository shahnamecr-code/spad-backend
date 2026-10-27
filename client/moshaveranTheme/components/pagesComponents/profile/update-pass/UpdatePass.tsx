import { Formik, Form, FormikHelpers } from "formik";
import styles from "./update-pass.module.scss";
import Button from "../../../general/button/Button";
import { useEffect, useState } from "react";
import { toast } from "react-toastify";
import SimpleLoading from "../../../general/simpleLoading/SimpleLoading";
import Popup, { IPopupProps } from "../../../general/popup/Popup";
import { IUpdatePass, IUpdatePassErrorMsg } from "../../../../types/auth.types";
import LoginInput from "../../auth/loginInput/LoginInput";
import {
  updatePass,
} from "../../../../services/ProfileServices";

const UpdatePass = (props: IPopupProps) => {
  const initialValues: IUpdatePass = {};
  const [loading, setLoading] = useState(false);
  const [errMsg, setErrMsg] = useState<IUpdatePassErrorMsg>();

  useEffect(() => {
    if (!props.visible) setErrMsg(undefined);
  }, [props.visible]);

  const formSubmit = (
    values: IUpdatePass,
    formikHelpers: FormikHelpers<IUpdatePass>
  ) => {
    setLoading(true);
    updatePass(values)
      .then((res) => {
        toast.success("اطلاعات با موفقیت تغییر یافت.");
        props.onHiding();
        formikHelpers.resetForm();
      })
      .catch((e) => {
        setErrMsg(e.response.data.errors);
      })
      .finally(() => setLoading(false));
  };

  return (
    <Popup width={400} {...props} title="ویرایش رمز عبور" hideCloseBtn>
      <Formik initialValues={initialValues} onSubmit={formSubmit}>
        {({ setFieldValue, values }) => (
          <Form className={styles["container"]}>
            <LoginInput
              placeholder="رمز عبور جدید"
              value={values.password}
              onChange={(e) => setFieldValue("password", e)}
              validationMessage={
                errMsg?.password && errMsg?.password.join(" - ")
              }
            />
            <LoginInput
              placeholder="تکرار رمز عبور"
              value={values.password_confirmation}
              onChange={(e) => setFieldValue("password_confirmation", e)}
              validationMessage={
                errMsg?.password_confirmation &&
                errMsg?.password_confirmation.join(" - ")
              }
            />
            <div className="btn-container">
              <Button type="outlined" onClick={props.onHiding}>
                لغو
              </Button>
              <Button className={styles["login-btn"]} btnType="submit">
                ذخیره تغییرات
              </Button>
              {loading && <SimpleLoading />}
            </div>
          </Form>
        )}
      </Formik>
    </Popup>
  );
};

export default UpdatePass;

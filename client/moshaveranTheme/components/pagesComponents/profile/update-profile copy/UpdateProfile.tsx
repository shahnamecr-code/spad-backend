import { Formik, Form, FormikHelpers } from "formik";
import styles from "./update-profile.module.scss";
import Button from "../../../general/button/Button";
import { useEffect, useState } from "react";
import { toast } from "react-toastify";
import { setStore, validateEmail } from "../../../../utils";
import { USER } from "../../../../utils/stores";
import SimpleLoading from "../../../general/simpleLoading/SimpleLoading";
import Popup, { IPopupProps } from "../../../general/popup/Popup";
import {
  IUpdateProfile,
  IUpdateProfileErrorMsg,
} from "../../../../types/auth.types";
import LoginInput from "../../auth/loginInput/LoginInput";
import { updateProfile } from "../../../../services/ProfileServices";
import { useAppContext } from "../../../../utils/context";

const UpdateProfile = (props: IPopupProps) => {
  const context = useAppContext();
  const initialValues: IUpdateProfile = {
    password: context.user.email,
    first_name: context.user.first_name,
    last_name: context.user.last_name,
  };
  const [loading, setLoading] = useState(false);
  const [errMsg, setErrMsg] = useState<IUpdateProfileErrorMsg>();

  useEffect(() => {
    if (!props.visible) setErrMsg(undefined);
  }, [props.visible]);

  const formSubmit = (
    values: IUpdateProfile,
    formikHelpers: FormikHelpers<IUpdateProfile>
  ) => {
    let isValidEmail = values.password ? validateEmail(values.password) : true;
    if (!isValidEmail) {
      setErrMsg({ ...errMsg, email: ["ایمیل وارد شده صحیح نمی باشد."] });
      return;
    }
    setLoading(true);
    updateProfile(values)
      .then((res) => {
        toast.success("اطلاعات با موفقیت تغییر یافت.");
        props.onHiding();
        formikHelpers.resetForm();
        if (res.data.user) {
          setStore(USER, res.data.user);
          context.setUser(res.data.user);
        }
      })
      .catch((e) => {
        setErrMsg(e.response.data.errors);
      })
      .finally(() => setLoading(false));
  };

  return (
    <Popup width={400} {...props} title="ویرایش پروفایل" hideCloseBtn>
      <Formik initialValues={initialValues} onSubmit={formSubmit}>
        {({ setFieldValue, values }) => (
          <Form className={styles["container"]}>
            <LoginInput
              placeholder="نام"
              value={values.first_name}
              onChange={(e) => setFieldValue("first_name", e)}
              validationMessage={
                errMsg?.first_name && errMsg?.first_name.join(" - ")
              }
            />
            <LoginInput
              placeholder="نام خانوادگی"
              value={values.last_name}
              onChange={(e) => setFieldValue("last_name", e)}
              validationMessage={
                errMsg?.last_name && errMsg?.last_name.join(" - ")
              }
            />
            <LoginInput
              placeholder="ایمیل"
              value={values.password}
              onChange={(e) => setFieldValue("email", e)}
              validationMessage={errMsg?.email && errMsg?.email.join(" - ")}
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

export default UpdateProfile;

import styles from "./loginWithPass.module.scss";
import * as yup from "yup";
import axios from "axios";
import { Formik, Field } from "formik";
import Link from "next/link";
import { useRouter } from "next/router";
import React from "react";
import { toast } from "react-toastify";
import { addFavorites } from "../../services/FavoritesServices";
import { LoginWithPassReqData } from "../../types/auth.types";
import { setStore, getStore, removeStore, paths } from "../../utils";
import { useLoadingContext, useAppContext } from "../../utils/context";
import { TOKEN, USER, FAVORITES } from "../../utils/stores";
import Button from "../../components/general/button/Button";
import { loginWithPass } from "../../services/AuthenticateServices";
import MainLayout from "../../components/layout/mainLayout/MainLayout";

const authSchema = yup.object().shape({
  mobile: yup
    .string()
    .max(11, "حداکثر 11 کاراکتر")
    .min(11, "حداقل 11 کاراکتر")
    .matches(/^(\+98?)?{?(0?9[0-9]{9,9}}?)$/, "با فرمت درست وارد کنید")
    .required("شماره موبایل را وارد کنید"),
  password: yup.string().required("رمز عبور را وارد کنید"),
});

const LoginWithPass = () => {
  const router = useRouter();
  const initial: LoginWithPassReqData = {
    mobile: "",
    password: "",
  };
  const loadingContext = useLoadingContext();
  const context = useAppContext();

  const onSubmit = (data: LoginWithPassReqData) => {
    loadingContext.setLoading(true);
    loginWithPass(data)
      .then((result) => {
        if (result.status) {
          toast.success(result.message);
          localStorage.setItem(TOKEN, result.token);
          axios.defaults.headers.common[
            "Authorization"
          ] = `Bearer ${result.token}`;
          setStore(USER, result.user);
          context.setUser(result.user);
          addFavorites(
            JSON.parse(getStore(FAVORITES) || "[]") || [],
            result.token
          ).then((res) => {
            removeStore(FAVORITES);
            res.status === 200 && router.replace("/");
          });
        } else {
          toast.error(result.message);
          if (result.redirect)
            router.push(`${paths.login}?mobile=${data.mobile}`);
        }
      })
      .finally(() => loadingContext.setLoading(false));
  };

  return (
    <MainLayout title="ورود با رمز عبور">
      <div className={styles.container}>
        <h1 className={styles.title}>
          لطفا برای ورود، اطلاعات زیر را وارد نمایید
        </h1>
        <Formik
          initialValues={initial}
          validationSchema={authSchema}
          onSubmit={onSubmit}
        >
          {({ handleSubmit, errors }) => (
            <div className={styles.login}>
              <label htmlFor="mobile">شماره موبایل</label>
              <Field
                className={styles.input}
                placeholder="شماره موبایل"
                id="mobile"
                name="mobile"
                maxLength={11}
              />
              <p className={styles.errors}>{errors.mobile}</p>
              <label htmlFor="mobile">رمز عبور</label>
              <Field
                className={styles.input}
                placeholder="رمز عبور"
                id="password"
                name="password"
                type="password"
              />
              <p className={styles.errors}>{errors.password}</p>
              <div className={styles["btns-container"]}>
                <Link href={paths.login}>
                  <a>ورود با کد یکبار مصرف</a>
                </Link>
                <Button
                  onClick={(e) => handleSubmit()}
                  className={styles["login-btn"]}
                >
                  ورود
                </Button>
              </div>
            </div>
          )}
        </Formik>

        <div className={styles["sign-up"]}>
          <span>قبلا ثبت نام نکرده اید؟</span>
          <Button
            type="text"
            onClick={() => router.push(`${paths.login}?hasAccount=false`)}
          >
            ثبت نام
          </Button>
        </div>
      </div>
    </MainLayout>
  );
};

export default LoginWithPass;

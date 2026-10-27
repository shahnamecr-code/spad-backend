import React, { useState, useContext, useEffect } from "react";

import { Field, Formik } from "formik";
import * as yup from "yup";

import { useRouter } from "next/router";

import { toast } from "react-toastify";

import { login, sendLoginCode } from "../../../services/AuthenticateServices";
import { LoginReqData } from "../../../types/auth.types";
import { FAVORITES, TOKEN, USER } from "../../../utils/stores";

import Button from "../../general/button/Button";

import styles from "./login.module.scss";
import {
  getStore,
  loginCodeTiem,
  paths,
  removeStore,
  setStore,
  showTimer,
} from "../../../utils";
import axios from "axios";
import { useAppContext, useLoadingContext } from "../../../utils/context";
import { addFavorites } from "../../../services/FavoritesServices";
import Link from "next/link";
import { time } from "console";

interface IProps {
  handleClickHasAcc: (value: boolean) => void;
}

const authSchema = yup.object().shape({
  mobile: yup
    .string()
    .max(11, "حداکثر 11 کاراکتر")
    .min(11, "حداقل 11 کاراکتر")
    .matches(/^(\+98?)?{?(0?9[0-9]{9,9}}?)$/, "با فرمت درست وارد کنید")
    .required("شماره موبایل را وارد کنید"),
  code: yup
    .string()
    .max(6, "حداکثر 6 کاراکتر")
    .min(6, "حداقل 6 کاراکتر")
    .required("کد 6 رقمی را وارد کنید"),
});

const Login = ({ handleClickHasAcc }: IProps) => {
  const router = useRouter();
  const [isCodeSent, setIsCodeSent] = useState(false);
  const initial: LoginReqData = {
    mobile: router.query.mobile ? `${router.query.mobile}` : "",
    code: "      ",
  };
  const loadingContext = useLoadingContext();
  const context = useAppContext();
  const [timer, setTimer] = useState<number>();

  const onSubmit = (data: LoginReqData) => {
    if (isCodeSent) {
      loadingContext.setLoading(true);
      login(data)
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
          }
        })
        .finally(() => loadingContext.setLoading(false));
    } else {
      sendCode(data);
    }
  };

  const sendCode = (data: LoginReqData) => {
    loadingContext.setLoading(true);
    sendLoginCode({ mobile: data.mobile })
      .then((result) => {
        if (!result.status) toast.error(result.message);
        if (result.message === "قبلا ثبت نام نکرده اید") {
          handleClickHasAcc(false);
        } else {
          if (result.status) {
            setIsCodeSent(true);
            setTimer(loginCodeTiem);
          }
        }
      })
      .finally(() => loadingContext.setLoading(false));
  };

  useEffect(() => {
    if (timer) {
      const interval = setInterval(() => {
        timer && setTimer((prev) => (prev || 0) - 1);
      }, 1000);

      return () => clearInterval(interval);
    }
  }, [timer]);

  useEffect(() => {}, [router.query]);

  return (
    <React.Fragment>
      <h1 className={styles.title}>
        لطفا برای ورود، اطلاعات زیر را وارد نمایید
      </h1>
      <Formik
        initialValues={initial}
        validationSchema={authSchema}
        onSubmit={onSubmit}
        enableReinitialize
      >
        {({ handleSubmit, errors, setFieldValue, values }) => (
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
            {isCodeSent && (
              <React.Fragment>
                <input
                  className={styles.input}
                  type="number"
                  onChange={(e) => setFieldValue("code", e.target.value)}
                  placeholder="کد ارسال شده را وارد کنید"
                  id="code"
                  name="code"
                  maxLength={6}
                />
                <p className={styles.errors}>{errors.code}</p>
              </React.Fragment>
            )}
            <div className={styles["btns-container"]}>
              <Link href={paths["login-with-pass"]}>
                <a>ورود با رمز عبور</a>
              </Link>
              {timer ? (
                <span className={styles.timer}>
                  ({showTimer(timer)} تا ارسال مجدد کد)
                </span>
              ) : (
                ""
              )}
              {isCodeSent && timer === 0 && (
                <Button type="text" onClick={() => sendCode(values)}>
                  ارسال مجدد کد
                </Button>
              )}
              <Button
                onClick={(e) => handleSubmit()}
                className={styles["login-btn"]}
              >
                {isCodeSent ? "ورود" : "ارسال کد"}
              </Button>
            </div>
          </div>
        )}
      </Formik>
      <div className={styles["bottom-links"]}>
        <div className={styles["sign-up"]}>
          <span>قبلا ثبت نام نکرده اید؟</span>
          <Button type="text" onClick={() => handleClickHasAcc(false)}>
            ثبت نام
          </Button>
        </div>
        {isCodeSent && (
          <Button
            type="text"
            onClick={() => {
              setIsCodeSent(false);
              setTimer(undefined);
            }}
          >
            تغییر شماره تلفن
          </Button>
        )}
      </div>
    </React.Fragment>
  );
};

export default Login;

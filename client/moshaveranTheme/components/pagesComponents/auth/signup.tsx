import React, { useRef } from "react";

import { Field, Form, Formik, FormikProps } from 'formik';
import * as yup from 'yup';

import Button from "../../general/button/Button";
import { toast } from "react-toastify";

import { register } from "../../../services/AuthenticateServices";
import { RegisterReqData } from "../../../types/auth.types";

import styles from "./signup.module.scss";

interface IProps {
    handleClickHasAcc: (value: boolean) => void;
}

const authSchema = yup.object().shape({
    first_name: yup
        .string()
        .min(2, 'حداقل 2 کاراکتر')
        .required('نام خود را وارد کنید'),
    last_name: yup
        .string()
        .min(2, 'حداقل 2 کاراکتر')
        .required('نام خانوادگی خود را وارد کنید'),
    email: yup
        .string()
        .matches(/\S+@\S+\.\S+/, 'ایمیل را با فرمت درست وارد کنید')
        .required('ایمیل خود را وارد کنید'),
    mobile: yup
        .string()
        .min(2, 'حداقل 2 کاراکتر')
        .matches(/^(\+98?)?{?(0?9[0-9]{9,9}}?)$/, 'موبایل را با فرمت درست وارد کنید')
        .required('موبایل خود را وارد کنید'),
    password: yup
        .string()
        .min(8, 'حداقل 8 کاراکتر')
        .required('رمز عبور را وارد کنید'),
})

const Signup = ({ handleClickHasAcc }: IProps) => {
    const formikRef = useRef<FormikProps<RegisterReqData>>(null);
    const initial: RegisterReqData = {
        email: '',
        first_name: '',
        last_name: '',
        mobile: '',
        password: '',
    }

    const onSubmit = (data: RegisterReqData) => {
        register(data)
            .then(result => {
                if (result.status === true) {
                    toast.success(result.message);
                    handleClickHasAcc(true);
                } else {
                    for (var prop in result.errors) {
                        if (Object.prototype.hasOwnProperty.call(result.errors, prop)) {
                            formikRef.current?.setFieldError(prop, result.errors[prop][0]);
                        };
                    };
                }
            })
            .catch(err => {
                console.log('err', err)
            })
    }

    return (
        <React.Fragment>
            <h1 className={styles.title}>
                لطفا برای ثبت نام اطلاعات زیر را وارد نمایید
            </h1>
            <Formik
                initialValues={initial}
                validationSchema={authSchema}
                onSubmit={onSubmit}
                innerRef={formikRef}
            >
                {({ handleSubmit, errors }) =>
                    <Form className={styles['sign-up']}>
                        <div>
                            <Field className={styles.input} placeholder="نام" id="first_name" name="first_name" />
                            <p className={styles.errors}>
                                {errors.first_name}
                            </p>
                        </div>
                        <div>
                            <Field className={styles.input} placeholder=" نام خانوادگی" id="last_name" name="last_name" />
                            <p className={styles.errors}>
                                {errors.last_name}
                            </p>
                        </div>
                        <div>
                            <Field className={styles.input} placeholder="شماره موبایل" id="mobile" name="mobile" maxLength={11} />
                            <p className={styles.errors}>
                                {errors.mobile}
                            </p>
                        </div>
                        <div>
                            <Field className={styles.input} placeholder="ایمیل" id="email" name="email" />
                            <p className={styles.errors}>
                                {errors.email}
                            </p>
                        </div>
                        <div>
                            <Field className={styles.input} placeholder="رمز عبور" id="password" name="password" />
                            <p className={styles.errors}>
                                {errors.password}
                            </p>
                        </div>
                        <Button onClick={e => handleSubmit()}
                            className={styles["signup-btn"]}>
                            ثبت نام
                        </Button>
                    </Form>
                }
            </Formik>

            <div className={styles["sign-up"]}>
                <span>قبلا ثبت نام کرده اید؟</span>
                <Button type="text" onClick={() => handleClickHasAcc(true)}>
                    ورود
                </Button>
            </div>
        </React.Fragment>
    );
};

export default Signup;

import React, {useEffect, useState} from 'react';
import {Button, FormGroup, Label} from 'reactstrap';
import {connect} from 'react-redux';
import {Field, Form, Formik} from 'formik';
import axios from 'axios';

import {NotificationManager} from '../../components/common/react-notifications';
import {checkLoginUser, loginUser} from '../../redux/actions';
import {adminRoot} from '../../constants/defaultValues';

const validatePassword = (value) => {
  if (!value) return 'لطفاً رمز عبور خود را وارد کنید';
  if (value.length < 4) return 'رمز عبور باید حداقل ۴ کاراکتر باشد';
  return undefined;
};

const validateEmail = (value) => {
  if (!value) return 'لطفاً ایمیل خود را وارد کنید';
  if (!/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i.test(value)) return 'ایمیل واردشده معتبر نیست';
  return undefined;
};

const Login = ({history, loading, error, loginUserAction, currentUser, checkUser}) => {
  const [globalSettings, setGlobalSettings] = useState({});

  useEffect(() => {
    axios
      .get('/api/global-settings')
      .then((res) => setGlobalSettings(res.data || {}))
      .catch(() => {});
  }, []);

  useEffect(() => {
    if (error !== 401) {
      if (!currentUser) checkUser(history);
      else history.push(adminRoot);
    }
  }, [currentUser]);

  useEffect(() => {
    if (error && error !== 401) {
      NotificationManager.warning(error, 'ورود ناموفق', 3000, null, null, 'filled');
    }
  }, [error]);

  const onUserLogin = (values) => {
    if (!loading && values.email && values.password) loginUserAction(values, history);
  };

  const logo = globalSettings?.panel_logo || '';

  return (
    <section className="spad-admin-login-card">
      <div className="spad-admin-login-visual">
        <div className="spad-admin-login-visual__glow spad-admin-login-visual__glow--one"/>
        <div className="spad-admin-login-visual__glow spad-admin-login-visual__glow--two"/>

        <div className="spad-admin-login-brand">
          {logo ? <img src={logo} alt="SPAD"/> : <span className="spad-admin-login-brand__mark">S</span>}
          <div>
            <strong>SPAD</strong>
            <small>سامانه مدیریت املاک</small>
          </div>
        </div>

        <div className="spad-admin-login-visual__content">
          <span className="spad-admin-login-chip"><i className="simple-icon-shield"/> محیط امن مدیریت</span>
          <h1>مرکز کنترل یکپارچه SPAD</h1>
          <p>مدیریت آگهی‌ها، درخواست‌ها، کاربران و قرعه‌کشی‌های ملک از یک داشبورد حرفه‌ای.</p>

          <div className="spad-admin-login-features">
            <div><i className="simple-icon-home"/><span>مدیریت املاک</span></div>
            <div><i className="simple-icon-people"/><span>مدیریت کاربران</span></div>
            <div><i className="simple-icon-chart"/><span>گزارش و عملیات</span></div>
          </div>
        </div>

        <div className="spad-admin-login-visual__foot">
          <span className="spad-admin-live-dot"/> پنل مدیریت آنلاین
        </div>
      </div>

      <div className="spad-admin-login-form-side">
        <div className="spad-admin-login-mobile-brand">
          {logo ? <img src={logo} alt="SPAD"/> : <span>S</span>}
        </div>

        <div className="spad-admin-login-heading">
          <span>ورود مدیران و اپراتورها</span>
          <h2>ورود به پنل مدیریت</h2>
          <p>اطلاعات حساب مدیریتی خود را وارد کنید.</p>
        </div>

        <Formik initialValues={{email: '', password: ''}} onSubmit={onUserLogin}>
          {({errors, touched}) => (
            <Form className="spad-admin-login-form">
              <FormGroup className="spad-admin-login-field">
                <Label htmlFor="admin-email">ایمیل</Label>
                <div className="spad-admin-login-input-wrap">
                  <i className="simple-icon-envelope"/>
                  <Field
                    id="admin-email"
                    className="form-control"
                    name="email"
                    type="email"
                    dir="ltr"
                    autoComplete="username"
                    placeholder="name@example.com"
                    validate={validateEmail}
                  />
                </div>
                {errors.email && touched.email ? <div className="spad-admin-login-error">{errors.email}</div> : null}
              </FormGroup>

              <FormGroup className="spad-admin-login-field">
                <Label htmlFor="admin-password">رمز عبور</Label>
                <div className="spad-admin-login-input-wrap">
                  <i className="simple-icon-lock"/>
                  <Field
                    id="admin-password"
                    className="form-control"
                    type="password"
                    name="password"
                    dir="ltr"
                    autoComplete="current-password"
                    placeholder="••••••••"
                    validate={validatePassword}
                  />
                </div>
                {errors.password && touched.password ? <div className="spad-admin-login-error">{errors.password}</div> : null}
              </FormGroup>

              <Button
                type="submit"
                color="primary"
                className={`spad-admin-login-submit btn-multiple-state ${loading ? 'show-spinner' : ''}`}
                disabled={loading}
              >
                <span className="spinner d-inline-block">
                  <span className="bounce1"/>
                  <span className="bounce2"/>
                  <span className="bounce3"/>
                </span>
                <span className="label">ورود به پنل مدیریت</span>
                <i className="simple-icon-arrow-left"/>
              </Button>
            </Form>
          )}
        </Formik>

        <div className="spad-admin-login-security">
          <i className="simple-icon-shield"/>
          <span>ورود به این بخش فقط برای کاربران مجاز امکان‌پذیر است.</span>
        </div>
      </div>
    </section>
  );
};

const mapStateToProps = ({authUser}) => {
  const {loading, error, currentUser} = authUser;
  return {loading, error, currentUser};
};

export default connect(mapStateToProps, {
  loginUserAction: loginUser,
  checkUser: checkLoginUser,
})(Login);

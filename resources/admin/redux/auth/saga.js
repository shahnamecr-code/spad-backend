import {all, call, fork, put, takeEvery} from 'redux-saga/effects';
import {auth} from '../../helpers/Firebase';
import {
  CHECK_LOGIN_USER,
  FORGOT_PASSWORD,
  haveStepAndLogin,
  haveStepToLogin,
  LOGIN_USER,
  LOGOUT_USER,
  REGISTER_USER,
  RESET_PASSWORD,
} from '../actions';
import axios from "axios";

import {
  forgotPasswordError,
  forgotPasswordSuccess,
  loginUserError,
  loginUserSuccess,
  registerUserError,
  registerUserSuccess,
  resetPasswordError,
  resetPasswordSuccess,
} from './actions';

import {adminPathApi, adminRoot, UserRole} from '../../constants/defaultValues';
import {setCurrentUser} from '../../helpers/Utils';

export function* watchLoginUser() {
  // eslint-disable-next-line no-use-before-define
  yield takeEvery(LOGIN_USER, loginWithEmailPassword);
}

const loginWithEmailPasswordAsync = async (email, password) =>
  // eslint-disable-next-line no-return-await
  await axios
    .post(`${adminPathApi}/login`, {email: email, password: password})
    .then((res) => res.data)
    .catch((error) => error);


function* loginWithEmailPassword({payload}) {
  const {email, password} = payload.user;
  const {history} = payload;
  try {
    const loginUser = yield call(loginWithEmailPasswordAsync, email, password);
    if (!loginUser.message) {
      if (loginUser.step) {
        yield responseForStepLogin(loginUser, history);
      } else {
        const item = {
          ...loginUser.user,
          id: loginUser.user.id,
          title: `${loginUser.user.first_name} ${loginUser.user.last_name}`,
          img: '/assets/img/profiles/l-8.jpg',
          date: loginUser.user.created_at,
          role: UserRole.Admin
        };
        //setCurrentUser(item);
        yield put(loginUserSuccess(item));
        history.push(adminRoot);
      }
    } else {
      yield put(loginUserError(loginUser.message));
    }
  } catch (error) {
    yield put(loginUserError(error));
  }
}

export function* watchRegisterUser() {
  // eslint-disable-next-line no-use-before-define
  yield takeEvery(REGISTER_USER, registerWithEmailPassword);
}

const registerWithEmailPasswordAsync = async (email, password) =>
  // eslint-disable-next-line no-return-await
  await auth
    .createUserWithEmailAndPassword(email, password)
    .then((user) => user)
    .catch((error) => error);

function* registerWithEmailPassword({payload}) {
  const {email, password} = payload.user;
  const {history} = payload;
  try {
    const registerUser = yield call(
      registerWithEmailPasswordAsync,
      email,
      password
    );
    if (!registerUser.message) {
      const item = {uid: registerUser.user.uid, ...currentUser};
      setCurrentUser(item);
      yield put(registerUserSuccess(item));
      history.push(adminRoot);
    } else {
      yield put(registerUserError(registerUser.message));
    }
  } catch (error) {
    yield put(registerUserError(error));
  }
}

export function* watchLogoutUser() {
  // eslint-disable-next-line no-use-before-define
  yield takeEvery(LOGOUT_USER, logout);
}

const logoutAsync = async (history) => {
  await axios
    .post(`${adminPathApi}/logout`)
    .then((res) => res.data)
    .catch((error) => error);
  history.push('/spad-login');
};

function* logout({payload}) {
  const {history} = payload;
  yield call(logoutAsync, history);
}

export function* watchForgotPassword() {
  // eslint-disable-next-line no-use-before-define
  yield takeEvery(FORGOT_PASSWORD, forgotPassword);
}

const forgotPasswordAsync = async (email) => {
  // eslint-disable-next-line no-return-await
  return await auth
    .sendPasswordResetEmail(email)
    .then((user) => user)
    .catch((error) => error);
};

function* forgotPassword({payload}) {
  const {email} = payload.forgotUserMail;
  try {
    const forgotPasswordStatus = yield call(forgotPasswordAsync, email);
    if (!forgotPasswordStatus) {
      yield put(forgotPasswordSuccess('success'));
    } else {
      yield put(forgotPasswordError(forgotPasswordStatus.message));
    }
  } catch (error) {
    yield put(forgotPasswordError(error));
  }
}

export function* watchResetPassword() {
  // eslint-disable-next-line no-use-before-define
  yield takeEvery(RESET_PASSWORD, resetPassword);
}

const resetPasswordAsync = async (resetPasswordCode, newPassword) => {
  // eslint-disable-next-line no-return-await
  return await auth
    .confirmPasswordReset(resetPasswordCode, newPassword)
    .then((user) => user)
    .catch((error) => error);
};

function* resetPassword({payload}) {
  const {newPassword, resetPasswordCode} = payload;
  try {
    const resetPasswordStatus = yield call(
      resetPasswordAsync,
      resetPasswordCode,
      newPassword
    );
    if (!resetPasswordStatus) {
      yield put(resetPasswordSuccess('success'));
    } else {
      yield put(resetPasswordError(resetPasswordStatus.message));
    }
  } catch (error) {
    yield put(resetPasswordError(error));
  }
}

export function* watchCheckLoginUser() {
  // eslint-disable-next-line no-use-before-define
  yield takeEvery(CHECK_LOGIN_USER, checkLoginUser);
}

const checkLoginUserAsync = async () =>
  await axios.get(`${adminPathApi}/user`)
    .then((res) => res.data)
    .catch((error) => error);


function* checkLoginUser({payload: {history}}) {
  try {
    const loginUser = yield call(checkLoginUserAsync);
    if (!loginUser.message) {
      if (loginUser.step) {
        yield responseForStepLogin(loginUser, history);
      } else {
        const item = {
          ...loginUser,
          id: loginUser.id,
          title: `${loginUser.first_name} ${loginUser.last_name}`,
          img: '/assets/img/profiles/l-1.jpg',
          date: loginUser.created_at,
          role: UserRole.Admin
        };
        yield put(loginUserSuccess(item));
      }
    } else {
      if (loginUser.request.status === 401) {
        yield put(loginUserError(401));
      } else {
        yield put(loginUserError(loginUser.message));
      }
    }
  } catch (error) {
    yield put(loginUserError(error))
  }
}

function* responseForStepLogin(loginUser, history) {
  if (loginUser.step === 'notApproved') {
    const item = {
      ...loginUser,
      id: loginUser.user.id,
      title: `${loginUser.user.first_name} ${loginUser.user.last_name}`,
      img: '/assets/img/profiles/l-1.jpg',
      date: loginUser.user.created_at,
      role: UserRole.Admin
    };
    yield put(haveStepAndLogin(loginUser.step, item));
    history.push('/spad-admin', {
      shopData: loginUser.shopData,
      reason: loginUser.reason,
      step: loginUser.step
    });
  } else if (loginUser.step === 'processing') {
    const item = {
      ...loginUser,
      id: loginUser.user.id,
      shop_id: loginUser.user.shop_id,
      title: `${loginUser.user.first_name} ${loginUser.user.last_name}`,
      img: '/assets/img/profiles/l-1.jpg',
      date: loginUser.user.created_at,
      role: UserRole.Admin
    };
    yield put(haveStepAndLogin(loginUser.step, item));
    history.push('/spad-admin', {
      shopData: loginUser.shopData,
      step: loginUser.step
    });
  } else {
    yield put(haveStepToLogin(loginUser.step));
    history.push('/spad-login/register', {
      email: loginUser.email ? loginUser.email : null,
      mobile: loginUser.mobile ? loginUser.mobile : null
    });
  }
}

export default function* rootSaga() {
  yield all([
    fork(watchLoginUser),
    fork(watchLogoutUser),
    fork(watchRegisterUser),
    fork(watchForgotPassword),
    fork(watchResetPassword),
    fork(watchCheckLoginUser)
  ]);
}

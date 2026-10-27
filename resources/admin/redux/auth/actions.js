// eslint-disable-next-line import/no-cycle
import {
  CHECK_LOGIN_USER,
  FORGOT_PASSWORD,
  FORGOT_PASSWORD_ERROR,
  FORGOT_PASSWORD_SUCCESS,
  HAVE_SHOP_LIST,
  HAVE_STEP_AND_LOGIN,
  HAVE_STEP_TO_LOGIN,
  LEAVE_USER,
  LOGIN_USER,
  LOGIN_USER_ERROR,
  LOGIN_USER_SUCCESS,
  LOGOUT_USER,
  REGISTER_USER,
  REGISTER_USER_ERROR,
  REGISTER_USER_SUCCESS,
  RESET_PASSWORD,
  RESET_PASSWORD_ERROR,
  RESET_PASSWORD_SUCCESS,
} from '../actions';

export const loginUser = (user, history) => ({
  type: LOGIN_USER,
  payload: {user, history},
});
export const loginUserSuccess = (user) => ({
  type: LOGIN_USER_SUCCESS,
  payload: user,
});
export const loginUserError = (message) => ({
  type: LOGIN_USER_ERROR,
  payload: {message},
});

export const forgotPassword = (forgotUserMail, history) => ({
  type: FORGOT_PASSWORD,
  payload: {forgotUserMail, history},
});
export const forgotPasswordSuccess = (forgotUserMail) => ({
  type: FORGOT_PASSWORD_SUCCESS,
  payload: forgotUserMail,
});
export const forgotPasswordError = (message) => ({
  type: FORGOT_PASSWORD_ERROR,
  payload: {message},
});

export const resetPassword = ({resetPasswordCode, newPassword, history}) => ({
  type: RESET_PASSWORD,
  payload: {resetPasswordCode, newPassword, history},
});
export const resetPasswordSuccess = (newPassword) => ({
  type: RESET_PASSWORD_SUCCESS,
  payload: newPassword,
});
export const resetPasswordError = (message) => ({
  type: RESET_PASSWORD_ERROR,
  payload: {message},
});

export const registerUser = (user, history) => ({
  type: REGISTER_USER,
  payload: {user, history},
});
export const registerUserSuccess = (user) => ({
  type: REGISTER_USER_SUCCESS,
  payload: user,
});
export const registerUserError = (message) => ({
  type: REGISTER_USER_ERROR,
  payload: {message},
});

export const logoutUser = (history) => ({
  type: LOGOUT_USER,
  payload: {history},
});

export const checkLoginUser = (history) => ({
  type: CHECK_LOGIN_USER,
  payload: {history}
})

export const haveStepToLogin = (step) => ({
  type: HAVE_STEP_TO_LOGIN,
  payload: {step}
})

export const haveStepAndLogin = (step, currentUser) => ({
  type: HAVE_STEP_AND_LOGIN,
  payload: {step, currentUser}
})

export const changeShopId = (shopId) => ({
  type: HAVE_SHOP_LIST,
  payload: shopId
})

export const leaveUser = () => ({
  type: LEAVE_USER,
  payload: null
})

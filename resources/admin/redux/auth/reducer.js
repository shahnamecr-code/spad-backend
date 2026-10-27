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
import {getShopInfo} from "../../helpers/Utils";

const INIT_STATE = {
  currentUser: null,
  shopId: getShopInfo(),
  step: null,
  forgotUserMail: '',
  newPassword: '',
  resetPasswordCode: '',
  loading: false,
  error: '',
};

export default (state = INIT_STATE, action) => {
  switch (action.type) {
    case LOGIN_USER:
      return {...state, loading: true, error: ''};
    case LOGIN_USER_SUCCESS:
      return {
        ...state,
        loading: false,
        currentUser: action.payload,
        error: '',
      };
    case LOGIN_USER_ERROR:
      return {
        ...state,
        loading: false,
        currentUser: null,
        error: action.payload.message,
      };
    case FORGOT_PASSWORD:
      return {...state, loading: true, error: ''};
    case FORGOT_PASSWORD_SUCCESS:
      return {
        ...state,
        loading: false,
        forgotUserMail: action.payload,
        error: '',
      };
    case FORGOT_PASSWORD_ERROR:
      return {
        ...state,
        loading: false,
        forgotUserMail: '',
        error: action.payload.message,
      };
    case RESET_PASSWORD:
      return {...state, loading: true, error: ''};
    case RESET_PASSWORD_SUCCESS:
      return {
        ...state,
        loading: false,
        newPassword: action.payload,
        resetPasswordCode: '',
        error: '',
      };
    case RESET_PASSWORD_ERROR:
      return {
        ...state,
        loading: false,
        newPassword: '',
        resetPasswordCode: '',
        error: action.payload.message,
      };
    case REGISTER_USER:
      return {...state, loading: true, error: ''};
    case REGISTER_USER_SUCCESS:
      return {
        ...state,
        loading: false,
        currentUser: action.payload,
        error: '',
      };
    case REGISTER_USER_ERROR:
      return {
        ...state,
        loading: false,
        currentUser: null,
        error: action.payload.message,
      };
    case LOGOUT_USER:
      return {...state, currentUser: null, error: ''};
    case CHECK_LOGIN_USER:
      return {...state, loading: true, error: ''}
    case HAVE_STEP_TO_LOGIN:
      return {
        ...state,
        loading: false,
        step: action.payload.step,
      };
    case HAVE_STEP_AND_LOGIN:
      return {
        ...state,
        loading: false,
        step: action.payload.step,
        currentUser: action.payload.currentUser
      }
    case HAVE_SHOP_LIST: {
      return {...state, shopId: action.payload}
    }
    case LEAVE_USER: {
      return {...state, currentUser: null, error: 401,}
    }
    default:
      return {...state};
  }
};

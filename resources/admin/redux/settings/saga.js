import {changePanelLogo} from "./actions";
import axios from "axios";
import {adminPathApi, adminRoot, UserRole} from "../../constants/defaultValues";
import {all, call, fork, put, takeEvery} from "redux-saga/effects";
import {GetPanelSettings} from './actions';
import {loginUserError, loginUserSuccess} from "../auth/actions";
import {GET_PANEL_SETTINGS, REGISTER_USER} from "../actions";



function* getPanleSetting({payload}) {
  const {email, password} = payload.user;
  const {history} = payload;
  try {
    const loginUser = yield call(SettingAsync);
    console.log(loginUser)
  } catch (error) {
    yield put(loginUserError(error));
  }
}

export function* watchRegisterUser() {
  // eslint-disable-next-line no-use-before-define
  yield takeEvery(GET_PANEL_SETTINGS, getPanleSetting);
}


export default function* rootSaga() {
  yield all([
    fork(changePanelLogo)
  ]);
}

const SettingAsync = async (history) => {
  await axios
    .post(`/api/global-settings`)
    .then((res) => res.data)
    .catch((error) => error);
};

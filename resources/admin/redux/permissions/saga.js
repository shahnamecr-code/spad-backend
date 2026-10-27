import {all, call, fork, put, takeEvery} from 'redux-saga/effects';
import {PERMISSION_ALL} from "../actions";
import axios from "axios";
import {permissionAllError, permissionAllSuccess} from "./actions";
import {adminPathApi} from "../../constants/defaultValues";

export function* watchPermissionAll() {
  yield takeEvery(PERMISSION_ALL, fetchPermissionAndRole);
}

const fetchPermissionAndRoleAsync = async () =>
  await axios.get(`${adminPathApi}/rolesAndPermissions`)
    .then((res) => res.data)
    .catch((error) => error);


function* fetchPermissionAndRole() {
  try {
    const permissionAndRole = yield call(fetchPermissionAndRoleAsync);
    yield put(permissionAllSuccess(permissionAndRole))
  }catch (error){
    yield put(permissionAllError(error))
  }
}

export default function* rootSaga() {
  yield all([
    fork(watchPermissionAll)
  ])
}

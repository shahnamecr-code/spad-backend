import {PERMISSION_ALL, PERMISSION_ALL_ERROR, PERMISSION_ALL_SUCCESS} from "../actions";


export const permissionAll = () => ({
  type: PERMISSION_ALL,
  payload: null
});

export const permissionAllSuccess = (permissions) => ({
  type: PERMISSION_ALL_SUCCESS,
  payload: permissions
});

export const permissionAllError = () => ({
  type: PERMISSION_ALL_ERROR,
  payload: null,
})

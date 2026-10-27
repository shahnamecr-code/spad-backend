import {PERMISSION_ALL, PERMISSION_ALL_ERROR, PERMISSION_ALL_SUCCESS} from "../actions";


const INIT_STATE = {
  permissions: [],
  loading: false,
  errors: ''
};

export default (state = INIT_STATE, action) => {
  switch (action.type) {
    case PERMISSION_ALL:
      return {...state, loading: true, errors: ''};
    case PERMISSION_ALL_SUCCESS:
      return {...state, loading: false, permissions: action.payload, errors: ''};
    case PERMISSION_ALL_ERROR:
      return {...state, loading: false, errors: action.payload};
    default:
      return {...state};
  }
}

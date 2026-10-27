import {combineReducers} from 'redux';
import settings from './settings/reducer';
import menu from './menu/reducer';
import authUser from './auth/reducer';
import permissionsRoles from './permissions/reducer';

const reducers = combineReducers({
  menu,
  settings,
  authUser,
  //todoApp,
  //chatApp,
  //surveyListApp,
  //surveyDetailApp,
  permissionsRoles
});

export default reducers;

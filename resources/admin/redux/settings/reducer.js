import {CHANGE_LOCALE, GET_PANEL_SETTINGS} from '../actions';
import { getCurrentLanguage,getPanelSetting } from '../../helpers/Utils';

const INIT_STATE = {
  locale: getCurrentLanguage(),
  panel_settings: getPanelSetting()
};

export default (state = INIT_STATE, action) => {
  switch (action.type) {
    case CHANGE_LOCALE:
      return { ...state, locale: action.payload };
    case  GET_PANEL_SETTINGS:
      return { ...state, panel_settings: action.payload };
    default:
      return { ...state };
  }
};

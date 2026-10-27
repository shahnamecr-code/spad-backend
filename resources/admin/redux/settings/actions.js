// eslint-disable-next-line import/no-cycle
import { CHANGE_LOCALE , GET_PANEL_SETTINGS, SET_PANEL_SETTINGS} from '../actions';
import { setCurrentLanguage } from '../../helpers/Utils';

// eslint-disable-next-line import/prefer-default-export
export const changeLocale = (locale) => {
  setCurrentLanguage(locale);
  return {
    type: CHANGE_LOCALE,
    payload: locale,
  };
};

export const GetPanelSettings = () => {
  return {
    type: GET_PANEL_SETTINGS
  };
};
export const SetPanelSettings = (setting) => {
  return {
    type:  SET_PANEL_SETTINGS,
    payload:setting
  };
};

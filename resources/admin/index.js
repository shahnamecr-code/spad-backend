/* eslint-disable global-require */
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window._ = require('lodash');

import {
  isMultiColorActive,
  defaultColor,
  isDarkSwitchActive,
} from './constants/defaultValues';
import {getCurrentColor, setCurrentColor} from './helpers/Utils';

const color = isMultiColorActive || isDarkSwitchActive ? getCurrentColor() : defaultColor;
setCurrentColor(color);

const render = () => {
  import(`./assets/css/sass/themes/gogo.${color}.scss`).then(() => {
    import('./assets/css/sass/spad-admin-v5.scss').then(() => {
      require('./AppRenderer');
    });
  });
};

render();

if (!Object.hasOwn) {
  Object.hasOwn = (obj, prop) =>
    Object.prototype.hasOwnProperty.call(obj, prop);
}

import { createApp } from 'vue';
import VueCookies from 'vue3-cookies';
import setViewportVariables from '@/vunit';
import App from './App.vue';
import router from './router';
import mitt, { Emitter, EventType } from 'mitt';
import VueObserveVisibility from 'vue3-observe-visibility2';
import * as i18n from '@/i18n';
import * as authService from '@/services/auth-service';

import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import initCustomPixiRenderer from '@/utils/game/customPixiRenderer';

import '@/assets/styles/global.scss';
import ElementPlus from 'element-plus';
import '@/assets/styles/element-plus.scss';
import 'md-editor-v3/lib/style.css';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  ArcElement,
  PointElement,
  Filler,
} from 'chart.js';

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  ArcElement,
  PointElement,
  Filler
);

ChartJS.defaults.font.family =
  "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif, 'Font Awesome 6 Free Solid'";

import { IconPack, library } from '@fortawesome/fontawesome-svg-core';
// internal icons
import { fas } from '@fortawesome/free-solid-svg-icons';
import { far } from '@fortawesome/free-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

import { fac } from '@/assets/icons/fac';

library.add(fas as IconPack);
library.add(far as IconPack);
library.add(fac);

/* eslint-disable @typescript-eslint/no-explicit-any*/

declare module '@vue/runtime-core' {
  interface ComponentCustomProperties {
    eventBus: Emitter<Record<EventType, unknown>>;
  }
}

const eventBus = mitt();
const app = createApp(App);
app.component('font-awesome-icon', FontAwesomeIcon);
app.use(router);
app.use(VueCookies as any, {
  expireTimes: '356d',
  secure: true,
  sameSite: 'Strict', // "Lax"
});
app.config.globalProperties.eventBus = eventBus;
app.use(ElementPlus);
app.use(VueObserveVisibility);
app.use(i18n.customI18n(authService.getLocale()));

app.component('GameContainer', GameContainer);
initCustomPixiRenderer();

/*app.config.warnHandler = (msg, instance, trace) => {
  console.log(msg, instance, trace);
  ![
    'A BaseTexture managed by Assets was destroyed instead of unloaded! Use Assets.unload() instead of destroying the BaseTexture.',
    'App already provides property with key "Symbol(pixi_application)". It will be overwritten with the new value.',
    'settings.FILTER_RESOLUTION is deprecated, use Filter.defaultResolution Deprecated since v7.1.0',
    'utils.rgb2hex is deprecated, use Color#toNumber instead Deprecated since v7.2.0',
  ].some((warning) => msg.includes(warning)) &&
  console.warn('[no warn]: '.concat(msg).concat(trace));
};*/
/*const warn = console.warn;
const groupCollapsed = console.groupCollapsed;
console.groupCollapsed = (...data: any[]): void => {
  const stringData = data.filter((item) => typeof item === 'string');
  const isPixiDeprecation = !!stringData.find((item) =>
    item.includes('PixiJS Deprecation Warning:')
  );
  if (isPixiDeprecation) {
    const isPackageWarning = !!stringData.find(
      (item) =>
        item.includes(
          'settings.FILTER_RESOLUTION is deprecated, use Filter.defaultResolution'
        ) ||
        item.includes(
          'utils.rgb2hex is deprecated, use Color#toNumber instead'
        ) ||
        item.includes(
          'utils.hex2rgb is deprecated, use Color#toRgbArray instead'
        )
    );
    if (!isPackageWarning) {
      groupCollapsed(...data);
    }
  } else groupCollapsed(...data);
};
console.warn = (...data: any[]): void => {
  const stringData = data.filter((item) => typeof item === 'string' && !!item);
  const objectData = data.filter((item) => typeof item === 'object' && !!item);
  const isPixiPackageWarning = !!stringData.find(
    (item) =>
      item.includes('@pixi/filter-advanced-bloom/') ||
      item.includes('@pixi/filter-multi-color-replace/') ||
      item.includes('@pixi/filter-color-overlay/') ||
      item.includes('@pixi/filter-outline/') ||
      item.includes(
        'App already provides property with key "Symbol(pixi_application)". It will be overwritten with the new value.'
      ) ||
      item.includes(
        'A BaseTexture managed by Assets was destroyed instead of unloaded! Use Assets.unload() instead of destroying the BaseTexture.'
      )
  );
  const isValidationWarning = !!objectData.find((item) => {
    const array = Object.values(item);
    if (
      Array.isArray(array) &&
      array.length > 0 &&
      Array.isArray(array[0]) &&
      array[0].length > 0
    ) {
      const value = array[0][0];
      return Object.hasOwn(value, 'message') && Object.hasOwn(value, 'field');
    }
    return false;
  });
  if (isValidationWarning) {
    for (const item of objectData) {
      const array = Object.values(item) as any[];
      for (const subArray of array) {
        for (const item2 of subArray) {
          warn(`validation warning: ${item2.field} - ${item2.message}`);
        }
      }
    }
  } else if (!isPixiPackageWarning && !isValidationWarning) {
    warn(...data);
  }
};*/
app.mount('#app');

setViewportVariables();
window.history.scrollRestoration = 'manual';

export default app;

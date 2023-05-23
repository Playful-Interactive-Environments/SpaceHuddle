import '@/assets/styles/global.scss';
import { createApp } from 'vue';
import VueCookies from 'vue3-cookies';
import setViewportVariables from '@/vunit';
import App from './App.vue';
import router from './router';
import mitt, { Emitter, EventType } from 'mitt';
import VueObserveVisibility from 'vue3-observe-visibility2';
import * as i18n from '@/i18n';
import * as authService from '@/services/auth-service';

import ElementPlus from 'element-plus';
import '@/assets/styles/element-plus.scss';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
} from 'chart.js';

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
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
app.mount('#app');

setViewportVariables();

export default app;

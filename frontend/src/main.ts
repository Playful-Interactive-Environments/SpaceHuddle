import { createApp } from 'vue';
import VueCookies from 'vue3-cookies';
import i18n from '@/i18n';
import setViewportVariables from '@/vunit';
import App from './App.vue';
import router from './router';
import mitt, { Emitter, EventType } from 'mitt';
import VueObserveVisibility from 'vue3-observe-visibility2';

import ElementPlus from 'element-plus';
import '@/assets/styles/global.scss';
import '@/assets/styles/element-plus.scss';

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
app.use(i18n);
app.use(VueCookies as any, {
  expireTimes: '30d',
  secure: true,
  sameSite: 'Strict', // "Lax"
});
app.config.globalProperties.eventBus = eventBus;
app.use(ElementPlus);
app.use(VueObserveVisibility);
app.mount('#app');

setViewportVariables();

export default app;

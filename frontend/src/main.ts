import { createApp } from 'vue';
import VueFinalModal from 'vue-final-modal';
import VueCookies from 'vue3-cookies';
import i18n from '@/i18n';
import App from './App.vue';
import router from './router';
import mitt, { Emitter, EventType } from 'mitt';

import ElementPlus from 'element-plus';
import '@/assets/styles/global.scss';
import '@/assets/styles/element-plus.scss';

import { library } from '@fortawesome/fontawesome-svg-core';
// internal icons
import { fas } from '@fortawesome/free-solid-svg-icons';
import { far } from '@fortawesome/free-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

import { fac } from '@/assets/icons/fac/index.ts';

library.add(fas);
library.add(far);
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
app.use(i18n as any);
app.use(VueCookies as any, {
  expireTimes: '30d',
  secure: true,
  sameSite: 'Strict', // "Lax"
});
app.config.globalProperties.eventBus = eventBus;
app.config.globalProperties.$t = i18n.t2;
app.use(VueFinalModal(), {});
app.use(ElementPlus);
app.mount('#app');

export default app;

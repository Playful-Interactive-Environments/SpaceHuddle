import { createApp } from 'vue';
import VueFinalModal from 'vue-final-modal';
import VueCookies from 'vue3-cookies';
import i18n from '@/i18n';
import App from './App.vue';
import router from './router';
import mitt, { Emitter, EventType } from 'mitt';
import { ComponentCustomProperties } from 'vue';

import ElementPlus from 'element-plus';
//import 'element-plus/lib/theme-chalk/index.css';
import 'element-plus/packages/theme-chalk/src/index.scss';
import '@/assets/styles/global.scss';

declare module '@vue/runtime-core' {
  interface ComponentCustomProperties {
    eventBus: Emitter<Record<EventType, unknown>>;
  }
}

const eventBus = mitt();
const app = createApp(App);
app.use(router);
app.use(i18n as any);
app.use(VueCookies as any, {
  expireTimes: "30d",
  secure: true,
  sameSite: "Strict" // "Lax"
});
app.config.globalProperties.eventBus = eventBus;
app.config.globalProperties.$t = i18n.t2;
app.use(VueFinalModal(), {});
app.use(ElementPlus);
app.mount('#app');

export default app;

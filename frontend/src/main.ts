import { createApp } from 'vue';
import VueFinalModal from 'vue-final-modal';
import VueCookies from 'vue3-cookies';
import i18n from '@/i18n';
import App from './App.vue';
import router from './router';
import '@/assets/styles/global.scss';
import mitt, { Emitter, EventType } from 'mitt';
import { ComponentCustomProperties } from 'vue';
import { VuesticPlugin } from 'vuestic-ui';
import 'vuestic-ui/dist/vuestic-ui.css';

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
app.use(VuesticPlugin);
app.mount('#app');

export default app;

import { createApp } from 'vue';
import VueFinalModal from 'vue-final-modal';
import VueCookies from 'vue3-cookies';
import App from './App.vue';
import router from './router';
import '@/assets/styles/global.scss';
import mitt, { Emitter, EventType } from 'mitt';
import { ComponentCustomProperties } from 'vue';

declare module '@vue/runtime-core' {
  interface ComponentCustomProperties {
    eventBus: Emitter<Record<EventType, unknown>>;
  }
}

const eventBus = mitt();
const app = createApp(App);
app.use(router);
app.use(VueCookies as any, {
  expireTimes: "30d",
  secure: true,
  sameSite: "Strict" // "Lax"
});
app.config.globalProperties.eventBus = eventBus;
app.use(VueFinalModal(), {});
app.mount('#app');

export default app;
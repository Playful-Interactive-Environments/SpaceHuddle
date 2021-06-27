import { createApp } from 'vue';
import VueFinalModal from 'vue-final-modal';
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
app.config.globalProperties.eventBus = eventBus;
app.use(VueFinalModal(), {});
app.mount('#app');

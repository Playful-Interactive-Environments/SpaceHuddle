import { createApp } from 'vue';
import VueFinalModal from 'vue-final-modal';
import App from './App.vue';
import router from './router';
import '@/assets/styles/global.scss';

const app = createApp(App);
app.use(router);
app.use(VueFinalModal(), {});
app.mount('#app');

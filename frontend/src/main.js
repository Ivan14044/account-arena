import './bootstrap';
import { createApp } from 'vue';
import App from './components/App.vue';
import vuetify from './plugins/vuetify';
import router from './router';
import i18n from './i18n';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';
import './assets/app.css';
import Toast, { POSITION } from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import Vue3Lottie from 'vue3-lottie';
import IntersectDirective from './directives/intersect';

// Disable native scroll restoration to let Vue Router handle it
if ('scrollRestoration' in window.history) {
    window.history.scrollRestoration = 'manual';
}

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);

const app = createApp(App);
app.use(i18n);
app.use(router);
app.use(vuetify);
app.use(pinia);
app.use(Toast, {
    position: POSITION.TOP_RIGHT,
    timeout: 5000
});
app.use(Vue3Lottie);
app.directive('intersect', IntersectDirective);
app.mount('#app');

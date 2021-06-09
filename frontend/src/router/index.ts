import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import Home from '../views/Home.vue';
import Join from '../views/client/Join.vue';
import ModuleOverview from '@/views/client/Overview.vue';
import SessionOverview from '@/views/moderator/SessionOverview.vue';
import SessionDetails from '@/views/moderator/SessionDetails.vue';

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    name: 'home',
    component: Home,
  },
  {
    path: '/about',
    name: 'about',
    // route level code-splitting
    // this generates a separate chunk (about.[hash].js) for this route
    // which is lazy-loaded when the route is visited.
    component: () =>
      import(/* webpackChunkName: "about" */ '../views/About.vue'),
  },
  {
    path: '/join',
    name: 'join',
    component: Join,
  },
  {
    path: '/overview',
    name: 'module-overview',
    component: ModuleOverview,
  },
  {
    path: '/session-overview',
    name: 'session-overview',
    component: SessionOverview,
  },
  {
    path: '/session-detail',
    name: 'session-detail',
    component: SessionDetails,
  },
];

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes,
});

export default router;

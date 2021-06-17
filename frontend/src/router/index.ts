import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import Home from '../views/Home.vue';
import Join from '../views/client/Join.vue';
import ModuleOverview from '@/views/client/Overview.vue';
import SessionOverview from '@/views/moderator/SessionOverview.vue';
import SessionDetails from '@/views/moderator/SessionDetails.vue';
import Register from '@/views/moderator/Register.vue';
import Debug from '@/views/Debug.vue';
import Login from '@/views/moderator/Login.vue';
import { isAuthenticated } from '@/services/moderator/auth-service';

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    name: 'home',
    component: Home,
  },
  {
    path: '/login',
    name: 'login',
    component: Login,
  },
  {
    path: '/register',
    name: 'register',
    component: Register,
  },
  {
    path: '/join',
    name: 'join',
    component: Join,
  },
  {
    path: '/client/:id',
    name: 'module-overview',
    component: ModuleOverview,
    props: (route) => ({ id: route.params.id }),
  },
  {
    path: '/overview',
    name: 'overview',
    component: SessionOverview,
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/:id',
    name: 'session-detail',
    component: SessionDetails,
    props: (route) => ({ id: route.params.id }),
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/debug',
    name: 'debug',
    component: Debug,
  },
];

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes,
});

router.beforeEach((to, from, next) => {
  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);

  if (requiresAuth && !isAuthenticated()) next('login');
  else next();
});

export default router;

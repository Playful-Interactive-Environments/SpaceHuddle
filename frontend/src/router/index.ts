import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';

import ClientJoin from '../views/client/ClientJoin.vue';
import ClientOverview from '@/views/client/ClientOverview.vue';
import Home from '../views/shared/Home.vue';
import ModeratorLogin from '@/views/moderator/ModeratorLogin.vue';
import ModeratorRegister from '@/views/moderator/ModeratorRegister.vue';
import ModeratorSessionDetails from '@/views/moderator/ModeratorSessionDetails.vue';
import ModeratorSessionOverview from '@/views/moderator/ModeratorSessionOverview.vue';
import ModeratorBrainstorming from '@/views/moderator/ModeratorBrainstorming.vue';
import ModeratorInformation from '@/views/moderator/ModeratorInformation.vue';
import ModeratorSelection from '@/views/moderator/ModeratorSelection.vue';
import ModeratorCategorisation from '@/views/moderator/ModeratorCategorisation.vue';
import ModeratorProfile from '@/views/moderator/ModeratorProfile.vue';
import ModeratorVoting from '@/views/moderator/ModeratorVoting.vue';
import NotFound from '@/views/shared/NotFound.vue';

import { isAuthenticated } from '@/services/auth-service';
import ClientBrainstorming from '@/views/client/ModeratorBrainstorming.vue';

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    name: 'home',
    component: Home,
  },
  {
    path: '/login',
    name: 'moderator-login',
    component: ModeratorLogin,
  },
  {
    path: '/register',
    name: 'moderator-register',
    component: ModeratorRegister,
  },
  {
    path: '/profile',
    name: 'moderator-profile',
    component: ModeratorProfile,
  },
  {
    path: '/sessions',
    name: 'moderator-session-overview',
    component: ModeratorSessionOverview,
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/session/:sessionId',
    name: 'moderator-session-details',
    component: ModeratorSessionDetails,
    props: (route) => ({ sessionId: route.params.sessionId }),
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/brainstorming/:sessionId/:taskId',
    name: 'moderator-brainstorming',
    component: ModeratorBrainstorming,
    props: (route) => ({
      sessionId: route.params.sessionId,
      taskId: route.params.taskId,
    }),
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/information/:sessionId/:taskId',
    name: 'moderator-information',
    component: ModeratorInformation,
    props: (route) => ({
      sessionId: route.params.sessionId,
      taskId: route.params.taskId,
    }),
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/selection/:sessionId/:taskId',
    name: 'moderator-selection',
    component: ModeratorSelection,
    props: (route) => ({
      sessionId: route.params.sessionId,
      taskId: route.params.taskId,
    }),
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/categorisation/:sessionId/:taskId',
    name: 'moderator-categorisation',
    component: ModeratorCategorisation,
    props: (route) => ({
      sessionId: route.params.sessionId,
      taskId: route.params.taskId,
    }),
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/voting/:sessionId/:taskId',
    name: 'moderator-voting',
    component: ModeratorVoting,
    props: (route) => ({
      sessionId: route.params.sessionId,
      taskId: route.params.taskId,
    }),
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/join',
    name: 'client-join',
    component: ClientJoin,
  },
  {
    path: '/:sessionKey',
    name: 'client-overview',
    component: ClientOverview,
    props: (route) => ({ sessionKey: route.params.sessionKey }),
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: '/:catchAll(.*)',
    component: NotFound,
  },
  {
    path: '/brainstorming',
    name: 'client-brainstorming',
    component: ClientBrainstorming,
  },
];

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes,
});

router.beforeEach((to, from, next) => {
  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);

  if (requiresAuth && !isAuthenticated()) next({ name: 'home' });
  else next();
});

export default router;

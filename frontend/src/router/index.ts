import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import { getRoutes } from '@/modules';

import ParticipantJoin from '@/views/participant/ParticipantJoin.vue';
import ParticipantOverview from '@/views/participant/ParticipantOverview.vue';
import Home from '@/views/shared/Home.vue';
import ModeratorLogin from '@/views/moderator/ModeratorLogin.vue';
import ModeratorRegister from '@/views/moderator/ModeratorRegister.vue';
import ModeratorSessionDetails from '@/views/moderator/ModeratorSessionDetails.vue';
import ModeratorSessionOverview from '@/views/moderator/ModeratorSessionOverview.vue';
import ModeratorProfile from '@/views/moderator/ModeratorProfile.vue';
import ModeratorModuleContent from '@/views/moderator/ModeratorModuleContent.vue';
import NotFound from '@/views/shared/NotFound.vue';
import PublicScreen from '@/views/PublicScreen.vue';

import {
  isParticipant,
  isAuthenticated,
  isUser,
  removeAccessToken,
} from '@/services/auth-service';
import app from '@/main';
import { EventType } from '@/types/enum/EventType';
import SnackbarType from '@/types/enum/SnackbarType';

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
    meta: {
      requiresAuth: true,
      requiresUser: true,
    },
  },
  {
    path: '/sessions',
    name: 'moderator-session-overview',
    component: ModeratorSessionOverview,
    meta: {
      requiresAuth: true,
      requiresUser: true,
    },
  },
  {
    path: '/session/:sessionId',
    name: 'moderator-session-details',
    component: ModeratorSessionDetails,
    props: (route) => ({ sessionId: route.params.sessionId }),
    meta: {
      requiresAuth: true,
      requiresUser: true,
    },
  },
  {
    path: '/module-content/:sessionId/:taskId',
    name: 'moderator-module-content',
    component: ModeratorModuleContent,
    props: (route) => ({
      sessionId: route.params.sessionId,
      taskId: route.params.taskId,
    }),
    meta: {
      requiresAuth: true,
      requiresUser: true,
    },
  },
  {
    path: '/join',
    name: 'participant-join',
    component: ParticipantJoin,
  },
  {
    path: '/overview',
    name: 'participant-overview',
    component: ParticipantOverview,
    meta: {
      requiresAuth: true,
      requiresParticipant: true,
    },
  },
  {
    path: '/:catchAll(.*)',
    component: NotFound,
  },
  {
    path: '/public-screen/:sessionId',
    name: 'public-screen',
    component: PublicScreen,
    meta: {
      requiresAuth: true,
    },
    props: (route) => ({
      sessionId: route.params.sessionId,
    }),
  },
];

let allRoutesLoaded = false;

getRoutes().then((moduleRoutes) => {
  moduleRoutes.forEach((item) => {
    router.addRoute(item);
  });
  allRoutesLoaded = true;
});

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes: routes,
});

router.beforeEach((to, from, next) => {
  //if (!to.name && !allRoutesLoaded) {
  if (!allRoutesLoaded) {
    setTimeout(() => {
      next({ path: to.path });
    }, 200);
    return;
  }

  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);
  const requiresUser = to.matched.some((record) => record.meta.requiresUser);
  const requiresParticipant = to.matched.some(
    (record) => record.meta.requiresParticipant
  );

  if (
    (requiresAuth && !isAuthenticated()) ||
    (requiresUser && !isUser()) ||
    (requiresParticipant && !isParticipant())
  ) {
    let errorMessage = 'authorisationExpired';
    if (isAuthenticated()) errorMessage = 'incorrectAuthorisationType';
    app.config.globalProperties.eventBus.emit(EventType.SHOW_SNACKBAR, {
      type: SnackbarType.ERROR,
      message: `error.route.${errorMessage}`,
    });
    removeAccessToken();
    next({ name: 'home' });
  } else next();
});

export default router;

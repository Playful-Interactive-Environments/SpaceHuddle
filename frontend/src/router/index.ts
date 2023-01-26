import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import { getRoutes } from '@/modules';

import ParticipantJoin from '@/views/participant/ParticipantJoin.vue';
import ParticipantOverview from '@/views/participant/ParticipantOverview.vue';
import Home from '@/views/shared/Home.vue';
import ModeratorLogin from '@/views/moderator/User/ModeratorLogin.vue';
import ModeratorRegister from '@/views/moderator/User/ModeratorRegister.vue';
import ModeratorSessionDetails from '@/views/moderator/ModeratorSessionDetails.vue';
import ModeratorTopicDetails from '@/views/moderator/ModeratorTopicDetails.vue';
import ModeratorSessionOverview from '@/views/moderator/ModeratorSessionOverview.vue';
import ModeratorProfile from '@/views/moderator/User/ModeratorProfile.vue';
import ConfirmEmail from '@/views/moderator/User/ConfirmEmail.vue';
import ChangePassword from '@/views/moderator/User/ChangePassword.vue';
import ForgetPassword from '@/views/moderator/User/ForgetPassword.vue';
import ResetPassword from '@/views/moderator/User/ResetPassword.vue';
import NotFound from '@/views/shared/NotFound.vue';
import PublicScreen from '@/views/PublicScreen.vue';
import ParticipantModuleContent from '@/views/participant/ParticipantModuleContent.vue';

import {
  isParticipant,
  isAuthenticated,
  isUser,
  removeAccessToken,
} from '@/services/auth-service';
import app from '@/main';
import { ElMessage } from 'element-plus';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { useI18n } from 'vue-i18n';

/* eslint-disable @typescript-eslint/no-explicit-any*/

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
    path: '/reset-password/',
    name: 'moderator-reset-password',
    component: ResetPassword,
  },
  {
    path: '/confirm-email/:token',
    name: 'moderator-confirm',
    component: ConfirmEmail,
    props: (route) => ({
      token: route.params.token,
    }),
  },
  {
    path: '/forget-password/:token',
    name: 'moderator-forget-password',
    component: ForgetPassword,
    props: (route) => ({
      token: route.params.token,
    }),
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
    path: '/change-password',
    name: 'moderator-change-password',
    component: ChangePassword,
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
    path: '/topic/:sessionId/:topicId/:taskId?',
    name: 'moderator-topic-details',
    component: ModeratorTopicDetails,
    props: (route) => ({
      sessionId: route.params.sessionId,
      topicId: route.params.topicId,
      taskId: route.params.taskId,
    }),
    meta: {
      requiresAuth: true,
      requiresUser: true,
    },
  },
  {
    path: '/join/:connectionKey?',
    name: 'participant-join',
    component: ParticipantJoin,
    props: (route) => ({
      connectionKey: route.params.connectionKey,
    }),
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
    path: '/participant-module-content/:taskId',
    name: 'participant-module-content',
    component: ParticipantModuleContent,
    props: (route) => ({
      taskId: route.params.taskId,
    }),
    meta: {
      requiresAuth: true,
      requiresParticipant: true,
    },
  },
  {
    path: '/public-screen/:sessionId/:authHeaderTyp?',
    name: 'public-screen',
    component: PublicScreen,
    meta: {
      requiresAuth: false,
    },
    props: (route) => ({
      sessionId: route.params.sessionId,
      authHeaderTyp: route.params.authHeaderTyp
        ? route.params.authHeaderTyp === 'everyone'
          ? EndpointAuthorisationType.UNAUTHORISED
          : route.params.authHeaderTyp
        : EndpointAuthorisationType.MODERATOR,
    }),
  },
  {
    path: '/:catchAll(.*)',
    component: NotFound,
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
      next({
        path: to.path,
        query: to.query,
      });
    }, 200);
    return;
  }
  if (to.query.locale) {
    const locale = to.query.locale as string;
    const i18n = useI18n();
    if (i18n.availableLocales.includes(locale)) {
      i18n.locale.value = locale;
    }
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
    ElMessage({
      message: app.config.globalProperties.$t(`error.route.${errorMessage}`),
      type: 'error',
      center: true,
      showClose: true,
    });
    removeAccessToken();
    next({ name: 'home' });
  } else next();
});

export default router;

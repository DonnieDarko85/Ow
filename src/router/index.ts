import { createRouter, createWebHashHistory } from 'vue-router';
import AppShell from '@/layouts/AppShell.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import DashboardView from '@/views/DashboardView.vue';
import TerritoryDetailView from '@/views/TerritoryDetailView.vue';
import SubmitResultView from '@/views/SubmitResultView.vue';
import ResultsView from '@/views/ResultsView.vue';
import ProfileView from '@/views/ProfileView.vue';
import LoginView from '@/views/LoginView.vue';
import RegisterView from '@/views/RegisterView.vue';
import ForgotPasswordView from '@/views/ForgotPasswordView.vue';
import { useAppStore } from '@/stores/app';

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    {
      path: '/',
      component: AppShell,
      children: [
        {
          path: '',
          name: 'dashboard',
          component: DashboardView,
        },
        {
          path: 'territories/:slug',
          name: 'territory-detail',
          component: TerritoryDetailView,
        },
        {
          path: 'submit-result',
          name: 'submit-result',
          component: SubmitResultView,
          meta: { requiresAuth: true },
        },
        {
          path: 'results',
          name: 'results',
          component: ResultsView,
          meta: { requiresAuth: true },
        },
        {
          path: 'profile',
          name: 'profile',
          component: ProfileView,
          meta: { requiresAuth: true },
        },
      ],
    },
    {
      path: '/auth',
      component: AuthLayout,
      children: [
        {
          path: 'login',
          name: 'login',
          component: LoginView,
        },
        {
          path: 'register',
          name: 'register',
          component: RegisterView,
        },
        {
          path: 'forgot-password',
          name: 'forgot-password',
          component: ForgotPasswordView,
        },
      ],
    },
  ],
});

router.beforeEach(async (to, from, next) => {
  const appStore = useAppStore();

  await appStore.ensureBootstrapped();

  if (to.matched.some((record) => record.meta.requiresAuth) && !appStore.isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } });
  } else if (to.name === 'login' && appStore.isAuthenticated) {
    next({ name: 'dashboard' });
  } else {
    next();
  }
});

export default router;

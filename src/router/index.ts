import { createRouter, createWebHistory } from 'vue-router';
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
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: AppShell,
      meta: { requiresAuth: true },
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
        },
        {
          path: 'results',
          name: 'results',
          component: ResultsView,
        },
        {
          path: 'profile',
          name: 'profile',
          component: ProfileView,
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

// Route guard per proteggere le rotte che richiedono autenticazione
router.beforeEach((to, from, next) => {
  const appStore = useAppStore();
  
  if (to.meta.requiresAuth && !appStore.isAuthenticated) {
    // Se la rotta richiede autenticazione e l'utente non è autenticato, reindirizza a login
    next({ name: 'login', query: { redirect: to.fullPath } });
  } else if (to.name === 'login' && appStore.isAuthenticated) {
    // Se l'utente è già autenticato e tenta di andare a login, reindirizza a dashboard
    next({ name: 'dashboard' });
  } else {
    next();
  }
});

export default router;


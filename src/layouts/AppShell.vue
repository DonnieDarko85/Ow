<template>
  <div class="app-shell">
    <aside class="shell-sidebar">
      <div class="sidebar-brand">
        <img :src="brandLogo" alt="Sun-Tzu Secrets logo" class="brand-logo" />
      </div>

      <nav class="main-nav" aria-label="Navigazione principale">
        <RouterLink v-for="item in navItems" :key="item.to" :to="item.to" class="nav-link">
          <span>{{ item.label }}</span>
        </RouterLink>
        <button v-if="user" type="button" class="nav-link nav-button logout-link" @click="handleLogout">
          Logout
        </button>
      </nav>
    </aside>

    <div class="shell-content">
      <header class="shell-topbar">
        <div>
          <p class="eyebrow">Campagna attiva</p>
          <h2>Le Ombre sulla Baia dei Relitti</h2>
        </div>
        <span class="season-badge">{{ user?.nickname ?? 'Accesso ospite' }}</span>
      </header>

      <main class="shell-main">
        <RouterView />
      </main>

      <footer class="shell-footer">
        <div>
          <strong>{{ config?.appName }}</strong>
          <p class="muted-copy">Versione {{ config?.appVersion }}</p>
        </div>
        <div>
          <p class="muted-copy">{{ config?.organizationName }}</p>
          <p class="muted-copy">{{ config?.legalNote }}</p>
        </div>
        <div class="footer-links">
          <a :href="config?.legalUrl">Note legali</a>
          <a :href="config?.privacyUrl">Privacy</a>
          <a :href="config?.cookieUrl">Cookie</a>
          <a :href="`mailto:${config?.contactEmail}`">Contatti</a>
        </div>
      </footer>
    </div>

    <nav class="mobile-nav" aria-label="Navigazione mobile">
      <RouterLink v-for="item in navItems" :key="item.to" :to="item.to" class="mobile-nav-link">
        {{ item.short }}
      </RouterLink>
      <button v-if="user" type="button" class="mobile-nav-link mobile-nav-button" @click="handleLogout">
        Logout
      </button>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/stores/app';
import brandLogo from '@/assets/sun-tzu-secrets-logo.jpg';

const appStore = useAppStore();
const { config, user } = storeToRefs(appStore);
const router = useRouter();

const navItems = computed(() => {
  if (!user.value) {
    return [
      { label: 'Mappa e territori', short: 'Mappa', to: '/' },
      { label: 'Accedi', short: 'Login', to: '/auth/login' },
      { label: 'Registrati', short: 'Join', to: '/auth/register' },
    ];
  }

  return [
    { label: 'Mappa e territori', short: 'Mappa', to: '/' },
    { label: 'Inserisci risultato', short: 'Esito', to: '/submit-result' },
    { label: 'I miei risultati', short: 'Storico', to: '/results' },
    { label: 'Profilo', short: 'Profilo', to: '/profile' },
  ];
});

const handleLogout = async () => {
  appStore.logout();
  await router.push({ name: 'dashboard' });
};

onMounted(async () => {
  if (!appStore.hasBootstrapped) {
    await appStore.ensureBootstrapped();
  }
});
</script>

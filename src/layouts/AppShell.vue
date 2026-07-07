<template>
  <div class="app-shell">
    <aside class="shell-sidebar">
      <div class="sidebar-brand">
        <img :src="brandLogo" alt="Sun-Tzu Secrets logo" class="brand-logo" />
      </div>

      <nav class="main-nav" aria-label="Navigazione principale">
        <RouterLink
          v-for="item in navItems"
          :key="item.label"
          :to="item.to"
          custom
          v-slot="{ href, navigate }"
        >
          <a
            :href="href"
            class="nav-link"
            :class="{ 'is-active': isNavItemActive(item.matchNames) }"
            @click="navigate"
          >
            <span>{{ item.label }}</span>
          </a>
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
        <section class="content-frame">
          <RouterView />
        </section>
      </main>

      <footer class="shell-footer">
        <div class="footer-block">
          <strong>{{ config?.appName }}</strong>
          <p class="muted-copy footer-meta-line">Versione {{ config?.appVersion }} · Powered by LiminalCode</p>
        </div>
        <div class="footer-block footer-block-center">
          <div class="footer-links">
            <RouterLink :to="{ name: 'info-legal' }">Note legali</RouterLink>
            <RouterLink :to="{ name: 'info-privacy' }">Privacy</RouterLink>
            <RouterLink :to="{ name: 'info-cookie' }">Cookie</RouterLink>
            <RouterLink :to="{ name: 'info-contacts' }">Contatti</RouterLink>
          </div>
        </div>
      </footer>
    </div>

    <nav class="mobile-nav" aria-label="Navigazione mobile">
      <RouterLink
        v-for="item in navItems"
        :key="item.short"
        :to="item.to"
        custom
        v-slot="{ href, navigate }"
      >
        <a
          :href="href"
          class="mobile-nav-link"
          :class="{ 'is-active': isNavItemActive(item.matchNames) }"
          @click="navigate"
        >
          {{ item.short }}
        </a>
      </RouterLink>
      <button v-if="user" type="button" class="mobile-nav-link mobile-nav-button" @click="handleLogout">
        Logout
      </button>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/stores/app';
import brandLogo from '@/assets/sun-tzu-secrets-logo.jpg';

const appStore = useAppStore();
const { config, user } = storeToRefs(appStore);
const router = useRouter();
const route = useRoute();

const navItems = computed(() => {
  if (!user.value) {
    return [
      { label: 'Mappa e territori', short: 'Mappa', to: { name: 'dashboard' }, matchNames: ['dashboard', 'territory-detail'] },
      { label: 'Accedi', short: 'Login', to: { name: 'login' }, matchNames: ['login', 'forgot-password'] },
      { label: 'Registrati', short: 'Join', to: { name: 'register' }, matchNames: ['register'] },
      { label: 'Risorse', short: 'Ris', to: { name: 'resources' }, matchNames: ['resources'] },
    ];
  }

  return [
    { label: 'Mappa e territori', short: 'Mappa', to: { name: 'dashboard' }, matchNames: ['dashboard', 'territory-detail'] },
    { label: 'Inserisci risultato', short: 'Esito', to: { name: 'submit-result' }, matchNames: ['submit-result'] },
    { label: 'I miei risultati', short: 'Storico', to: { name: 'results' }, matchNames: ['results'] },
    { label: 'Profilo', short: 'Profilo', to: { name: 'profile' }, matchNames: ['profile'] },
    { label: 'Risorse', short: 'Ris', to: { name: 'resources' }, matchNames: ['resources'] },
    ...(user.value?.role === 'ADMIN'
      ? [{ label: 'Admin', short: 'Admin', to: { name: 'admin' }, matchNames: ['admin'] }]
      : []),
  ];
});

const isNavItemActive = (matchNames: string[]) =>
  route.name !== undefined && matchNames.includes(String(route.name));

const handleLogout = async () => {
  await appStore.logout();
  await router.push({ name: 'dashboard' });
};

onMounted(async () => {
  if (!appStore.hasBootstrapped) {
    await appStore.ensureBootstrapped();
  }
});
</script>

<template>
  <div class="app-shell">
    <aside class="shell-sidebar">
      <div class="brand-card">
        <div class="brand-mark">OW</div>
        <div>
          <p class="eyebrow">Campaign Command</p>
          <h1>{{ config?.appName ?? 'Old World Campaign Portal' }}</h1>
        </div>
      </div>

      <nav class="main-nav" aria-label="Navigazione principale">
        <RouterLink v-for="item in navItems" :key="item.to" :to="item.to" class="nav-link">
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>

      <section class="guild-card">
        <p class="eyebrow">Federazione / Associazione</p>
        <div class="guild-logo-placeholder">
          <span>Logo Federazione</span>
        </div>
        <p class="muted-copy">
          Area pronta per stemma ufficiale, patrocinio o marchio evento.
        </p>
      </section>
    </aside>

    <div class="shell-content">
      <header class="shell-topbar">
        <div>
          <p class="eyebrow">Stagione attiva</p>
          <h2>Conquista dei Tre Fronti</h2>
        </div>
        <div class="topbar-user">
          <div class="avatar-placeholder">{{ initials }}</div>
          <div>
            <strong>{{ user?.nickname ?? 'Comandante' }}</strong>
            <p class="muted-copy">Profilo operativo</p>
          </div>
        </div>
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
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { RouterLink, RouterView } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/stores/app';

const appStore = useAppStore();
const { config, user } = storeToRefs(appStore);

const navItems = [
  { label: 'Mappa e territori', short: 'Mappa', to: '/' },
  { label: 'Inserisci risultato', short: 'Esito', to: '/submit-result' },
  { label: 'I miei risultati', short: 'Storico', to: '/results' },
  { label: 'Profilo', short: 'Profilo', to: '/profile' },
];

const initials = computed(() => {
  const nickname = user.value?.nickname ?? 'OW';
  return nickname.slice(0, 2).toUpperCase();
});

onMounted(async () => {
  if (!config.value) {
    await appStore.bootstrap();
  }
});
</script>


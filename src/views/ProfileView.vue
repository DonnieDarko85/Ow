<template>
  <div class="page-stack">
    <SectionHeader
      eyebrow="Profilo utente"
      title="Account, avatar e preferenze"
      description="Area predisposta per aggiornamento email, avatar, armata preferita e contenuti branding."
    />

    <section class="content-grid two-columns">
      <div class="panel-card profile-card">
        <div class="profile-head">
          <div class="avatar-large">{{ initials }}</div>
          <div>
            <h3>{{ user?.nickname }}</h3>
            <p class="muted-copy">{{ user?.email }}</p>
          </div>
        </div>

        <div class="profile-grid">
          <label>
            <span>Email</span>
            <input type="email" :value="user?.email" />
          </label>
          <label>
            <span>Armata preferita</span>
            <select :value="user?.preferredArmyId">
              <option v-for="army in armies" :key="army.id" :value="army.id">
                {{ army.name }}
              </option>
            </select>
          </label>
          <label>
            <span>Fazione preferita</span>
            <select :value="user?.preferredFaction">
              <option v-for="faction in factions" :key="faction.id" :value="faction.code">
                {{ faction.name }}
              </option>
            </select>
          </label>
          <label>
            <span>Nuova password</span>
            <input type="password" placeholder="Aggiorna password" />
          </label>
          <label class="theme-toggle full-span">
            <span class="theme-toggle-copy">
              <strong>Grafica 3d</strong>
              <small class="muted-copy">Attiva la cornice decorata ispirata al layout Sun-Tzu Secrets.</small>
            </span>
            <input :checked="useThreeDTheme" type="checkbox" @change="handleThemeToggle" />
          </label>
        </div>

        <button class="primary-button">Salva modifiche</button>
      </div>

      <div class="panel-card">
        <p class="eyebrow">Branding pronto</p>
        <h3>Placeholder grafici e istituzionali</h3>
        <div class="logo-wall stacked">
          <div class="logo-placeholder">Logo profilo / team</div>
          <div class="logo-placeholder">Logo federazione / sponsor</div>
          <div class="logo-placeholder">Banner leghe / stagione</div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import SectionHeader from '@/components/SectionHeader.vue';
import { useAppStore } from '@/stores/app';

const appStore = useAppStore();
const { armies, factions, user, useThreeDTheme } = storeToRefs(appStore);

const initials = computed(() => (user.value?.nickname ?? 'OW').slice(0, 2).toUpperCase());

const handleThemeToggle = (event: Event) => {
  const target = event.target as HTMLInputElement;
  appStore.setThreeDTheme(target.checked);
};
</script>

<template>
  <div class="auth-layout">
    <section class="auth-panel">
      <img :src="brandLogo" alt="Sun-Tzu Secrets logo" class="auth-brand-logo" />
      <p class="eyebrow">Sun-Tzu Secrets</p>
      <h1>Accesso al Play Portal</h1>
      <p class="muted-copy">
        Autenticazione, recupero credenziali e accesso da mobile alla mappa di campagna.
      </p>
    </section>

    <section class="auth-form-card">
      <RouterView />
    </section>
  </div>
</template>

<script setup lang="ts">
import { RouterView } from 'vue-router';
import brandLogo from '@/assets/sun-tzu-secrets-logo.jpg';
</script>

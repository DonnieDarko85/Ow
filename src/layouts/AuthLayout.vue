<template>
  <div class="auth-layout">
    <section class="auth-panel">
      <p class="eyebrow">Portale campagna</p>
      <h1>Accesso ai teatri di guerra</h1>
      <p class="muted-copy">
        Autenticazione, recupero credenziali e accesso da mobile alla mappa di campagna.
      </p>
      <div class="logo-wall">
        <div class="logo-placeholder">Logo App</div>
        <div class="logo-placeholder">Logo Federazione</div>
      </div>
    </section>

    <section class="auth-form-card">
      <RouterView />
    </section>
  </div>
</template>

<script setup lang="ts">
import { RouterView } from 'vue-router';
</script>


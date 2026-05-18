<template>
  <form class="auth-form">
    <p class="eyebrow">Recupero accesso</p>
    <h2>Richiedi reset password</h2>
    <label>
      <span>Email</span>
      <input type="email" placeholder="player@example.com" />
    </label>
    <button class="primary-button" type="submit">Invia istruzioni</button>
    <RouterLink to="/auth/login" class="text-link">Torna al login</RouterLink>
  </form>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router';
</script>


<template>
  <form class="auth-form">
    <p class="eyebrow">Registrazione</p>
    <h2>Crea un nuovo comandante</h2>
    <label>
      <span>Nickname</span>
      <input type="text" />
    </label>
    <label>
      <span>Email</span>
      <input type="email" />
    </label>
    <label>
      <span>Password</span>
      <input type="password" />
    </label>
    <label>
      <span>Conferma password</span>
      <input type="password" />
    </label>
    <button class="primary-button" type="submit">Registrati</button>
    <RouterLink to="/auth/login" class="text-link">Hai gia un account? Accedi</RouterLink>
  </form>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router';
</script>


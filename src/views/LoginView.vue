<template>
  <form class="auth-form" @submit.prevent="handleSubmit">
    <p class="eyebrow">Accesso</p>
    <h2>Entra nel portale</h2>
    <label>
      <span>Email o nickname</span>
      <input 
        v-model="email" 
        type="text" 
        placeholder="player@example.com" 
        required
      />
    </label>
    <label>
      <span>Password</span>
      <input 
        v-model="password" 
        type="password" 
        placeholder="••••••••" 
        required
      />
    </label>
    <button class="primary-button" type="submit" :disabled="isLoading">
      {{ isLoading ? 'Accesso in corso...' : 'Accedi' }}
    </button>
    <div v-if="error" class="error-message">{{ error }}</div>
    <RouterLink to="/auth/forgot-password" class="text-link">Password dimenticata?</RouterLink>
    <RouterLink to="/auth/register" class="text-link">Non hai un account? Registrati</RouterLink>
  </form>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { RouterLink, useRouter, useRoute } from 'vue-router';
import { useAppStore } from '@/stores/app';

const router = useRouter();
const route = useRoute();
const appStore = useAppStore();

const email = ref('');
const password = ref('');
const error = ref('');
const isLoading = ref(false);

const handleSubmit = async () => {
  error.value = '';
  isLoading.value = true;
  
  try {
    await appStore.login(email.value, password.value);
    
    // Se c'è una pagina di redirect nel query, vai lì; altrimenti vai al dashboard
    const redirectTo = route.query.redirect as string || '/';
    router.push(redirectTo);
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Errore durante l\'accesso';
  } finally {
    isLoading.value = false;
  }
};
</script>


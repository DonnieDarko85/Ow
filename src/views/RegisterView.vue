<template>
  <form class="auth-form" @submit.prevent="handleSubmit">
    <p class="eyebrow">Registrazione</p>
    <h2>Crea un nuovo comandante</h2>
    <label>
      <span>Nickname</span>
      <input v-model.trim="form.nickname" type="text" minlength="4" autocomplete="username" required />
      <small v-if="errors.nickname" class="field-error">{{ errors.nickname }}</small>
    </label>
    <label>
      <span>Email</span>
      <input v-model.trim="form.email" type="email" autocomplete="email" required />
      <small v-if="errors.email" class="field-error">{{ errors.email }}</small>
    </label>
    <label>
      <span>Password</span>
      <input v-model="form.password" type="password" minlength="8" autocomplete="new-password" required />
      <small v-if="errors.password" class="field-error">{{ errors.password }}</small>
    </label>
    <label>
      <span>Conferma password</span>
      <input v-model="form.passwordConfirmation" type="password" minlength="8" autocomplete="new-password" required />
      <small v-if="errors.passwordConfirmation" class="field-error">{{ errors.passwordConfirmation }}</small>
    </label>
    <button class="primary-button" :disabled="appStore.isLoading" type="submit">
      {{ appStore.isLoading ? 'Registrazione in corso...' : 'Registrati' }}
    </button>
    <p v-if="serverMessage" class="success-message">{{ serverMessage }}</p>
    <p v-if="serverError" class="field-error">{{ serverError }}</p>
    <RouterLink to="/auth/login" class="text-link">Hai gia un account? Accedi</RouterLink>
  </form>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAppStore } from '@/stores/app';

const router = useRouter();
const appStore = useAppStore();

const form = reactive({
  nickname: '',
  email: '',
  password: '',
  passwordConfirmation: '',
});

const errors = reactive({
  nickname: '',
  email: '',
  password: '',
  passwordConfirmation: '',
});

const serverMessage = ref('');
const serverError = ref('');

const validEmail = (value: string) =>
  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

function resetErrors() {
  errors.nickname = '';
  errors.email = '';
  errors.password = '';
  errors.passwordConfirmation = '';
  serverError.value = '';
  serverMessage.value = '';
}

function validateForm() {
  resetErrors();
  let isValid = true;

  if (form.nickname.length < 4) {
    errors.nickname = 'Il nickname deve avere almeno 4 caratteri.';
    isValid = false;
  }

  if (!validEmail(form.email)) {
    errors.email = 'Inserisci una email valida.';
    isValid = false;
  }

  if (form.password.length < 8) {
    errors.password = 'La password deve avere almeno 8 caratteri.';
    isValid = false;
  }

  if (form.password !== form.passwordConfirmation) {
    errors.passwordConfirmation = 'Le due password non coincidono.';
    isValid = false;
  }

  return isValid;
}

async function handleSubmit() {
  if (!validateForm()) {
    return;
  }

  try {
    const result = await appStore.register(form);
    serverMessage.value = result.message;

    if (result.emailStatus === 'failed') {
      serverError.value = 'Registrazione completata, ma l invio email non e riuscito sul server.';
    }

    setTimeout(() => {
      router.push('/');
    }, 1200);
  } catch (error) {
    serverError.value = error instanceof Error ? error.message : 'Registrazione non riuscita.';
  }
}
</script>

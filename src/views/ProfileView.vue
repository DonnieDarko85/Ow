<template>
  <div class="page-stack">
    <SectionHeader
      eyebrow="Profilo utente"
      title="Account, avatar e preferenze"
    />

    <section class="panel-card profile-card">
      <div class="profile-head">
        <div class="avatar-large">{{ initials }}</div>
        <div>
          <h3>{{ user?.nickname }}</h3>
          <p class="muted-copy">Dati sensibili nascosti lato interfaccia.</p>
        </div>
      </div>

      <form class="profile-grid" @submit.prevent="handleSubmit">
        <label>
          <span>Armata preferita</span>
          <select v-model="form.preferredArmyId">
            <option value="">Nessuna</option>
            <option v-for="army in armies" :key="army.id" :value="army.id">
              {{ army.name }}
            </option>
          </select>
        </label>
        <div>
          <span>Fazione preferita</span>
          <div class="selected-faction-chip profile-faction-chip" :style="selectedFactionStyle">
            {{ selectedFactionName }}
          </div>
        </div>
        <label>
          <span>Nuova password</span>
          <input v-model="form.password" type="password" placeholder="Aggiorna password" />
        </label>
        <label>
          <span>Conferma password</span>
          <input v-model="form.passwordConfirmation" type="password" placeholder="Conferma nuova password" />
        </label>
        <p v-if="saveMessage" class="success-message full-span">{{ saveMessage }}</p>
        <p v-if="saveError" class="field-error full-span">{{ saveError }}</p>
        <button class="primary-button full-span" :disabled="appStore.isLoading" type="submit">
          {{ appStore.isLoading ? 'Salvataggio in corso...' : 'Salva modifiche' }}
        </button>
      </form>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import SectionHeader from '@/components/SectionHeader.vue';
import { useAppStore } from '@/stores/app';

const appStore = useAppStore();
const { armies, factions, user } = storeToRefs(appStore);
const form = reactive({
  preferredArmyId: '',
  password: '',
  passwordConfirmation: '',
});
const saveMessage = ref('');
const saveError = ref('');

const initials = computed(() => (user.value?.nickname ?? 'OW').slice(0, 2).toUpperCase());
const selectedArmy = computed(() => armies.value.find((army) => army.id === form.preferredArmyId));
const selectedFaction = computed(() => factions.value.find((faction) => faction.code === selectedArmy.value?.defaultFaction));
const selectedFactionName = computed(() => selectedFaction.value?.name ?? 'Nessuna');
const selectedFactionStyle = computed(() => ({
  color: selectedFaction.value?.color ?? 'var(--text-muted)',
}));

watch(
  user,
  (nextUser) => {
    form.preferredArmyId = nextUser?.preferredArmyId ?? '';
    form.password = '';
    form.passwordConfirmation = '';
  },
  { immediate: true },
);

const handleSubmit = async () => {
  saveMessage.value = '';
  saveError.value = '';

  if (form.password && form.password.length < 8) {
    saveError.value = 'La password deve avere almeno 8 caratteri.';
    return;
  }

  if (form.password !== form.passwordConfirmation) {
    saveError.value = 'Le due password non coincidono.';
    return;
  }

  try {
    const result = await appStore.updateMyProfile({
      preferredArmyId: form.preferredArmyId || null,
      preferredFaction: selectedArmy.value?.defaultFaction ?? null,
      password: form.password || undefined,
    });

    form.password = '';
    form.passwordConfirmation = '';
    saveMessage.value = result.message;
  } catch (error) {
    saveError.value = error instanceof Error ? error.message : 'Salvataggio non riuscito.';
  }
};
</script>

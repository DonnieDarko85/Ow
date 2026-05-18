<template>
  <div class="page-stack">
    <SectionHeader
      eyebrow="Nuovo inserimento"
      title="Registra un risultato partita"
      description="Form mobile-first con campi grandi, validazione client e payload pronto per backend PHP."
    />

    <section class="content-grid two-columns">
      <form class="panel-card form-grid" @submit.prevent="handleSubmit">
        <label>
          <span>Territorio</span>
          <select v-model="form.territoryId" required>
            <option value="" disabled>Seleziona un territorio</option>
            <option v-for="territory in territories" :key="territory.id" :value="territory.id">
              {{ territory.name }}
            </option>
          </select>
        </label>

        <label>
          <span>Armata</span>
          <select v-model="form.ownArmyId" required>
            <option value="" disabled>Seleziona una armata</option>
            <option v-for="army in sortedArmies" :key="army.id" :value="army.id">
              {{ army.name }}
            </option>
          </select>
        </label>

        <label>
          <span>Fazione</span>
          <select v-model="form.ownFaction" required>
            <option value="FORCES_OF_FANTASY">Forces of Fantasy</option>
            <option value="RAVAGING_HORDES">Ravaging Hordes</option>
            <option value="UNDEAD">Undead</option>
          </select>
        </label>

        <label>
          <span>Nickname avversario</span>
          <input
            v-model.trim="opponentSearch"
            type="text"
            placeholder="Cerca nickname avversario"
            autocomplete="off"
          />
          <select v-model="form.opponentNickname" required>
            <option value="" disabled>Seleziona un avversario</option>
            <option
              v-for="opponent in filteredOpponents"
              :key="opponent.id"
              :value="opponent.nickname"
            >
              {{ opponent.nickname }}
            </option>
          </select>
        </label>

        <label>
          <span>Punti tuoi</span>
          <input v-model.number="form.ownScore" type="number" min="0" required />
        </label>

        <label>
          <span>Punti avversario</span>
          <input v-model.number="form.opponentScore" type="number" min="0" required />
        </label>

        <label>
          <span>Data partita</span>
          <input v-model="form.playedAt" type="date" required />
        </label>

        <label class="full-span">
          <span>Note</span>
          <textarea v-model="form.note" rows="4" placeholder="Dettagli extra, missione, scenario, anomalia da segnalare..." />
        </label>

        <button class="primary-button full-span" :disabled="isSubmitting" type="submit">
          {{ isSubmitting ? 'Invio in corso...' : 'Invia risultato' }}
        </button>
        <p v-if="loadError" class="field-error full-span">{{ loadError }}</p>
      </form>

      <aside class="panel-card callout-card">
        <p class="eyebrow">Regole di conferma</p>
        <h3>Come il sistema conferma il match</h3>
        <ul class="text-list">
          <li>Entrambi i giocatori devono indicare lo stesso territorio.</li>
          <li>I punteggi devono combaciare in modo speculare.</li>
          <li>La data deve rientrare nella tolleranza prevista dal backend.</li>
          <li>I match incoerenti restano fuori dalle statistiche finche non vengono risolti.</li>
        </ul>
        <p v-if="submitMessage" class="success-message">{{ submitMessage }}</p>
      </aside>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { storeToRefs } from 'pinia';
import SectionHeader from '@/components/SectionHeader.vue';
import { api } from '@/services/api';
import { useAppStore } from '@/stores/app';
import type { SubmitResultPayload, UserLookup } from '@/types';

const appStore = useAppStore();
const { armies, territories, user } = storeToRefs(appStore);
const isSubmitting = ref(false);
const submitMessage = ref('');
const loadError = ref('');
const opponents = ref<UserLookup[]>([]);
const opponentSearch = ref('');

const form = reactive<SubmitResultPayload>({
  territoryId: '',
  ownArmyId: user.value?.preferredArmyId ?? '',
  ownFaction: user.value?.preferredFaction ?? 'FORCES_OF_FANTASY',
  opponentNickname: '',
  ownScore: 0,
  opponentScore: 0,
  playedAt: new Date().toISOString().slice(0, 10),
  note: '',
});

const sortedArmies = computed(() =>
  [...armies.value].sort((a, b) => a.name.localeCompare(b.name, 'it', { sensitivity: 'base' })),
);

const availableOpponents = computed(() =>
  opponents.value
    .filter((opponent) => opponent.nickname !== user.value?.nickname)
    .sort((a, b) => a.nickname.localeCompare(b.nickname, 'it', { sensitivity: 'base' })),
);

const filteredOpponents = computed(() => {
  const query = opponentSearch.value.trim().toLowerCase();

  if (!query) {
    return availableOpponents.value;
  }

  return availableOpponents.value.filter((opponent) =>
    opponent.nickname.toLowerCase().includes(query),
  );
});

const hasLookupData = computed(() =>
  sortedArmies.value.length > 0 && territories.value.length > 0 && availableOpponents.value.length > 0,
);

onMounted(async () => {
  if (!appStore.hasBootstrapped) {
    await appStore.ensureBootstrapped();
  }

  try {
    opponents.value = await api.getUsers();
  } catch (error) {
    loadError.value = 'Non sono riuscito a caricare la lista avversari dal server.';
  }

  if (!hasLookupData.value) {
    loadError.value = 'Non sono riuscito a caricare armate, territori o lista avversari dal server. Verifica gli endpoint /armies, /territories e /users.';
  } else {
    if (!loadError.value) {
      loadError.value = '';
    }

    if (!form.ownArmyId && sortedArmies.value[0]) {
      form.ownArmyId = sortedArmies.value[0].id;
    }
  }
});

async function handleSubmit() {
  isSubmitting.value = true;
  submitMessage.value = '';

  try {
    const result = await api.submitResult(form);
    submitMessage.value = result.message;
  } finally {
    isSubmitting.value = false;
  }
}
</script>

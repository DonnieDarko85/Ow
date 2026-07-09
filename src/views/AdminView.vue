<template>
  <div class="page-stack">
    <SectionHeader
      eyebrow="Amministrazione"
      title="Strumenti admin"
      description="Gestione utenti, revisione partite e prima base per il mapping esagonale della campagna."
    />

    <section class="panel-card admin-tabs">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="admin-tab-button"
        :class="{ 'is-active': activeTab === tab.id }"
        @click="activeTab = tab.id"
      >
        {{ tab.label }}
      </button>
    </section>

    <section v-if="activeTab === 'users'" class="panel-card admin-panel">
      <div class="admin-panel-head">
        <div>
          <p class="eyebrow">Utenti</p>
          <h3>Dettagli utente e ruoli</h3>
        </div>
        <div class="admin-panel-tools">
          <label class="admin-search">
            <span class="sr-only">Cerca utenti per nickname</span>
            <input
              v-model.trim="userSearch"
              type="search"
              placeholder="Cerca utente per nickname"
            />
          </label>
          <p v-if="adminMessage" class="success-message">{{ adminMessage }}</p>
        </div>
      </div>

      <div class="admin-list">
        <article v-for="entry in filteredAdminUsers" :key="entry.id" class="admin-card">
          <div class="admin-card-head">
            <strong>{{ entry.nickname }}</strong>
            <span class="muted-copy">{{ entry.role === 'ADMIN' ? 'Amministratore' : 'Utente' }}</span>
          </div>

          <div class="admin-user-grid">
            <label>
              <span>Nickname</span>
              <input v-model.trim="entry.nickname" type="text" />
            </label>

            <label>
              <span>Ruolo</span>
              <select v-model="entry.role">
                <option value="USER">Utente</option>
                <option value="ADMIN">Admin</option>
              </select>
            </label>

            <label>
              <span>Armata preferita</span>
              <select v-model="entry.preferredArmyId">
                <option value="">Nessuna</option>
                <option v-for="army in armies" :key="army.id" :value="army.id">
                  {{ army.name }}
                </option>
              </select>
            </label>

            <label>
              <span>Fazione preferita</span>
              <select v-model="entry.preferredFaction">
                <option value="">Nessuna</option>
                <option v-for="faction in factions" :key="faction.id" :value="faction.code">
                  {{ faction.name }}
                </option>
              </select>
            </label>

            <label class="admin-flag">
              <span>Utente attivo</span>
              <input v-model="entry.isActive" type="checkbox" />
            </label>
          </div>

          <div class="admin-card-actions">
            <button class="primary-button admin-inline-button" type="button" @click="saveUser(entry)">
              Salva utente
            </button>
          </div>
        </article>
      </div>
    </section>

    <section v-else-if="activeTab === 'matches'" class="panel-card admin-panel">
      <div class="admin-panel-head">
        <div>
          <p class="eyebrow">Partite</p>
          <h3>Partite e risultati</h3>
        </div>
        <div class="admin-panel-tools">
          <label class="admin-filter">
            <span class="sr-only">Filtra partite per stato</span>
            <select v-model="matchStatusFilter">
              <option v-for="option in matchStatusOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </label>
          <label class="admin-search">
            <span class="sr-only">Cerca partite</span>
            <input
              v-model.trim="matchSearch"
              type="search"
              placeholder="Cerca per uno dei due partecipanti"
            />
          </label>
          <p v-if="adminMessage" class="success-message">{{ adminMessage }}</p>
        </div>
      </div>

      <div class="admin-list">
        <article v-for="match in filteredAdminMatches" :key="match.id" class="admin-card">
          <div class="admin-card-head">
            <div class="admin-card-title">
              <strong>{{ match.playerAName }} vs {{ match.playerBName }}</strong>
              <span class="muted-copy">{{ match.territoryName }}</span>
            </div>
            <span class="admin-status-badge" :class="matchStatusClass(match.status)">
              {{ matchStatusLabel(match.status) }}
            </span>
          </div>

          <div class="admin-match-grid">
            <label>
              <span>Territorio</span>
              <select v-model="match.territoryId">
                <option v-for="territory in territories" :key="territory.id" :value="territory.id">
                  {{ territory.name }}
                </option>
              </select>
            </label>

            <label>
              <span>Stato</span>
              <select v-model="match.status">
                <option v-for="option in matchStatusOptions.slice(1)" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </label>

            <label>
              <span>Armata {{ match.playerAName }}</span>
              <select v-model="match.armyAId">
                <option :value="null">Nessuna</option>
                <option v-for="army in armies" :key="army.id" :value="army.id">
                  {{ army.name }}
                </option>
              </select>
            </label>

            <label>
              <span>Armata {{ match.playerBName }}</span>
              <select v-model="match.armyBId">
                <option :value="null">Nessuna</option>
                <option v-for="army in armies" :key="army.id" :value="army.id">
                  {{ army.name }}
                </option>
              </select>
            </label>

            <label>
              <span>{{ match.playerAName }} PV</span>
              <input v-model.number="match.victoryPointsA" type="number" min="0" />
            </label>

            <label>
              <span>{{ match.playerBName }} PV</span>
              <input v-model.number="match.victoryPointsB" type="number" min="0" />
            </label>

            <label>
              <span>Data partita</span>
              <input v-model="match.playedAt" type="date" />
            </label>

            <div class="admin-match-readonly">
              <span>Punti partita calcolati</span>
              <strong>{{ match.matchPointsA ?? 0 }} - {{ match.matchPointsB ?? 0 }}</strong>
            </div>
          </div>

          <div class="admin-card-actions">
            <button class="primary-button admin-inline-button" type="button" @click="saveMatch(match)">
              Salva match
            </button>
          </div>
        </article>
      </div>
    </section>

    <section v-else class="panel-card admin-panel">
      <div class="admin-panel-head">
        <div>
          <p class="eyebrow">Mappa</p>
          <h3>Editor esagoni territorio</h3>
        </div>
      </div>
      <p class="muted-copy">
        Editor admin per assegnare gli esagoni ai territori, con caricamento e salvataggio esplicito della mappa condivisa lato server.
      </p>
      <AdminHexMapEditor :territories="territories" />
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import AdminHexMapEditor from '@/components/AdminHexMapEditor.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import { api } from '@/services/api';
import { useAppStore } from '@/stores/app';
import type { AdminMatchRecord, AdminUserRecord, MatchStatus } from '@/types';
import { calculateMatchPoints } from '@/utils/matchScoring';

const appStore = useAppStore();
const { armies, factions, territories } = storeToRefs(appStore);

const tabs = [
  { id: 'users', label: 'Utenti' },
  { id: 'matches', label: 'Partite e risultati' },
  { id: 'map', label: 'Mappa' },
] as const;

const activeTab = ref<(typeof tabs)[number]['id']>('users');
const adminUsers = ref<AdminUserRecord[]>([]);
const adminMatches = ref<AdminMatchRecord[]>([]);
const adminMessage = ref('');
const userSearch = ref('');
const matchSearch = ref('');
const matchStatusFilter = ref<'ALL' | MatchStatus>('ALL');

const matchStatusOptions: Array<{ value: 'ALL' | MatchStatus; label: string }> = [
  { value: 'ALL', label: 'Tutti gli stati' },
  { value: 'PENDING', label: 'Da confermare' },
  { value: 'CONFIRMED', label: 'Confermati' },
  { value: 'CONFLICT', label: 'In conflitto' },
  { value: 'CANCELLED', label: 'Cancellati' },
];

const matchStatusLabels: Record<MatchStatus, string> = {
  PENDING: 'Da confermare',
  CONFIRMED: 'Confermato',
  CONFLICT: 'In conflitto',
  CANCELLED: 'Cancellato',
};

const filteredAdminUsers = computed(() => {
  const query = userSearch.value.trim().toLowerCase();
  if (!query) {
    return adminUsers.value;
  }

  return adminUsers.value.filter((entry) => {
    return entry.nickname.toLowerCase().includes(query);
  });
});

const filteredAdminMatches = computed(() => {
  const query = matchSearch.value.trim().toLowerCase();
  return adminMatches.value.filter((match) => {
    const matchesQuery =
      query === '' ||
      match.playerAName.toLowerCase().includes(query) ||
      match.playerBName.toLowerCase().includes(query);
    const matchesStatus =
      matchStatusFilter.value === 'ALL' || match.status === matchStatusFilter.value;
    return matchesQuery && matchesStatus;
  });
});

const matchStatusLabel = (status: MatchStatus) => matchStatusLabels[status];

const matchStatusClass = (status: MatchStatus) => `is-${status.toLowerCase()}`;

onMounted(async () => {
  if (!appStore.hasBootstrapped) {
    await appStore.ensureBootstrapped();
  }

  await Promise.allSettled([loadUsers(), loadMatches()]);
});

async function loadUsers() {
  adminUsers.value = await api.getAdminUsers();
}

async function loadMatches() {
  const matches = await api.getAdminMatches();
  adminMatches.value = matches.map((match) => {
    const recalculated = calculateMatchPoints(match.victoryPointsA, match.victoryPointsB);
    return {
      ...match,
      matchPointsA: recalculated.playerA,
      matchPointsB: recalculated.playerB,
    };
  });
}

async function saveUser(entry: AdminUserRecord) {
  const result = await api.updateAdminUser(entry.id, entry);
  adminMessage.value = result.message;
  const index = adminUsers.value.findIndex((user) => user.id === entry.id);
  if (index >= 0) {
    adminUsers.value[index] = result.user;
  }
}

async function saveMatch(entry: AdminMatchRecord) {
  const result = await api.updateAdminMatch(entry.id, {
    territoryId: entry.territoryId,
    status: entry.status,
    armyAId: entry.armyAId,
    armyBId: entry.armyBId,
    playedAt: entry.playedAt,
    victoryPointsA: entry.victoryPointsA,
    victoryPointsB: entry.victoryPointsB,
  });
  adminMessage.value = result.message;
  const recalculated = calculateMatchPoints(result.match.victoryPointsA, result.match.victoryPointsB);
  const index = adminMatches.value.findIndex((match) => match.id === entry.id);
  if (index >= 0) {
    adminMatches.value[index] = {
      ...result.match,
      matchPointsA: recalculated.playerA,
      matchPointsB: recalculated.playerB,
    };
  }
}
</script>

<template>
  <div class="admin-map-editor">
    <section class="admin-map-panel">
      <div class="admin-map-panel-head">
        <div>
          <p class="eyebrow">Nuovo territorio</p>
          <h4>Crea territorio</h4>
        </div>
      </div>

      <div class="admin-map-create-grid">
        <label>
          <span>Nome territorio</span>
          <input v-model.trim="territoryForm.name" type="text" placeholder="Es. Colline di Talshanz" />
        </label>

        <label>
          <span>Map Path ID</span>
          <input v-model.trim="territoryForm.mapPathId" type="text" placeholder="Opzionale" />
        </label>

        <label class="full-span">
          <span>Descrizione breve</span>
          <input v-model.trim="territoryForm.description" type="text" placeholder="Riassunto rapido del territorio" />
        </label>

        <label class="full-span">
          <span>Lore</span>
          <textarea v-model.trim="territoryForm.lore" placeholder="Testo descrittivo opzionale"></textarea>
        </label>
      </div>

      <div class="admin-map-footer">
        <button class="primary-button admin-inline-button" type="button" :disabled="isCreatingTerritory" @click="createTerritory">
          Crea territorio
        </button>
        <span v-if="creationMessage" class="muted-copy">{{ creationMessage }}</span>
      </div>
    </section>

    <div class="admin-map-toolbar">
      <label>
        <span>Territorio da assegnare</span>
        <select v-model="selectedTerritoryId">
          <option value="" disabled>Seleziona un territorio</option>
          <option v-for="territory in sortedTerritories" :key="territory.id" :value="territory.id">
            {{ territory.name }}
          </option>
        </select>
      </label>

      <div class="admin-map-actions">
        <button class="primary-button admin-inline-button" type="button" :disabled="!canAssign" @click="assignSelection">
          Assegna selezione
        </button>
        <button class="secondary-button admin-inline-button" type="button" :disabled="selectedHexIds.length === 0" @click="clearAssignment">
          Rimuovi assegnazione
        </button>
        <button class="secondary-button admin-inline-button" type="button" :disabled="selectedHexIds.length === 0" @click="clearSelection">
          Pulisci selezione
        </button>
      </div>
    </div>

    <div class="admin-map-meta">
      <p class="muted-copy">
        Clicca uno o più esagoni per selezionarli. Il mouse over evidenzia la singola cella.
      </p>
      <p class="muted-copy">
        Selezionati: <strong>{{ selectedHexIds.length }}</strong>
        <span v-if="hoveredHex">
          · Hover: <strong>{{ hoveredHex.label }}</strong>
          <span v-if="hoveredTerritory">({{ hoveredTerritory.name }})</span>
        </span>
      </p>
      <div class="admin-map-persist-actions">
        <button class="primary-button admin-inline-button" type="button" :disabled="isSavingMap" @click="saveSharedMap">
          Salva mappa condivisa
        </button>
        <button class="secondary-button admin-inline-button" type="button" :disabled="isLoadingMap" @click="loadSharedMap">
          Ricarica dal server
        </button>
        <span v-if="message" class="muted-copy">{{ message }}</span>
      </div>
    </div>

    <div class="admin-map-stage">
      <img :src="campaignMap" alt="Mappa campagna con overlay esagonale admin" class="admin-map-image" />

      <svg
        class="admin-map-overlay"
        :viewBox="`0 0 ${HEX_MAP_WIDTH} ${HEX_MAP_HEIGHT}`"
        preserveAspectRatio="xMidYMid meet"
      >
        <g>
          <polygon
            v-for="hex in hexes"
            :key="hex.id"
            :points="hex.points"
            class="admin-map-hex"
            :class="{
              'is-hovered': hoveredHex?.id === hex.id,
              'is-selected': selectedHexIds.includes(hex.id),
              'is-assigned': Boolean(assignments[hex.id]),
            }"
            :style="hexStyle(hex.id)"
            @mouseenter="hoveredHexId = hex.id"
            @mouseleave="hoveredHexId = ''"
            @click="toggleHexSelection(hex.id)"
          />
        </g>
      </svg>
    </div>

    <div class="admin-map-bottom">
      <section class="admin-map-panel">
        <div class="admin-map-panel-head">
          <div>
            <p class="eyebrow">Copertura</p>
            <h4>Esagoni assegnati per territorio</h4>
          </div>
          <button class="secondary-button admin-inline-button" type="button" @click="resetAssignments">
            Reset totale
          </button>
        </div>

        <div class="admin-map-assignment-list">
          <article
            v-for="entry in assignmentSummary"
            :key="entry.territory.id"
            class="admin-map-assignment-row"
          >
            <div>
              <strong>{{ entry.territory.name }}</strong>
              <p class="muted-copy">{{ entry.count }} esagoni assegnati</p>
            </div>
            <button class="secondary-button admin-inline-button" type="button" @click="selectTerritoryHexes(entry.territory.id)">
              Seleziona
            </button>
          </article>

          <p v-if="assignmentSummary.length === 0" class="muted-copy">
            Nessun esagono assegnato per ora.
          </p>
        </div>
      </section>

      <section class="admin-map-panel">
        <div class="admin-map-panel-head">
          <div>
            <p class="eyebrow">Persistenza</p>
            <h4>Export / import JSON</h4>
          </div>
          <button class="secondary-button admin-inline-button" type="button" @click="exportAssignments">
            Genera export
          </button>
        </div>

        <textarea
          v-model="jsonBuffer"
          class="admin-map-json"
          placeholder="Qui puoi esportare o incollare la configurazione JSON degli esagoni."
        />

        <div class="admin-map-footer">
          <button class="primary-button admin-inline-button" type="button" @click="importAssignments">
            Importa JSON
          </button>
          <span v-if="message" class="muted-copy">{{ message }}</span>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import campaignMap from '@/assets/maps/campaign-map.jpeg';
import { api } from '@/services/api';
import { useAppStore } from '@/stores/app';
import type { Territory } from '@/types';
import type { HexCell } from '@/utils/hexMap';
import {
  HEX_GRID_COLS,
  HEX_GRID_ROWS,
  HEX_MAP_HEIGHT,
  HEX_MAP_STORAGE_KEY,
  HEX_MAP_WIDTH,
  buildHexGrid,
  loadStoredHexAssignments,
} from '@/utils/hexMap';

interface Props {
  territories: Territory[];
}

const props = defineProps<Props>();
const appStore = useAppStore();

const selectedTerritoryId = ref('');
const hoveredHexId = ref('');
const selectedHexIds = ref<string[]>([]);
const assignments = ref<Record<string, string>>({});
const jsonBuffer = ref('');
const message = ref('');
const creationMessage = ref('');
const isCreatingTerritory = ref(false);
const isSavingMap = ref(false);
const isLoadingMap = ref(false);
const territoryForm = ref({
  name: '',
  description: '',
  lore: '',
  mapPathId: '',
});

const sortedTerritories = computed(() =>
  [...props.territories].sort((a, b) => a.name.localeCompare(b.name, 'it', { sensitivity: 'base' })),
);

const hexes = computed<HexCell[]>(() => buildHexGrid());

const hoveredHex = computed(() => hexes.value.find((hex) => hex.id === hoveredHexId.value) ?? null);

const hoveredTerritory = computed(() => {
  if (!hoveredHex.value) {
    return null;
  }

  return territoryById(assignments.value[hoveredHex.value.id] ?? '');
});

const canAssign = computed(() => selectedTerritoryId.value !== '' && selectedHexIds.value.length > 0);

const assignmentSummary = computed(() => {
  return sortedTerritories.value
    .map((territory) => ({
      territory,
      count: Object.values(assignments.value).filter((territoryId) => territoryId === territory.id).length,
    }))
    .filter((entry) => entry.count > 0);
});

watch(
  assignments,
  (value) => {
    if (typeof window === 'undefined') {
      return;
    }

    window.localStorage.setItem(
      HEX_MAP_STORAGE_KEY,
      JSON.stringify({
        version: 1,
        rows: HEX_GRID_ROWS,
        cols: HEX_GRID_COLS,
        assignments: value,
      }),
    );
  },
  { deep: true },
);

onMounted(() => {
  selectedTerritoryId.value = sortedTerritories.value[0]?.id ?? '';
  void loadSharedMap();
});

function toggleHexSelection(hexId: string) {
  const assignedTerritoryId = assignments.value[hexId] ?? '';

  if (assignedTerritoryId !== '') {
    selectedTerritoryId.value = assignedTerritoryId;
    selectedHexIds.value = Object.entries(assignments.value)
      .filter(([, territoryId]) => territoryId === assignedTerritoryId)
      .map(([assignedHexId]) => assignedHexId);
    message.value = 'Territorio gia assegnato selezionato.';
    return;
  }

  if (selectedHexIds.value.includes(hexId)) {
    selectedHexIds.value = selectedHexIds.value.filter((id) => id !== hexId);
    return;
  }

  selectedHexIds.value = [...selectedHexIds.value, hexId];
}

function assignSelection() {
  if (!canAssign.value) {
    return;
  }

  const assignedCount = selectedHexIds.value.length;
  const nextAssignments = { ...assignments.value };

  for (const hexId of selectedHexIds.value) {
    nextAssignments[hexId] = selectedTerritoryId.value;
  }

  assignments.value = nextAssignments;
  selectedHexIds.value = [];
  hoveredHexId.value = '';
  message.value = `Assegnati ${assignedCount} esagoni. Salva la mappa condivisa per pubblicarla.`;
}

function clearAssignment() {
  const clearedCount = selectedHexIds.value.length;
  const nextAssignments = { ...assignments.value };

  for (const hexId of selectedHexIds.value) {
    delete nextAssignments[hexId];
  }

  assignments.value = nextAssignments;
  selectedHexIds.value = [];
  hoveredHexId.value = '';
  message.value = `Assegnazione rimossa da ${clearedCount} esagoni. Salva la mappa condivisa per pubblicarla.`;
}

function clearSelection() {
  selectedHexIds.value = [];
}

function resetAssignments() {
  assignments.value = {};
  selectedHexIds.value = [];
  hoveredHexId.value = '';
  message.value = 'Mappatura azzerata in bozza. Salva la mappa condivisa per pubblicarla.';
}

function selectTerritoryHexes(territoryId: string) {
  selectedHexIds.value = Object.entries(assignments.value)
    .filter(([, assignedTerritoryId]) => assignedTerritoryId === territoryId)
    .map(([hexId]) => hexId);
  selectedTerritoryId.value = territoryId;
}

function exportAssignments() {
  jsonBuffer.value = JSON.stringify(
    {
      version: 1,
      rows: HEX_GRID_ROWS,
      cols: HEX_GRID_COLS,
      assignments: assignments.value,
    },
    null,
    2,
  );
  message.value = 'Export JSON generato.';
}

function importAssignments() {
  try {
    const parsed = JSON.parse(jsonBuffer.value) as { assignments?: Record<string, string> };
    assignments.value = parsed.assignments ?? {};
    selectedHexIds.value = [];
    hoveredHexId.value = '';
    message.value = 'Import completato. Salva la mappa condivisa per pubblicarla.';
  } catch {
    message.value = 'JSON non valido.';
  }
}

async function loadSharedMap() {
  isLoadingMap.value = true;

  try {
    assignments.value = await api.getTerritoryMap();
    selectedHexIds.value = [];
    hoveredHexId.value = '';
    message.value = 'Mappa condivisa caricata dal server.';
  } catch (error) {
    assignments.value = loadStoredHexAssignments();
    message.value = error instanceof Error
      ? `${error.message} Uso la bozza locale del browser.`
      : 'Caricamento server non riuscito. Uso la bozza locale del browser.';
  } finally {
    isLoadingMap.value = false;
  }
}

async function saveSharedMap() {
  isSavingMap.value = true;

  try {
    const result = await api.saveAdminTerritoryMap(assignments.value);
    assignments.value = result.assignments;
    message.value = result.message;
  } catch (error) {
    message.value = error instanceof Error ? error.message : 'Salvataggio mappa non riuscito.';
  } finally {
    isSavingMap.value = false;
  }
}

function hexStyle(hexId: string) {
  return {
    '--hex-fill': assignments.value[hexId] ? 'rgba(255, 255, 255, 0.14)' : 'rgba(255, 255, 255, 0.04)',
    '--hex-stroke': assignments.value[hexId] ? 'rgba(255, 255, 255, 0.72)' : 'rgba(255, 255, 255, 0.22)',
  };
}

function territoryById(territoryId: string) {
  return props.territories.find((territory) => territory.id === territoryId) ?? null;
}

async function createTerritory() {
  creationMessage.value = '';

  if (territoryForm.value.name.trim().length < 3) {
    creationMessage.value = 'Il nome territorio deve avere almeno 3 caratteri.';
    return;
  }

  isCreatingTerritory.value = true;

  try {
    const result = await api.createAdminTerritory({
      name: territoryForm.value.name,
      description: territoryForm.value.description,
      lore: territoryForm.value.lore,
      mapPathId: territoryForm.value.mapPathId,
    });

    appStore.territories = [...appStore.territories, result.territory].sort((a, b) =>
      a.name.localeCompare(b.name, 'it', { sensitivity: 'base' }),
    );

    selectedTerritoryId.value = result.territory.id;
    territoryForm.value = {
      name: '',
      description: '',
      lore: '',
      mapPathId: '',
    };
    creationMessage.value = result.message;
  } catch (error) {
    creationMessage.value = error instanceof Error ? error.message : 'Creazione territorio non riuscita.';
  } finally {
    isCreatingTerritory.value = false;
  }
}
</script>

<style scoped>
.admin-map-editor {
  display: grid;
  gap: 1rem;
}

.admin-map-toolbar,
.admin-map-meta,
.admin-map-bottom {
  display: grid;
  gap: 1rem;
}

.admin-map-persist-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.admin-map-toolbar {
  grid-template-columns: minmax(280px, 420px) 1fr;
  align-items: end;
}

.admin-map-actions {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
  align-items: stretch;
}

.admin-map-footer {
  display: grid;
  grid-template-columns: minmax(220px, 280px) 1fr;
  align-items: center;
  gap: 0.75rem;
}

.admin-map-create-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.admin-map-create-grid .full-span {
  grid-column: 1 / -1;
}

.admin-map-stage {
  position: relative;
  overflow: hidden;
  border-radius: 22px;
  border: 1px solid rgba(179, 24, 31, 0.24);
  background: #120f0b;
}

.admin-map-image,
.admin-map-overlay {
  display: block;
  width: 100%;
  height: auto;
}

.admin-map-overlay {
  position: absolute;
  inset: 0;
}

.admin-map-hex {
  fill: var(--hex-fill);
  stroke: var(--hex-stroke);
  stroke-width: 1.4;
  cursor: pointer;
  transition: fill 120ms ease, stroke 120ms ease, opacity 120ms ease;
}

.admin-map-hex:hover,
.admin-map-hex.is-hovered {
  fill: rgba(255, 255, 255, 0.16);
  stroke: rgba(255, 255, 255, 0.88);
}

.admin-map-hex.is-selected {
  fill: rgba(255, 255, 255, 0.22);
  stroke: rgba(255, 255, 255, 0.96);
  stroke-width: 2.4;
}

.admin-map-hex.is-assigned {
  opacity: 0.92;
}

.admin-map-bottom {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.admin-map-panel {
  display: grid;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.02);
}

.admin-map-panel-head {
  display: flex;
  align-items: start;
  justify-content: space-between;
  gap: 1rem;
}

.admin-map-panel-head h4 {
  margin: 0;
}

.admin-map-assignment-list {
  display: grid;
  gap: 0.75rem;
}

.admin-map-assignment-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.85rem 1rem;
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-left-width: 4px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.02);
}

.admin-map-assignment-row p {
  margin: 0.2rem 0 0;
}

.admin-map-json {
  min-height: 220px;
  font-family: inherit;
}

@media (max-width: 960px) {
  .admin-map-toolbar,
  .admin-map-bottom,
  .admin-map-footer {
    grid-template-columns: 1fr;
  }

  .admin-map-create-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 720px) {
  .admin-map-actions,
  .admin-map-panel-head,
  .admin-map-assignment-row {
    display: grid;
  }

  .admin-map-actions {
    grid-template-columns: 1fr;
  }

  .admin-map-assignment-row {
    justify-content: stretch;
  }
}
</style>

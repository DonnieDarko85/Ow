<template>
  <section class="map-card">
    <div class="map-stage interactive-map-stage">
      <div class="map-territory-banner">
        <strong>{{ activeTerritory?.name ?? 'Territori campagna' }}</strong>
        <span>{{ activeTerritory ? 'Territorio selezionato sulla mappa' : 'Passa sopra un territorio o cliccalo per mantenerlo selezionato' }}</span>
      </div>

      <img
        :src="campaignMap"
        alt="Mappa della campagna"
        class="campaign-map"
        loading="eager"
        decoding="async"
        fetchpriority="high"
      />

      <svg
        class="campaign-map-overlay"
        :viewBox="`0 0 ${HEX_MAP_WIDTH} ${HEX_MAP_HEIGHT}`"
        preserveAspectRatio="xMidYMid meet"
      >
        <g>
          <polygon
            v-for="hex in assignedHexes"
            :key="hex.id"
            :points="hex.points"
            class="campaign-map-hex"
            :class="{ 'is-active': activeTerritoryId === assignments[hex.id] }"
            @mouseenter="setActiveTerritory(assignments[hex.id])"
            @mouseleave="clearHover"
            @click="pinTerritory(assignments[hex.id])"
          />
        </g>
      </svg>
    </div>

    <div v-if="activeTerritory" class="map-territory-caption">
      <strong>{{ activeTerritory.name }}</strong>
      <span>{{ activeTerritory.description }}</span>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import campaignMap from '@/assets/maps/campaign-map.webp';
import { useAppStore } from '@/stores/app';
import {
  HEX_MAP_HEIGHT,
  HEX_MAP_WIDTH,
  buildHexGrid,
  loadStoredHexAssignments,
} from '@/utils/hexMap';

const appStore = useAppStore();
const { territories } = storeToRefs(appStore);

const assignments = ref<Record<string, string>>({});
const activeTerritoryId = ref('');
const pinnedTerritoryId = ref('');
const hexes = computed(() => buildHexGrid());

const assignedHexes = computed(() =>
  hexes.value.filter((hex) => Boolean(assignments.value[hex.id])),
);

const activeTerritory = computed(() =>
  territories.value.find((territory) => territory.id === activeTerritoryId.value) ?? null,
);

onMounted(() => {
  assignments.value = loadStoredHexAssignments();
});

function setActiveTerritory(territoryId?: string) {
  if (!territoryId) {
    return;
  }

  activeTerritoryId.value = territoryId;
}

function clearHover() {
  if (typeof window !== 'undefined' && window.matchMedia('(hover: none)').matches) {
    return;
  }

  activeTerritoryId.value = pinnedTerritoryId.value;
}

function pinTerritory(territoryId?: string) {
  if (!territoryId) {
    return;
  }

  pinnedTerritoryId.value = territoryId;
  activeTerritoryId.value = territoryId;
}
</script>

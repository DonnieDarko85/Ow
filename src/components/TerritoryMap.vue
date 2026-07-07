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
            v-for="hex in hexes"
            :key="hex.id"
            :points="hex.points"
            class="campaign-map-hex"
            :class="{
              'is-active': activeTerritoryId === assignments[hex.id],
              'is-visible': activeTerritoryId !== '',
            }"
            @mouseenter="setActiveTerritory(assignments[hex.id])"
            @mouseleave="clearHover"
            @click="pinTerritory(assignments[hex.id])"
          />
        </g>
        <g class="campaign-map-boundaries">
          <line
            v-for="segment in boundarySegments"
            :key="segment.id"
            :x1="segment.x1"
            :y1="segment.y1"
            :x2="segment.x2"
            :y2="segment.y2"
            class="campaign-map-boundary"
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
import { api } from '@/services/api';
import { useAppStore } from '@/stores/app';
import type { HexCell } from '@/utils/hexMap';
import { HEX_MAP_HEIGHT, HEX_MAP_WIDTH, buildHexGrid, loadStoredHexAssignments } from '@/utils/hexMap';

const appStore = useAppStore();
const { territories } = storeToRefs(appStore);

const assignments = ref<Record<string, string>>({});
const activeTerritoryId = ref('');
const pinnedTerritoryId = ref('');
const hexes = computed(() => buildHexGrid());
const boundarySegments = computed(() => buildBoundarySegments(hexes.value, assignments.value));

const activeTerritory = computed(() =>
  territories.value.find((territory) => territory.id === activeTerritoryId.value) ?? null,
);

onMounted(() => {
  void loadAssignments();
});

async function loadAssignments() {
  try {
    assignments.value = await api.getTerritoryMap();
  } catch {
    assignments.value = loadStoredHexAssignments();
  }
}

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

interface BoundarySegment {
  id: string;
  x1: number;
  y1: number;
  x2: number;
  y2: number;
}

function buildBoundarySegments(hexList: HexCell[], territoryAssignments: Record<string, string>): BoundarySegment[] {
  const hexById = new Map(hexList.map((hex) => [hex.id, hex] as const));
  const segments: BoundarySegment[] = [];

  for (const hex of hexList) {
    const territoryId = territoryAssignments[hex.id];

    if (!territoryId) {
      continue;
    }

    const points = parseHexPoints(hex.points);
    const neighbors = neighborCoordinates(hex.row, hex.col);

    neighbors.forEach(([neighborRow, neighborCol], edgeIndex) => {
      const neighborHex = hexById.get(`${neighborRow}-${neighborCol}`);
      const neighborTerritoryId = neighborHex ? territoryAssignments[neighborHex.id] : '';

      if (neighborTerritoryId === territoryId) {
        return;
      }

      const start = points[edgeIndex];
      const end = points[(edgeIndex + 1) % points.length];

      segments.push({
        id: `${hex.id}:${edgeIndex}`,
        x1: start.x,
        y1: start.y,
        x2: end.x,
        y2: end.y,
      });
    });
  }

  return segments;
}

function parseHexPoints(points: string): Array<{ x: number; y: number }> {
  return points.split(' ').map((pair) => {
    const [x, y] = pair.split(',').map(Number);
    return { x, y };
  });
}

function neighborCoordinates(row: number, col: number): Array<[number, number]> {
  if (row % 2 === 0) {
    return [
      [row, col + 1],
      [row + 1, col],
      [row + 1, col - 1],
      [row, col - 1],
      [row - 1, col - 1],
      [row - 1, col],
    ];
  }

  return [
    [row, col + 1],
    [row + 1, col + 1],
    [row + 1, col],
    [row, col - 1],
    [row - 1, col],
    [row - 1, col + 1],
  ];
}
</script>

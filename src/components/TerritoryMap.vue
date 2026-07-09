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
            :class="{
              'is-active': activeTerritoryId === assignments[hex.id],
              'is-assigned': Boolean(assignments[hex.id]),
            }"
            :style="hexStyle(assignments[hex.id], activeTerritoryId === assignments[hex.id])"
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
            :style="boundaryStyle(segment.territoryId)"
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
import campaignMap from '@/assets/maps/campaign-map.jpeg';
import { api } from '@/services/api';
import { useTheme } from '@/composables/useTheme';
import { useAppStore } from '@/stores/app';
import type { HexCell } from '@/utils/hexMap';
import type { Territory } from '@/types';
import { HEX_MAP_HEIGHT, HEX_MAP_WIDTH, buildHexGrid, loadStoredHexAssignments } from '@/utils/hexMap';

const appStore = useAppStore();
const { territories } = storeToRefs(appStore);
const { factionColor } = useTheme();

const assignments = ref<Record<string, string>>({});
const activeTerritoryId = ref('');
const pinnedTerritoryId = ref('');
const hexes = computed(() => buildHexGrid());
const assignedHexes = computed(() => hexes.value.filter((hex) => Boolean(assignments.value[hex.id])));
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
  territoryId: string;
  x1: number;
  y1: number;
  x2: number;
  y2: number;
}

function buildBoundarySegments(hexList: HexCell[], territoryAssignments: Record<string, string>): BoundarySegment[] {
  const edgeGroups = new Map<
    string,
    {
      coords: { x1: number; y1: number; x2: number; y2: number };
      territories: Set<string>;
      count: number;
    }
  >();

  for (const hex of hexList) {
    const territoryId = territoryAssignments[hex.id];

    if (!territoryId) {
      continue;
    }

    const points = parseHexPoints(hex.points);
    points.forEach((start, edgeIndex) => {
      const end = points[(edgeIndex + 1) % points.length];
      const key = edgeKey(start.x, start.y, end.x, end.y);
      const existing = edgeGroups.get(key);

      if (existing) {
        existing.count += 1;
        existing.territories.add(territoryId);
        return;
      }

      edgeGroups.set(key, {
        coords: {
          x1: start.x,
          y1: start.y,
          x2: end.x,
          y2: end.y,
        },
        territories: new Set([territoryId]),
        count: 1,
      });
    });
  }

  const segments: BoundarySegment[] = [];

  for (const [key, entry] of edgeGroups.entries()) {
    if (entry.territories.size > 1) {
      for (const territoryId of entry.territories) {
        segments.push({
          id: `${key}:${territoryId}`,
          territoryId,
          ...entry.coords,
        });
      }

      continue;
    }

    const [territoryId] = Array.from(entry.territories);

    if (!territoryId || entry.count > 1) {
      continue;
    }

    segments.push({
      id: `${key}:${territoryId}`,
      territoryId,
      ...entry.coords,
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

function boundaryStyle(territoryId: string) {
  const territory = territoryById(territoryId);
  const color = territoryDominanceColor(territory);

  return {
    '--boundary-color': color,
  };
}

function hexStyle(territoryId: string | undefined, isActive: boolean) {
  const territory = territoryId ? territoryById(territoryId) : null;
  const color = territoryDominanceColor(territory);
  return {
    '--territory-fill': hexToRgba(color, isActive ? 0.28 : 0.18),
    '--territory-stroke': hexToRgba(color, isActive ? 0.96 : 0.34),
  };
}

function territoryDominanceColor(territory: Territory | null) {
  if (!territory) {
    return '#ffffff';
  }

  const topEntries = [...territory.stats.factionControl].sort((left, right) => right.wins - left.wins);
  const leader = topEntries[0];
  const runnerUp = topEntries[1];

  if (!leader || leader.wins <= 0) {
    return '#ffffff';
  }

  const lead = leader.wins - (runnerUp?.wins ?? 0);

  if (lead <= 0) {
    return '#ffffff';
  }

  return mixHexColors('#ffffff', factionColor(leader.faction), Math.min(lead / 5, 1));
}

function territoryById(territoryId: string): Territory | null {
  return territories.value.find((territory) => territory.id === territoryId) ?? null;
}

function hexToRgba(hex: string, alpha: number) {
  const [red, green, blue] = hexToRgb(hex);
  return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
}

function mixHexColors(fromHex: string, toHex: string, weight: number) {
  const [fromRed, fromGreen, fromBlue] = hexToRgb(fromHex);
  const [toRed, toGreen, toBlue] = hexToRgb(toHex);
  const clampedWeight = Math.max(0, Math.min(weight, 1));

  const mixed = [fromRed, fromGreen, fromBlue].map((channel, index) =>
    Math.round(channel + (([toRed, toGreen, toBlue][index] - channel) * clampedWeight)),
  );

  return rgbToHex(mixed[0], mixed[1], mixed[2]);
}

function hexToRgb(hex: string): [number, number, number] {
  const normalized = hex.replace('#', '');
  const value = normalized.length === 3
    ? normalized.split('').map((char) => `${char}${char}`).join('')
    : normalized;

  return [
    parseInt(value.slice(0, 2), 16),
    parseInt(value.slice(2, 4), 16),
    parseInt(value.slice(4, 6), 16),
  ];
}

function rgbToHex(red: number, green: number, blue: number) {
  return `#${[red, green, blue].map((channel) => channel.toString(16).padStart(2, '0')).join('')}`;
}

function edgeKey(x1: number, y1: number, x2: number, y2: number): string {
  const start = `${x1.toFixed(2)},${y1.toFixed(2)}`;
  const end = `${x2.toFixed(2)},${y2.toFixed(2)}`;

  return start < end ? `${start}|${end}` : `${end}|${start}`;
}
</script>

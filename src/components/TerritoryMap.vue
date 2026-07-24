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
        <defs>
          <pattern
            v-for="pattern in contestedPatterns"
            :id="pattern.id"
            :key="pattern.id"
            patternUnits="userSpaceOnUse"
            width="18"
            height="18"
            patternTransform="rotate(32)"
          >
            <rect width="18" height="18" :fill="pattern.baseColor" />
            <rect
              v-for="stripe in pattern.stripes"
              :key="stripe.id"
              :x="stripe.x"
              y="0"
              :width="stripe.width"
              height="18"
              :fill="stripe.color"
            />
          </pattern>
        </defs>
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

    <section v-if="activeTerritory" class="map-territory-details">
      <div class="map-territory-summary">
        <p class="eyebrow">Focus territorio</p>
        <h3>{{ activeTerritory.name }}</h3>
        <p class="muted-copy">{{ activeTerritory.description }}</p>

        <div class="map-territory-metrics">
          <article class="map-metric-card">
            <span>Partite giocate</span>
            <strong>{{ activeTerritoryConfirmedBattles }}</strong>
          </article>
          <article class="map-metric-card">
            <span>Fazione dominante</span>
            <strong v-if="activeTerritoryLeadingFaction">{{ factionLabel(activeTerritoryLeadingFaction) }}</strong>
            <strong v-else-if="activeTerritoryIsContested">Conteso</strong>
            <strong v-else>N.A.</strong>
          </article>
        </div>
      </div>

      <div class="map-territory-chart-card">
        <p class="eyebrow">Controllo locale</p>
        <div v-if="activeTerritoryHasBattleData" class="map-territory-chart-content">
          <div class="map-territory-pie-wrap">
            <FactionPieChart
              class="faction-pie map-territory-pie"
              :segments="activeTerritoryFactionControl"
              label="Distribuzione del controllo per il territorio selezionato"
            />
          </div>

          <div class="map-territory-legend">
            <div
              v-for="entry in activeTerritoryFactionControl"
              :key="entry.faction"
              class="map-territory-legend-item"
            >
              <FactionBadge :faction="entry.faction" />
              <span>{{ entry.percentage }}% · {{ entry.wins }} vittorie</span>
            </div>
          </div>
        </div>

        <div v-else class="map-territory-empty-state">
          <strong>N.A.</strong>
          <span>Ancora nessuna partita confermata su questo territorio.</span>
        </div>
      </div>
    </section>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import fallbackCampaignMap from '@/assets/maps/campaign-map.jpeg';
import FactionBadge from '@/components/FactionBadge.vue';
import FactionPieChart from '@/components/FactionPieChart.vue';
import { api } from '@/services/api';
import { useTheme } from '@/composables/useTheme';
import { useAppStore } from '@/stores/app';
import type { Faction, Territory } from '@/types';
import type { HexCell } from '@/utils/hexMap';
import { HEX_MAP_HEIGHT, HEX_MAP_WIDTH, buildHexGrid, loadStoredHexAssignments } from '@/utils/hexMap';

const appStore = useAppStore();
const { config, territories } = storeToRefs(appStore);
const { factionColor, factionLabel } = useTheme();

const assignments = ref<Record<string, string>>({});
const activeTerritoryId = ref('');
const pinnedTerritoryId = ref('');
const hexes = computed(() => buildHexGrid());
const assignedHexes = computed(() => hexes.value.filter((hex) => Boolean(assignments.value[hex.id])));
const boundarySegments = computed(() => buildBoundarySegments(hexes.value, assignments.value));
const MUTED_NEUTRAL_COLOR = '#ece7df';
const campaignMap = computed(() =>
  config.value?.campaignMapAvailable ? config.value.campaignMapUrl : fallbackCampaignMap,
);

const activeTerritory = computed(() =>
  territories.value.find((territory) => territory.id === activeTerritoryId.value) ?? null,
);
const activeTerritoryConfirmedBattles = computed(() => activeTerritory.value?.stats.confirmedBattles ?? 0);
const activeTerritoryHasBattleData = computed(() => activeTerritoryConfirmedBattles.value > 0);
const activeTerritoryFactionControl = computed(() =>
  activeTerritory.value?.stats.factionControl.filter((entry) => entry.wins > 0) ?? [],
);
const activeTerritoryIsContested = computed(() =>
  activeTerritory.value ? territoryVisualState(activeTerritory.value).status === 'contested' : false,
);
const activeTerritoryLeadingFaction = computed(() => {
  const [first, second] = [...activeTerritoryFactionControl.value].sort((a, b) => b.percentage - a.percentage);

  if (!first || !second) {
    return first?.faction ?? null;
  }

  if (Math.abs(first.percentage - second.percentage) < 0.001) {
    return null;
  }

  return first.faction;
});
const contestedPatterns = computed(() =>
  territories.value
    .map((territory) => buildContestedPattern(territory))
    .filter((pattern): pattern is ContestedPattern => pattern !== null),
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

interface TerritoryVisualState {
  status: 'neutral' | 'dominant' | 'contested';
  color: string;
  tiedFactions: Faction[];
}

interface ContestedPattern {
  id: string;
  baseColor: string;
  stripes: Array<{
    id: string;
    x: number;
    width: number;
    color: string;
  }>;
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
  const visualState = territoryVisualState(territory);

  return {
    '--boundary-color': visualState.status === 'contested' ? 'rgba(232, 228, 220, 0.72)' : visualState.color,
  };
}

function hexStyle(territoryId: string | undefined, isActive: boolean) {
  const territory = territoryId ? territoryById(territoryId) : null;
  const visualState = territoryVisualState(territory);
  const isNeutralTerritory = visualState.status === 'neutral';
  const contestedPatternId = territory ? contestedPatternIdForTerritory(territory.id) : '';

  return {
    '--territory-fill': visualState.status === 'contested'
      ? `url(#${contestedPatternId})`
      : hexToRgba(visualState.color, isNeutralTerritory ? (isActive ? 0.36 : 0.28) : (isActive ? 0.62 : 0.52)),
    '--territory-stroke': hexToRgba(visualState.color, isActive ? 0.98 : 0.52),
  };
}

function territoryVisualState(territory: Territory | null): TerritoryVisualState {
  if (!territory) {
    return {
      status: 'neutral',
      color: MUTED_NEUTRAL_COLOR,
      tiedFactions: [],
    };
  }

  const topEntries = [...territory.stats.factionControl].sort((left, right) => right.wins - left.wins);
  const leader = topEntries[0];
  const runnerUp = topEntries[1];

  if (!leader || leader.wins <= 0) {
    return {
      status: 'neutral',
      color: MUTED_NEUTRAL_COLOR,
      tiedFactions: [],
    };
  }

  const tiedFactions = topEntries
    .filter((entry) => entry.wins === leader.wins && entry.wins > 0)
    .map((entry) => entry.faction);

  if (tiedFactions.length > 1) {
    return {
      status: 'contested',
      color: mixFactionSetWithNeutral(tiedFactions, 0.72),
      tiedFactions,
    };
  }

  const lead = leader.wins - (runnerUp?.wins ?? 0);
  const intensity = Math.min(0.6 + (((Math.min(lead, 5) - 1) / 4) * 0.4), 1);
  return {
    status: 'dominant',
    color: mixHexColors(MUTED_NEUTRAL_COLOR, factionColor(leader.faction), intensity),
    tiedFactions: [leader.faction],
  };
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

function mixFactionSetWithNeutral(factions: Faction[], neutralWeight: number) {
  const factionValues = factions.map((faction) => hexToRgb(factionColor(faction)));
  const [neutralRed, neutralGreen, neutralBlue] = hexToRgb(MUTED_NEUTRAL_COLOR);

  const average = factionValues.reduce(
    (accumulator, current) => [
      accumulator[0] + current[0],
      accumulator[1] + current[1],
      accumulator[2] + current[2],
    ],
    [0, 0, 0],
  ).map((channel) => Math.round(channel / factionValues.length)) as [number, number, number];

  const weighted = [
    Math.round((average[0] * neutralWeight) + (neutralRed * (1 - neutralWeight))),
    Math.round((average[1] * neutralWeight) + (neutralGreen * (1 - neutralWeight))),
    Math.round((average[2] * neutralWeight) + (neutralBlue * (1 - neutralWeight))),
  ] as [number, number, number];

  return rgbToHex(weighted[0], weighted[1], weighted[2]);
}

function contestedPatternIdForTerritory(territoryId: string) {
  return `territory-contested-${territoryId}`;
}

function buildContestedPattern(territory: Territory): ContestedPattern | null {
  const visualState = territoryVisualState(territory);

  if (visualState.status !== 'contested') {
    return null;
  }

  const stripeWidth = 18 / visualState.tiedFactions.length;

  return {
    id: contestedPatternIdForTerritory(territory.id),
    baseColor: hexToRgba(MUTED_NEUTRAL_COLOR, 0.28),
    stripes: visualState.tiedFactions.map((faction, index) => ({
      id: `${territory.id}-${faction}`,
      x: Number((index * stripeWidth).toFixed(2)),
      width: Number(stripeWidth.toFixed(2)),
      color: hexToRgba(factionColor(faction), 0.52),
    })),
  };
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

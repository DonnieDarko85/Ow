<template>
  <div class="page-stack">
    <section class="home-map-section">
      <TerritoryMap />
    </section>

    <section class="home-stats-layout">
      <div class="home-stats-stack">
        <StatCard label="Territori attivi" :value="territories.length" />
        <StatCard label="Battaglie confermate" :value="confirmedBattles" />
      </div>

      <article class="stat-card faction-stat-card">
        <p class="eyebrow">Fazione in vantaggio</p>
        <div class="faction-stat-body">
          <div class="faction-pie-wrap">
            <div
              class="faction-pie"
              :style="{ background: factionPieBackground }"
              aria-label="Distribuzione del controllo tra le tre fazioni"
            ></div>
            <p class="faction-stat-label" :style="leadingFactionColor ? { color: leadingFactionColor } : undefined">
              {{ leadingFaction ?? 'Equilibrio tra le fazioni' }}
            </p>
          </div>

          <div class="faction-points-legend">
            <span
              v-for="entry in factionPointsLegend"
              :key="entry.faction"
              class="faction-points-item"
              :style="{ color: factionColor(entry.faction) }"
            >
              {{ entry.short }} {{ entry.points }} pt
            </span>
          </div>
        </div>
      </article>
    </section>

    <section class="content-grid single-column">
      <div class="panel-card home-recent-matches">
        <SectionHeader
          eyebrow="Ultimi scontri"
          title="Match recenti"
        />
        <MatchesTable :matches="confirmedRecentMatches.slice(0, 10)" />
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import MatchesTable from '@/components/MatchesTable.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import StatCard from '@/components/StatCard.vue';
import TerritoryMap from '@/components/TerritoryMap.vue';
import type { Faction } from '@/types';
import { useTheme } from '@/composables/useTheme';
import { useAppStore } from '@/stores/app';

const appStore = useAppStore();
const { recentMatches, territories } = storeToRefs(appStore);
const { factionColor, factionLabel } = useTheme();
const factionOrder: Faction[] = ['FORCES_OF_FANTASY', 'RAVAGING_HORDES', 'UNDEAD'];

const confirmedRecentMatches = computed(() =>
  recentMatches.value.filter((match) => match.status === 'CONFIRMED'),
);

const confirmedBattles = computed(() => confirmedRecentMatches.value.length);

const factionPointTotals = computed(() => {
  const totals = new Map<Faction, number>(
    factionOrder.map((faction) => [faction, 0]),
  );

  for (const match of confirmedRecentMatches.value) {
    totals.set(match.factionA, (totals.get(match.factionA) ?? 0) + match.scoreA);
    totals.set(match.factionB, (totals.get(match.factionB) ?? 0) + match.scoreB);
  }

  return totals;
});

const factionDistribution = computed(() => {
  if (confirmedRecentMatches.value.length === 0) {
    return factionOrder.map((faction) => ({
      faction,
      percentage: 100 / factionOrder.length,
    }));
  }

  const totalPoints = Array.from(factionPointTotals.value.values()).reduce((sum, value) => sum + value, 0);

  return factionOrder.map((faction) => ({
    faction,
    percentage: totalPoints > 0 ? ((factionPointTotals.value.get(faction) ?? 0) / totalPoints) * 100 : 0,
  }));
});

const factionPointsLegend = computed(() => ([
  { faction: 'FORCES_OF_FANTASY' as Faction, short: 'FoF', points: factionPointTotals.value.get('FORCES_OF_FANTASY') ?? 0 },
  { faction: 'RAVAGING_HORDES' as Faction, short: 'RH', points: factionPointTotals.value.get('RAVAGING_HORDES') ?? 0 },
  { faction: 'UNDEAD' as Faction, short: 'Undead', points: factionPointTotals.value.get('UNDEAD') ?? 0 },
]));

const leadingFaction = computed(() => {
  const sorted = [...factionDistribution.value].sort((a, b) => b.percentage - a.percentage);
  const [first, second] = sorted;

  if (!first || !second) {
    return null;
  }

  if (Math.abs(first.percentage - second.percentage) < 0.001) {
    return null;
  }

  return factionLabel(first.faction);
});

const leadingFactionColor = computed(() => {
  const sorted = [...factionDistribution.value].sort((a, b) => b.percentage - a.percentage);
  const [first, second] = sorted;

  if (!first || !second || Math.abs(first.percentage - second.percentage) < 0.001) {
    return null;
  }

  return factionColor(first.faction);
});

const factionPieBackground = computed(() => {
  let currentStop = 0;
  const segments = factionDistribution.value.map(({ faction, percentage }) => {
    const nextStop = currentStop + percentage;
    const segment = `${factionColor(faction)} ${currentStop}% ${nextStop}%`;
    currentStop = nextStop;
    return segment;
  });

  return `conic-gradient(${segments.join(', ')})`;
});
</script>

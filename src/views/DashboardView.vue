<template>
  <div class="page-stack">
    <section class="stats-grid home-stats">
      <StatCard label="Territori attivi" :value="territories.length" />
      <StatCard label="Battaglie confermate" :value="confirmedBattles" />
      <article class="stat-card faction-stat-card">
        <p class="eyebrow">Fazione in vantaggio</p>
        <div class="faction-stat-body">
          <div>
            <strong>{{ leadingFaction }}</strong>
          </div>
          <div
            class="faction-pie"
            :style="{ background: factionPieBackground }"
            aria-label="Distribuzione del controllo tra le tre fazioni"
          ></div>
        </div>
      </article>
    </section>

    <section class="home-map-section">
      <TerritoryMap />
    </section>

    <section class="content-grid single-column">
      <div class="panel-card">
        <SectionHeader
          eyebrow="Ultimi scontri"
          title="Match recenti"
        />
        <MatchesTable :matches="recentMatches.slice(0, 10)" />
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
const { factionLabel } = useTheme();
const factionOrder: Faction[] = ['FORCES_OF_FANTASY', 'RAVAGING_HORDES', 'UNDEAD'];
const factionColors: Record<Faction, string> = {
  FORCES_OF_FANTASY: '#f5f5f5',
  RAVAGING_HORDES: '#b3181f',
  UNDEAD: '#777777',
};

const confirmedBattles = computed(() =>
  territories.value.reduce((sum, territory) => sum + territory.stats.confirmedBattles, 0),
);

const factionDistribution = computed(() => {
  if (confirmedBattles.value === 0 || territories.value.length === 0) {
    return factionOrder.map((faction) => ({
      faction,
      percentage: 100 / factionOrder.length,
    }));
  }

  const totals = new Map<Faction, number>(
    factionOrder.map((faction) => [faction, 0]),
  );

  for (const territory of territories.value) {
    for (const entry of territory.stats.factionControl) {
      totals.set(entry.faction, (totals.get(entry.faction) ?? 0) + entry.percentage);
    }
  }

  return factionOrder.map((faction) => ({
    faction,
    percentage: (totals.get(faction) ?? 0) / territories.value.length,
  }));
});

const leadingFaction = computed(() => {
  const leading = factionDistribution.value.reduce((best, current) =>
    current.percentage > best.percentage ? current : best,
  );
  return factionLabel(leading.faction);
});

const factionPieBackground = computed(() => {
  let currentStop = 0;
  const segments = factionDistribution.value.map(({ faction, percentage }) => {
    const nextStop = currentStop + percentage;
    const segment = `${factionColors[faction]} ${currentStop}% ${nextStop}%`;
    currentStop = nextStop;
    return segment;
  });

  return `conic-gradient(${segments.join(', ')})`;
});
</script>

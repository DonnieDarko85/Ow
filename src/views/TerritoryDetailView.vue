<template>
  <div v-if="territory" class="page-stack">
    <section class="detail-hero">
      <div>
        <p class="eyebrow">Dettaglio territorio</p>
        <h1>{{ territory.name }}</h1>
        <p class="muted-copy">{{ territory.description }}</p>
        <p class="detail-lore">{{ territory.lore }}</p>
      </div>
      <div class="detail-side">
        <FactionBadge :faction="territory.stats.dominantFaction" />
        <div class="logo-placeholder">Stemmi / Artwork territorio</div>
      </div>
    </section>

    <section class="stats-grid">
      <StatCard label="Match confermati" :value="territory.stats.confirmedBattles" />
      <StatCard label="Match pendenti" :value="territory.stats.pendingBattles" />
      <StatCard label="Fazione dominante" :value="factionLabel(territory.stats.dominantFaction)" />
    </section>

    <section class="content-grid two-columns">
      <div class="panel-card">
        <SectionHeader eyebrow="Controllo fazioni" title="Ripartizione territorio" />
        <div class="breakdown-list">
          <div v-for="entry in territory.stats.factionControl" :key="entry.faction" class="breakdown-row">
            <div class="breakdown-head">
              <FactionBadge :faction="entry.faction" />
              <strong>{{ entry.percentage }}%</strong>
            </div>
            <div class="progress-track">
              <div class="progress-fill" :style="{ ...factionFillStyle(entry.faction), width: `${entry.percentage}%` }" />
            </div>
          </div>
        </div>
      </div>

      <div class="panel-card">
        <SectionHeader eyebrow="Controllo armate" title="Armate piu influenti" />
        <div class="breakdown-list">
          <div v-for="entry in territory.stats.armyControl" :key="entry.armyName" class="breakdown-row">
            <div class="breakdown-head">
              <span>{{ entry.armyName }}</span>
              <strong>{{ entry.percentage }}%</strong>
            </div>
            <div class="progress-track">
              <div class="progress-fill neutral" :style="{ width: `${entry.percentage}%` }" />
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="panel-card">
      <SectionHeader eyebrow="Storico recente" title="Battaglie sul territorio" />
      <MatchesTable :matches="territoryMatches" />
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import FactionBadge from '@/components/FactionBadge.vue';
import MatchesTable from '@/components/MatchesTable.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import StatCard from '@/components/StatCard.vue';
import { useTheme } from '@/composables/useTheme';
import { useAppStore } from '@/stores/app';

const route = useRoute();
const appStore = useAppStore();
const { recentMatches, territories } = storeToRefs(appStore);
const { factionFillStyle, factionLabel } = useTheme();

const territory = computed(() => appStore.territoryBySlug(String(route.params.slug)));

const territoryMatches = computed(() =>
  recentMatches.value.filter((match) => match.territorySlug === route.params.slug),
);
</script>

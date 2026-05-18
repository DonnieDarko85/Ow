<template>
  <div class="matches-list">
    <article v-for="match in matches" :key="match.id" class="match-row">
      <div>
        <p class="eyebrow">{{ match.territoryName }}</p>
        <strong>{{ formatDate(match.playedAt) }}</strong>
      </div>
      <div class="match-score">
        <div>
          <span>{{ match.playerA }}</span>
          <small>{{ match.armyA }}</small>
        </div>
        <strong>{{ match.scoreA }} - {{ match.scoreB }}</strong>
        <div>
          <span>{{ match.playerB }}</span>
          <small>{{ match.armyB }}</small>
        </div>
      </div>
      <div class="match-meta">
        <FactionBadge :faction="match.factionA" />
        <FactionBadge :faction="match.factionB" />
        <span class="status-pill" :class="`status-${match.status.toLowerCase()}`">
          {{ match.status }}
        </span>
      </div>
    </article>
  </div>
</template>

<script setup lang="ts">
import FactionBadge from '@/components/FactionBadge.vue';
import type { MatchSummary } from '@/types';

defineProps<{
  matches: MatchSummary[];
}>();

const formatDate = (value: string) =>
  new Intl.DateTimeFormat('it-IT', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(new Date(value));
</script>


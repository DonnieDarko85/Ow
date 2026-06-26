<template>
  <div class="matches-list">
    <p v-if="matches.length === 0" class="muted-copy empty-state">Nessuna partita disputata</p>

    <article v-for="match in matches" :key="match.id" class="match-row compact">
      <p class="match-line">
        <strong>Vincitore</strong>
        <span> {{ winner(match).player }}</span>
        <span>
          ({{ winner(match).army }} - {{ factionLabel(winner(match).faction) }}) {{ winner(match).score }}
        </span>
        <span> - </span>
        <strong>Perdente</strong>
        <span>
          {{ loser(match).player }} ({{ loser(match).army }} - {{ factionLabel(loser(match).faction) }}) {{ loser(match).score }}
        </span>
      </p>
    </article>
  </div>
</template>

<script setup lang="ts">
import type { MatchSummary } from '@/types';
import { useTheme } from '@/composables/useTheme';

defineProps<{
  matches: MatchSummary[];
}>();

const { factionLabel } = useTheme();

const winner = (match: MatchSummary) =>
  match.scoreA >= match.scoreB
    ? { player: match.playerA, army: match.armyA, faction: match.factionA, score: match.scoreA }
    : { player: match.playerB, army: match.armyB, faction: match.factionB, score: match.scoreB };

const loser = (match: MatchSummary) =>
  match.scoreA >= match.scoreB
    ? { player: match.playerB, army: match.armyB, faction: match.factionB, score: match.scoreB }
    : { player: match.playerA, army: match.armyA, faction: match.factionA, score: match.scoreA };
</script>

<template>
  <div class="matches-list">
    <p v-if="matches.length === 0" class="muted-copy empty-state">Nessuna partita disputata</p>

    <article v-for="match in matches" :key="match.id" class="match-row compact">
      <p class="match-battle-title">
        <strong>Battaglia per: {{ match.territoryName }}</strong>
        <span> - {{ battleOutcomeLabel(match) }}</span>
      </p>

      <div class="match-duel">
        <div class="match-player-card" :style="playerStyle(leftSide(match).faction)">
          <div class="match-player-head">
            <strong>{{ leftSide(match).player }}</strong>
          </div>
          <p class="match-player-meta">({{ leftSide(match).army }} - {{ factionLabel(leftSide(match).faction) }})</p>
        </div>

        <span class="match-player-score match-player-score-outside">{{ leftSide(match).score }}</span>
        <span class="match-separator">-</span>
        <span class="match-player-score match-player-score-outside">{{ rightSide(match).score }}</span>

        <div class="match-player-card is-opponent" :style="playerStyle(rightSide(match).faction)">
          <div class="match-player-head">
            <strong>{{ rightSide(match).player }}</strong>
          </div>
          <p class="match-player-meta">({{ rightSide(match).army }} - {{ factionLabel(rightSide(match).faction) }})</p>
        </div>
      </div>
    </article>
  </div>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import type { MatchSummary } from '@/types';
import { useTheme } from '@/composables/useTheme';
import { useAppStore } from '@/stores/app';

defineProps<{
  matches: MatchSummary[];
}>();

const appStore = useAppStore();
const { user } = storeToRefs(appStore);
const { factionBadgeStyle, factionLabel } = useTheme();

const leftSide = (match: MatchSummary) =>
  user.value?.nickname === match.playerB
    ? { player: match.playerB, army: match.armyB, faction: match.factionB, score: match.scoreB }
    : { player: match.playerA, army: match.armyA, faction: match.factionA, score: match.scoreA };

const rightSide = (match: MatchSummary) =>
  user.value?.nickname === match.playerB
    ? { player: match.playerA, army: match.armyA, faction: match.factionA, score: match.scoreA }
    : { player: match.playerB, army: match.armyB, faction: match.factionB, score: match.scoreB };

const playerStyle = (faction: MatchSummary['factionA']) => factionBadgeStyle(faction);

const battleOutcomeLabel = (match: MatchSummary) => {
  const left = leftSide(match).score;
  const right = rightSide(match).score;

  if (left === right) {
    return 'Pareggio';
  }

  const isWin = left > right;
  const winningScore = Math.max(left, right);

  if (winningScore === 4) {
    return isWin ? 'Vittoria Minore' : 'Sconfitta Minore';
  }

  if (winningScore === 5) {
    return isWin ? 'Vittoria Maggiore' : 'Sconfitta Maggiore';
  }

  if (winningScore === 6) {
    return isWin ? 'Massacro' : 'Sconfitta Totale';
  }

  return isWin ? 'Vittoria' : 'Sconfitta';
};
</script>

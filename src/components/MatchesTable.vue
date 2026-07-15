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
            <span class="match-player-vp">({{ leftSide(match).victoryPoints }})</span>
          </div>
          <p class="match-player-meta">({{ leftSide(match).army }} - {{ factionLabel(leftSide(match).faction) }})</p>
        </div>

        <span class="match-player-score match-player-score-outside">{{ leftSide(match).score }}</span>
        <span class="match-separator">-</span>
        <span class="match-player-score match-player-score-outside">{{ rightSide(match).score }}</span>

        <div class="match-player-card is-opponent" :style="playerStyle(rightSide(match).faction)">
          <div class="match-player-head">
            <strong>{{ rightSide(match).player }}</strong>
            <span class="match-player-vp">({{ rightSide(match).victoryPoints }})</span>
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
import { battleOutcomeLabel as getBattleOutcomeLabel } from '@/utils/matchScoring';

defineProps<{
  matches: MatchSummary[];
}>();

const appStore = useAppStore();
const { user } = storeToRefs(appStore);
const { factionLabel, factionSurfaceStyle } = useTheme();

const leftSide = (match: MatchSummary) =>
  user.value?.nickname === match.playerB
    ? { player: match.playerB, army: match.armyB, faction: match.factionB, score: match.scoreB, victoryPoints: match.victoryPointsB }
    : { player: match.playerA, army: match.armyA, faction: match.factionA, score: match.scoreA, victoryPoints: match.victoryPointsA };

const rightSide = (match: MatchSummary) =>
  user.value?.nickname === match.playerB
    ? { player: match.playerA, army: match.armyA, faction: match.factionA, score: match.scoreA, victoryPoints: match.victoryPointsA }
    : { player: match.playerB, army: match.armyB, faction: match.factionB, score: match.scoreB, victoryPoints: match.victoryPointsB };

const playerStyle = (faction: MatchSummary['factionA']) => factionSurfaceStyle(faction);

const battleOutcomeLabel = (match: MatchSummary) => {
  return getBattleOutcomeLabel(leftSide(match).score, rightSide(match).score);
};
</script>

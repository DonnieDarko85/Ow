<template>
  <div class="page-stack">
    <SectionHeader
      eyebrow="I miei risultati"
      title="Storico conferme e partite pendenti"
    />

    <section class="panel-card">
      <MatchesTable :matches="recentMatches" />
    </section>

    <section v-if="pendingMatchesForMe.length > 0" class="panel-card pending-results-panel">
      <div class="pending-results-head">
        <p class="eyebrow">Da confermare</p>
        <h3>Partite da verificare</h3>
      </div>

      <div class="pending-results-list">
        <article
          v-for="match in pendingMatchesForMe"
          :key="match.matchId"
          class="pending-result-card"
        >
          <p class="match-battle-title">
            <strong>Battaglia per: {{ match.territoryName }}</strong>
            <span> - {{ pendingForMeOutcomeLabel(match) }}</span>
          </p>

          <div class="match-duel pending-result-duel">
            <div class="match-player-card pending-result-player-card">
              <div class="match-player-head">
                <strong>{{ user?.nickname }}</strong>
                <span class="match-player-vp">({{ match.yourScore }})</span>
              </div>
              <p class="match-player-meta">(In attesa della tua conferma)</p>
            </div>

            <span class="match-player-score match-player-score-outside">{{ pendingForMeMatchPoints(match).playerA }}</span>
            <span class="match-separator">-</span>
            <span class="match-player-score match-player-score-outside">{{ pendingForMeMatchPoints(match).playerB }}</span>

            <div class="match-player-card pending-result-player-card is-opponent">
              <div class="match-player-head">
                <strong>{{ match.opponentNickname }}</strong>
                <span class="match-player-vp">({{ match.opponentScore }})</span>
              </div>
              <p class="match-player-meta">({{ match.opponentArmyName }} - {{ factionLabel(match.opponentFaction) }})</p>
            </div>
          </div>

          <p class="pending-result-status">Da confermare</p>
        </article>
      </div>
    </section>

    <section v-if="pendingMatchesByMe.length > 0" class="panel-card pending-results-panel">
      <div class="pending-results-head">
        <p class="eyebrow">In attesa di conferma</p>
        <h3>Partite inserite da te</h3>
      </div>

      <div class="pending-results-list">
        <article
          v-for="match in pendingMatchesByMe"
          :key="match.matchId"
          class="pending-result-card"
        >
          <p class="match-battle-title">
            <strong>Battaglia per: {{ match.territoryName }}</strong>
            <span> - {{ pendingOutcomeLabel(match) }}</span>
          </p>

          <div class="match-duel pending-result-duel">
            <div class="match-player-card pending-result-player-card">
              <div class="match-player-head">
                <strong>{{ user?.nickname }}</strong>
                <span class="match-player-vp">({{ match.ownScore }})</span>
              </div>
              <p class="match-player-meta">({{ match.ownArmyName }} - {{ factionLabel(match.ownFaction) }})</p>
            </div>

            <span class="match-player-score match-player-score-outside">{{ pendingMatchPoints(match).playerA }}</span>
            <span class="match-separator">-</span>
            <span class="match-player-score match-player-score-outside">{{ pendingMatchPoints(match).playerB }}</span>

            <div class="match-player-card pending-result-player-card is-opponent">
              <div class="match-player-head">
                <strong>{{ match.opponentNickname }}</strong>
                <span class="match-player-vp">({{ match.opponentScore }})</span>
              </div>
              <p class="match-player-meta">(In attesa di conferma)</p>
            </div>
          </div>

          <p class="pending-result-status">In attesa di conferma</p>
        </article>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import MatchesTable from '@/components/MatchesTable.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import { useTheme } from '@/composables/useTheme';
import { api } from '@/services/api';
import { useAppStore } from '@/stores/app';
import type { PendingMatchSuggestion, PendingOwnMatch } from '@/types';
import { battleOutcomeLabel, calculateMatchPoints } from '@/utils/matchScoring';

const appStore = useAppStore();
const { recentMatches, user } = storeToRefs(appStore);
const pendingMatchesForMe = ref<PendingMatchSuggestion[]>([]);
const pendingMatchesByMe = ref<PendingOwnMatch[]>([]);
const { factionLabel } = useTheme();

onMounted(async () => {
  const [pendingForMeResult, pendingByMeResult] = await Promise.allSettled([
    api.getPendingMatchesForMe(),
    api.getPendingMatchesByMe(),
  ]);

  pendingMatchesForMe.value = pendingForMeResult.status === 'fulfilled' ? pendingForMeResult.value : [];
  pendingMatchesByMe.value = pendingByMeResult.status === 'fulfilled' ? pendingByMeResult.value : [];
});

const pendingMatchPoints = (match: PendingOwnMatch) =>
  calculateMatchPoints(match.ownScore, match.opponentScore);

const pendingOutcomeLabel = (match: PendingOwnMatch) => {
  const points = pendingMatchPoints(match);
  return battleOutcomeLabel(points.playerA, points.playerB);
};

const pendingForMeMatchPoints = (match: PendingMatchSuggestion) =>
  calculateMatchPoints(match.yourScore, match.opponentScore);

const pendingForMeOutcomeLabel = (match: PendingMatchSuggestion) => {
  const points = pendingForMeMatchPoints(match);
  return battleOutcomeLabel(points.playerA, points.playerB);
};
</script>

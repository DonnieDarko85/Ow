<template>
  <div class="page-stack">
    <SectionHeader
      eyebrow="I miei risultati"
      title="Storico conferme e partite pendenti"
    />

    <section class="panel-card">
      <MatchesTable :matches="recentMatches" />
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
          </p>

          <div class="match-duel pending-result-duel">
            <div class="match-player-card pending-result-player-card">
              <div class="match-player-head">
                <strong>{{ user?.nickname }}</strong>
              </div>
              <p class="match-player-meta">({{ match.ownArmyName }} - {{ factionLabel(match.ownFaction) }})</p>
            </div>

            <span class="match-player-score match-player-score-outside">{{ match.ownScore }}</span>
            <span class="match-separator">-</span>
            <span class="match-player-score match-player-score-outside">{{ match.opponentScore }}</span>

            <div class="match-player-card pending-result-player-card is-opponent">
              <div class="match-player-head">
                <strong>{{ match.opponentNickname }}</strong>
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
import type { PendingOwnMatch } from '@/types';

const appStore = useAppStore();
const { recentMatches, user } = storeToRefs(appStore);
const pendingMatchesByMe = ref<PendingOwnMatch[]>([]);
const { factionLabel } = useTheme();

onMounted(async () => {
  try {
    pendingMatchesByMe.value = await api.getPendingMatchesByMe();
  } catch (error) {
    pendingMatchesByMe.value = [];
  }
});
</script>

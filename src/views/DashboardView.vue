<template>
  <div class="page-stack">
    <section class="hero-panel">
      <div>
        <p class="eyebrow">Comando di campagna</p>
        <h1>Controlla territori, conferme incrociate e avanzata delle fazioni.</h1>
        <p class="muted-copy hero-copy">
          Dashboard centrale pensata per desktop e cellulare, con mappa interattiva, riepilogo rapido e branding configurabile.
        </p>
      </div>
      <div class="hero-side">
        <div class="logo-placeholder large">Logo App / Evento</div>
        <div class="logo-placeholder large">Banner campagna</div>
      </div>
    </section>

    <section class="stats-grid">
      <StatCard label="Territori attivi" :value="territories.length" caption="Mappa campagna configurabile" />
      <StatCard label="Battaglie confermate" :value="confirmedBattles" caption="Calcolo aggregato live" />
      <StatCard label="Battaglie pendenti" :value="pendingBattles" caption="Da confermare dai due giocatori" />
      <StatCard label="Fazione in vantaggio" :value="leadingFaction" caption="Dominio complessivo attuale" />
    </section>

    <TerritoryMap :territories="territories" />

    <section class="content-grid two-columns">
      <div class="panel-card">
        <SectionHeader
          eyebrow="Ultimi scontri"
          title="Match recenti"
          description="Storico compatto, leggibile su mobile e pronto per alimentarsi dalle API PHP."
        />
        <MatchesTable :matches="recentMatches" />
      </div>

      <div class="panel-card">
        <SectionHeader
          eyebrow="Accesso rapido"
          title="Azioni immediate"
          description="Scorciatoie per i flussi piu frequenti nel portale."
        />
        <div class="quick-actions">
          <RouterLink to="/submit-result" class="action-card">
            Inserisci risultato
          </RouterLink>
          <RouterLink to="/results" class="action-card">
            Controlla stato conferme
          </RouterLink>
          <RouterLink to="/profile" class="action-card">
            Aggiorna profilo e branding
          </RouterLink>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { storeToRefs } from 'pinia';
import MatchesTable from '@/components/MatchesTable.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import StatCard from '@/components/StatCard.vue';
import TerritoryMap from '@/components/TerritoryMap.vue';
import { useTheme } from '@/composables/useTheme';
import { useAppStore } from '@/stores/app';

const appStore = useAppStore();
const { recentMatches, territories } = storeToRefs(appStore);
const { factionLabel } = useTheme();

const confirmedBattles = computed(() =>
  territories.value.reduce((sum, territory) => sum + territory.stats.confirmedBattles, 0),
);

const pendingBattles = computed(() =>
  territories.value.reduce((sum, territory) => sum + territory.stats.pendingBattles, 0),
);

const leadingFaction = computed(() => {
  const firstTerritory = territories.value[0];
  return firstTerritory ? factionLabel(firstTerritory.stats.dominantFaction) : 'N/D';
});
</script>


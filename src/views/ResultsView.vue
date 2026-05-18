<template>
  <div class="page-stack">
    <SectionHeader
      eyebrow="I miei risultati"
      title="Storico conferme e partite pendenti"
      description="Vista pronta per essere collegata alla tua API reale e filtrabile per stato."
    />

    <section class="panel-card">
      <MatchesTable :matches="recentMatches" />
    </section>
  </div>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import MatchesTable from '@/components/MatchesTable.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import { useAppStore } from '@/stores/app';

const appStore = useAppStore();
const { recentMatches } = storeToRefs(appStore);
</script>


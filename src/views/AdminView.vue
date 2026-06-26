<template>
  <div class="page-stack">
    <SectionHeader
      eyebrow="Amministrazione"
      title="Pannello admin"
      description="Area riservata agli amministratori per gestione portale, campagna e configurazioni."
    />

    <section class="content-grid two-columns">
      <div class="panel-card">
        <p class="eyebrow">Gestione campagna</p>
        <div class="breakdown-list">
          <div class="breakdown-row">
            <strong>Territori e mappa</strong>
            <p class="muted-copy">Attiva, ordina o aggiorna i territori disponibili nella campagna.</p>
          </div>
          <div class="breakdown-row">
            <strong>Moderazione risultati</strong>
            <p class="muted-copy">Controlla conferme pendenti, conflitti e match da revisionare.</p>
          </div>
          <div class="breakdown-row">
            <strong>Utenti e ruoli</strong>
            <p class="muted-copy">Promuovi admin e monitora lo stato operativo dei giocatori.</p>
          </div>
        </div>
      </div>

      <div class="panel-card">
        <p class="eyebrow">Operazioni rapide</p>
        <div class="quick-actions">
          <div class="action-card static-card">Configura stagione attiva</div>
          <div class="action-card static-card">Esporta log risultati</div>
          <div class="action-card static-card">Gestisci privilegi admin</div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import SectionHeader from '@/components/SectionHeader.vue';
</script>

<template>
  <div class="page-stack">
    <SectionHeader
      eyebrow="Risorse"
      title="Download e riferimenti"
      description="Materiale utile per la campagna, il manuale e i riferimenti esterni ufficiali."
    />

    <section class="content-grid single-column">
      <article class="panel-card resources-card">
        <div class="resources-list">
          <a class="resource-item" :href="manualPdf" download>
            <strong>Manuale Campagna</strong>
            <span>Scarica il PDF del manuale della campagna.</span>
          </a>

          <a class="resource-item" :href="efigaUrl" target="_blank" rel="noreferrer">
            <strong>EFIGA</strong>
            <span>Download del pack EFIGA ospitato su Old World Federation.</span>
          </a>

          <a class="resource-item" :href="gwFaqUrl" target="_blank" rel="noreferrer">
            <strong>FAQ GW</strong>
            <span>Pagina ufficiale Games Workshop con i download e le FAQ di Warhammer: The Old World.</span>
          </a>

          <a class="resource-item" :href="federationUrl" target="_blank" rel="noreferrer">
            <strong>Sito Federazione</strong>
            <span>Portale ufficiale della federazione Old World Federation.</span>
          </a>
        </div>
      </article>
    </section>
  </div>
</template>

<script setup lang="ts">
import SectionHeader from '@/components/SectionHeader.vue';
import manualPdf from '@/assets/resources/manuale-campagna.pdf';

const efigaUrl = 'https://oldworldfederation.com/assets/infopacks/Old%20World%20Federation%20-%20EFIGA%202026.03ita.pdf';
const gwFaqUrl = 'https://www.warhammer-community.com/en-gb/downloads/warhammer-the-old-world/';
const federationUrl = 'https://oldworldfederation.com/';
</script>

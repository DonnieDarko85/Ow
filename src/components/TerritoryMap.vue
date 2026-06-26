<template>
  <section class="map-card">
    <div class="map-stage">
      <img
        :src="campaignMap"
        alt="Mappa della campagna"
        class="campaign-map"
        loading="eager"
        decoding="async"
        fetchpriority="high"
      />
    </div>
  </section>
</template>

<script setup lang="ts">
import campaignMap from '@/assets/maps/campaign-map.webp';
</script>

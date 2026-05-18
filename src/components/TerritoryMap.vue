<template>
  <section class="map-card">
    <div class="map-card-header">
      <div>
        <p class="eyebrow">Mappa campagne</p>
        <h3>Teatri operativi</h3>
      </div>
      <p class="muted-copy">
        Ogni territorio mostra controllo dominante, volume battaglie e accesso rapido al dettaglio.
      </p>
    </div>

    <div class="map-stage">
      <svg
        viewBox="0 0 780 520"
        class="campaign-map"
        role="img"
        aria-label="Mappa stilizzata dei territori di campagna"
      >
        <defs>
          <linearGradient id="parchment" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#ebd8aa" />
            <stop offset="100%" stop-color="#bba26d" />
          </linearGradient>
        </defs>

        <rect x="24" y="24" width="732" height="472" rx="28" fill="url(#parchment)" class="map-frame" />

        <g
          v-for="territory in territories"
          :key="territory.id"
          class="map-region-group"
          tabindex="0"
          role="link"
          @click="goToTerritory(territory.slug)"
          @keydown.enter="goToTerritory(territory.slug)"
        >
          <path
            :id="territory.mapPathId"
            class="map-region"
            :class="factionClass(territory.stats.dominantFaction)"
            :d="pathMap[territory.mapPathId]"
          />
          <text
            :x="labelMap[territory.mapPathId].x"
            :y="labelMap[territory.mapPathId].y"
            class="map-label"
          >
            {{ territory.name }}
          </text>
        </g>
      </svg>
    </div>

    <div class="territory-grid">
      <RouterLink
        v-for="territory in territories"
        :key="territory.id"
        :to="`/territories/${territory.slug}`"
        class="territory-tile"
      >
        <div>
          <p class="eyebrow">Territorio</p>
          <h4>{{ territory.name }}</h4>
        </div>
        <FactionBadge :faction="territory.stats.dominantFaction" />
        <p class="muted-copy">{{ territory.description }}</p>
      </RouterLink>
    </div>
  </section>
</template>

<script setup lang="ts">
import { RouterLink, useRouter } from 'vue-router';
import FactionBadge from '@/components/FactionBadge.vue';
import { useTheme } from '@/composables/useTheme';
import type { Territory } from '@/types';

defineProps<{
  territories: Territory[];
}>();

const { factionClass } = useTheme();
const router = useRouter();

const goToTerritory = (slug: string) => {
  router.push(`/territories/${slug}`);
};

const pathMap: Record<string, string> = {
  'north-pass': 'M150 85 L355 60 L430 155 L300 245 L115 205 Z',
  'ash-plains': 'M110 255 L330 255 L405 390 L250 455 L90 385 Z',
  'black-sun-necropolis': 'M450 175 L665 110 L685 340 L520 425 L385 310 Z',
};

const labelMap: Record<string, { x: number; y: number }> = {
  'north-pass': { x: 170, y: 155 },
  'ash-plains': { x: 150, y: 355 },
  'black-sun-necropolis': { x: 465, y: 270 },
};
</script>

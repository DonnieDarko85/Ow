<template>
  <span class="faction-badge" :style="factionBadgeStyle(faction)">
    {{ factionLabel(faction) }}
  </span>
</template>

<script setup lang="ts">
import { useTheme } from '@/composables/useTheme';
import type { Faction } from '@/types';

defineProps<{
  faction: Faction;
}>();

const { factionBadgeStyle, factionLabel } = useTheme();
</script>

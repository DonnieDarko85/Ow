<template>
  <span class="faction-badge" :class="factionClass(faction)">
    {{ factionLabel(faction) }}
  </span>
</template>

<script setup lang="ts">
import { useTheme } from '@/composables/useTheme';
import type { Faction } from '@/types';

defineProps<{
  faction: Faction;
}>();

const { factionClass, factionLabel } = useTheme();
</script>


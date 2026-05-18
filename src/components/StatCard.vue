<template>
  <article class="stat-card">
    <p class="eyebrow">{{ label }}</p>
    <strong>{{ value }}</strong>
    <p v-if="caption" class="muted-copy">{{ caption }}</p>
  </article>
</template>

<script setup lang="ts">
defineProps<{
  label: string;
  value: string | number;
  caption?: string;
}>();
</script>


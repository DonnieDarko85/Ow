<template>
  <div class="app-root" :class="{ 'three-d-enabled': appStore.useThreeDTheme }">
    <RouterView />
  </div>
</template>

<script setup lang="ts">
import { useAppStore } from '@/stores/app';

const appStore = useAppStore();
</script>

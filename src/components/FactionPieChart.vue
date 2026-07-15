<template>
  <svg
    v-bind="$attrs"
    class="faction-pie-svg"
    viewBox="0 0 100 100"
    width="100"
    height="100"
    role="img"
    :aria-label="label"
  >
    <circle cx="50" cy="50" r="50" class="faction-pie-base" />
    <path
      v-for="segment in normalizedSegments"
      :key="segment.faction"
      :d="describeArc(segment.startAngle, segment.endAngle)"
      :fill="factionColor(segment.faction)"
    />
  </svg>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Faction } from '@/types';
import { useTheme } from '@/composables/useTheme';

defineOptions({
  inheritAttrs: false,
});

interface PieSegment {
  faction: Faction;
  percentage: number;
}

const props = defineProps<{
  label: string;
  segments: PieSegment[];
}>();

const { factionColor } = useTheme();

const normalizedSegments = computed(() => {
  const positiveSegments = props.segments.filter((segment) => segment.percentage > 0);
  const total = positiveSegments.reduce((sum, segment) => sum + segment.percentage, 0);

  if (total <= 0) {
    return [];
  }

  let currentAngle = -90;

  return positiveSegments.map((segment, index) => {
    const ratio = segment.percentage / total;
    const sweep = index === positiveSegments.length - 1 ? 360 - (currentAngle + 90) : ratio * 360;
    const startAngle = currentAngle;
    const endAngle = currentAngle + sweep;

    currentAngle = endAngle;

    return {
      ...segment,
      startAngle,
      endAngle,
    };
  });
});

function polarToCartesian(angle: number) {
  const radians = (angle * Math.PI) / 180;
  return {
    x: 50 + (50 * Math.cos(radians)),
    y: 50 + (50 * Math.sin(radians)),
  };
}

function describeArc(startAngle: number, endAngle: number) {
  const start = polarToCartesian(startAngle);
  const end = polarToCartesian(endAngle);
  const largeArcFlag = endAngle - startAngle > 180 ? 1 : 0;

  if (endAngle - startAngle >= 360) {
    return 'M 50 50 m -50 0 a 50 50 0 1 0 100 0 a 50 50 0 1 0 -100 0';
  }

  return [
    'M 50 50',
    `L ${start.x} ${start.y}`,
    `A 50 50 0 ${largeArcFlag} 1 ${end.x} ${end.y}`,
    'Z',
  ].join(' ');
}
</script>

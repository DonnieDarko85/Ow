import { useAppStore } from '@/stores/app';
import type { CSSProperties } from 'vue';
import type { Faction } from '@/types';

const fallbackFactions: Record<Faction, { name: string; color: string }> = {
  FORCES_OF_FANTASY: { name: 'Forces of Fantasy', color: '#2f6fdd' },
  RAVAGING_HORDES: { name: 'Ravaging Hordes', color: '#b3181f' },
  UNDEAD: { name: 'Undead', color: '#777777' },
};

function hexToRgba(hex: string, alpha: number) {
  const normalized = hex.replace('#', '');
  const value = normalized.length === 3
    ? normalized.split('').map((char) => `${char}${char}`).join('')
    : normalized;

  const red = parseInt(value.slice(0, 2), 16);
  const green = parseInt(value.slice(2, 4), 16);
  const blue = parseInt(value.slice(4, 6), 16);

  return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
}

function hexToRgb(hex: string): [number, number, number] {
  const normalized = hex.replace('#', '');
  const value = normalized.length === 3
    ? normalized.split('').map((char) => `${char}${char}`).join('')
    : normalized;

  return [
    parseInt(value.slice(0, 2), 16),
    parseInt(value.slice(2, 4), 16),
    parseInt(value.slice(4, 6), 16),
  ];
}

export function useTheme() {
  const appStore = useAppStore();

  const factionMeta = (faction: Faction) =>
    appStore.factionByCode(faction) ?? { code: faction, ...fallbackFactions[faction] };

  const factionLabel = (faction: Faction) => factionMeta(faction).name;
  const factionColor = (faction: Faction) => factionMeta(faction).color;
  const factionBadgeStyle = (faction: Faction): CSSProperties => {
    const color = factionColor(faction);

    return {
      color,
    };
  };
  const factionSurfaceStyle = (faction: Faction): CSSProperties => {
    const color = factionColor(faction);

    return {
      color,
      borderColor: hexToRgba(color, 0.2),
      background: 'rgba(0, 0, 0, 0.24)',
      boxShadow: 'none',
    };
  };
  const factionFillStyle = (faction: Faction): CSSProperties => {
    const color = factionColor(faction);
    return {
      background: `linear-gradient(90deg, ${hexToRgba(color, 0.86)}, ${color})`,
    };
  };

  return {
    factionMeta,
    factionLabel,
    factionColor,
    factionBadgeStyle,
    factionSurfaceStyle,
    factionFillStyle,
  };
}

import type { Faction } from '@/types';

const factionLabels: Record<Faction, string> = {
  FORCES_OF_FANTASY: 'Forces of Fantasy',
  RAVAGING_HORDES: 'Ravaging Hordes',
  UNDEAD: 'Undead',
};

const factionClassMap: Record<Faction, string> = {
  FORCES_OF_FANTASY: 'is-fantasy',
  RAVAGING_HORDES: 'is-hordes',
  UNDEAD: 'is-undead',
};

export function useTheme() {
  const factionLabel = (faction: Faction) => factionLabels[faction];
  const factionClass = (faction: Faction) => factionClassMap[faction];

  return {
    factionLabel,
    factionClass,
  };
}


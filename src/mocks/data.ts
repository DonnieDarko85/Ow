import type { AppConfig, Army, MatchSummary, Territory, UserProfile } from '@/types';

export const appConfig: AppConfig = {
  appName: 'Old World Campaign Portal',
  appVersion: '0.1.0-alpha',
  organizationName: 'Federazione Wargame Placeholder',
  legalNote: 'Portale dimostrativo per gestione campagne. Inserire qui titolarita, termini d uso e note legali definitive.',
  privacyUrl: '#privacy',
  legalUrl: '#legal',
  cookieUrl: '#cookie',
  contactEmail: 'federazione@example.org',
};

export const armies: Army[] = [
  { id: 'army-empire', name: 'Empire of Man', slug: 'empire-of-man', defaultFaction: 'FORCES_OF_FANTASY' },
  { id: 'army-orcs', name: 'Orc & Goblin Tribes', slug: 'orc-goblin-tribes', defaultFaction: 'RAVAGING_HORDES' },
  { id: 'army-tomb-kings', name: 'Tomb Kings of Khemri', slug: 'tomb-kings-of-khemri', defaultFaction: 'UNDEAD' },
  { id: 'army-dwarfs', name: 'Dwarfen Mountain Holds', slug: 'dwarfen-mountain-holds', defaultFaction: 'FORCES_OF_FANTASY' },
];

export const currentUser: UserProfile = {
  id: 'user-1',
  nickname: 'GeneralBastion',
  email: 'general.bastion@example.com',
  preferredArmyId: 'army-empire',
  preferredFaction: 'FORCES_OF_FANTASY',
};

export const territories: Territory[] = [
  {
    id: 'territory-1',
    name: 'Passo delle Corone',
    slug: 'passo-delle-corone',
    description: 'Valico conteso tra fortezze imperiali, alture pietrose e rovine di torri sentinella.',
    lore: 'Le armate che controllano questo passaggio decidono il ritmo dell intera campagna settentrionale.',
    mapPathId: 'north-pass',
    stats: {
      confirmedBattles: 18,
      pendingBattles: 3,
      dominantFaction: 'FORCES_OF_FANTASY',
      factionControl: [
        { faction: 'FORCES_OF_FANTASY', percentage: 48 },
        { faction: 'RAVAGING_HORDES', percentage: 31 },
        { faction: 'UNDEAD', percentage: 21 },
      ],
      armyControl: [
        { armyName: 'Empire of Man', percentage: 34 },
        { armyName: 'Dwarfen Mountain Holds', percentage: 14 },
        { armyName: 'Orc & Goblin Tribes', percentage: 31 },
        { armyName: 'Tomb Kings of Khemri', percentage: 21 },
      ],
    },
  },
  {
    id: 'territory-2',
    name: 'Piane Cineree',
    slug: 'piane-cineree',
    description: 'Distesa bruciata da scorrerie e fuochi rituali, ideale per incursioni e schermaglie rapide.',
    lore: 'Chi domina le Piane Cineree puo minacciare i convogli, le riserve e le retrovie nemiche.',
    mapPathId: 'ash-plains',
    stats: {
      confirmedBattles: 11,
      pendingBattles: 4,
      dominantFaction: 'RAVAGING_HORDES',
      factionControl: [
        { faction: 'RAVAGING_HORDES', percentage: 52 },
        { faction: 'FORCES_OF_FANTASY', percentage: 28 },
        { faction: 'UNDEAD', percentage: 20 },
      ],
      armyControl: [
        { armyName: 'Orc & Goblin Tribes', percentage: 40 },
        { armyName: 'Empire of Man', percentage: 19 },
        { armyName: 'Dwarfen Mountain Holds', percentage: 9 },
        { armyName: 'Tomb Kings of Khemri', percentage: 20 },
      ],
    },
  },
  {
    id: 'territory-3',
    name: 'Necropoli del Sole Nero',
    slug: 'necropoli-del-sole-nero',
    description: 'Citta funeraria infestata da guardiani eterni e cripte che si risvegliano al crepuscolo.',
    lore: 'Gli equilibri della campagna cambiano ogni volta che le sabbie della necropoli si muovono.',
    mapPathId: 'black-sun-necropolis',
    stats: {
      confirmedBattles: 9,
      pendingBattles: 1,
      dominantFaction: 'UNDEAD',
      factionControl: [
        { faction: 'UNDEAD', percentage: 58 },
        { faction: 'RAVAGING_HORDES', percentage: 16 },
        { faction: 'FORCES_OF_FANTASY', percentage: 26 },
      ],
      armyControl: [
        { armyName: 'Tomb Kings of Khemri', percentage: 42 },
        { armyName: 'Empire of Man', percentage: 17 },
        { armyName: 'Dwarfen Mountain Holds', percentage: 9 },
        { armyName: 'Orc & Goblin Tribes', percentage: 16 },
      ],
    },
  },
];

export const recentMatches: MatchSummary[] = [
  {
    id: 'match-1',
    territorySlug: 'passo-delle-corone',
    territoryName: 'Passo delle Corone',
    playedAt: '2026-05-15',
    playerA: 'GeneralBastion',
    armyA: 'Empire of Man',
    factionA: 'FORCES_OF_FANTASY',
    scoreA: 14,
    playerB: 'SkullRider',
    armyB: 'Orc & Goblin Tribes',
    factionB: 'RAVAGING_HORDES',
    scoreB: 9,
    status: 'CONFIRMED',
  },
  {
    id: 'match-2',
    territorySlug: 'piane-cineree',
    territoryName: 'Piane Cineree',
    playedAt: '2026-05-13',
    playerA: 'SkullRider',
    armyA: 'Orc & Goblin Tribes',
    factionA: 'RAVAGING_HORDES',
    scoreA: 16,
    playerB: 'DuneKing',
    armyB: 'Tomb Kings of Khemri',
    factionB: 'UNDEAD',
    scoreB: 12,
    status: 'CONFIRMED',
  },
  {
    id: 'match-3',
    territorySlug: 'necropoli-del-sole-nero',
    territoryName: 'Necropoli del Sole Nero',
    playedAt: '2026-05-11',
    playerA: 'GeneralBastion',
    armyA: 'Empire of Man',
    factionA: 'FORCES_OF_FANTASY',
    scoreA: 10,
    playerB: 'DuneKing',
    armyB: 'Tomb Kings of Khemri',
    factionB: 'UNDEAD',
    scoreB: 10,
    status: 'PENDING',
  },
];


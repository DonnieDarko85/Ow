export type Faction = 'RAVAGING_HORDES' | 'FORCES_OF_FANTASY' | 'UNDEAD';
export type MatchStatus = 'PENDING' | 'CONFIRMED' | 'CONFLICT' | 'CANCELLED';
export type UserRole = 'USER' | 'ADMIN';

export interface Army {
  id: string;
  name: string;
  slug: string;
  defaultFaction: Faction;
}

export interface UserProfile {
  id: string;
  nickname: string;
  email: string;
  role: UserRole;
  avatarUrl?: string;
  preferredArmyId?: string;
  preferredFaction?: Faction;
}

export interface UserLookup {
  id: string;
  nickname: string;
}

export interface TerritoryStats {
  confirmedBattles: number;
  pendingBattles: number;
  dominantFaction: Faction;
  factionControl: Array<{
    faction: Faction;
    percentage: number;
  }>;
  armyControl: Array<{
    armyName: string;
    percentage: number;
  }>;
}

export interface Territory {
  id: string;
  name: string;
  slug: string;
  description: string;
  lore: string;
  mapPathId: string;
  stats: TerritoryStats;
}

export interface MatchSummary {
  id: string;
  territorySlug: string;
  territoryName: string;
  playedAt: string;
  playerA: string;
  armyA: string;
  factionA: Faction;
  scoreA: number;
  playerB: string;
  armyB: string;
  factionB: Faction;
  scoreB: number;
  status: MatchStatus;
}

export interface SubmitResultPayload {
  territoryId: string;
  ownArmyId: string;
  ownFaction: Faction;
  opponentNickname: string;
  ownScore: number;
  opponentScore: number;
  playedAt: string;
  note: string;
}

export interface AppConfig {
  appName: string;
  appVersion: string;
  organizationName: string;
  legalNote: string;
  privacyUrl: string;
  legalUrl: string;
  cookieUrl: string;
  contactEmail: string;
}

export interface RegisterPayload {
  nickname: string;
  email: string;
  password: string;
  passwordConfirmation: string;
}

export interface AuthResult {
  user: UserProfile;
  message: string;
  emailStatus?: 'sent' | 'failed' | 'skipped';
}

export interface MeResult {
  user: UserProfile | null;
}

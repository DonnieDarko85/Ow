export type Faction = 'RAVAGING_HORDES' | 'FORCES_OF_FANTASY' | 'UNDEAD';
export type MatchStatus = 'PENDING' | 'CONFIRMED' | 'CONFLICT' | 'CANCELLED';
export type UserRole = 'USER' | 'ADMIN';

export interface FactionDefinition {
  id: string;
  code: Faction;
  name: string;
  color: string;
}

export interface Army {
  id: string;
  name: string;
  slug: string;
  factionId?: string;
  defaultFaction: Faction;
}

export interface UserProfile {
  id: string;
  nickname: string;
  email?: string;
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
    wins: number;
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
  victoryPointsA: number;
  playerB: string;
  armyB: string;
  factionB: Faction;
  scoreB: number;
  victoryPointsB: number;
  status: MatchStatus;
}

export interface PendingMatchSuggestion {
  matchId: string;
  territoryId: string;
  territoryName: string;
  opponentUserId: string;
  opponentNickname: string;
  opponentArmyId: string;
  opponentArmyName: string;
  opponentFaction: Faction;
  opponentScore: number;
  yourScore: number;
  note: string;
  createdAt: string;
}

export interface PendingOwnMatch {
  matchId: string;
  territoryId: string;
  territoryName: string;
  opponentUserId: string;
  opponentNickname: string;
  ownArmyId: string;
  ownArmyName: string;
  ownFaction: Faction;
  ownScore: number;
  opponentScore: number;
  note: string;
  createdAt: string;
  status: 'PENDING';
}

export interface AdminUserRecord {
  id: string;
  nickname: string;
  role: UserRole;
  isActive: boolean;
  preferredArmyId?: string | null;
  preferredFaction?: Faction | null;
  createdAt: string;
  updatedAt: string;
}

export interface AdminMatchRecord {
  id: string;
  territoryId: string;
  territoryName: string;
  status: MatchStatus;
  playedAt?: string | null;
  playerAId: string;
  playerAName: string;
  playerBId: string;
  playerBName: string;
  armyAId?: string | null;
  armyAName?: string | null;
  factionA?: Faction | null;
  armyBId?: string | null;
  armyBName?: string | null;
  factionB?: Faction | null;
  victoryPointsA: number;
  victoryPointsB: number;
  matchPointsA?: number | null;
  matchPointsB?: number | null;
  createdAt: string;
  updatedAt: string;
}

export interface SubmitResultPayload {
  territoryId: string;
  ownArmyId: string;
  ownFaction?: Faction;
  opponentNickname: string;
  ownScore: number;
  opponentScore: number;
  playedAt?: string;
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

export interface UpdateProfilePayload {
  preferredArmyId?: string | null;
  preferredFaction?: Faction | null;
  password?: string;
}

export interface AuthResult {
  user: UserProfile;
  message: string;
  emailStatus?: 'sent' | 'failed' | 'skipped';
}

export interface MeResult {
  user: UserProfile | null;
}

export interface CreateTerritoryPayload {
  name: string;
  description?: string;
  lore?: string;
  mapPathId?: string;
}

export interface TerritoryMapPayload {
  assignments: Record<string, string>;
}

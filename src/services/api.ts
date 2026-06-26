import { appConfig, armies, currentUser, factions, recentMatches, territories } from '@/mocks/data';
import type { AdminMatchRecord, AdminUserRecord, AppConfig, Army, AuthResult, CreateTerritoryPayload, FactionDefinition, MatchSummary, MeResult, PendingMatchSuggestion, PendingOwnMatch, RegisterPayload, SubmitResultPayload, Territory, UserLookup, UserProfile } from '@/types';

const configuredApiBaseUrl = import.meta.env.VITE_API_BASE_URL;
const apiBaseUrls = configuredApiBaseUrl
  ? [configuredApiBaseUrl]
  : import.meta.env.DEV
    ? ['']
    : ['./api/index.php', './backend/public/api/index.php'];

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  let lastError: Error | null = null;

  for (const apiBaseUrl of apiBaseUrls) {
    const url = apiBaseUrl
      ? `${apiBaseUrl}?route=${encodeURIComponent(path)}`
      : path;

    const response = await fetch(url, {
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        ...(init?.headers ?? {}),
      },
      ...init,
    });

    if (response.ok) {
      return response.json() as Promise<T>;
    }

    const errorPayload = await response.json().catch(() => null);
    const error = new Error(
      errorPayload?.error ?? errorPayload?.message ?? `API request failed: ${response.status}`,
    ) as Error & { status?: number; payload?: unknown };
    error.status = response.status;
    error.payload = errorPayload;

    if (response.status !== 404) {
      throw error;
    }

    lastError = error;
  }

  throw lastError ?? new Error('API non raggiungibile.');
}

export const api = {
  async getConfig(): Promise<AppConfig> {
    if (apiBaseUrls[0] === '') {
      return appConfig;
    }

    return request<AppConfig>('/config');
  },
  async getCurrentUser(): Promise<UserProfile | null> {
    if (apiBaseUrls[0] === '') {
      return currentUser;
    }

    const payload = await request<MeResult>('/me');
    return payload.user;
  },
  async getArmies(): Promise<Army[]> {
    if (apiBaseUrls[0] === '') {
      return armies;
    }

    const result = await request<Army[]>('/armies');
    return [...result].sort((a, b) => a.name.localeCompare(b.name, 'it', { sensitivity: 'base' }));
  },
  async getFactions(): Promise<FactionDefinition[]> {
    if (apiBaseUrls[0] === '') {
      return factions;
    }

    const result = await request<FactionDefinition[]>('/factions');
    return [...result].sort((a, b) => a.name.localeCompare(b.name, 'it', { sensitivity: 'base' }));
  },
  async getTerritories(): Promise<Territory[]> {
    if (apiBaseUrls[0] === '') {
      return territories;
    }

    return request<Territory[]>('/territories');
  },
  async createAdminTerritory(payload: CreateTerritoryPayload): Promise<{ message: string; territory: Territory }> {
    if (apiBaseUrls[0] === '') {
      const territory: Territory = {
        id: `territory-${Date.now()}`,
        name: payload.name,
        slug: payload.name
          .toLowerCase()
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/^-+|-+$/g, ''),
        description: payload.description ?? '',
        lore: payload.lore ?? '',
        mapPathId: payload.mapPathId ?? '',
        stats: {
          confirmedBattles: 0,
          pendingBattles: 0,
          dominantFaction: 'FORCES_OF_FANTASY',
          factionControl: [
            { faction: 'FORCES_OF_FANTASY', percentage: 0 },
            { faction: 'RAVAGING_HORDES', percentage: 0 },
            { faction: 'UNDEAD', percentage: 0 },
          ],
          armyControl: [],
        },
      };

      territories.push(territory);

      return {
        message: 'Territorio creato in modalita locale.',
        territory,
      };
    }

    return request<{ message: string; territory: Territory }>('/admin/territories', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },
  async getRecentMatches(): Promise<MatchSummary[]> {
    if (apiBaseUrls[0] === '') {
      return recentMatches;
    }

    return request<MatchSummary[]>('/matches/recent');
  },
  async getPendingMatchesForMe(): Promise<PendingMatchSuggestion[]> {
    if (apiBaseUrls[0] === '') {
      return [
        {
          matchId: 'pending-1',
          territoryId: territories[0]?.id ?? 'territory-1',
          territoryName: territories[0]?.name ?? 'Territorio demo',
          opponentUserId: 'user-2',
          opponentNickname: 'SkullRider',
          opponentArmyId: armies[1]?.id ?? 'army-2',
          opponentArmyName: armies[1]?.name ?? 'Armata demo',
          opponentFaction: armies[1]?.defaultFaction ?? 'RAVAGING_HORDES',
          opponentScore: 12,
          yourScore: 15,
          note: '',
          createdAt: new Date().toISOString(),
        },
      ];
    }

    return request<PendingMatchSuggestion[]>('/matches/pending-for-me');
  },
  async getPendingMatchesByMe(): Promise<PendingOwnMatch[]> {
    if (apiBaseUrls[0] === '') {
      return [
        {
          matchId: 'pending-own-1',
          territoryId: territories[0]?.id ?? 'territory-1',
          territoryName: territories[0]?.name ?? 'Territorio demo',
          opponentUserId: 'user-2',
          opponentNickname: 'SkullRider',
          ownArmyId: armies[0]?.id ?? 'army-1',
          ownArmyName: armies[0]?.name ?? 'Armata demo',
          ownFaction: armies[0]?.defaultFaction ?? 'FORCES_OF_FANTASY',
          ownScore: 1250,
          opponentScore: 980,
          note: '',
          createdAt: new Date().toISOString(),
          status: 'PENDING',
        },
      ];
    }

    return request<PendingOwnMatch[]>('/matches/pending-by-me');
  },
  async submitResult(payload: SubmitResultPayload): Promise<{ message: string }> {
    if (apiBaseUrls[0] === '') {
      await new Promise((resolve) => setTimeout(resolve, 350));
      return { message: `Mock submit completato per ${payload.opponentNickname}` };
    }

    return request<{ message: string }>('/matches/results', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },
  async login(email: string, password: string): Promise<UserProfile> {
    if (apiBaseUrls[0] === '') {
      await new Promise((resolve) => setTimeout(resolve, 500));
      return currentUser;
    }

    const result = await request<AuthResult>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });

    return result.user;
  },
  async register(payload: RegisterPayload): Promise<AuthResult> {
    if (apiBaseUrls[0] === '') {
      await new Promise((resolve) => setTimeout(resolve, 500));
      return {
        message: 'Registrazione mock completata. Nessuna email reale inviata in modalita locale.',
        emailStatus: 'skipped',
        user: {
          ...currentUser,
          nickname: payload.nickname,
          email: payload.email,
          role: 'USER',
        },
      };
    }

    return request<AuthResult>('/auth/register', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },
  async getUsers(): Promise<UserLookup[]> {
    if (apiBaseUrls[0] === '') {
      return [
        { id: 'user-1', nickname: 'GeneralBastion' },
        { id: 'user-2', nickname: 'SkullRider' },
        { id: 'user-3', nickname: 'DuneKing' },
      ].sort((a, b) => a.nickname.localeCompare(b.nickname, 'it', { sensitivity: 'base' }));
    }

    const result = await request<UserLookup[]>('/users');
    return [...result].sort((a, b) => a.nickname.localeCompare(b.nickname, 'it', { sensitivity: 'base' }));
  },
  async getAdminUsers(): Promise<AdminUserRecord[]> {
    return request<AdminUserRecord[]>('/admin/users');
  },
  async updateAdminUser(userId: string, payload: Partial<AdminUserRecord>): Promise<{ message: string; user: AdminUserRecord }> {
    return request<{ message: string; user: AdminUserRecord }>(`/admin/users/${userId}`, {
      method: 'PATCH',
      body: JSON.stringify(payload),
    });
  },
  async getAdminMatches(): Promise<AdminMatchRecord[]> {
    return request<AdminMatchRecord[]>('/admin/matches');
  },
  async updateAdminMatch(matchId: string, payload: Partial<AdminMatchRecord>): Promise<{ message: string; match: AdminMatchRecord }> {
    return request<{ message: string; match: AdminMatchRecord }>(`/admin/matches/${matchId}`, {
      method: 'PATCH',
      body: JSON.stringify(payload),
    });
  },
};

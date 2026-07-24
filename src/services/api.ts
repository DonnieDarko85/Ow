import type { AdminMatchRecord, AdminUserRecord, AppConfig, Army, AuthResult, CreateTerritoryPayload, FactionDefinition, MatchSummary, MeResult, PendingMatchSuggestion, PendingOwnMatch, RegisterPayload, SubmitResultPayload, Territory, TerritoryMapPayload, UpdateProfilePayload, UserLookup, UserProfile } from '@/types';

const configuredApiBaseUrl = import.meta.env.VITE_API_BASE_URL;
const apiBaseUrls = configuredApiBaseUrl
  ? [configuredApiBaseUrl]
  : ['./api/index.php', './backend/public/api/index.php'];

function buildApiUrl(path: string, apiBaseUrl: string) {
  return apiBaseUrl
    ? `${apiBaseUrl}?route=${encodeURIComponent(path)}`
    : path;
}

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  let lastError: Error | null = null;

  for (const apiBaseUrl of apiBaseUrls) {
    const url = buildApiUrl(path, apiBaseUrl);

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
  manualDownloadUrl() {
    return buildApiUrl('/resources/manuale-campagna', apiBaseUrls[0] ?? '');
  },
  efigaDownloadUrl() {
    return buildApiUrl('/resources/efiga', apiBaseUrls[0] ?? '');
  },
  campaignMapDownloadUrl() {
    return buildApiUrl('/resources/campaign-map', apiBaseUrls[0] ?? '');
  },
  async getConfig(): Promise<AppConfig> {
    return request<AppConfig>('/config');
  },
  async getCurrentUser(): Promise<UserProfile | null> {
    const payload = await request<MeResult>('/me');
    return payload.user;
  },
  async getArmies(): Promise<Army[]> {
    const result = await request<Army[]>('/armies');
    return [...result].sort((a, b) => a.name.localeCompare(b.name, 'it', { sensitivity: 'base' }));
  },
  async getFactions(): Promise<FactionDefinition[]> {
    const result = await request<FactionDefinition[]>('/factions');
    return [...result].sort((a, b) => a.name.localeCompare(b.name, 'it', { sensitivity: 'base' }));
  },
  async getTerritories(): Promise<Territory[]> {
    return request<Territory[]>('/territories');
  },
  async createAdminTerritory(payload: CreateTerritoryPayload): Promise<{ message: string; territory: Territory }> {
    return request<{ message: string; territory: Territory }>('/admin/territories', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },
  async updateAdminTerritoryMatchAvailability(
    territoryId: string,
    isMatchSubmissionEnabled: boolean,
  ): Promise<{ message: string }> {
    return request<{ message: string }>(`/admin/territories/${territoryId}`, {
      method: 'PATCH',
      body: JSON.stringify({ isMatchSubmissionEnabled }),
    });
  },
  async getTerritoryMap(): Promise<Record<string, string>> {
    const result = await request<TerritoryMapPayload>('/territory-map');
    return result.assignments ?? {};
  },
  async saveAdminTerritoryMap(assignments: Record<string, string>): Promise<{ message: string; assignments: Record<string, string> }> {
    return request<{ message: string; assignments: Record<string, string> }>('/admin/territory-map', {
      method: 'PUT',
      body: JSON.stringify({ assignments }),
    });
  },
  async uploadAdminCampaignManual(file: File): Promise<{ message: string; manualUrl: string; fileName: string }> {
    const formData = new FormData();
    formData.append('file', file);

    let lastError: Error | null = null;

    for (const apiBaseUrl of apiBaseUrls) {
      const url = buildApiUrl('/admin/resources/manuale-campagna', apiBaseUrl);
      const response = await fetch(url, {
        method: 'POST',
        credentials: 'include',
        body: formData,
      });

      if (response.ok) {
        return response.json() as Promise<{ message: string; manualUrl: string; fileName: string }>;
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
  },
  async uploadAdminEfiga(file: File): Promise<{ message: string; efigaUrl: string; fileName: string }> {
    const formData = new FormData();
    formData.append('file', file);

    let lastError: Error | null = null;

    for (const apiBaseUrl of apiBaseUrls) {
      const url = buildApiUrl('/admin/resources/efiga', apiBaseUrl);
      const response = await fetch(url, {
        method: 'POST',
        credentials: 'include',
        body: formData,
      });

      if (response.ok) {
        return response.json() as Promise<{ message: string; efigaUrl: string; fileName: string }>;
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
  },
  async uploadAdminCampaignMap(file: File): Promise<{ message: string; campaignMapUrl: string; fileName: string }> {
    const formData = new FormData();
    formData.append('file', file);

    let lastError: Error | null = null;

    for (const apiBaseUrl of apiBaseUrls) {
      const url = buildApiUrl('/admin/resources/campaign-map', apiBaseUrl);
      const response = await fetch(url, {
        method: 'POST',
        credentials: 'include',
        body: formData,
      });

      if (response.ok) {
        return response.json() as Promise<{ message: string; campaignMapUrl: string; fileName: string }>;
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
  },
  async getRecentMatches(): Promise<MatchSummary[]> {
    return request<MatchSummary[]>('/matches/recent');
  },
  async getPendingMatchesForMe(): Promise<PendingMatchSuggestion[]> {
    return request<PendingMatchSuggestion[]>('/matches/pending-for-me');
  },
  async getPendingMatchesByMe(): Promise<PendingOwnMatch[]> {
    return request<PendingOwnMatch[]>('/matches/pending-by-me');
  },
  async submitResult(payload: SubmitResultPayload): Promise<{ message: string }> {
    return request<{ message: string }>('/matches/results', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },
  async login(email: string, password: string): Promise<UserProfile> {
    const result = await request<AuthResult>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });

    return result.user;
  },
  async logout(): Promise<{ message: string }> {
    return request<{ message: string }>('/auth/logout', {
      method: 'POST',
      body: JSON.stringify({}),
    });
  },
  async register(payload: RegisterPayload): Promise<AuthResult> {
    return request<AuthResult>('/auth/register', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },
  async updateMyProfile(payload: UpdateProfilePayload): Promise<{ message: string; user: UserProfile }> {
    return request<{ message: string; user: UserProfile }>('/me/profile', {
      method: 'PATCH',
      body: JSON.stringify(payload),
    });
  },
  async getUsers(): Promise<UserLookup[]> {
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

import { appConfig, armies, currentUser, recentMatches, territories } from '@/mocks/data';
import type { AppConfig, Army, AuthResult, MatchSummary, MeResult, RegisterPayload, SubmitResultPayload, Territory, UserLookup, UserProfile } from '@/types';

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
  async getTerritories(): Promise<Territory[]> {
    if (apiBaseUrls[0] === '') {
      return territories;
    }

    return request<Territory[]>('/territories');
  },
  async getRecentMatches(): Promise<MatchSummary[]> {
    if (apiBaseUrls[0] === '') {
      return recentMatches;
    }

    return request<MatchSummary[]>('/matches/recent');
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
};

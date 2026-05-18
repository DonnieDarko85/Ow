import { appConfig, armies, currentUser, recentMatches, territories } from '@/mocks/data';
import type { AppConfig, Army, MatchSummary, SubmitResultPayload, Territory, UserProfile } from '@/types';

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL;

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const response = await fetch(`${apiBaseUrl}${path}`, {
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      ...(init?.headers ?? {}),
    },
    ...init,
  });

  if (!response.ok) {
    throw new Error(`API request failed: ${response.status}`);
  }

  return response.json() as Promise<T>;
}

export const api = {
  async getConfig(): Promise<AppConfig> {
    if (!apiBaseUrl) {
      return appConfig;
    }

    return request<AppConfig>('/config');
  },
  async getCurrentUser(): Promise<UserProfile> {
    if (!apiBaseUrl) {
      return currentUser;
    }

    return request<UserProfile>('/me');
  },
  async getArmies(): Promise<Army[]> {
    if (!apiBaseUrl) {
      return armies;
    }

    return request<Army[]>('/armies');
  },
  async getTerritories(): Promise<Territory[]> {
    if (!apiBaseUrl) {
      return territories;
    }

    return request<Territory[]>('/territories');
  },
  async getRecentMatches(): Promise<MatchSummary[]> {
    if (!apiBaseUrl) {
      return recentMatches;
    }

    return request<MatchSummary[]>('/matches/recent');
  },
  async submitResult(payload: SubmitResultPayload): Promise<{ message: string }> {
    if (!apiBaseUrl) {
      await new Promise((resolve) => setTimeout(resolve, 350));
      return { message: `Mock submit completato per ${payload.opponentNickname}` };
    }

    return request<{ message: string }>('/matches/results', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },
  async login(email: string, password: string): Promise<UserProfile> {
    if (!apiBaseUrl) {
      // Mock login - simula un ritardo e ritorna l'utente corrente
      await new Promise((resolve) => setTimeout(resolve, 500));
      return currentUser;
    }

    return request<UserProfile>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
  },
};


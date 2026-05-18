import { defineStore } from 'pinia';
import { api } from '@/services/api';
import type { AppConfig, Army, MatchSummary, RegisterPayload, Territory, UserProfile } from '@/types';

interface AppState {
  config: AppConfig | null;
  user: UserProfile | null;
  armies: Army[];
  territories: Territory[];
  recentMatches: MatchSummary[];
  isLoading: boolean;
  hasBootstrapped: boolean;
  bootstrapErrors: string[];
}

let bootstrapPromise: Promise<void> | null = null;

export const useAppStore = defineStore('app', {
  state: (): AppState => ({
    config: null,
    user: null,
    armies: [],
    territories: [],
    recentMatches: [],
    isLoading: false,
    hasBootstrapped: false,
    bootstrapErrors: [],
  }),
  getters: {
    isAuthenticated: (state) => state.user !== null,
    territoryBySlug: (state) => (slug: string) =>
      state.territories.find((territory) => territory.slug === slug),
  },
  actions: {
    async bootstrap() {
      this.isLoading = true;
      this.bootstrapErrors = [];

      try {
        const results = await Promise.allSettled([
          api.getConfig(),
          api.getCurrentUser(),
          api.getArmies(),
          api.getTerritories(),
          api.getRecentMatches(),
        ]);

        const [configResult, userResult, armiesResult, territoriesResult, recentMatchesResult] = results;

        if (configResult.status === 'fulfilled') {
          this.config = configResult.value;
        } else {
          this.bootstrapErrors.push('config');
        }

        if (userResult.status === 'fulfilled') {
          this.user = userResult.value;
        } else {
          this.user = null;
          this.bootstrapErrors.push('me');
        }

        if (armiesResult.status === 'fulfilled') {
          this.armies = armiesResult.value;
        } else {
          this.armies = [];
          this.bootstrapErrors.push('armies');
        }

        if (territoriesResult.status === 'fulfilled') {
          this.territories = territoriesResult.value;
        } else {
          this.territories = [];
          this.bootstrapErrors.push('territories');
        }

        if (recentMatchesResult.status === 'fulfilled') {
          this.recentMatches = recentMatchesResult.value;
        } else {
          this.recentMatches = [];
          this.bootstrapErrors.push('matches');
        }
      } finally {
        this.hasBootstrapped = true;
        this.isLoading = false;
      }
    },
    async ensureBootstrapped() {
      if (this.hasBootstrapped) {
        return;
      }

      if (!bootstrapPromise) {
        bootstrapPromise = this.bootstrap().finally(() => {
          bootstrapPromise = null;
        });
      }

      await bootstrapPromise;
    },
    async login(email: string, password: string) {
      this.isLoading = true;
      try {
        const user = await api.login(email, password);
        this.user = user;
        return user;
      } finally {
        this.isLoading = false;
      }
    },
    async register(payload: RegisterPayload) {
      this.isLoading = true;
      try {
        const result = await api.register(payload);
        this.user = result.user;
        return result;
      } finally {
        this.isLoading = false;
      }
    },
    logout() {
      this.user = null;
      this.armies = [];
      this.territories = [];
      this.recentMatches = [];
    },
  },
});

import { defineStore } from 'pinia';
import { api } from '@/services/api';
import type { AppConfig, Army, MatchSummary, Territory, UserProfile } from '@/types';

interface AppState {
  config: AppConfig | null;
  user: UserProfile | null;
  armies: Army[];
  territories: Territory[];
  recentMatches: MatchSummary[];
  isLoading: boolean;
}

export const useAppStore = defineStore('app', {
  state: (): AppState => ({
    config: null,
    user: null,
    armies: [],
    territories: [],
    recentMatches: [],
    isLoading: false,
  }),
  getters: {
    isAuthenticated: (state) => state.user !== null,
    territoryBySlug: (state) => (slug: string) =>
      state.territories.find((territory) => territory.slug === slug),
  },
  actions: {
    async bootstrap() {
      this.isLoading = true;

      try {
        const [config, user, armies, territories, recentMatches] = await Promise.all([
          api.getConfig(),
          api.getCurrentUser(),
          api.getArmies(),
          api.getTerritories(),
          api.getRecentMatches(),
        ]);

        this.config = config;
        this.user = user;
        this.armies = armies;
        this.territories = territories;
        this.recentMatches = recentMatches;
      } finally {
        this.isLoading = false;
      }
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
    logout() {
      this.user = null;
      this.armies = [];
      this.territories = [];
      this.recentMatches = [];
    },
  },
});


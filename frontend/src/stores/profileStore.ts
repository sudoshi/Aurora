import { create } from "zustand";
import { persist } from "zustand/middleware";

export interface RecentProfile {
  patientId: number;
  name: string;
  mrn: string;
  viewedAt: number;
}

interface ProfileStoreState {
  recentProfiles: RecentProfile[];
  addRecentProfile: (profile: Omit<RecentProfile, "viewedAt">) => void;
  clearRecentProfiles: () => void;
}

const MAX_RECENT = 15;

export const useProfileStore = create<ProfileStoreState>()(
  persist(
    (set) => ({
      recentProfiles: [],

      addRecentProfile: (profile) =>
        set((state) => {
          const filtered = state.recentProfiles.filter(
            (rp) => rp.patientId !== profile.patientId,
          );
          const entry: RecentProfile = {
            ...profile,
            viewedAt: Date.now(),
          };
          return {
            recentProfiles: [entry, ...filtered].slice(0, MAX_RECENT),
          };
        }),

      clearRecentProfiles: () => set({ recentProfiles: [] }),
    }),
    { name: "aurora-recent-profiles" },
  ),
);

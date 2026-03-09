import { create } from "zustand";

interface UiState {
  sidebarOpen: boolean;
  commandPaletteOpen: boolean;
  toggleSidebar: () => void;
  toggleCommandPalette: () => void;
  setSidebarOpen: (open: boolean) => void;
}

export const useUiStore = create<UiState>()((set) => ({
  sidebarOpen: true,
  commandPaletteOpen: false,

  toggleSidebar: () => set((s) => ({ sidebarOpen: !s.sidebarOpen })),
  toggleCommandPalette: () =>
    set((s) => ({ commandPaletteOpen: !s.commandPaletteOpen })),
  setSidebarOpen: (open) => set({ sidebarOpen: open }),
}));

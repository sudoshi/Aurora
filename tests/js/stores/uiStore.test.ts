import { describe, it, expect, beforeEach } from 'vitest';
import { useUIStore } from '@/stores/uiStore';

describe('uiStore', () => {
    beforeEach(() => {
        useUIStore.setState({
            sidebarOpen: true,
            commandPaletteOpen: false,
        });
    });

    describe('sidebar', () => {
        it('starts with sidebar open', () => {
            expect(useUIStore.getState().sidebarOpen).toBe(true);
        });

        it('toggles sidebar closed', () => {
            useUIStore.getState().toggleSidebar();

            expect(useUIStore.getState().sidebarOpen).toBe(false);
        });

        it('toggles sidebar back open', () => {
            useUIStore.getState().toggleSidebar();
            useUIStore.getState().toggleSidebar();

            expect(useUIStore.getState().sidebarOpen).toBe(true);
        });
    });

    describe('command palette', () => {
        it('starts with command palette closed', () => {
            expect(useUIStore.getState().commandPaletteOpen).toBe(false);
        });

        it('opens command palette', () => {
            useUIStore.getState().setCommandPaletteOpen(true);

            expect(useUIStore.getState().commandPaletteOpen).toBe(true);
        });

        it('closes command palette', () => {
            useUIStore.getState().setCommandPaletteOpen(true);
            useUIStore.getState().setCommandPaletteOpen(false);

            expect(useUIStore.getState().commandPaletteOpen).toBe(false);
        });
    });
});

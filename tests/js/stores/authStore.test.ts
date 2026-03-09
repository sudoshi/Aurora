import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAuthStore } from '@/stores/authStore';
import type { User } from '@/types';

// Mock axios
vi.mock('axios', () => ({
    default: {
        defaults: {
            headers: {
                common: {} as Record<string, string>,
            },
        },
        get: vi.fn(),
    },
}));

const mockUser: User = {
    id: 1,
    name: 'Dr. Test User',
    email: 'test@acumenus.net',
    roles: ['physician'],
};

describe('authStore', () => {
    beforeEach(() => {
        // Reset the store state between tests
        useAuthStore.setState({
            user: null,
            token: null,
            isAuthenticated: false,
            loading: true,
        });
    });

    describe('initial state', () => {
        it('has null user and loading true by default', () => {
            const state = useAuthStore.getState();

            expect(state.user).toBeNull();
            expect(state.isAuthenticated).toBe(false);
            expect(state.loading).toBe(true);
        });
    });

    describe('setAuth', () => {
        it('sets user and token and marks as authenticated', () => {
            const { setAuth } = useAuthStore.getState();

            setAuth(mockUser, 'test-token-abc');

            const state = useAuthStore.getState();
            expect(state.user).toEqual(mockUser);
            expect(state.token).toBe('test-token-abc');
            expect(state.isAuthenticated).toBe(true);
            expect(state.loading).toBe(false);
        });

        it('stores token in localStorage', () => {
            const { setAuth } = useAuthStore.getState();

            setAuth(mockUser, 'persist-token');

            expect(localStorage.setItem).toHaveBeenCalledWith('token', 'persist-token');
        });
    });

    describe('logout', () => {
        it('clears user, token, and authentication state', () => {
            // First set auth
            useAuthStore.getState().setAuth(mockUser, 'session-token');

            // Then logout
            useAuthStore.getState().logout();

            const state = useAuthStore.getState();
            expect(state.user).toBeNull();
            expect(state.token).toBeNull();
            expect(state.isAuthenticated).toBe(false);
            expect(state.loading).toBe(false);
        });

        it('removes token from localStorage', () => {
            useAuthStore.getState().setAuth(mockUser, 'temp-token');
            useAuthStore.getState().logout();

            expect(localStorage.removeItem).toHaveBeenCalledWith('token');
            expect(localStorage.removeItem).toHaveBeenCalledWith('user');
        });
    });

    describe('hasRole', () => {
        it('returns true when user has one of the specified roles', () => {
            useAuthStore.getState().setAuth(mockUser, 'token');

            const result = useAuthStore.getState().hasRole(['physician', 'admin']);

            expect(result).toBe(true);
        });

        it('returns false when user does not have any of the specified roles', () => {
            useAuthStore.getState().setAuth(mockUser, 'token');

            const result = useAuthStore.getState().hasRole(['admin', 'superadmin']);

            expect(result).toBe(false);
        });

        it('returns false when user is null', () => {
            const result = useAuthStore.getState().hasRole(['physician']);

            expect(result).toBe(false);
        });

        it('returns false when user has no roles array', () => {
            const userWithoutRoles: User = {
                id: 2,
                name: 'No Roles User',
                email: 'noroles@acumenus.net',
            };
            useAuthStore.getState().setAuth(userWithoutRoles, 'token');

            const result = useAuthStore.getState().hasRole(['physician']);

            expect(result).toBe(false);
        });
    });

    describe('initFromStorage', () => {
        it('sets loading false when no token in storage', async () => {
            // localStorage.getItem returns null by default after reset
            await useAuthStore.getState().initFromStorage();

            const state = useAuthStore.getState();
            expect(state.loading).toBe(false);
            expect(state.isAuthenticated).toBe(false);
        });
    });

    describe('setLoading', () => {
        it('updates loading state', () => {
            useAuthStore.getState().setLoading(false);

            expect(useAuthStore.getState().loading).toBe(false);

            useAuthStore.getState().setLoading(true);

            expect(useAuthStore.getState().loading).toBe(true);
        });
    });
});

import { renderHook, act } from "@testing-library/react";
import { useAuthStore } from "@/stores/authStore";
import { createMockUser } from "@/test/factories";
import { resetStores } from "@/test/utils";

afterEach(() => {
  resetStores();
});

describe("authStore", () => {
  it("has initial state with isAuthenticated false and token null", () => {
    const { result } = renderHook(() => useAuthStore());

    expect(result.current.isAuthenticated).toBe(false);
    expect(result.current.token).toBeNull();
    expect(result.current.user).toBeNull();
  });

  it("sets authenticated state via setAuth", () => {
    const mockUser = createMockUser();
    const { result } = renderHook(() => useAuthStore());

    act(() => {
      result.current.setAuth("token-123", mockUser);
    });

    expect(result.current.isAuthenticated).toBe(true);
    expect(result.current.token).toBe("token-123");
    expect(result.current.user).toEqual(mockUser);
  });

  it("resets state back to initial on logout", () => {
    const mockUser = createMockUser();
    const { result } = renderHook(() => useAuthStore());

    act(() => {
      result.current.setAuth("token-123", mockUser);
    });
    expect(result.current.isAuthenticated).toBe(true);

    act(() => {
      result.current.logout();
    });

    expect(result.current.isAuthenticated).toBe(false);
    expect(result.current.token).toBeNull();
    expect(result.current.user).toBeNull();
  });

  it("updateUser merges partial data into current user", () => {
    const mockUser = createMockUser();
    const { result } = renderHook(() => useAuthStore());

    act(() => {
      result.current.setAuth("token-123", mockUser);
    });

    act(() => {
      result.current.updateUser({ name: "Updated Name" });
    });

    expect(result.current.user?.name).toBe("Updated Name");
    expect(result.current.user?.email).toBe("test@example.com");
  });

  it("updateUser does nothing when no user is set", () => {
    const { result } = renderHook(() => useAuthStore());

    act(() => {
      result.current.updateUser({ name: "Ghost" });
    });

    expect(result.current.user).toBeNull();
  });

  it("hasRole returns true for matching role and false for non-matching", () => {
    const mockUser = createMockUser({ roles: ["physician"] });
    const { result } = renderHook(() => useAuthStore());

    act(() => {
      result.current.setAuth("token-123", mockUser);
    });

    expect(result.current.hasRole("physician")).toBe(true);
    expect(result.current.hasRole("admin")).toBe(false);
  });

  it("hasPermission returns true for matching permission and false for non-matching", () => {
    const mockUser = createMockUser({ permissions: ["view-patients"] });
    const { result } = renderHook(() => useAuthStore());

    act(() => {
      result.current.setAuth("token-123", mockUser);
    });

    expect(result.current.hasPermission("view-patients")).toBe(true);
    expect(result.current.hasPermission("delete-patients")).toBe(false);
  });

  it("isAdmin returns true for admin or super-admin roles", () => {
    const { result } = renderHook(() => useAuthStore());

    act(() => {
      result.current.setAuth("t1", createMockUser({ roles: ["admin"] }));
    });
    expect(result.current.isAdmin()).toBe(true);

    act(() => {
      result.current.setAuth("t2", createMockUser({ roles: ["super-admin"] }));
    });
    expect(result.current.isAdmin()).toBe(true);

    act(() => {
      result.current.setAuth("t3", createMockUser({ roles: ["physician"] }));
    });
    expect(result.current.isAdmin()).toBe(false);
  });

  it("isSuperAdmin returns true only for super-admin role", () => {
    const { result } = renderHook(() => useAuthStore());

    act(() => {
      result.current.setAuth("t1", createMockUser({ roles: ["super-admin"] }));
    });
    expect(result.current.isSuperAdmin()).toBe(true);

    act(() => {
      result.current.setAuth("t2", createMockUser({ roles: ["admin"] }));
    });
    expect(result.current.isSuperAdmin()).toBe(false);
  });
});

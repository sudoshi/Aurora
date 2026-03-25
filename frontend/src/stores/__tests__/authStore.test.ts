import { renderHook, act } from "@testing-library/react";
import { useAuthStore } from "@/stores/authStore";
import type { User } from "@/stores/authStore";
import { resetStores } from "@/test/utils";

const mockUser: User = {
  id: 1,
  name: "Test User",
  email: "test@example.com",
  phone: null,
  avatar: null,
  phone_number: null,
  job_title: "Physician",
  department: "Cardiology",
  organization: "Test Hospital",
  bio: null,
  must_change_password: false,
  is_active: true,
  last_login_at: "2026-03-25T10:00:00Z",
  roles: ["physician"],
  permissions: ["view-patients", "edit-patients"],
  created_at: "2026-01-01T00:00:00Z",
  updated_at: "2026-03-25T10:00:00Z",
};

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
    const { result } = renderHook(() => useAuthStore());

    act(() => {
      result.current.setAuth("token-123", mockUser);
    });

    expect(result.current.isAuthenticated).toBe(true);
    expect(result.current.token).toBe("token-123");
    expect(result.current.user).toEqual(mockUser);
  });

  it("resets state back to initial on logout", () => {
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
});

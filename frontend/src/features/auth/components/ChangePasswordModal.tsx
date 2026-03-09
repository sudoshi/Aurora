import { useState, type FormEvent } from "react";
import { authApi } from "@/features/auth/api/authApi";
import { useAuthStore } from "@/stores/authStore";
import { AxiosError } from "axios";

export default function ChangePasswordModal() {
  const setAuth = useAuthStore((s) => s.setAuth);

  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError("");

    if (newPassword.length < 8) {
      setError("New password must be at least 8 characters.");
      return;
    }

    if (newPassword !== confirmPassword) {
      setError("Passwords do not match.");
      return;
    }

    setLoading(true);

    try {
      const { data } = await authApi.changePassword(
        currentPassword,
        newPassword,
        confirmPassword,
      );
      setAuth(data.access_token, data.user);
    } catch (err) {
      if (err instanceof AxiosError) {
        setError(
          err.response?.data?.message ?? "Failed to change password.",
        );
      } else {
        setError("An unexpected error occurred.");
      }
    } finally {
      setLoading(false);
    }
  };

  const inputStyle: React.CSSProperties = {
    width: "100%",
    padding: "var(--space-3)",
    background: "var(--surface-overlay)",
    border: "1px solid var(--border-default)",
    borderRadius: "var(--radius-sm)",
    color: "var(--text-primary)",
    fontSize: "var(--text-base)",
    outline: "none",
    boxSizing: "border-box" as const,
  };

  const labelStyle: React.CSSProperties = {
    display: "block",
    fontSize: "var(--text-sm)",
    color: "var(--text-secondary)",
    marginBottom: "var(--space-1)",
  };

  return (
    <div
      style={{
        position: "fixed",
        inset: 0,
        zIndex: "var(--z-modal-backdrop)" as unknown as number,
        background: "rgba(0, 0, 0, 0.80)",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        padding: "var(--space-4)",
      }}
    >
      <div
        style={{
          width: "100%",
          maxWidth: 420,
          background: "var(--surface-raised)",
          borderRadius: "var(--radius-lg)",
          border: "1px solid var(--border-default)",
          padding: "var(--space-8)",
          boxShadow: "var(--shadow-xl)",
        }}
      >
        <h2
          style={{
            fontSize: "var(--text-xl)",
            fontWeight: 600,
            color: "var(--text-primary)",
            textAlign: "center",
            marginBottom: "var(--space-2)",
          }}
        >
          Change Your Password
        </h2>
        <p
          style={{
            color: "var(--text-muted)",
            textAlign: "center",
            marginBottom: "var(--space-6)",
            fontSize: "var(--text-sm)",
          }}
        >
          You must change your temporary password before continuing.
        </p>

        <form onSubmit={handleSubmit}>
          {error && (
            <div
              style={{
                background: "var(--critical-bg)",
                border: "1px solid var(--critical-border)",
                borderRadius: "var(--radius-sm)",
                padding: "var(--space-3)",
                marginBottom: "var(--space-4)",
                color: "var(--critical-light)",
                fontSize: "var(--text-sm)",
              }}
            >
              {error}
            </div>
          )}

          <div style={{ marginBottom: "var(--space-4)" }}>
            <label htmlFor="current-password" style={labelStyle}>
              Current Password
            </label>
            <input
              id="current-password"
              type="password"
              required
              value={currentPassword}
              onChange={(e) => setCurrentPassword(e.target.value)}
              autoComplete="current-password"
              style={inputStyle}
            />
          </div>

          <div style={{ marginBottom: "var(--space-4)" }}>
            <label htmlFor="new-password" style={labelStyle}>
              New Password
            </label>
            <input
              id="new-password"
              type="password"
              required
              minLength={8}
              value={newPassword}
              onChange={(e) => setNewPassword(e.target.value)}
              autoComplete="new-password"
              style={inputStyle}
            />
          </div>

          <div style={{ marginBottom: "var(--space-6)" }}>
            <label htmlFor="confirm-password" style={labelStyle}>
              Confirm New Password
            </label>
            <input
              id="confirm-password"
              type="password"
              required
              minLength={8}
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              autoComplete="new-password"
              style={inputStyle}
            />
          </div>

          <button
            type="submit"
            disabled={loading}
            style={{
              width: "100%",
              padding: "var(--space-3)",
              background: loading
                ? "var(--accent-muted)"
                : "var(--accent)",
              color: "var(--surface-darkest)",
              border: "none",
              borderRadius: "var(--radius-sm)",
              fontSize: "var(--text-base)",
              fontWeight: 600,
              cursor: loading ? "not-allowed" : "pointer",
              transition: "background var(--duration-fast) var(--ease-out)",
            }}
          >
            {loading ? "Changing Password..." : "Change Password"}
          </button>
        </form>
      </div>
    </div>
  );
}

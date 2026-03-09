import { useAuthStore } from "@/stores/authStore";

interface TopNavigationProps {
  onLogout: () => void;
}

export default function TopNavigation({ onLogout }: TopNavigationProps) {
  const user = useAuthStore((s) => s.user);
  const primaryRole = user?.roles[0] ?? "user";

  return (
    <header
      style={{
        position: "sticky",
        top: 0,
        zIndex: "var(--z-topbar)" as unknown as number,
        height: "var(--topbar-height)",
        background: "var(--surface-raised)",
        borderBottom: "1px solid var(--border-default)",
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        padding: "0 var(--space-6)",
      }}
    >
      <span
        style={{
          fontFamily: "var(--font-mono)",
          fontSize: "var(--text-xl)",
          fontWeight: 500,
          color: "var(--accent)",
          letterSpacing: "-0.02em",
        }}
      >
        Aurora
      </span>

      <div
        style={{
          display: "flex",
          alignItems: "center",
          gap: "var(--space-4)",
        }}
      >
        <span
          style={{
            fontSize: "var(--text-sm)",
            color: "var(--text-secondary)",
          }}
        >
          {user?.name}
        </span>

        <span
          style={{
            fontSize: "var(--text-xs)",
            padding: "2px 8px",
            borderRadius: "var(--radius-full)",
            background: "var(--accent-bg)",
            color: "var(--accent)",
            textTransform: "capitalize",
          }}
        >
          {primaryRole}
        </span>

        <button
          onClick={onLogout}
          style={{
            padding: "var(--space-2) var(--space-3)",
            background: "transparent",
            border: "1px solid var(--border-default)",
            borderRadius: "var(--radius-sm)",
            color: "var(--text-muted)",
            fontSize: "var(--text-sm)",
            cursor: "pointer",
            transition: "color var(--duration-fast) var(--ease-out)",
          }}
        >
          Logout
        </button>
      </div>
    </header>
  );
}

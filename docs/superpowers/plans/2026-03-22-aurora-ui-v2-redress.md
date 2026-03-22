# Aurora UI V2 Redress — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the broken 64px sidebar rail with a top navigation bar + contextual section sidebar, fix login page color bleed, improve surface differentiation, and resolve font/MIME infrastructure issues.

**Architecture:** The app shell changes from `[Sidebar | Content]` to `[BrandHeader / NavBar / [SectionSidebar | Content]]`. Navigation config is extracted to a shared module. Login page gets CSS-scoped token overrides. Surface tokens are brightened for depth.

**Tech Stack:** React/TypeScript, CSS custom properties, Lucide icons.

**Spec:** `docs/superpowers/specs/2026-03-22-aurora-ui-v2-redress.md`

---

## File Map

### New Files
| File | Responsibility |
|---|---|
| `frontend/src/config/navigation.ts` | Navigation structure: groups, items, section→sidebar mappings, icons |
| `frontend/src/components/layout/TopNav.tsx` | Top navigation bar with grouped dropdown menus |
| `frontend/src/components/layout/SectionSidebar.tsx` | Contextual section sidebar (200px, route-aware) |

### Rewrites
| File | Changes |
|---|---|
| `frontend/src/components/layouts/DashboardLayout.tsx` | New shell: Header + TopNav + SectionSidebar + Content |
| `frontend/src/styles/components/layout.css` | Remove rail/flyout, add top nav bar + section sidebar + new content grid |
| `frontend/src/styles/components/navigation.css` | Remove rail icon styles, add top nav items + dropdowns + section sidebar items |

### Targeted Edits
| File | Changes |
|---|---|
| `frontend/src/styles/tokens-dark.css` | Brighten surface stack (6 values) |
| `frontend/src/features/auth/components/auth-layout.css` | Add token override scope at top of `.auth-layout` |
| `frontend/src/components/layout/Header.tsx` | Simplify to brand header only (remove redundant nav concerns) |

### Deletes
| File | Reason |
|---|---|
| `frontend/src/components/layout/Sidebar.tsx` | Replaced by TopNav + SectionSidebar |

### Infrastructure
| File | Changes |
|---|---|
| `frontend/public/fonts/JetBrainsMono-Variable.woff2` | Re-download (corrupted) |
| Apache vhost config | Static file MIME + CSP (requires user to run sudo) |

---

## Task 1: Fix infrastructure — font, MIME types, CSP

**Files:**
- Replace: `frontend/public/fonts/JetBrainsMono-Variable.woff2`
- Modify: Apache vhost config (requires sudo)

- [ ] **Step 1: Re-download JetBrains Mono font**

```bash
cd /home/smudoshi/Github/Aurora
# Download the release zip
curl -L -o /tmp/jbmono.zip "https://github.com/JetBrains/JetBrainsMono/releases/download/v2.304/JetBrainsMono-2.304.zip"
# Extract the variable woff2
unzip -o /tmp/jbmono.zip "fonts/variable/*" -d /tmp/jbmono
cp /tmp/jbmono/fonts/variable/JetBrainsMono\[wght\].woff2 frontend/public/fonts/JetBrainsMono-Variable.woff2
rm -rf /tmp/jbmono /tmp/jbmono.zip
```

If zip approach fails, try Google Fonts API or download from https://fonts.google.com/specimen/JetBrains+Mono and extract the variable woff2.

- [ ] **Step 2: Verify font is valid**

```bash
file frontend/public/fonts/JetBrainsMono-Variable.woff2
# Expected: "Web Open Font Format (Version 2)" or similar, NOT "HTML document"
ls -la frontend/public/fonts/JetBrainsMono-Variable.woff2
# Expected: ~100-300KB
```

- [ ] **Step 3: Print Apache config instructions for user**

The user needs to run these commands with sudo. Print instructions:

```
MANUAL STEP REQUIRED (needs sudo):

1. Add static asset handler to Apache config:
   sudo nano /etc/apache2/sites-available/aurora.acumenus.net-le-ssl.conf

   Inside the <Directory> block, add:
   <FilesMatch "\.(js|css|woff2|woff|ttf|png|jpg|jpeg|svg|ico|json)$">
       SetHandler none
   </FilesMatch>

   Add CSP header:
   Header set Content-Security-Policy "default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self'; script-src 'self' 'unsafe-inline'"

2. Enable headers module and reload:
   sudo a2enmod headers
   sudo systemctl reload apache2
```

- [ ] **Step 4: Commit font fix**

```bash
git add frontend/public/fonts/JetBrainsMono-Variable.woff2
git commit -m "fix: re-download JetBrains Mono variable font (was corrupted)"
```

---

## Task 2: Brighten surface stack + isolate login page

**Files:**
- Modify: `frontend/src/styles/tokens-dark.css`
- Modify: `frontend/src/features/auth/components/auth-layout.css`

- [ ] **Step 1: Update surface tokens in tokens-dark.css**

Find the surface section and replace these 6 values:

```css
--surface-darkest:   #050510;   /* unchanged */
--surface-base:      #080816;   /* was #0A0A18 */
--surface-raised:    #12122E;   /* was #10102A — brighter */
--surface-overlay:   #1A1A42;   /* was #16163A — brighter */
--surface-elevated:  #222250;   /* was #1C1C48 — brighter */
--surface-accent:    #2A2A60;   /* was #222256 — brighter */
--surface-highlight: #323270;   /* was #2A2A60 — brighter */
```

Also update `--sidebar-bg` to match the new raised surface since the sidebar is being replaced:
```css
--sidebar-bg:        #080816;   /* match surface-base */
--sidebar-bg-light:  #0E0E22;
```

- [ ] **Step 2: Add token override scope to auth-layout.css**

At the very top of `auth-layout.css`, BEFORE the existing `.auth-layout` rule, add a new `.auth-layout` block that overrides all tokens. Then the existing `.auth-layout` rule follows with its position/overflow styles.

Insert this block at line 1 (before the existing comment):

```css
/* Pin original auth page colors — immune to app token changes */
.auth-layout {
  --primary:          #9B1B30;
  --primary-light:    #B82D42;
  --primary-dark:     #6A1220;
  --primary-lighter:  #D04058;
  --primary-glow:     rgba(155, 27, 48, 0.4);
  --primary-bg:       rgba(155, 27, 48, 0.15);
  --primary-border:   rgba(184, 45, 66, 0.4);
  --accent:           #2A9D8F;
  --accent-dark:      #1F7A6E;
  --accent-light:     #3DB8A9;
  --accent-lighter:   #56D4C4;
  --accent-muted:     #1F7A6E;
  --accent-pale:      rgba(42, 157, 143, 0.15);
  --accent-bg:        rgba(42, 157, 143, 0.10);
  --accent-glow:      rgba(42, 157, 143, 0.30);
  --surface-darkest:  #08080A;
  --surface-base:     #0E0E11;
  --surface-raised:   #151518;
  --surface-overlay:  #1C1C20;
  --text-primary:     #F0EDE8;
  --text-secondary:   #C5C0B8;
  --text-muted:       #8A857D;
  --text-ghost:       #5A5650;
  --border-default:   #2A2A30;
  --border-hover:     #A68B1F;
  --gradient-teal:    linear-gradient(135deg, #3DB8A9, #1F7A6E);
  --font-mono:        'IBM Plex Mono', Consolas, monospace;
  --success:          #2DD4BF;
  --success-bg:       rgba(45, 212, 191, 0.20);
  --success-border:   rgba(45, 212, 191, 0.30);
  --success-light:    #45E0CF;
  --critical-light:   #FF6B7D;
  --critical-bg:      rgba(232, 90, 107, 0.20);
  --critical-border:  rgba(232, 90, 107, 0.30);
  --focus-ring:       0 0 0 3px rgba(42, 157, 143, 0.15);
}
```

IMPORTANT: This must be a SEPARATE rule block from the existing `.auth-layout` that has `position: relative; min-height: 100vh;`. CSS merges duplicate selectors, so both blocks apply. The token overrides in the first block cascade into all children.

- [ ] **Step 3: Update Blade template body background**

In `backend/resources/views/app.blade.php`, update the body background to match new surface-base:

```html
<body class="antialiased" style="background-color: #080816; color: #E8ECF4;">
```

- [ ] **Step 4: Commit**

```bash
git add frontend/src/styles/tokens-dark.css frontend/src/features/auth/components/auth-layout.css backend/resources/views/app.blade.php
git commit -m "fix: brighten surfaces for depth, isolate login page from token changes"
```

---

## Task 3: Create navigation config

**Files:**
- Create: `frontend/src/config/navigation.ts`

- [ ] **Step 1: Create the navigation structure**

This file defines the entire navigation hierarchy used by TopNav, SectionSidebar, and dropdown menus. Single source of truth.

```typescript
import {
  LayoutDashboard,
  Briefcase,
  Calendar,
  Users,
  CheckCircle2,
  ScanLine,
  Dna,
  Cpu,
  MessageSquare,
  Shield,
  Settings,
  Activity,
  UsersRound,
  ScrollText,
  ShieldCheck,
  Bell,
  type LucideIcon,
} from "lucide-react";

export interface NavItem {
  path: string;
  label: string;
  icon: LucideIcon;
  adminOnly?: boolean;
  superAdminOnly?: boolean;
}

export interface NavGroup {
  label: string;
  path?: string; // direct link if no children
  items?: NavItem[];
  adminOnly?: boolean;
}

export interface SectionConfig {
  /** Which top-nav group this section belongs to */
  group: string;
  /** Sidebar items for this section */
  sidebarItems: NavItem[];
}

/** Top navigation groups — displayed as labels in the nav bar */
export const navGroups: NavGroup[] = [
  {
    label: "Dashboard",
    path: "/",
  },
  {
    label: "Clinical",
    items: [
      { path: "/cases", label: "Cases", icon: Briefcase },
      { path: "/sessions", label: "Sessions", icon: Calendar },
      { path: "/profiles", label: "Patient Profiles", icon: Users },
      { path: "/decisions", label: "Decisions", icon: CheckCircle2 },
    ],
  },
  {
    label: "Intelligence",
    items: [
      { path: "/imaging", label: "Imaging", icon: ScanLine },
      { path: "/genomics", label: "Genomics", icon: Dna },
      { path: "/copilot", label: "AI Copilot", icon: Cpu },
    ],
  },
  {
    label: "Commons",
    path: "/commons",
  },
  {
    label: "Admin",
    adminOnly: true,
    items: [
      { path: "/admin", label: "Admin Dashboard", icon: Settings },
      { path: "/admin/system-health", label: "System Health", icon: Activity },
      { path: "/admin/users", label: "Users", icon: UsersRound },
      { path: "/admin/user-audit", label: "Audit Log", icon: ScrollText },
      { path: "/admin/roles", label: "Roles & Permissions", icon: ShieldCheck, superAdminOnly: true },
      { path: "/admin/ai-providers", label: "AI Providers", icon: Cpu },
      { path: "/admin/notifications", label: "Notifications", icon: Bell },
    ],
  },
];

/** Map a pathname to the section sidebar items it should show */
export function getSectionForPath(pathname: string): { group: string; items: NavItem[] } {
  // Dashboard
  if (pathname === "/") {
    return { group: "Dashboard", items: [{ path: "/", label: "Dashboard", icon: LayoutDashboard }] };
  }

  // Commons
  if (pathname.startsWith("/commons")) {
    return { group: "Commons", items: [{ path: "/commons", label: "Commons", icon: MessageSquare }] };
  }

  // Check each group's items
  for (const group of navGroups) {
    if (!group.items) continue;
    for (const item of group.items) {
      if (pathname === item.path || pathname.startsWith(item.path + "/")) {
        // For Admin, show all admin children in sidebar
        if (group.label === "Admin") {
          return { group: group.label, items: group.items };
        }
        // For other groups, show just this single item
        return { group: group.label, items: [item] };
      }
    }
  }

  // Fallback
  return { group: "Dashboard", items: [{ path: "/", label: "Dashboard", icon: LayoutDashboard }] };
}

/** Icons for top-level group labels (used in mobile/collapsed views) */
export const groupIcons: Record<string, LucideIcon> = {
  Dashboard: LayoutDashboard,
  Clinical: Users,
  Intelligence: Cpu,
  Commons: MessageSquare,
  Admin: Shield,
};
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/config/navigation.ts
git commit -m "feat: extract navigation config — single source of truth for nav structure"
```

---

## Task 4: Create TopNav component

**Files:**
- Create: `frontend/src/components/layout/TopNav.tsx`

- [ ] **Step 1: Implement the top navigation bar**

```typescript
import { useState, useRef, useEffect, useCallback } from "react";
import { Link, useLocation } from "react-router-dom";
import { ChevronDown } from "lucide-react";
import { cn } from "@/lib/utils";
import { useAuthStore } from "@/stores/authStore";
import { navGroups, type NavGroup } from "@/config/navigation";

function NavDropdown({ group, isActive }: { group: NavGroup; isActive: boolean }) {
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);
  const timeoutRef = useRef<ReturnType<typeof setTimeout>>();
  const location = useLocation();

  // Close on route change
  useEffect(() => { setOpen(false); }, [location.pathname]);

  // Close on click outside
  useEffect(() => {
    if (!open) return;
    const handler = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, [open]);

  // Close on Escape
  useEffect(() => {
    if (!open) return;
    const handler = (e: KeyboardEvent) => { if (e.key === "Escape") setOpen(false); };
    document.addEventListener("keydown", handler);
    return () => document.removeEventListener("keydown", handler);
  }, [open]);

  const handleEnter = useCallback(() => {
    clearTimeout(timeoutRef.current);
    timeoutRef.current = setTimeout(() => setOpen(true), 100);
  }, []);

  const handleLeave = useCallback(() => {
    clearTimeout(timeoutRef.current);
    timeoutRef.current = setTimeout(() => setOpen(false), 150);
  }, []);

  const handleKeyDown = useCallback((e: React.KeyboardEvent) => {
    if (e.key === "Enter" || e.key === " " || e.key === "ArrowDown") {
      e.preventDefault();
      setOpen(true);
    }
  }, []);

  const isItemActive = (path: string) =>
    path === "/" ? location.pathname === "/" : location.pathname.startsWith(path);

  return (
    <div
      ref={ref}
      className="topnav-group"
      onPointerEnter={(e) => { if (e.pointerType !== "touch") handleEnter(); }}
      onPointerLeave={(e) => { if (e.pointerType !== "touch") handleLeave(); }}
    >
      <button
        className={cn("topnav-label", isActive && "active")}
        onClick={() => setOpen(!open)}
        onKeyDown={handleKeyDown}
        aria-expanded={open}
        aria-haspopup="menu"
      >
        {group.label}
        <ChevronDown size={14} className={cn("topnav-chevron", open && "open")} />
      </button>

      {open && group.items && (
        <div className="topnav-dropdown" role="menu" aria-label={group.label}>
          {group.items.map((item) => (
            <Link
              key={item.path}
              to={item.path}
              className={cn("topnav-dropdown-item", isItemActive(item.path) && "active")}
              role="menuitem"
              onClick={() => setOpen(false)}
            >
              <item.icon size={16} />
              {item.label}
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}

export function TopNav() {
  const location = useLocation();
  const { isAdmin } = useAuthStore();

  const isGroupActive = (group: NavGroup): boolean => {
    if (group.path) {
      return group.path === "/"
        ? location.pathname === "/"
        : location.pathname.startsWith(group.path);
    }
    return group.items?.some((item) =>
      item.path === "/" ? location.pathname === "/" : location.pathname.startsWith(item.path)
    ) ?? false;
  };

  const visibleGroups = navGroups.filter((g) => {
    if (g.adminOnly) return isAdmin();
    return true;
  });

  return (
    <nav className="topnav-bar" aria-label="Main navigation">
      {visibleGroups.map((group) =>
        group.path ? (
          <Link
            key={group.label}
            to={group.path}
            className={cn("topnav-label", isGroupActive(group) && "active")}
          >
            {group.label}
          </Link>
        ) : (
          <NavDropdown
            key={group.label}
            group={group}
            isActive={isGroupActive(group)}
          />
        )
      )}
    </nav>
  );
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/components/layout/TopNav.tsx
git commit -m "feat: TopNav component — grouped dropdown navigation bar"
```

---

## Task 5: Create SectionSidebar component

**Files:**
- Create: `frontend/src/components/layout/SectionSidebar.tsx`

- [ ] **Step 1: Implement the contextual section sidebar**

```typescript
import { Link, useLocation } from "react-router-dom";
import { cn } from "@/lib/utils";
import { useAuthStore } from "@/stores/authStore";
import { getSectionForPath } from "@/config/navigation";

export function SectionSidebar() {
  const location = useLocation();
  const { isSuperAdmin } = useAuthStore();
  const section = getSectionForPath(location.pathname);

  const isActive = (path: string) =>
    path === "/" ? location.pathname === "/" : location.pathname === path;

  const visibleItems = section.items.filter((item) => {
    if (item.superAdminOnly) return isSuperAdmin();
    return true;
  });

  return (
    <aside className="section-sidebar" aria-label={`${section.group} navigation`}>
      <div className="section-sidebar-title">{section.group}</div>
      <nav className="section-sidebar-nav">
        {visibleItems.map((item) => (
          <Link
            key={item.path}
            to={item.path}
            className={cn("section-sidebar-item", isActive(item.path) && "active")}
            aria-current={isActive(item.path) ? "page" : undefined}
          >
            <item.icon size={16} />
            <span>{item.label}</span>
          </Link>
        ))}
      </nav>
    </aside>
  );
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/components/layout/SectionSidebar.tsx
git commit -m "feat: SectionSidebar component — contextual section navigation"
```

---

## Task 6: Rewrite layout.css — New app shell structure

**Files:**
- Rewrite: `frontend/src/styles/components/layout.css`

- [ ] **Step 1: Replace the entire layout CSS**

Remove all sidebar rail/flyout styles. Replace with the new top nav + section sidebar + content grid.

The file should contain:

```css
/* ============================================================
   Aurora Layout — Top Nav + Contextual Section Sidebar
   ============================================================ */

/* --- App Shell --- */
.app-shell {
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
  background-color: var(--surface-base);
}

/* --- Brand Header (56px) --- */
.app-topbar {
  position: sticky;
  top: 0;
  z-index: var(--z-topbar);
  height: 56px;
  background-color: var(--surface-raised);
  border-bottom: 1px solid var(--border-default);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--space-6);
  flex-shrink: 0;
}

.topbar-brand {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.topbar-brand-name {
  font-family: var(--font-display);
  font-size: var(--text-xl);
  font-weight: 700;
  color: var(--text-primary);
  letter-spacing: -0.03em;
  text-decoration: none;
}

.topbar-actions {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

/* --- Content wrapper (sidebar + main) --- */
.app-body {
  display: flex;
  flex: 1;
  overflow: hidden;
}

/* --- Content Area --- */
.app-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.content-main {
  flex: 1;
  overflow-y: auto;
  padding: var(--content-padding);
  max-width: var(--content-max-width);
}

/* Full-bleed pages bypass padding + max-width */
.content-main:has(.layout-full-bleed) {
  padding: 0;
  max-width: none;
  overflow: hidden;
}

/* --- Page Header --- */
.page-header {
  margin-bottom: var(--space-6);
}
.page-title {
  font-family: var(--font-display);
  font-size: var(--text-2xl);
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}
.page-subtitle {
  font-size: var(--text-base);
  color: var(--text-muted);
  margin-top: var(--space-1);
}

/* --- Responsive --- */
@media (max-width: 1024px) {
  .section-sidebar {
    display: none;
  }
}

@media (max-width: 768px) {
  .topnav-bar {
    display: none;
  }
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/styles/components/layout.css
git commit -m "feat(layout): new app shell — top nav bar + section sidebar + content area"
```

---

## Task 7: Rewrite navigation.css — Top nav + dropdown + sidebar item styles

**Files:**
- Rewrite: `frontend/src/styles/components/navigation.css`

- [ ] **Step 1: Replace navigation styles**

Remove all rail icon styles. Add top nav bar, dropdown, and section sidebar styles.

```css
/* ============================================================
   Aurora Navigation — Top Nav + Dropdowns + Section Sidebar
   ============================================================ */

/* --- Top Navigation Bar (44px) --- */
.topnav-bar {
  display: flex;
  align-items: center;
  gap: var(--space-1);
  height: 44px;
  padding: 0 var(--space-6);
  background-color: var(--surface-base);
  border-bottom: 1px solid var(--border-default);
  flex-shrink: 0;
}

.topnav-group {
  position: relative;
}

.topnav-label {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  padding: var(--space-2) var(--space-3);
  font-family: var(--font-body);
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--text-muted);
  background: none;
  border: none;
  cursor: pointer;
  text-decoration: none;
  border-radius: var(--radius-md);
  transition: color var(--duration-fast), background var(--duration-fast);
  position: relative;
  white-space: nowrap;
}

.topnav-label:hover {
  color: var(--text-primary);
  background: rgba(255, 255, 255, 0.04);
}

.topnav-label.active {
  color: var(--primary);
}

/* Green glow underline on active top nav label */
.topnav-label.active::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: var(--space-3);
  right: var(--space-3);
  height: 2px;
  background: var(--primary);
  border-radius: var(--radius-full);
  box-shadow: 0 2px 8px rgba(0, 214, 143, 0.4);
}

.topnav-chevron {
  color: var(--text-ghost);
  transition: transform var(--duration-fast);
  flex-shrink: 0;
}
.topnav-chevron.open {
  transform: rotate(180deg);
}

/* --- Dropdown Menu --- */
.topnav-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  min-width: 200px;
  background: var(--surface-overlay);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  padding: var(--space-1);
  z-index: var(--z-dropdown);
  animation: fadeInUp 150ms var(--ease-out);
}

.topnav-dropdown-item {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-2) var(--space-3);
  font-size: var(--text-sm);
  color: var(--text-secondary);
  text-decoration: none;
  border-radius: var(--radius-md);
  transition: color var(--duration-fast), background var(--duration-fast);
  cursor: pointer;
  position: relative;
}

.topnav-dropdown-item:hover {
  color: var(--text-primary);
  background: rgba(0, 214, 143, 0.06);
}

.topnav-dropdown-item.active {
  color: var(--primary);
}

/* Green dot for active dropdown item */
.topnav-dropdown-item.active::before {
  content: '';
  position: absolute;
  left: 6px;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: var(--primary);
  box-shadow: 0 0 4px rgba(0, 214, 143, 0.5);
}

/* --- Section Sidebar (200px) --- */
.section-sidebar {
  width: 200px;
  flex-shrink: 0;
  background-color: var(--surface-raised);
  border-right: 1px solid var(--border-default);
  padding: var(--space-4) var(--space-2);
  overflow-y: auto;
  height: 100%;
}

.section-sidebar-title {
  font-family: var(--font-display);
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--text-ghost);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  padding: var(--space-1) var(--space-3);
  margin-bottom: var(--space-2);
}

.section-sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.section-sidebar-item {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-2) var(--space-3);
  font-size: var(--text-sm);
  color: var(--text-muted);
  text-decoration: none;
  border-radius: var(--radius-md);
  transition: color var(--duration-fast), background var(--duration-fast);
  position: relative;
}

.section-sidebar-item:hover {
  color: var(--text-primary);
  background: rgba(255, 255, 255, 0.04);
}

.section-sidebar-item.active {
  color: var(--text-primary);
  background: rgba(0, 214, 143, 0.06);
}

/* Green dot for active sidebar item */
.section-sidebar-item.active::before {
  content: '';
  position: absolute;
  left: 6px;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: var(--primary);
  box-shadow: 0 0 4px rgba(0, 214, 143, 0.5);
}

/* --- Tab bar (keep from v1) --- */
.tab-bar {
  display: flex;
  gap: 0;
  border-bottom: 1px solid var(--border-default);
  overflow-x: auto;
}
.tab-item {
  position: relative;
  padding: var(--space-3) var(--space-4);
  font-size: var(--text-base);
  color: var(--text-muted);
  cursor: pointer;
  border: none;
  background: transparent;
  white-space: nowrap;
  transition: color var(--duration-fast);
}
.tab-item:hover { color: var(--text-primary); }
.tab-item.active { color: var(--primary); }
.tab-item.active::after {
  content: '';
  position: absolute;
  bottom: -1px;
  left: 0;
  right: 0;
  height: 2px;
  background: var(--primary);
  border-radius: var(--radius-full) var(--radius-full) 0 0;
  box-shadow: 0 2px 8px rgba(0, 214, 143, 0.4);
}

/* --- Breadcrumb (keep) --- */
.breadcrumb { display: flex; align-items: center; gap: var(--space-2); font-size: var(--text-sm); color: var(--text-muted); }
.breadcrumb a { color: var(--text-muted); text-decoration: none; transition: color var(--duration-fast); }
.breadcrumb a:hover { color: var(--accent); }
.breadcrumb .breadcrumb-separator { color: var(--text-ghost); }
.breadcrumb .breadcrumb-current { color: var(--text-secondary); }

/* --- Search bar (keep) --- */
.search-bar { display: flex; align-items: center; gap: var(--space-2); background: var(--surface-overlay); border: 1px solid var(--border-default); border-radius: var(--radius-md); padding: var(--space-2) var(--space-3); color: var(--text-secondary); transition: border-color var(--duration-fast), box-shadow var(--duration-fast); }
.search-bar:focus-within { border-color: var(--border-focus); box-shadow: var(--focus-ring); }
.search-bar input { flex: 1; background: transparent; border: none; outline: none; color: var(--text-primary); font-size: var(--text-base); font-family: var(--font-body); }
.search-bar input::placeholder { color: var(--text-ghost); }
.search-bar .search-icon { color: var(--text-ghost); flex-shrink: 0; }
.search-bar .search-shortcut { font-size: var(--text-xs); color: var(--text-ghost); background: var(--surface-accent); border-radius: var(--radius-xs); padding: 2px 6px; font-family: var(--font-mono); }

/* --- Filter chip (keep) --- */
.filter-chip { display: inline-flex; align-items: center; gap: var(--space-1); padding: var(--space-1) var(--space-3); font-size: var(--text-sm); border-radius: var(--radius-full); border: 1px solid var(--border-default); background: var(--surface-accent); color: var(--text-secondary); cursor: pointer; transition: all var(--duration-fast); white-space: nowrap; }
.filter-chip:hover { border-color: var(--border-hover); color: var(--text-primary); }
.filter-chip.active { background: var(--accent-bg); border-color: var(--accent); color: var(--accent-light); }
.filter-chip .chip-close { margin-left: var(--space-1); cursor: pointer; opacity: 0.6; }
.filter-chip .chip-close:hover { opacity: 1; }
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/styles/components/navigation.css
git commit -m "feat(nav): top nav bar + dropdown + section sidebar styles"
```

---

## Task 8: Update Header.tsx — Brand header only

**Files:**
- Modify: `frontend/src/components/layout/Header.tsx`

- [ ] **Step 1: Update Header to be brand header**

The Header becomes the 56px brand row. The TopNav sits below it in the layout. Key changes:
- Add the Aurora logo + wordmark on the left (currently missing — Header only shows search)
- Keep search bar, About Abby, Abby sparkle, notifications, user dropdown on the right
- The `className` stays as `app-topbar` (matching the CSS)
- Add a `.topbar-brand` div on the left with the logo

Replace the left side of the header (the search bar as the first element) with:

```tsx
{/* Left: Brand + Search */}
<div style={{ display: "flex", alignItems: "center", gap: "var(--space-6)" }}>
  <Link to="/" className="topbar-brand">
    <img src="/aurora_icon.png" alt="Aurora" className="w-8 h-8 shrink-0" />
    <span className="topbar-brand-name">Aurora</span>
  </Link>
  <button
    className="search-bar"
    onClick={() => setCommandPaletteOpen(true)}
    style={{ maxWidth: 280, cursor: "pointer" }}
  >
    <Search size={16} className="search-icon" />
    <span style={{ color: "var(--text-ghost)", fontSize: "var(--text-sm)" }}>
      Search...
    </span>
    <span className="search-shortcut">Ctrl K</span>
  </button>
</div>
```

Add `Link` to the imports from `react-router-dom`.

- [ ] **Step 2: Commit**

```bash
git add frontend/src/components/layout/Header.tsx
git commit -m "feat(header): add Aurora brand + wordmark to header bar"
```

---

## Task 9: Rewrite DashboardLayout — New shell structure

**Files:**
- Modify: `frontend/src/components/layouts/DashboardLayout.tsx`

- [ ] **Step 1: Update the layout shell**

Replace the current sidebar-based layout with top nav + section sidebar:

```tsx
import { Outlet } from "react-router-dom";
import { Header } from "@/components/layout/Header";
import { TopNav } from "@/components/layout/TopNav";
import { SectionSidebar } from "@/components/layout/SectionSidebar";
import { CommandPalette } from "@/components/layout/CommandPalette";
import { AbbyPanel } from "@/components/layout/AbbyPanel";
import ChangePasswordModal from "@/features/auth/components/ChangePasswordModal";
import { useAuthStore } from "@/stores/authStore";

export default function DashboardLayout() {
  const user = useAuthStore((s) => s.user);

  return (
    <div className="app-shell">
      {user?.must_change_password && <ChangePasswordModal />}
      <Header />
      <TopNav />
      <div className="app-body">
        <SectionSidebar />
        <div className="app-content">
          <main className="content-main">
            <Outlet />
          </main>
        </div>
      </div>
      <CommandPalette />
      <AbbyPanel />
    </div>
  );
}
```

- [ ] **Step 2: Delete Sidebar.tsx**

```bash
rm frontend/src/components/layout/Sidebar.tsx
```

- [ ] **Step 3: Verify TypeScript compiles**

```bash
cd frontend && npx tsc --noEmit
```

Expected: no errors. If there are import errors from other files referencing Sidebar, fix them.

- [ ] **Step 4: Commit**

```bash
git add frontend/src/components/layouts/DashboardLayout.tsx
git rm frontend/src/components/layout/Sidebar.tsx
git commit -m "feat(layout): new shell — Header + TopNav + SectionSidebar, delete old Sidebar"
```

---

## Task 10: Build, deploy, verify

- [ ] **Step 1: Build frontend**

```bash
cd /home/smudoshi/Github/Aurora/frontend && npm run build
```

- [ ] **Step 2: Deploy**

```bash
cd /home/smudoshi/Github/Aurora && bash deploy.sh
```

- [ ] **Step 3: Verify deployment**

```bash
curl -s https://aurora.acumenus.net | grep "topnav\|TopNav\|section-sidebar"
```

- [ ] **Step 4: Visual checklist**

Open https://aurora.acumenus.net and verify:
- [ ] Login page: original Parthenon-era colors (teal accent, warm ivory text, crimson primary)
- [ ] After login: brand header with Aurora logo + wordmark
- [ ] Top nav bar below header with Dashboard, Clinical, Intelligence, Commons, Admin
- [ ] Hovering "Clinical" shows dropdown with Cases, Sessions, Patient Profiles, Decisions
- [ ] Section sidebar shows current section's pages
- [ ] Cards and panels visibly lift off the background (surface differentiation)
- [ ] No 64px rail sidebar anywhere
- [ ] Active states use green glow dot (no left border)

- [ ] **Step 5: Push**

```bash
git push
```

---

## Task Dependency Graph

```
Task 1 (font + infra) ────────────────────┐
Task 2 (surfaces + login isolation) ──────┤
Task 3 (navigation config) ──────────────┐│
                                           ▼▼
Task 4 (TopNav.tsx) ─────────────────────┐
Task 5 (SectionSidebar.tsx) ────────────┐│
Task 6 (layout.css) ───────────────────┐││
Task 7 (navigation.css) ──────────────┐│││
Task 8 (Header.tsx) ──────────────────┐││││
                                       ▼▼▼▼▼
Task 9 (DashboardLayout + delete Sidebar) ──┐
                                              ▼
Task 10 (build, deploy, verify) ─────── DONE
```

**Parallelism:**
- Tasks 1-2 can run in parallel (infrastructure)
- Task 3 must complete before Tasks 4-5 (they import from navigation.ts)
- Tasks 4-8 can run in parallel (independent components + CSS)
- Task 9 depends on all of 4-8 (assembles the shell)
- Task 10 runs last

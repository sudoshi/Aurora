# Aurora UI V2 Redress — Top Nav + Contextual Sidebar

**Date:** 2026-03-22
**Status:** Draft
**Scope:** Fix v1 redesign issues — replace 64px sidebar rail with top navigation + contextual sidebar, isolate login page from token changes, fix surface differentiation, resolve font/MIME issues

## Problem Statement

The v1 "Northern Light" redesign introduced several critical UX issues:

1. **64px sidebar rail is too narrow** — 9px labels are unreadable, "Aurora" truncates to "Auro", clinical users can't navigate efficiently
2. **Login page color bleed** — auth pages inherit app tokens via CSS variables, turning login's teal accents violet and warm ivory text cool blue-white
3. **Surface differentiation failure** — cold blue-black surfaces (#050510 → #0A0A18 → #10102A) are too close in luminance, creating a featureless dark void
4. **Corrupted JetBrains Mono font** — woff2 file has invalid sfntVersion
5. **CSP blocks Google Fonts** — style-src directive missing fonts.googleapis.com
6. **MIME type errors** — Apache serves some static assets as text/html via Laravel catch-all
7. **Active nav state still shows left border** — Parthenon pattern persists in some views

## Design Direction

Replace the sidebar-based navigation with a **top navigation bar + contextual section sidebar** pattern. This is the approach used by Linear, Figma, and modern EHR systems. It maximizes content width, keeps navigation scannable, and scales well as sections grow.

## Healthcare UX Rationale

- Clinicians prefer **visible, predictable navigation** over hidden/compact patterns
- Time-pressured users need **one-click access** to any section
- Grouped dropdowns reduce cognitive load while keeping everything discoverable
- Contextual sidebars provide **wayfinding within sections** without permanent horizontal space cost
- Consistent sidebar presence across all sections provides structural predictability

---

## 1. Navigation Architecture

### Brand Header (56px)

```
┌──────────────────────────────────────────────────────────────────────┐
│  [Aurora icon] Aurora          [Search... Ctrl+K]  [Abby] [🔔] [👤] │
└──────────────────────────────────────────────────────────────────────┘
```

- Left: Aurora logo icon (32x32) + "Aurora" wordmark (Inter 700, `--text-primary`)
- Center-right: Search bar (command palette trigger)
- Right: About Abby link, Abby sparkle icon, notification bell, user avatar dropdown
- Background: `--surface-raised` with `1px solid --border-default` bottom border
- Height: 56px fixed, sticky top

### Navigation Bar (44px)

```
┌──────────────────────────────────────────────────────────────────────┐
│  Dashboard    Clinical ▾    Intelligence ▾    Commons    Admin ▾     │
└──────────────────────────────────────────────────────────────────────┘
```

- Sits directly below brand header
- Background: `--surface-base` with `1px solid --border-default` bottom border
- Height: 44px
- Items are evenly spaced with padding, left-aligned
- Active section: text color `--primary` (#00D68F) + 2px bottom border with glow (`box-shadow: 0 2px 8px rgba(0, 214, 143, 0.4)`)
- Hover: text brightens to `--text-primary`
- Total header height: 56 + 44 = 100px

### Dropdown Menus

**Clinical dropdown:**
- Cases
- Sessions
- Patient Profiles
- Decisions

**Intelligence dropdown:**
- Imaging
- Genomics
- AI Copilot

**Admin dropdown:**
- Admin Dashboard
- System Health
- Users
- Audit Log
- Roles & Permissions
- AI Providers
- Notifications

**Dashboard** and **Commons** are direct links (no dropdown).

**Settings** moves to the user avatar dropdown menu (alongside Logout).

**Dropdown behavior:**
- Opens on hover after 100ms delay (prevents accidental triggers)
- Closes on mouse-leave after 150ms delay (allows diagonal mouse movement to menu)
- Also opens on click for touch devices
- Closes on click-outside or Escape
- Background: `--surface-overlay` with `backdrop-filter: blur(12px)`
- Border: `1px solid --border-default`
- Border-radius: `--radius-lg` (12px)
- Shadow: `--shadow-lg`
- Items: padding `8px 16px`, hover background `rgba(0, 214, 143, 0.06)`
- Active item: `--primary` text + green dot left indicator
- `role="menu"`, items are `role="menuitem"`

### Keyboard Navigation

- Tab moves between top-bar items
- Enter/Space opens dropdown
- Arrow Down enters dropdown from top bar label
- Arrow Up/Down moves within dropdown
- Escape closes dropdown, returns focus to top bar label
- Home/End jump to first/last dropdown item

---

## 2. Contextual Section Sidebar

Every section gets a sidebar showing that section's pages. This provides consistent visual structure.

### Layout

```
┌──────────────────────────────────────────────────────────────────────┐
│  Brand Header (56px)                                                 │
├──────────────────────────────────────────────────────────────────────┤
│  Navigation Bar (44px)                                               │
├────────────┬─────────────────────────────────────────────────────────┤
│  Section   │                                                         │
│  Sidebar   │  Main Content Area                                      │
│  (200px)   │                                                         │
│            │                                                         │
│  Dashboard │                                                         │
│  ●         │                                                         │
│            │                                                         │
│            │                                                         │
│            │                                                         │
└────────────┴─────────────────────────────────────────────────────────┘
```

### Specifications

- Width: `200px` fixed
- Background: `--surface-raised`
- Border-right: `1px solid --border-default`
- Padding: `var(--space-4)` top, `var(--space-2)` horizontal
- Position: fixed left, below top nav (top: 100px), height: `calc(100vh - 100px)`

### Sidebar Items

- Font: `--font-body`, `--text-sm` (13px)
- Default color: `--text-muted`
- Hover: `--text-primary`, background `rgba(255, 255, 255, 0.04)`
- Active: `--text-primary` + 4px green dot to the left
- Active background: `rgba(0, 214, 143, 0.06)`
- Padding: `8px 12px`
- Border-radius: `--radius-md` (8px)
- Icons: 16px, to the left of label, color matches text state
- No left border indicator (Parthenon's pattern — explicitly avoided)

### Section → Sidebar Mapping

| Top Nav Item | Sidebar Items |
|---|---|
| Dashboard | Dashboard (single item) |
| Clinical > Cases | Cases (single item) |
| Clinical > Sessions | Sessions (single item) |
| Clinical > Patient Profiles | Patient Profiles (single item) |
| Clinical > Decisions | Decisions (single item) |
| Intelligence > Imaging | Imaging (single item) |
| Intelligence > Genomics | Genomics (single item) |
| Intelligence > AI Copilot | AI Copilot (single item) |
| Commons | Commons (single item) |
| Admin | Admin Dashboard, System Health, Users, Audit Log, Roles & Permissions, AI Providers, Notifications |

For single-item sections, the sidebar shows just that one item highlighted. This provides visual consistency — the sidebar is always present, always in the same place.

### Responsive Behavior

- Below 1024px: sidebar collapses, content goes full-width
- Below 768px: top nav collapses to hamburger menu

---

## 3. Login Page Isolation

The auth pages (`AuthLayout.tsx`, `LoginPage.tsx`, `RegisterPage.tsx`) must render with their **original Parthenon-era visual design** regardless of what the app tokens say.

### Implementation

Add a CSS scope block at the top of `auth-layout.css` that overrides all tokens used within `.auth-layout`:

```css
.auth-layout {
  /* Pin original auth page colors — immune to app token changes */
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

This is a pure CSS fix — zero changes to `AuthLayout.tsx`, `LoginPage.tsx`, or `RegisterPage.tsx`.

---

## 4. Surface Differentiation

Increase luminance gaps between surface levels so cards and panels visibly lift off the background.

### Updated Surface Stack

```css
--surface-darkest:   #050510;   /* unchanged */
--surface-base:      #080816;   /* was #0A0A18 — slightly darker base */
--surface-raised:    #12122E;   /* was #10102A — brighter, cards lift */
--surface-overlay:   #1A1A42;   /* was #16163A — dropdowns clearly float */
--surface-elevated:  #222250;   /* was #1C1C48 — modals pop */
--surface-accent:    #2A2A60;   /* was #222256 — interactive elements */
--surface-highlight: #323270;   /* was #2A2A60 — hover states */
```

The key change is increasing the **gap between base and raised** — this is what makes cards, panels, and the section sidebar visually distinct from the page background.

---

## 5. Font & Infrastructure Fixes

### JetBrains Mono Re-download

The current woff2 file is corrupted (invalid sfntVersion). Re-download from the official release:

```bash
curl -L -o frontend/public/fonts/JetBrainsMono-Variable.woff2 \
  "https://github.com/JetBrains/JetBrainsMono/releases/download/v2.304/JetBrainsMono-2.304.zip"
# Extract the variable woff2 from the zip
```

Or download directly from Google Fonts API.

### Apache Static Asset Configuration

Add directives to prevent Laravel's catch-all from intercepting static files:

```apache
# In aurora.acumenus.net-le-ssl.conf, inside <Directory>
<FilesMatch "\.(js|css|woff2|woff|ttf|png|jpg|jpeg|svg|ico|json)$">
    SetHandler none
</FilesMatch>
```

### CSP Headers

Add to Apache config or Laravel middleware:

```
Content-Security-Policy: style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
font-src 'self' https://fonts.gstatic.com;
```

---

## 6. Files Affected

### New Files
| File | Purpose |
|---|---|
| `frontend/src/components/layout/TopNav.tsx` | Top navigation bar with grouped dropdowns |
| `frontend/src/components/layout/SectionSidebar.tsx` | Contextual section sidebar |
| `frontend/src/config/navigation.ts` | Navigation structure config (sections, items, groups) |

### Rewrites
| File | Changes |
|---|---|
| `frontend/src/styles/components/layout.css` | Remove sidebar rail/flyout, add top nav + section sidebar + new content area |
| `frontend/src/components/layouts/DashboardLayout.tsx` | New shell: TopNav + SectionSidebar + content |
| `frontend/src/components/layout/Header.tsx` | Becomes the brand header row only (logo + search + user) |

### Targeted Edits
| File | Changes |
|---|---|
| `frontend/src/styles/tokens-dark.css` | Brighten surface stack (Section 4) |
| `frontend/src/features/auth/components/auth-layout.css` | Add token override scope (Section 3) |
| `frontend/src/components/layout/Sidebar.tsx` | Delete (replaced by TopNav + SectionSidebar) |
| `frontend/src/styles/components/navigation.css` | Remove rail icon styles, add top nav + dropdown + section sidebar item styles |
| `frontend/public/fonts/JetBrainsMono-Variable.woff2` | Re-download (corrupted) |

### Infrastructure
| File | Changes |
|---|---|
| Apache vhost config | Add static file handler + CSP headers |
| `deploy.sh` | Already fixed in previous commit |

### Unchanged
| File | Reason |
|---|---|
| `frontend/src/features/auth/**` | Login page stays as-is (CSS scope isolates it) |
| `frontend/src/styles/tokens-base.css` | Typography changes from v1 are good (Inter, JetBrains Mono, 15px base) |
| `frontend/src/styles/components/cards.css` | Glass constellation panels are good |
| `frontend/src/styles/components/forms.css` | Green buttons, violet focus ring are good |
| All feature page TSX files | v1 color sweep was correct |

---

## 7. Accessibility Checklist

- [ ] Top nav keyboard navigable (Tab, Enter, Arrow keys, Escape)
- [ ] Dropdown menus have `role="menu"`, items are `role="menuitem"`
- [ ] Active section announced to screen readers via `aria-current="page"`
- [ ] Section sidebar items keyboard navigable
- [ ] Touch devices: dropdowns open on click (no hover)
- [ ] All animations respect `prefers-reduced-motion`
- [ ] Minimum 44px touch targets on nav items
- [ ] Color contrast maintained with brighter surface stack

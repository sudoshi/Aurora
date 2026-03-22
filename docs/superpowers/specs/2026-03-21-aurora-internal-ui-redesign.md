# Aurora Internal Application UI Redesign

**Date:** 2026-03-21
**Status:** Draft
**Scope:** Full visual overhaul of Aurora's internal application UI to establish a unique identity distinct from Parthenon

## Problem Statement

Aurora's internal application UI is a near-clone of Parthenon: same surface depth stack, same 3px left border active state, same card shimmer `::before` trick, same sidebar structure, same crimson+teal palette (vs Parthenon's crimson+gold). The result is a reskin, not a new product identity.

The login page — with its full-bleed aurora borealis photography, luminous typography, and ethereal mood — establishes a visual promise that the internal app doesn't deliver on.

## Design Direction

**"Northern Light — Luminous and Ethereal"**

The aurora borealis is the brand. The internal UI should evoke the same awe: deep space-dark surfaces, luminous green and violet accents that feel like they're emitting light, generous breathing room, and a sense of floating in an open sky.

## Healthcare Best Practices

All design decisions are constrained by clinical UI standards:

- **WCAG AAA contrast (7:1)** for all body text on surfaces
- **Aurora green used for accents/borders/indicators only** — never as body text color. All readable text uses the cool white scale
- **Red reserved exclusively** for alerts, errors, and clinical warnings — never for branding or decoration
- **Color is never the sole indicator** — all status states pair color with icons, labels, or patterns
- **`prefers-reduced-motion` respected** — all animations disabled when user prefers reduced motion
- **High-contrast mode** — design tokens include `--hc-` override set
- **Minimum 12px (0.75rem)** for all readable text including labels
- **15px (0.9375rem) base font** — clinical users work on large monitors, often at distance

---

## 1. Color System — "Northern Sky"

### Primary — Aurora Green (replaces Parthenon crimson)

```css
--primary:          #00D68F;
--primary-light:    #33E0A8;
--primary-dark:     #00A56E;
--primary-darker:   #008555;
--primary-glow:     rgba(0, 214, 143, 0.35);
--primary-bg:       rgba(0, 214, 143, 0.12);
--primary-border:   rgba(0, 214, 143, 0.25);
```

### Accent — Aurora Violet (replaces Parthenon gold/teal)

```css
--accent:           #8B5CF6;
--accent-light:     #A78BFA;
--accent-dark:      #6D28D9;
--accent-pale:      rgba(139, 92, 246, 0.15);
--accent-bg:        rgba(139, 92, 246, 0.10);
--accent-glow:      rgba(139, 92, 246, 0.30);
```

### Secondary — Aurora Cyan (new, no Parthenon equivalent)

```css
--secondary:        #22D3EE;
--secondary-light:  #67E8F9;
--secondary-dark:   #06B6D4;
--secondary-bg:     rgba(34, 211, 238, 0.10);
--secondary-glow:   rgba(34, 211, 238, 0.25);
```

### Surfaces — Cold Space Black (replaces warm grey-black)

```css
--surface-darkest:   #050510;   /* hint of blue */
--surface-base:      #0A0A18;   /* cold midnight */
--surface-raised:    #10102A;   /* deep indigo-black */
--surface-overlay:   #16163A;
--surface-elevated:  #1C1C48;
--surface-accent:    #222256;
--surface-highlight: #2A2A60;

--sidebar-bg:        #060612;
--sidebar-bg-light:  #0C0C1E;
```

### Text — Cool White (replaces warm ivory)

```css
--text-primary:   #E8ECF4;   /* cool blue-white */
--text-secondary: #B4BAC8;
--text-muted:     #7A8298;
--text-ghost:     #4A5068;
--text-disabled:  #3A3E50;
```

### Semantic Colors (tuned brighter for cold surfaces)

```css
/* Critical / Error — reserved for clinical alerts */
--critical:        #F0607A;
--critical-dark:   #D44A62;
--critical-light:  #FF7A92;
--critical-bg:     rgba(240, 96, 122, 0.15);
--critical-border: rgba(240, 96, 122, 0.30);

/* Warning */
--warning:         #F0B040;
--warning-dark:    #D49A2A;
--warning-light:   #F5C060;
--warning-bg:      rgba(240, 176, 64, 0.15);
--warning-border:  rgba(240, 176, 64, 0.30);

/* Success */
--success:         #34D9A0;
--success-dark:    #22B880;
--success-light:   #50E8B8;
--success-bg:      rgba(52, 217, 160, 0.15);
--success-border:  rgba(52, 217, 160, 0.30);

/* Info */
--info:            #60A5FA;
--info-dark:       #4A94E8;
--info-light:      #78B4FF;
--info-bg:         rgba(96, 165, 250, 0.15);
--info-border:     rgba(96, 165, 250, 0.30);
```

### Borders

```css
--border-default: rgba(255, 255, 255, 0.06);
--border-subtle:  rgba(255, 255, 255, 0.03);
--border-hover:   rgba(139, 92, 246, 0.20);   /* violet tint on hover */
--border-focus:   rgba(139, 92, 246, 0.40);
--border-active:  rgba(0, 214, 143, 0.30);
```

### Focus Ring

```css
--focus-ring: 0 0 0 3px rgba(139, 92, 246, 0.25);   /* violet, not gold */
```

### Glassmorphism (recalibrated for cold surfaces)

```css
--glass-00: rgba(255, 255, 255, 0.02);
--glass-01: rgba(255, 255, 255, 0.04);
--glass-02: rgba(255, 255, 255, 0.06);
--glass-03: rgba(255, 255, 255, 0.08);
--glass-04: rgba(255, 255, 255, 0.12);
--glass-05: rgba(255, 255, 255, 0.16);
```

### Gradients

```css
--gradient-panel:        linear-gradient(135deg, rgba(255,255,255,0.04) 0%, rgba(255,255,255,0.01) 100%);
--gradient-panel-raised: linear-gradient(135deg, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0.02) 100%);
--gradient-panel-inset:  linear-gradient(135deg, rgba(0,0,0,0.30) 0%, rgba(0,0,0,0.10) 100%);
--gradient-aurora:       linear-gradient(135deg, #00D68F, #8B5CF6);
--gradient-aurora-cyan:  linear-gradient(135deg, #00D68F, #22D3EE);
--gradient-primary:      linear-gradient(135deg, #00D68F, #00A56E);
```

---

## 2. Layout — "Open Sky" Shell

### Sidebar → 64px Icon Rail + Flyout

**Rail (always visible):**
- Width: `64px` fixed
- Background: `--sidebar-bg` (#060612)
- Subtle vertical gradient at bottom edge: transparent → `rgba(0, 214, 143, 0.03)` (aurora light on the horizon)
- Contains: brand icon (top), nav icons (middle), user avatar (bottom)
- No full-width expanded state — the rail is the sidebar

**Flyout Panel (on demand):**
- Triggered by: hover or click on a rail icon that has children
- Width: `240px`, slides out from right edge of rail
- Background: glass treatment (`rgba(10, 10, 24, 0.85)` + `backdrop-filter: blur(16px)`)
- Border: `1px solid rgba(255, 255, 255, 0.06)`
- Border-radius: `0 16px 16px 0`
- Contains: section title + child nav items (text only, no icons)
- Auto-closes on navigation or click-outside
- Transition: `transform: translateX` + `ease-out` over 200ms

**Active State (rail icon):**
- Icon color: `--primary` (#00D68F)
- Below icon: 4px diameter glowing dot, centered
- Dot: `background: #00D68F; box-shadow: 0 0 6px rgba(0, 214, 143, 0.6); border-radius: 50%`
- No left border (Parthenon's signature — explicitly avoided)

**Active State (flyout child):**
- Text color: `--text-primary` (white)
- 4px violet dot to the left of text
- Background: `rgba(139, 92, 246, 0.08)`

### Header → Transparent Frosted Bar

```css
.app-header {
  position: sticky;
  top: 0;
  z-index: var(--z-topbar);
  height: 56px;
  background: rgba(10, 10, 24, 0.6);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  /* border becomes visible only when content scrolls beneath */
}
```

- Left: page title (Inter, 600 weight, `--text-primary`)
- Right: command palette trigger (`Cmd+K` badge), notification bell with count badge, user avatar circle
- No "Aurora" wordmark in header (it's in the sidebar rail)

### Content Area

```css
.content-main {
  padding: 32px;
  max-width: 1800px;
}
```

- More breathing room than Parthenon's `24px` padding
- Panels have `16px` gap between them (up from `16px` — keeping consistent)

---

## 3. Card & Panel System — "Glass Constellation"

### Base Panel

```css
.panel {
  background: linear-gradient(
    135deg,
    rgba(16, 16, 42, 0.8) 0%,
    rgba(16, 16, 42, 0.6) 100%
  );
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 16px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.50);
  padding: 20px;
  position: relative;
  overflow: hidden;
  transition: border-color 200ms ease-out, box-shadow 200ms ease-out;
}

/* NO ::before shimmer line — that's Parthenon's signature */

.panel:hover {
  border-color: rgba(139, 92, 246, 0.20);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.50), 0 0 20px rgba(0, 214, 143, 0.06);
}
```

### Metric Card

```css
.metric-card .metric-value {
  font-size: var(--text-4xl);
  font-weight: 600;
  background: linear-gradient(135deg, #00D68F, #22D3EE);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1.1;
}

.metric-card:hover {
  border-image: linear-gradient(135deg, rgba(0,214,143,0.3), rgba(139,92,246,0.3)) 1;
  /* NO translateY — that's Parthenon's move */
}
```

### Panel Variants

- **`.panel-inset`**: `background: --surface-darkest`, `box-shadow: inset`, no glass. For embedded sub-sections.
- **`.panel-highlight`**: 2px left-edge gradient strip (green→violet vertical gradient) for key clinical data. Not a solid border.
- **`.panel-clinical-alert`**: semantic red/yellow border + background per severity. Accessible contrast. Icon + text label required (not color-only).

### Data Tables

```css
/* Row hover */
.data-table tbody tr:hover {
  background: rgba(0, 214, 143, 0.04);
}

/* Selected row */
.data-table tbody tr.selected {
  background: rgba(139, 92, 246, 0.08);
  border-left: 2px solid var(--accent);
}

/* Header */
.data-table thead th {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: var(--text-ghost);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}
```

---

## 4. Navigation & Interaction Patterns

### Buttons

```css
.btn-primary {
  background: linear-gradient(135deg, #00D68F, #00A56E);
  color: #050510;
  border: none;
  font-weight: 600;
}
.btn-primary:hover:not(:disabled) {
  box-shadow: 0 4px 20px rgba(0, 214, 143, 0.35);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.04);
  color: var(--text-secondary);
  border: 1px solid rgba(255, 255, 255, 0.08);
}
.btn-secondary:hover:not(:disabled) {
  border-color: rgba(139, 92, 246, 0.25);
  color: var(--text-primary);
}

.btn-ghost {
  background: transparent;
  color: var(--text-secondary);
}
.btn-ghost:hover:not(:disabled) {
  background: rgba(0, 214, 143, 0.06);
  color: var(--text-primary);
}

.btn-danger {
  /* Unchanged — red is red in healthcare */
  background: rgba(240, 96, 122, 0.15);
  color: #FF7A92;
  border: 1px solid rgba(240, 96, 122, 0.30);
}
```

### Tabs

```css
.tab-item.active {
  color: var(--primary);
}
.tab-item.active::after {
  content: '';
  position: absolute;
  bottom: -1px;
  left: 0;
  right: 0;
  height: 2px;
  background: var(--primary);
  border-radius: 9999px 9999px 0 0;
  box-shadow: 0 2px 8px rgba(0, 214, 143, 0.4);  /* glowing underline */
}
```

### Focus States

```css
:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.25);  /* violet ring */
}
```

### Page Load Animation

```css
.panel, .metric-card {
  animation: fadeInUp 300ms var(--ease-out) both;
}

/* Staggered: each sibling delays 50ms */
.panel:nth-child(1) { animation-delay: 0ms; }
.panel:nth-child(2) { animation-delay: 50ms; }
.panel:nth-child(3) { animation-delay: 100ms; }
/* ... up to ~8 children */

@media (prefers-reduced-motion: reduce) {
  .panel, .metric-card {
    animation: none;
  }
}
```

---

## 5. Typography

### Font Stack Changes

```css
--font-display: 'Inter', 'Helvetica Neue', sans-serif;  /* NEW — headings/brand */
--font-body:    'Source Sans 3', 'Helvetica Neue', sans-serif;  /* unchanged */
--font-mono:    'JetBrains Mono', Consolas, monospace;  /* replaces IBM Plex Mono */
```

### Usage

| Context | Parthenon | Aurora |
|---|---|---|
| Brand wordmark | IBM Plex Mono, 500 | Inter, 700, letter-spacing -0.03em |
| Page titles | font-body, 600 | font-display (Inter), 600 |
| Metric values | font-body, 600 | font-display (Inter), 600 |
| Body text | Source Sans 3 | Source Sans 3 (unchanged) |
| Code/data | IBM Plex Mono | JetBrains Mono |
| Labels | font-body, uppercase | font-body, uppercase (unchanged) |

### Scale

```css
--text-base: 0.9375rem;  /* 15px, up from 14px — healthcare accessibility */
```

All other scale values remain proportional. Minimum readable size: `0.75rem` (12px).

---

## 6. Iconography

- **Library:** Lucide (unchanged)
- **Active state:** icon color `--primary` + soft glow
- **Clinical domain icons:** semantic colors tuned to new palette
  - Condition: `--critical` (red)
  - Drug: `--info` (blue)
  - Measurement: `--primary` (aurora green)
  - Visit: `--accent` (violet)
  - Observation: `#A78BFA` (light violet)
  - Procedure: `#F472B6` (pink)
  - Device: `#FB923C` (orange)
  - Death: `--critical` (red)

---

## 7. Signature Differentiators — Aurora vs. Parthenon

| Element | Parthenon | Aurora |
|---|---|---|
| Surface tone | Warm grey-black (#08080A) | Cold blue-black (#050510) |
| Primary color | Dark crimson (#9B1B30) | Aurora green (#00D68F) |
| Accent color | Research gold (#C9A227) | Aurora violet (#8B5CF6) |
| Third color | None | Aurora cyan (#22D3EE) |
| Text warmth | Ivory (#F0EDE8) | Cool blue-white (#E8ECF4) |
| Sidebar | Full 260px, collapsible to 72px | 64px rail + 240px flyout |
| Active nav | 3px left crimson border | Glowing green dot beneath icon |
| Card top edge | 1px shimmer `::before` line | None — clean glass |
| Card hover | translateY(-1px) + gold border | Violet border glow + green outer glow |
| Tab active | Solid 2px gold underline | Glowing green underline with box-shadow |
| Brand font | IBM Plex Mono | Inter |
| Code font | IBM Plex Mono | JetBrains Mono |
| Button primary | Crimson gradient | Green gradient |
| Focus ring | Gold | Violet |
| Metric values | Single-color text | Green→cyan gradient text |
| Base font size | 14px | 15px (healthcare accessibility) |
| Overall feel | Data research cockpit | Clinical sky observatory |

---

## 8. Files Affected

### Token Rewrites (complete replacement)
- `frontend/src/styles/tokens-dark.css` — entire color system
- `frontend/src/styles/tokens-base.css` — typography, radius, shadow updates

### Component CSS Rewrites
- `frontend/src/styles/components/layout.css` — sidebar rail + flyout, header, content area
- `frontend/src/styles/components/navigation.css` — rail icons, flyout items, tabs, active states
- `frontend/src/styles/components/cards.css` — panel + metric-card glass treatment
- `frontend/src/styles/components/forms.css` — button variants, focus states, inputs

### Component TSX Changes
- `frontend/src/components/layout/Sidebar.tsx` — rewrite to rail + flyout architecture
- `frontend/src/components/navigation/TopNavigation.tsx` — transparent header, new layout
- `frontend/src/components/layouts/DashboardLayout.tsx` — updated content area margins
- `frontend/src/components/layout/Header.tsx` — frosted header with blur

### New Dependencies
- `Inter` font (Google Fonts or self-hosted)
- `JetBrains Mono` font (Google Fonts or self-hosted)

### Unchanged
- `frontend/src/features/auth/` — login page stays as-is (user confirmed)
- All business logic, API calls, state management — zero changes
- Component structure and feature organization — unchanged

---

## 9. Accessibility Checklist

- [ ] All text meets WCAG AAA (7:1) contrast on its background
- [ ] Primary green never used as text color for body copy
- [ ] Red used exclusively for errors/alerts/clinical warnings
- [ ] All status indicators use color + icon + label (never color alone)
- [ ] Focus ring visible on all interactive elements
- [ ] All animations respect `prefers-reduced-motion: reduce`
- [ ] Minimum text size 12px (0.75rem) for all readable content
- [ ] Tab order preserved in rail + flyout sidebar
- [ ] Flyout dismissible via Escape key
- [ ] Screen reader announces flyout open/close state
- [ ] High-contrast token overrides available

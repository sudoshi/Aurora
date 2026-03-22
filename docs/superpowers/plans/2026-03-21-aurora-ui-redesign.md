# Aurora Internal UI Redesign — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace Aurora's Parthenon-cloned visual identity with a unique "Northern Light" aesthetic — cold blue-black surfaces, aurora green/violet/cyan palette, 64px sidebar rail with flyout, glass constellation cards, Inter + JetBrains Mono typography.

**Architecture:** Pure visual overhaul. Zero business logic changes. Token files rewritten, component CSS updated, Sidebar component rebuilt as rail+flyout, Header made transparent/frosted. All other TSX components pick up changes via CSS token cascade.

**Tech Stack:** CSS custom properties, React/TypeScript, self-hosted Inter + JetBrains Mono woff2 fonts.

**Spec:** `docs/superpowers/specs/2026-03-21-aurora-internal-ui-redesign.md`

---

## File Map

### Complete Rewrites
| File | Responsibility |
|---|---|
| `frontend/src/styles/tokens-dark.css` | Entire color system — primary, accent, surfaces, text, semantic, borders, glass, gradients, domain, chart |
| `frontend/src/styles/tokens-base.css` | Typography stacks, type scale, spacing, radius, shadows, motion, text utilities |
| `frontend/src/styles/components/layout.css` | App shell — sidebar rail, flyout, frosted header, content area |
| `frontend/src/styles/components/navigation.css` | Rail icons, flyout items, tabs, breadcrumbs, search, filter chips |
| `frontend/src/styles/components/cards.css` | Panel + metric card glass treatment |
| `frontend/src/styles/components/forms.css` | Buttons, inputs, selects, toggles, focus states |
| `frontend/src/components/layout/Sidebar.tsx` | Rail + flyout architecture (replaces collapsible sidebar) |

### Targeted Edits
| File | Changes |
|---|---|
| `frontend/src/styles/components/tables.css` | Row hover, selected, header color, sorted column |
| `frontend/src/styles/components/badges.css` | Replace hardcoded Parthenon rgba values with tokens |
| `frontend/src/styles/components/alerts.css` | Mention highlight color, progress fill accent |
| `frontend/src/styles/components/modals.css` | No hardcoded colors found — cascades via tokens automatically |
| `frontend/src/styles/components/ai.css` | AI send button uses `--primary` — cascades automatically |
| `frontend/src/styles/app.css` | Add `@font-face`, update selection color |
| `frontend/src/components/layout/Header.tsx` | Remove hardcoded Parthenon colors, apply frosted header class |
| `frontend/src/components/layouts/DashboardLayout.tsx` | Remove sidebar-collapsed class logic (rail is fixed-width) |
| `frontend/src/stores/uiStore.ts` | Remove `sidebarOpen` / `toggleSidebar` (rail doesn't collapse) |

### New Files
| File | Purpose |
|---|---|
| `frontend/public/fonts/Inter-Variable.woff2` | Self-hosted Inter variable font |
| `frontend/public/fonts/JetBrainsMono-Variable.woff2` | Self-hosted JetBrains Mono variable font |

### Unchanged (verified)
- `frontend/src/features/auth/` — login page stays as-is
- `frontend/src/components/layout/CommandPalette.tsx` — uses CSS classes, cascades via tokens
- `frontend/src/components/layout/AbbyPanel.tsx` — has one hardcoded `#0E0E11` in history panel, minor fix

---

## Task 1: Download and self-host fonts

**Files:**
- Create: `frontend/public/fonts/Inter-Variable.woff2`
- Create: `frontend/public/fonts/JetBrainsMono-Variable.woff2`

- [ ] **Step 1: Create fonts directory and download Inter**

```bash
mkdir -p frontend/public/fonts
curl -L -o frontend/public/fonts/Inter-Variable.woff2 \
  "https://github.com/rsms/inter/raw/master/docs/font-files/InterVariable.woff2"
```

- [ ] **Step 2: Download JetBrains Mono**

```bash
curl -L -o frontend/public/fonts/JetBrainsMono-Variable.woff2 \
  "https://github.com/JetBrains/JetBrainsMono/raw/master/fonts/variable/JetBrainsMono%5Bwght%5D.woff2"
```

If the direct links fail, download from Google Fonts or the official repos and place the variable woff2 files at the paths above.

- [ ] **Step 3: Verify fonts are accessible**

```bash
ls -la frontend/public/fonts/
```

Expected: two `.woff2` files, each 100-300KB.

- [ ] **Step 4: Commit**

```bash
git add frontend/public/fonts/
git commit -m "chore: add self-hosted Inter and JetBrains Mono variable fonts"
```

---

## Task 2: Rewrite tokens-dark.css — Color system

**Files:**
- Rewrite: `frontend/src/styles/tokens-dark.css`

- [ ] **Step 1: Replace the entire color system**

Replace the full contents of `tokens-dark.css` with the new "Northern Sky" palette from the spec (Section 1). The file should contain ALL of these token families:

1. Primary — Aurora Green (`#00D68F` family)
2. Accent — Aurora Violet (`#9D75F8` family, including `--accent-lighter`, `--accent-muted`)
3. Secondary — Aurora Cyan (`#22D3EE` family)
4. Surfaces — Cold Space Black (`#050510` → `#2A2A60`)
5. Text — Cool White (`#E8ECF4` scale)
6. Semantic — Critical, Warning, Success (`#2DD4BF`), Info
7. Semantic Glow tokens
8. Borders (rgba-based, violet hover)
9. Focus Ring (violet)
10. Glassmorphism + blur + glass-dark
11. Gradients (including `--gradient-aurora`, replacing `--gradient-crimson`/`--gradient-teal`)
12. Domain status tokens (dqd, job, cohort, source, ai)
13. Chart categorical
14. OMOP domain colors (remapped: Condition→critical, Measurement→primary, Visit→accent)
15. High-contrast `@media (prefers-contrast: more)` block

Copy every value directly from spec Sections 1 and 10.

- [ ] **Step 2: Verify no old Parthenon colors remain**

```bash
grep -n "9B1B30\|C9A227\|2A9D8F\|08080A\|0E0E11\|F0EDE8\|C5C0B8" frontend/src/styles/tokens-dark.css
```

Expected: zero matches.

- [ ] **Step 3: Commit**

```bash
git add frontend/src/styles/tokens-dark.css
git commit -m "feat(tokens): rewrite color system — Northern Sky palette"
```

---

## Task 3: Rewrite tokens-base.css — Typography, scale, shadows

**Files:**
- Rewrite: `frontend/src/styles/tokens-base.css`

- [ ] **Step 1: Update font stacks**

Replace the font stack section:

```css
--font-display: 'Inter', 'Helvetica Neue', sans-serif;
--font-body:    'Source Sans 3', 'Helvetica Neue', sans-serif;
--font-mono:    'JetBrains Mono', Consolas, monospace;
```

- [ ] **Step 2: Update type scale**

```css
--text-xs:   0.75rem;    /* 12px — healthcare minimum */
--text-sm:   0.8125rem;  /* 13px */
--text-base: 0.9375rem;  /* 15px */
--text-md:   1rem;       /* 16px */
--text-lg:   1.125rem;   /* 18px */
--text-xl:   1.25rem;    /* 20px */
--text-2xl:  1.5rem;     /* 24px */
--text-3xl:  1.875rem;   /* 30px */
--text-4xl:  2.25rem;    /* 36px */
--text-5xl:  3rem;       /* 48px */
--text-6xl:  3.5rem;     /* 56px */
```

- [ ] **Step 3: Update layout variables**

```css
--sidebar-width:           64px;    /* was 260px — now the rail width */
--sidebar-width-collapsed: 64px;    /* same as width — rail doesn't collapse */
--content-max-width:       1800px;  /* was 1600px */
--content-padding:         var(--space-8); /* 32px, was --space-6 (24px) */
```

- [ ] **Step 4: Update border radius**

```css
--radius-xl:  16px;  /* was 16px — unchanged but verify */
--radius-2xl: 24px;  /* was 24px — unchanged */
```

- [ ] **Step 5: Update text utilities to use --font-display**

```css
.text-panel-title { font-family: var(--font-display); font-size: var(--text-xl); font-weight: 600; color: var(--text-primary); }
.text-section     { font-family: var(--font-display); font-size: var(--text-2xl); color: var(--text-primary); }
.text-value       { font-family: var(--font-display); font-size: var(--text-3xl); color: var(--text-primary); }
```

- [ ] **Step 6: Commit**

```bash
git add frontend/src/styles/tokens-base.css
git commit -m "feat(tokens): update typography, scale, and layout variables"
```

---

## Task 4: Update app.css — Font faces, selection, scrollbar

**Files:**
- Modify: `frontend/src/styles/app.css`

- [ ] **Step 1: Add @font-face declarations before the @import lines**

Add at the very top of app.css, before `@import "tailwindcss"`:

```css
@font-face {
  font-family: 'Inter';
  src: url('/fonts/Inter-Variable.woff2') format('woff2');
  font-weight: 100 900;
  font-display: swap;
}

@font-face {
  font-family: 'JetBrains Mono';
  src: url('/fonts/JetBrainsMono-Variable.woff2') format('woff2');
  font-weight: 100 800;
  font-display: swap;
}
```

- [ ] **Step 2: Update the `::selection` block**

```css
::selection {
  background-color: rgba(157, 117, 248, 0.30);
  color: var(--text-primary);
}
```

- [ ] **Step 3: Add prefers-reduced-motion global rule**

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

- [ ] **Step 4: Commit**

```bash
git add frontend/src/styles/app.css
git commit -m "feat: add Inter/JetBrains Mono font-faces, violet selection, reduced-motion"
```

---

## Task 5: Rewrite layout.css — Sidebar rail, frosted header, content area

**Files:**
- Rewrite: `frontend/src/styles/components/layout.css`

- [ ] **Step 1: Replace sidebar styles with rail**

Remove `.app-sidebar` (260px, collapsible) and replace with:

```css
.app-sidebar {
  position: fixed;
  left: 0;
  top: 0;
  z-index: var(--z-sidebar);
  height: 100vh;
  width: 64px;
  background-color: var(--sidebar-bg);
  border-right: 1px solid var(--border-default);
  display: flex;
  flex-direction: column;
  align-items: center;
  overflow: visible; /* flyout must overflow */
  /* subtle aurora glow at bottom */
  background-image: linear-gradient(to bottom, transparent 80%, rgba(0, 214, 143, 0.03) 100%);
}
/* Remove .app-sidebar.collapsed — rail doesn't collapse */
```

- [ ] **Step 2: Add flyout panel styles**

```css
.sidebar-flyout {
  position: absolute;
  left: 64px;
  top: 0;
  width: 240px;
  height: 100vh;
  background: rgba(10, 10, 24, 0.85);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-left: none;
  border-radius: 0 16px 16px 0;
  transform: translateX(-100%);
  opacity: 0;
  transition: transform 200ms var(--ease-out), opacity 200ms var(--ease-out);
  z-index: var(--z-sidebar);
  padding: var(--space-4) 0;
  overflow-y: auto;
  pointer-events: none;
}
.sidebar-flyout.open {
  transform: translateX(0);
  opacity: 1;
  pointer-events: auto;
}
```

- [ ] **Step 3: Update sidebar header for rail**

```css
.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: center;
  height: var(--topbar-height);
  padding: 0;
  border-bottom: 1px solid var(--border-default);
  flex-shrink: 0;
  width: 100%;
}
```

- [ ] **Step 4: Replace topbar with frosted header**

```css
.app-topbar {
  position: sticky;
  top: 0;
  z-index: var(--z-topbar);
  height: var(--topbar-height);
  background: rgba(10, 10, 24, 0.6);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--space-6);
  flex-shrink: 0;
}
```

- [ ] **Step 5: Update content area margin**

```css
.app-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  margin-left: 64px; /* fixed rail width */
}
/* Remove .app-content.sidebar-collapsed */

.content-main {
  flex: 1;
  overflow-y: auto;
  padding: var(--content-padding);
  max-width: var(--content-max-width);
}
```

- [ ] **Step 6: Update page header to use font-display**

```css
.page-title {
  font-family: var(--font-display);
  font-size: var(--text-2xl);
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}
```

- [ ] **Step 7: Commit**

```bash
git add frontend/src/styles/components/layout.css
git commit -m "feat(layout): sidebar rail + flyout, frosted header, wider content area"
```

---

## Task 6: Rewrite navigation.css — Rail icons, flyout items, tabs

**Files:**
- Rewrite: `frontend/src/styles/components/navigation.css`

- [ ] **Step 1: Replace nav-item with rail icon styles**

```css
/* Rail icon */
.nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  width: 48px;
  height: 48px;
  border-radius: var(--radius-md);
  color: var(--text-ghost);
  cursor: pointer;
  transition: color var(--duration-fast), background var(--duration-fast);
  border: none;
  background: transparent;
  position: relative;
  text-decoration: none;
}
.nav-item:hover {
  color: var(--text-secondary);
  background: rgba(0, 214, 143, 0.08);
}
.nav-item.active {
  color: var(--primary);
}
/* Glowing dot beneath active icon — NOT a left border */
.nav-item.active::after {
  content: '';
  position: absolute;
  bottom: 4px;
  left: 50%;
  transform: translateX(-50%);
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: var(--primary);
  box-shadow: 0 0 6px rgba(0, 214, 143, 0.6);
}
.nav-item .nav-icon {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
}
.nav-item .nav-label {
  font-size: 9px;
  letter-spacing: 0.02em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 56px;
  text-align: center;
}
```

- [ ] **Step 2: Add flyout child item styles**

```css
/* Flyout child item */
.flyout-title {
  font-family: var(--font-display);
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--text-primary);
  padding: var(--space-3) var(--space-4);
  letter-spacing: -0.01em;
}
.flyout-item {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-2) var(--space-4);
  font-size: var(--text-sm);
  color: var(--text-muted);
  text-decoration: none;
  transition: color var(--duration-fast), background var(--duration-fast);
  cursor: pointer;
  position: relative;
}
.flyout-item:hover {
  color: var(--text-primary);
  background: rgba(255, 255, 255, 0.04);
}
.flyout-item.active {
  color: var(--text-primary);
  background: rgba(157, 117, 248, 0.08);
}
/* Violet dot indicator for active flyout child */
.flyout-item.active::before {
  content: '';
  position: absolute;
  left: var(--space-2);
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: var(--accent);
}
```

- [ ] **Step 3: Update tab styles with glowing underline**

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
  border-radius: var(--radius-full) var(--radius-full) 0 0;
  box-shadow: 0 2px 8px rgba(0, 214, 143, 0.4);
}
```

- [ ] **Step 4: Update sorted column and pagination active to use accent**

In the search-bar section, keep existing styles — they cascade from tokens. For filter-chip `.active`, update:

```css
.filter-chip.active {
  background: var(--accent-bg);
  border-color: var(--accent);
  color: var(--accent-light);
}
```

- [ ] **Step 5: Commit**

```bash
git add frontend/src/styles/components/navigation.css
git commit -m "feat(nav): rail icons with glow dots, flyout items, glowing tab underline"
```

---

## Task 7: Rewrite cards.css — Glass constellation panels

**Files:**
- Rewrite: `frontend/src/styles/components/cards.css`

- [ ] **Step 1: Replace panel with glass treatment**

```css
.panel {
  background: linear-gradient(135deg, rgba(16, 16, 42, 0.8) 0%, rgba(16, 16, 42, 0.6) 100%);
  background-color: var(--surface-raised);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 16px;
  box-shadow: var(--shadow-sm);
  padding: var(--panel-padding);
  position: relative;
  overflow: hidden;
  transition: border-color 200ms ease-out, box-shadow 200ms ease-out;
  animation: fadeInUp 300ms var(--ease-out) both;
}
/* NO ::before shimmer line */
.panel:hover {
  border-color: rgba(157, 117, 248, 0.20);
  box-shadow: var(--shadow-sm), 0 0 20px rgba(0, 214, 143, 0.06);
}
```

- [ ] **Step 2: Add staggered animation delays**

```css
.panel:nth-child(1) { animation-delay: 0ms; }
.panel:nth-child(2) { animation-delay: 50ms; }
.panel:nth-child(3) { animation-delay: 100ms; }
.panel:nth-child(4) { animation-delay: 150ms; }
.panel:nth-child(5) { animation-delay: 200ms; }
.panel:nth-child(6) { animation-delay: 250ms; }
.panel:nth-child(7) { animation-delay: 300ms; }
.panel:nth-child(8) { animation-delay: 350ms; }
```

- [ ] **Step 3: Replace metric card with gradient text + hover glow**

```css
.metric-card .metric-value {
  font-family: var(--font-display);
  font-size: var(--text-4xl);
  font-weight: 600;
  background: linear-gradient(135deg, #00D68F, #22D3EE);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1.1;
}

.metric-card {
  position: relative;
}
.metric-card::after {
  content: '';
  position: absolute;
  inset: -1px;
  border-radius: 17px;
  background: linear-gradient(135deg, rgba(0,214,143,0.3), rgba(157,117,248,0.3));
  z-index: -1;
  opacity: 0;
  transition: opacity 200ms ease-out;
}
.metric-card:hover::after {
  opacity: 1;
}
/* Remove old .metric-card:hover translateY */
```

- [ ] **Step 4: Add panel-highlight variant**

```css
.panel-highlight {
  border-left: 2px solid transparent;
  border-image: linear-gradient(to bottom, var(--primary), var(--accent)) 1;
  border-image-slice: 1;
  /* Only applies to left border */
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  border-right: 1px solid rgba(255, 255, 255, 0.06);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}
```

- [ ] **Step 5: Commit**

```bash
git add frontend/src/styles/components/cards.css
git commit -m "feat(cards): glass constellation panels, gradient metric text, hover glow"
```

---

## Task 8: Rewrite forms.css — Buttons, inputs, focus

**Files:**
- Rewrite: `frontend/src/styles/components/forms.css`

- [ ] **Step 1: Update btn-primary to green gradient**

```css
.btn-primary {
  background: linear-gradient(135deg, #00D68F, #00A56E);
  color: #050510;
  border-color: transparent;
  font-weight: 600;
}
.btn-primary:hover:not(:disabled) {
  box-shadow: 0 4px 20px rgba(0, 214, 143, 0.35);
}
```

- [ ] **Step 2: Update btn-secondary to glass**

```css
.btn-secondary {
  background: rgba(255, 255, 255, 0.04);
  color: var(--text-secondary);
  border-color: rgba(255, 255, 255, 0.08);
}
.btn-secondary:hover:not(:disabled) {
  border-color: rgba(157, 117, 248, 0.25);
  color: var(--text-primary);
}
```

- [ ] **Step 3: Update btn-ghost hover to green-tinted**

```css
.btn-ghost:hover:not(:disabled) {
  background: rgba(0, 214, 143, 0.06);
  color: var(--text-primary);
}
```

- [ ] **Step 4: Update focus ring to violet**

```css
:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(157, 117, 248, 0.25);
}
```

- [ ] **Step 5: Commit**

```bash
git add frontend/src/styles/components/forms.css
git commit -m "feat(forms): green gradient buttons, glass secondary, violet focus ring"
```

---

## Task 9: Update tables.css — New hover and header colors

**Files:**
- Modify: `frontend/src/styles/components/tables.css`

- [ ] **Step 1: Update table row hover**

Replace `.data-table tbody tr:hover { background: var(--surface-overlay); }` with:

```css
.data-table tbody tr:hover { background: rgba(0, 214, 143, 0.04); }
```

- [ ] **Step 2: Update selected row**

Replace `.data-table tbody tr.selected { background: var(--surface-elevated); }` with:

```css
.data-table tbody tr.selected {
  background: rgba(157, 117, 248, 0.08);
  border-left: 2px solid var(--accent);
}
```

- [ ] **Step 3: Commit**

```bash
git add frontend/src/styles/components/tables.css
git commit -m "feat(tables): aurora green hover, violet selected row"
```

---

## Task 10: Update badges.css — Replace hardcoded Parthenon colors

**Files:**
- Modify: `frontend/src/styles/components/badges.css`

- [ ] **Step 1: Fix badge-accent border**

Replace `rgba(42, 157, 143, 0.30)` in `.badge-accent` with:

```css
.badge-accent { background: var(--accent-bg); color: var(--accent-light); border: 1px solid rgba(157, 117, 248, 0.30); }
```

- [ ] **Step 2: Fix badge-condition**

Replace `rgba(155, 27, 48, 0.15)` with:

```css
.badge-condition { background: var(--critical-bg); color: var(--critical-light); border-color: var(--critical-border); }
```

- [ ] **Step 3: Fix badge-visit border**

Replace `rgba(42, 157, 143, 0.30)` with:

```css
.badge-visit { background: var(--accent-bg); color: var(--accent-light); border-color: rgba(157, 117, 248, 0.30); }
```

- [ ] **Step 4: Commit**

```bash
git add frontend/src/styles/components/badges.css
git commit -m "fix(badges): replace hardcoded Parthenon rgba with new tokens"
```

---

## Task 11: Update alerts.css — Mention highlight colors

**Files:**
- Modify: `frontend/src/styles/components/alerts.css`

- [ ] **Step 1: Update mention highlight colors**

Replace the hardcoded mention and highlight colors:

```css
.mention {
  background: rgba(157, 117, 248, 0.1);
  color: var(--accent-light);
}

@keyframes msgHighlightFade {
  0%   { background-color: rgba(157, 117, 248, 0.18); }
  60%  { background-color: rgba(157, 117, 248, 0.12); }
  100% { background-color: transparent; }
}
```

- [ ] **Step 2: Update About Abby link color**

Replace `rgba(45, 212, 191, 0.1)` and `rgb(94, 234, 212)` references with token-based values.

- [ ] **Step 3: Commit**

```bash
git add frontend/src/styles/components/alerts.css
git commit -m "fix(alerts): update mention and highlight to violet accent"
```

---

## Task 12: Rewrite Sidebar.tsx — Rail + flyout architecture

**Files:**
- Rewrite: `frontend/src/components/layout/Sidebar.tsx`

- [ ] **Step 1: Rewrite the component**

The new Sidebar renders:
1. A 64px icon rail with icons only (+ tiny label below each)
2. For items with children: hover/click opens a flyout panel
3. The Aurora icon sits at the top of the rail
4. The Acumenus branding sits at the very bottom
5. Only one flyout open at a time
6. Flyout auto-closes on navigation
7. Touch devices: click-only (no hover trigger)

Key changes from current:
- Remove `sidebarOpen` / `toggleSidebar` / collapse logic
- Remove `ChevronLeft`/`ChevronRight` toggle button
- Add `activeGroup` state for flyout management
- Icons always visible with tiny labels beneath
- Flyout has `role="menu"`, `aria-expanded`, children are `role="menuitem"`
- Escape closes flyout, focus returns to rail icon

Use `@media (hover: none)` or `onPointerEnter`/`onPointerLeave` (checking `pointerType`) to handle touch vs. mouse.

The Acumenus branding footer remains at the bottom of the rail.

- [ ] **Step 2: Verify visual result**

```bash
cd frontend && npm run dev
```

Open http://localhost:5177 and verify:
- Rail shows 64px wide with icons
- Hovering Admin shows flyout with children
- Active route has green glow dot
- Clicking a nav item navigates correctly
- Escape closes flyout

- [ ] **Step 3: Commit**

```bash
git add frontend/src/components/layout/Sidebar.tsx
git commit -m "feat(sidebar): rewrite as 64px icon rail with flyout panels"
```

---

## Task 13: Update Header.tsx — Remove hardcoded colors

**Files:**
- Modify: `frontend/src/components/layout/Header.tsx`

- [ ] **Step 1: Remove hardcoded Parthenon colors in UserDropdown**

Replace all hardcoded hex values in the dropdown:
- `#232328` → `var(--border-default)`
- `#151518` → `var(--surface-raised)`
- `#C5C0B8` → `var(--text-secondary)`
- `#1A1A1F` → `var(--surface-overlay)`
- `#E85A6B` → `var(--critical)`

Replace the inline Tailwind classes with CSS token references.

- [ ] **Step 2: Update About Abby button color**

Replace hardcoded `#2DD4BF` with `var(--primary)`.

- [ ] **Step 3: Commit**

```bash
git add frontend/src/components/layout/Header.tsx
git commit -m "fix(header): replace hardcoded Parthenon colors with tokens"
```

---

## Task 14: Update DashboardLayout.tsx — Remove collapse logic

**Files:**
- Modify: `frontend/src/components/layouts/DashboardLayout.tsx`

- [ ] **Step 1: Simplify layout — remove sidebar-collapsed class**

The rail is always 64px. Remove `sidebarOpen` usage and the conditional class:

```tsx
export default function DashboardLayout() {
  const user = useAuthStore((s) => s.user);

  return (
    <div className="app-shell">
      {user?.must_change_password && <ChangePasswordModal />}
      <Sidebar />
      <div className="app-content">
        <Header />
        <main className="content-main">
          <Outlet />
        </main>
      </div>
      <CommandPalette />
      <AbbyPanel />
    </div>
  );
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/components/layouts/DashboardLayout.tsx
git commit -m "refactor(layout): remove sidebar collapse logic — rail is fixed-width"
```

---

## Task 15: Update AbbyPanel.tsx — Remove hardcoded background

**Files:**
- Modify: `frontend/src/components/layout/AbbyPanel.tsx`

- [ ] **Step 1: Replace hardcoded history panel background**

Find `background: "#0E0E11"` (line ~442) and replace with:

```tsx
background: "var(--surface-base)",
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/components/layout/AbbyPanel.tsx
git commit -m "fix(abby): replace hardcoded background with token"
```

---

## Task 16: Clean up uiStore — Remove sidebar toggle

**Files:**
- Modify: `frontend/src/stores/uiStore.ts`

- [ ] **Step 1: Check if sidebarOpen is used elsewhere**

```bash
grep -rn "sidebarOpen\|toggleSidebar" frontend/src/ --include="*.ts" --include="*.tsx"
```

If only referenced in Sidebar.tsx, DashboardLayout.tsx (both already updated), and uiStore.ts itself, remove:
- `sidebarOpen` state
- `toggleSidebar` action

Keep `sidebarOpen` if any other component still references it — in that case, set it to `true` as a constant and leave a `// TODO: remove after full migration` comment.

- [ ] **Step 2: Commit**

```bash
git add frontend/src/stores/uiStore.ts
git commit -m "refactor(store): remove sidebarOpen toggle — rail is always visible"
```

---

## Task 17: Visual verification and final commit

- [ ] **Step 1: Start dev server**

```bash
cd frontend && npm run dev
```

- [ ] **Step 2: Visual checklist**

Open http://localhost:5177 and verify each page:

- [ ] Login page: **UNCHANGED** — aurora borealis slideshow, glass panels, shimmer border
- [ ] Dashboard: cold blue-black surfaces, green/violet accents, gradient metric values
- [ ] Sidebar: 64px rail with glow dots, flyout on hover/click
- [ ] Header: frosted/transparent, blur effect when scrolling
- [ ] Panels: glass treatment, violet hover glow, no shimmer line
- [ ] Buttons: green gradient primary, glass secondary, violet focus ring
- [ ] Tabs: glowing green underline
- [ ] Tables: green hover, violet selected row
- [ ] Badges: correct token colors, no Parthenon remnants
- [ ] Command palette: works, uses token colors
- [ ] Abby panel: works, correct colors
- [ ] Text: cool blue-white, readable at all levels

- [ ] **Step 3: Grep for any remaining Parthenon colors**

```bash
grep -rn "9B1B30\|C9A227\|2A9D8F\|0E0E11\|151518\|08080A\|F0EDE8\|C5C0B8\|IBM Plex" frontend/src/ --include="*.css" --include="*.tsx" --include="*.ts"
```

Fix any remaining hardcoded values.

- [ ] **Step 4: Final commit**

```bash
git add -A
git commit -m "feat: complete Aurora Northern Light UI redesign — visual verification pass"
```

---

## Task Dependency Graph

```
Task 1 (fonts) ──────────────────────────────────────┐
Task 2 (tokens-dark) ───────────────────────────────┐ │
Task 3 (tokens-base) ───────────────────────────────┤ │
Task 4 (app.css) ───────────────────────────────────┤ │
                                                     ▼ ▼
Task 5 (layout.css) ─────────────────────────────┐
Task 6 (navigation.css) ────────────────────────┐│
Task 7 (cards.css) ─────────────────────────────┤│
Task 8 (forms.css) ─────────────────────────────┤│
Task 9 (tables.css) ────────────────────────────┤│
Task 10 (badges.css) ───────────────────────────┤│
Task 11 (alerts.css) ───────────────────────────┤│
                                                 ▼▼
Task 12 (Sidebar.tsx) ──────────────────────────┐
Task 13 (Header.tsx) ──────────────────────────┐│
Task 14 (DashboardLayout.tsx) ─────────────────┤│
Task 15 (AbbyPanel.tsx) ───────────────────────┤│
Task 16 (uiStore.ts) ─────────────────────────┐││
                                                ▼▼▼
Task 17 (visual verification) ──────────────── DONE
```

**Parallelism:** Tasks 1-4 can run in parallel. Tasks 5-11 can run in parallel (all CSS-only). Tasks 12-16 can run in parallel (all TSX). Task 17 runs last.

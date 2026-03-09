# Aurora V2 Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Rebuild Aurora as the most advanced clinical case intelligence platform — combining Parthenon's patient profile exploration with real-time collaboration, AI-powered decision support, and cross-institutional federation.

**Architecture:** New monorepo (`backend/`, `frontend/`, `ai/`) built on Parthenon's proven patterns. Clinical adapter layer normalizes OMOP, FHIR, and manual data sources. Laravel Reverb for WebSockets, Abby (FastAPI) for AI, pgvector for similarity queries. Deployed natively to aurora.acumenus.net via Apache + PHP-FPM.

**Tech Stack:** Laravel 11 / PHP 8.4, React 19 / TypeScript 5.9+ / Tailwind 4, Python FastAPI, PostgreSQL 16 + pgvector, Redis 7, Laravel Reverb, Meilisearch, Cornerstone3D, Playwright

**Design Doc:** `docs/plans/2026-03-09-aurora-v2-complete-overhaul-design.md`

**Deployment Target:** aurora.acumenus.net (Apache + PHP-FPM, Let's Encrypt SSL)

**Dev Superuser:** admin@acumenus.net / superuser (all privileges, must_change_password: false, password never changes)

---

## Phase 0: Archive & Scaffold

**Goal:** Archive the current Aurora codebase, restructure into a Parthenon-style monorepo, and get a blank app deploying to aurora.acumenus.net.

---

### Task 0.1: Tag and Archive Current Codebase

**Files:**
- None created/modified — git operations only

**Step 1: Tag the current state**

```bash
cd /home/smudoshi/Github/Aurora
git add -A
git stash  # Save any uncommitted work
git tag v1-archive -m "Archive Aurora v1 before complete overhaul"
git push origin v1-archive
```

**Step 2: Create archive branch**

```bash
git checkout -b archive/v1-legacy
git push origin archive/v1-legacy
```

**Step 3: Return to working branch**

```bash
git checkout main
git checkout -b v2/phase-0-scaffold
```

**Step 4: Commit**

```bash
git commit --allow-empty -m "chore: begin Aurora V2 overhaul — Phase 0 scaffold"
```

---

### Task 0.2: Restructure into Monorepo

**Files:**
- Create: `backend/` (move all Laravel files here)
- Create: `frontend/` (new React SPA, separate from Laravel)
- Create: `ai/` (Python FastAPI placeholder)
- Create: `federation/` (placeholder)
- Create: `e2e/` (Playwright)
- Create: `docker/` (container definitions)
- Modify: Root-level config files

**Step 1: Create the new directory structure**

```bash
cd /home/smudoshi/Github/Aurora

# Create new top-level directories
mkdir -p backend frontend/src ai federation e2e docker/php docker/nginx docker/ai

# Move ALL Laravel files into backend/
# First, list what needs to move (everything except the new dirs and git)
```

**Step 2: Move Laravel files into backend/**

Move these directories and files into `backend/`:
```
app/ → backend/app/
bootstrap/ → backend/bootstrap/
config/ → backend/config/
database/ → backend/database/
public/ → backend/public/
resources/ → backend/resources/ (only blade views, not JS/CSS)
routes/ → backend/routes/
storage/ → backend/storage/
tests/ → backend/tests/ (only PHP tests)
artisan → backend/artisan
composer.json → backend/composer.json
composer.lock → backend/composer.lock
phpunit.xml → backend/phpunit.xml
.env → backend/.env
.env.example → backend/.env.example
```

**Do NOT move** into backend:
- `resources/js/` — will be rebuilt in `frontend/`
- `resources/css/` — will be rebuilt in `frontend/`
- `package.json` — new one in `frontend/`
- `vite.config.js` — new one in `frontend/`
- `tailwind.config.js` — new one in `frontend/`
- `tsconfig.json` — new one in `frontend/`
- `node_modules/` — delete
- `docs/` — stays at root
- `.claude/` — stays at root
- `.github/` — stays at root

```bash
# Move Laravel core
for dir in app bootstrap config database routes storage vendor; do
  [ -d "$dir" ] && mv "$dir" backend/
done

# Move Laravel files
for file in artisan composer.json composer.lock phpunit.xml server.php; do
  [ -f "$file" ] && mv "$file" backend/
done

# Move public (Laravel's public dir becomes backend/public)
mv public backend/

# Move resources (blade views only — JS/CSS will be in frontend)
mkdir -p backend/resources
mv resources/views backend/resources/

# Move PHP tests
mkdir -p backend/tests
# Copy PHP test files (Unit, Feature, Pest.php, TestCase.php)
cp -r tests/Unit tests/Feature tests/Pest.php tests/TestCase.php backend/tests/ 2>/dev/null

# Move env files
cp .env backend/.env 2>/dev/null
cp .env.example backend/.env.example 2>/dev/null
```

**Step 3: Clean up old files from root**

```bash
# Remove files that have been moved or are no longer needed
rm -rf app bootstrap config database routes storage vendor
rm -f artisan composer.json composer.lock phpunit.xml server.php
rm -rf resources
rm -rf node_modules
rm -f package.json package-lock.json vite.config.js tailwind.config.js tsconfig.json
rm -f vitest.config.ts playwright.config.ts postcss.config.js
rm -rf tests
```

**Step 4: Create root-level project files**

Create `Makefile`:
```makefile
.PHONY: up down build fresh logs test lint shell-php shell-node shell-ai deploy

up:
	docker compose --profile dev up -d

down:
	docker compose down

build:
	docker compose build

fresh:
	docker compose down -v
	docker compose --profile dev up -d
	docker compose exec php php artisan migrate:fresh --seed

logs:
	docker compose logs -f

test:
	@echo "=== PHP Tests ==="
	cd backend && php artisan test
	@echo "=== Frontend Tests ==="
	cd frontend && npm test
	@echo "=== AI Tests ==="
	cd ai && python -m pytest

lint:
	@echo "=== PHP Lint ==="
	cd backend && ./vendor/bin/pint --test
	cd backend && ./vendor/bin/phpstan analyse
	@echo "=== Frontend Lint ==="
	cd frontend && npx tsc --noEmit
	cd frontend && npx eslint src/
	@echo "=== Python Lint ==="
	cd ai && python -m mypy app/

shell-php:
	docker compose exec php bash

shell-node:
	docker compose exec node sh

shell-ai:
	docker compose exec ai bash

deploy:
	./deploy.sh
```

Create `deploy.sh`:
```bash
#!/usr/bin/env bash
set -euo pipefail

DEPLOY_DIR="/home/smudoshi/Github/Aurora"
echo "=== Aurora V2 Deployment ==="

# 1. Pull latest
echo "[1/6] Pulling latest code..."
cd "$DEPLOY_DIR"
git pull origin "$(git branch --show-current)"

# 2. Backend dependencies
echo "[2/6] Installing backend dependencies..."
cd "$DEPLOY_DIR/backend"
composer install --no-dev --optimize-autoloader

# 3. Run migrations
echo "[3/6] Running migrations..."
php artisan migrate --force

# 4. Clear and rebuild caches
echo "[4/6] Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Build frontend
echo "[5/6] Building frontend..."
cd "$DEPLOY_DIR/frontend"
npm ci
npm run build
# Copy built assets to backend/public for Apache to serve
cp -r dist/* "$DEPLOY_DIR/backend/public/build/" 2>/dev/null || true

# 6. Reload PHP-FPM
echo "[6/6] Reloading PHP-FPM..."
sudo systemctl reload php8.4-fpm

echo "=== Deployment complete ==="
echo "Visit: https://aurora.acumenus.net"
```

```bash
chmod +x deploy.sh
```

**Step 5: Commit**

```bash
git add -A
git commit -m "chore: restructure into monorepo (backend/, frontend/, ai/)"
```

---

### Task 0.3: Initialize Backend (Laravel in backend/)

**Files:**
- Modify: `backend/composer.json`
- Modify: `backend/.env`
- Create: `backend/public/index.php` (ensure it works from new path)
- Modify: `backend/bootstrap/app.php`

**Step 1: Update backend composer.json**

Add new dependencies needed for V2:

```bash
cd /home/smudoshi/Github/Aurora/backend
composer require laravel/reverb --no-interaction
composer require meilisearch/meilisearch-php --no-interaction
```

**Step 2: Update backend .env for new structure**

Key changes in `backend/.env`:
```
APP_NAME=Aurora
APP_ENV=production
APP_URL=https://aurora.acumenus.net
VITE_API_URL=https://aurora.acumenus.net/api

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=aurora
DB_USERNAME=smudoshi
DB_PASSWORD=acumenus

# Schemas
DB_SCHEMA=app

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

BROADCAST_DRIVER=reverb

REVERB_APP_ID=aurora
REVERB_APP_KEY=aurora-key
REVERB_APP_SECRET=aurora-secret

RESEND_API_KEY=${RESEND_API_KEY}

AURORA_SSO_ENABLED=false
AURORA_SSO_PARTHENON_SECRET=
AURORA_SSO_PARTHENON_URL=

AURORA_FEDERATION_ENABLED=false

MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=
```

**Step 3: Verify backend boots**

```bash
cd /home/smudoshi/Github/Aurora/backend
composer install
php artisan --version
# Expected: Laravel Framework 11.x.x
```

**Step 4: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add -A
git commit -m "chore: configure backend Laravel in monorepo structure"
```

---

### Task 0.4: Initialize Frontend (React SPA in frontend/)

**Files:**
- Create: `frontend/package.json`
- Create: `frontend/tsconfig.json`
- Create: `frontend/vite.config.ts`
- Create: `frontend/tailwind.config.ts` (or use Tailwind 4 CSS-first)
- Create: `frontend/index.html`
- Create: `frontend/src/main.tsx`
- Create: `frontend/src/App.tsx`
- Create: `frontend/src/styles/tokens-dark.css` (port from Parthenon)
- Create: `frontend/src/styles/tokens-base.css` (port from Parthenon)
- Create: `frontend/src/styles/app.css`

**Step 1: Initialize frontend package.json**

```bash
cd /home/smudoshi/Github/Aurora/frontend
```

Create `frontend/package.json`:
```json
{
  "name": "aurora-frontend",
  "private": true,
  "version": "2.0.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "tsc -b && vite build",
    "preview": "vite preview",
    "test": "vitest run",
    "test:watch": "vitest",
    "test:coverage": "vitest run --coverage",
    "lint": "eslint src/",
    "typecheck": "tsc --noEmit"
  },
  "dependencies": {
    "react": "^19.0.0",
    "react-dom": "^19.0.0",
    "react-router-dom": "^6.30.0",
    "@tanstack/react-query": "^5.90.0",
    "@tanstack/react-query-devtools": "^5.90.0",
    "@tanstack/react-table": "^8.21.0",
    "@tanstack/react-virtual": "^3.13.0",
    "zustand": "^5.0.0",
    "axios": "^1.13.0",
    "react-hook-form": "^7.71.0",
    "zod": "^4.3.0",
    "lucide-react": "^0.577.0",
    "recharts": "^3.8.0",
    "framer-motion": "^12.35.0",
    "react-hot-toast": "^2.6.0",
    "cmdk": "^1.1.0"
  },
  "devDependencies": {
    "@types/react": "^19.0.0",
    "@types/react-dom": "^19.0.0",
    "@vitejs/plugin-react-swc": "^4.0.0",
    "typescript": "^5.9.0",
    "vite": "^7.0.0",
    "@tailwindcss/vite": "^4.0.0",
    "tailwindcss": "^4.0.0",
    "vitest": "^4.0.0",
    "@testing-library/react": "^16.0.0",
    "@testing-library/jest-dom": "^6.0.0",
    "jsdom": "^25.0.0",
    "eslint": "^10.0.0",
    "@eslint/js": "^10.0.0",
    "typescript-eslint": "^8.0.0",
    "eslint-plugin-react-hooks": "^5.0.0",
    "prettier": "^3.8.0"
  }
}
```

**Step 2: Create tsconfig.json**

Create `frontend/tsconfig.json`:
```json
{
  "compilerOptions": {
    "target": "ES2022",
    "lib": ["ES2022", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "moduleResolution": "bundler",
    "jsx": "react-jsx",
    "strict": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "noFallthroughCasesInSwitch": true,
    "noUncheckedIndexedAccess": true,
    "forceConsistentCasingInFileNames": true,
    "resolveJsonModule": true,
    "isolatedModules": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "baseUrl": ".",
    "paths": {
      "@/*": ["src/*"]
    }
  },
  "include": ["src"],
  "references": [{ "path": "./tsconfig.node.json" }]
}
```

Create `frontend/tsconfig.node.json`:
```json
{
  "compilerOptions": {
    "target": "ES2022",
    "lib": ["ES2022"],
    "module": "ESNext",
    "moduleResolution": "bundler",
    "strict": true,
    "isolatedModules": true,
    "skipLibCheck": true
  },
  "include": ["vite.config.ts"]
}
```

**Step 3: Create vite.config.ts**

Create `frontend/vite.config.ts`:
```typescript
import { defineConfig } from "vite";
import react from "@vitejs/plugin-react-swc";
import tailwindcss from "@tailwindcss/vite";
import { resolve } from "path";

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      "@": resolve(__dirname, "src"),
    },
  },
  server: {
    port: 5175,
    proxy: {
      "/api": {
        target: "http://localhost:8000",
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: "dist",
    sourcemap: false,
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ["react", "react-dom", "react-router-dom"],
          query: ["@tanstack/react-query"],
          state: ["zustand"],
        },
      },
    },
  },
});
```

**Step 4: Create index.html**

Create `frontend/index.html`:
```html
<!DOCTYPE html>
<html lang="en" class="dark">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Aurora — Advanced Clinical Case Intelligence Platform" />
    <title>Aurora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Source+Sans+3:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  </head>
  <body class="bg-surface-base text-text-primary antialiased">
    <div id="root"></div>
    <script type="module" src="/src/main.tsx"></script>
  </body>
</html>
```

**Step 5: Port design tokens from Parthenon**

Create `frontend/src/styles/tokens-dark.css` — port from `/home/smudoshi/Github/Parthenon/frontend/src/styles/tokens-dark.css`:
```css
/* Aurora V2 Design Tokens — Dark Theme */
/* Ported from Parthenon with Aurora-specific adjustments */

:root {
  /* === PRIMARY PALETTE === */
  --color-primary: #9B1B30;           /* Dark Crimson */
  --color-primary-light: #B8243D;
  --color-primary-dark: #7A1526;
  --color-primary-muted: rgba(155, 27, 48, 0.15);

  --color-accent: #C9A227;            /* Research Gold */
  --color-accent-light: #D4B23E;
  --color-accent-dark: #A8871F;
  --color-accent-muted: rgba(201, 162, 39, 0.15);

  /* === SEMANTIC COLORS === */
  --color-critical: #E85A6B;
  --color-critical-muted: rgba(232, 90, 107, 0.15);
  --color-warning: #E5A84B;
  --color-warning-muted: rgba(229, 168, 75, 0.15);
  --color-success: #2DD4BF;
  --color-success-muted: rgba(45, 212, 191, 0.15);
  --color-info: #60A5FA;
  --color-info-muted: rgba(96, 165, 250, 0.15);

  /* === CLINICAL DOMAIN COLORS === */
  --color-domain-condition: #E85A6B;   /* Crimson */
  --color-domain-drug: #2DD4BF;        /* Teal */
  --color-domain-procedure: #C9A227;   /* Gold */
  --color-domain-measurement: #818CF8; /* Indigo */
  --color-domain-observation: #94A3B8; /* Slate */
  --color-domain-visit: #F59E0B;       /* Amber */
  --color-domain-device: #A78BFA;      /* Purple */
  --color-domain-death: #6B7280;       /* Gray */

  /* === SURFACE COLORS === */
  --color-surface-base: #0E0E11;
  --color-surface-raised: #151518;
  --color-surface-elevated: #232328;
  --color-surface-overlay: rgba(14, 14, 17, 0.85);

  /* === TEXT COLORS === */
  --color-text-primary: #F0EDE8;
  --color-text-secondary: #C5C0B8;
  --color-text-muted: #8A857D;
  --color-text-ghost: #5A5650;
  --color-text-inverse: #0E0E11;

  /* === BORDER COLORS === */
  --color-border-default: #232328;
  --color-border-strong: #3A3A42;
  --color-border-focus: var(--color-accent);

  /* === GLASSMORPHISM === */
  --glass-opacity-1: rgba(21, 21, 24, 0.4);
  --glass-opacity-2: rgba(21, 21, 24, 0.6);
  --glass-opacity-3: rgba(21, 21, 24, 0.75);
  --glass-blur-sm: blur(8px);
  --glass-blur-md: blur(12px);
  --glass-blur-lg: blur(20px);

  /* === GRADIENTS === */
  --gradient-panel: linear-gradient(135deg, var(--color-surface-raised) 0%, var(--color-surface-base) 100%);
  --gradient-crimson: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
  --gradient-gold: linear-gradient(135deg, var(--color-accent) 0%, var(--color-accent-dark) 100%);

  /* === CHART COLORS === */
  --chart-1: #E85A6B;
  --chart-2: #2DD4BF;
  --chart-3: #C9A227;
  --chart-4: #818CF8;
  --chart-5: #F59E0B;
  --chart-6: #60A5FA;
  --chart-7: #A78BFA;
  --chart-8: #94A3B8;
}
```

Create `frontend/src/styles/tokens-base.css` — port from `/home/smudoshi/Github/Parthenon/frontend/src/styles/tokens-base.css`:
```css
/* Aurora V2 Base Tokens — Typography, Spacing, Motion */
/* Ported from Parthenon */

:root {
  /* === TYPOGRAPHY === */
  --font-sans: 'Source Sans 3', system-ui, -apple-system, sans-serif;
  --font-mono: 'IBM Plex Mono', ui-monospace, monospace;

  /* Type Scale */
  --text-xs: 0.6875rem;     /* 11px */
  --text-sm: 0.8125rem;     /* 13px */
  --text-base: 0.875rem;    /* 14px */
  --text-md: 0.9375rem;     /* 15px */
  --text-lg: 1rem;          /* 16px */
  --text-xl: 1.125rem;      /* 18px */
  --text-2xl: 1.375rem;     /* 22px */
  --text-3xl: 1.75rem;      /* 28px */
  --text-4xl: 2.25rem;      /* 36px */
  --text-5xl: 2.75rem;      /* 44px */
  --text-6xl: 3.5rem;       /* 56px */

  /* === SPACING === */
  --space-0: 0;
  --space-0-5: 2px;
  --space-1: 4px;
  --space-1-5: 6px;
  --space-2: 8px;
  --space-3: 12px;
  --space-4: 16px;
  --space-5: 20px;
  --space-6: 24px;
  --space-8: 32px;
  --space-10: 40px;
  --space-12: 48px;
  --space-16: 64px;
  --space-20: 80px;
  --space-24: 96px;

  /* === LAYOUT === */
  --sidebar-width: 260px;
  --topbar-height: 56px;
  --content-max-width: 1600px;

  /* === Z-INDEX === */
  --z-base: 0;
  --z-dropdown: 100;
  --z-sticky: 200;
  --z-overlay: 300;
  --z-modal: 400;
  --z-toast: 450;
  --z-tooltip: 500;

  /* === BORDER RADIUS === */
  --radius-xs: 4px;
  --radius-sm: 6px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 16px;
  --radius-full: 9999px;

  /* === SHADOWS === */
  --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.3);
  --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.3);
  --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.3);
  --shadow-lg: 0 8px 16px rgba(0, 0, 0, 0.3);
  --shadow-xl: 0 12px 24px rgba(0, 0, 0, 0.3);
  --shadow-2xl: 0 20px 40px rgba(0, 0, 0, 0.4);
  --shadow-inset: inset 0 1px 3px rgba(0, 0, 0, 0.3);

  /* === MOTION === */
  --duration-fast: 100ms;
  --duration-normal: 200ms;
  --duration-slow: 300ms;
  --duration-slower: 500ms;
  --duration-slowest: 700ms;

  --ease-default: cubic-bezier(0.4, 0, 0.2, 1);
  --ease-in: cubic-bezier(0.4, 0, 1, 1);
  --ease-out: cubic-bezier(0, 0, 0.2, 1);
  --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* === ANIMATIONS === */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeInScale {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

@keyframes slideInRight {
  from { opacity: 0; transform: translateX(16px); }
  to { opacity: 1; transform: translateX(0); }
}

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* === TEXT UTILITIES === */
.text-label {
  font-size: var(--text-xs);
  font-weight: 600;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--color-text-ghost);
}

.text-caption {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.text-mono {
  font-family: var(--font-mono);
  font-size: var(--text-sm);
  color: var(--color-success);
}

.text-value {
  font-family: var(--font-mono);
  font-size: var(--text-base);
  font-weight: 500;
  color: var(--color-text-primary);
}

.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* === GRID UTILITIES === */
.grid-two {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--space-4);
}

.grid-three {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-4);
}

.grid-four {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: var(--space-4);
}

@media (max-width: 768px) {
  .grid-two,
  .grid-three,
  .grid-four {
    grid-template-columns: 1fr;
  }
}
```

Create `frontend/src/styles/app.css`:
```css
@import "tailwindcss";
@import "./tokens-dark.css";
@import "./tokens-base.css";

/* === BASE STYLES === */
body {
  font-family: var(--font-sans);
  font-size: var(--text-base);
  line-height: 1.6;
  background-color: var(--color-surface-base);
  color: var(--color-text-primary);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Scrollbar styling */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: var(--color-surface-base);
}

::-webkit-scrollbar-thumb {
  background: var(--color-text-ghost);
  border-radius: var(--radius-full);
}

::-webkit-scrollbar-thumb:hover {
  background: var(--color-text-muted);
}

/* Focus ring */
*:focus-visible {
  outline: 2px solid var(--color-accent);
  outline-offset: 2px;
}
```

**Step 6: Create entry point**

Create `frontend/src/main.tsx`:
```typescript
import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { App } from "./App";
import "./styles/app.css";

const root = document.getElementById("root");
if (!root) throw new Error("Root element not found");

createRoot(root).render(
  <StrictMode>
    <App />
  </StrictMode>
);
```

Create `frontend/src/App.tsx`:
```typescript
import { BrowserRouter, Routes, Route } from "react-router-dom";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000,
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

function HomePage() {
  return (
    <div className="flex min-h-screen items-center justify-center">
      <div className="text-center">
        <h1
          className="text-4xl font-bold"
          style={{ color: "var(--color-accent)" }}
        >
          Aurora
        </h1>
        <p className="mt-2" style={{ color: "var(--color-text-secondary)" }}>
          Advanced Clinical Case Intelligence Platform
        </p>
        <p className="mt-1 text-mono" style={{ color: "var(--color-success)" }}>
          v2.0.0 — scaffold deployed
        </p>
      </div>
    </div>
  );
}

export function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <Routes>
          <Route path="/*" element={<HomePage />} />
        </Routes>
      </BrowserRouter>
    </QueryClientProvider>
  );
}
```

**Step 7: Install dependencies and verify**

```bash
cd /home/smudoshi/Github/Aurora/frontend
npm install
npm run build
# Expected: builds successfully to dist/
```

**Step 8: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add -A
git commit -m "feat: initialize frontend SPA with Parthenon design tokens"
```

---

### Task 0.5: Initialize AI Service (Python FastAPI in ai/)

**Files:**
- Create: `ai/requirements.txt`
- Create: `ai/app/__init__.py`
- Create: `ai/app/main.py`
- Create: `ai/app/config.py`
- Create: `ai/tests/test_health.py`

**Step 1: Create requirements.txt**

Create `ai/requirements.txt`:
```
fastapi==0.115.0
uvicorn[standard]==0.32.0
pydantic==2.10.0
pydantic-settings==2.7.0
httpx==0.28.0
psycopg2-binary==2.9.10
pgvector==0.3.6
numpy==2.2.0
python-dotenv==1.0.1
pytest==8.3.0
mypy==1.14.0
```

**Step 2: Create FastAPI app**

Create `ai/app/__init__.py`:
```python
```

Create `ai/app/config.py`:
```python
from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    app_name: str = "Aurora AI (Abby)"
    debug: bool = False
    database_url: str = "postgresql://smudoshi:acumenus@localhost:5432/aurora"
    ollama_base_url: str = "http://localhost:11434"
    ollama_model: str = "MedAIBase/MedGemma1.5:4b"

    class Config:
        env_file = ".env"


settings = Settings()
```

Create `ai/app/main.py`:
```python
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from .config import settings

app = FastAPI(
    title=settings.app_name,
    version="2.0.0",
    docs_url="/api/ai/docs",
    openapi_url="/api/ai/openapi.json",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["https://aurora.acumenus.net", "http://localhost:5175"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/api/ai/health")
async def health():
    return {"status": "ok", "service": "abby", "version": "2.0.0"}
```

**Step 3: Create test**

Create `ai/tests/__init__.py`:
```python
```

Create `ai/tests/test_health.py`:
```python
from fastapi.testclient import TestClient

from app.main import app

client = TestClient(app)


def test_health_endpoint():
    response = client.get("/api/ai/health")
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "ok"
    assert data["service"] == "abby"
```

**Step 4: Verify**

```bash
cd /home/smudoshi/Github/Aurora/ai
python -m venv venv
source venv/bin/activate
pip install -r requirements.txt
python -m pytest tests/ -v
# Expected: 1 passed
```

**Step 5: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add -A
git commit -m "feat: initialize AI service (Abby) with FastAPI health endpoint"
```

---

### Task 0.6: Database Schema Reset for V2

**Files:**
- Create: `backend/database/migrations/2026_03_09_000001_create_app_schema.php`
- Create: `backend/database/migrations/2026_03_09_000002_create_clinical_schema.php`
- Create: `backend/database/migrations/2026_03_09_000003_create_users_table.php`
- Create: `backend/database/migrations/2026_03_09_000004_create_permission_tables.php`
- Create: `backend/database/migrations/2026_03_09_000005_seed_superuser.php`

**Step 1: Remove old migrations**

```bash
cd /home/smudoshi/Github/Aurora/backend
rm -f database/migrations/*.php
```

**Step 2: Create schema migrations**

Create `backend/database/migrations/2026_03_09_000001_create_app_schema.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS app');
        DB::statement('CREATE SCHEMA IF NOT EXISTS clinical');
        DB::statement("SET search_path TO app, clinical, public");
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS clinical CASCADE');
        DB::statement('DROP SCHEMA IF EXISTS app CASCADE');
    }
};
```

Create `backend/database/migrations/2026_03_09_000002_create_users_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app.users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->boolean('must_change_password')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('institution_id')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('app.personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('app.password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('app.sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app.sessions');
        Schema::dropIfExists('app.password_reset_tokens');
        Schema::dropIfExists('app.personal_access_tokens');
        Schema::dropIfExists('app.users');
    }
};
```

Create `backend/database/migrations/2026_03_09_000003_create_permission_tables.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teams = config('permission.teams');

        Schema::create('app.permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('app.roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('app.model_has_permissions', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained('app.permissions')->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('app.model_has_roles', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('app.roles')->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('app.role_has_permissions', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained('app.permissions')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('app.roles')->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        app()['cache']->forget('spatie.permission.cache');
    }

    public function down(): void
    {
        Schema::dropIfExists('app.role_has_permissions');
        Schema::dropIfExists('app.model_has_roles');
        Schema::dropIfExists('app.model_has_permissions');
        Schema::dropIfExists('app.roles');
        Schema::dropIfExists('app.permissions');
    }
};
```

**Step 3: Create superuser seeder**

Create `backend/database/seeders/SuperuserSeeder.php`:
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperuserSeeder extends Seeder
{
    public function run(): void
    {
        // Seed roles
        $roles = [
            'admin', 'department_head', 'attending', 'fellow',
            'resident', 'nurse_coordinator', 'data_analyst', 'observer',
        ];

        foreach ($roles as $role) {
            DB::table('app.roles')->updateOrInsert(
                ['name' => $role, 'guard_name' => 'sanctum'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Seed superuser — NEVER change this password
        $user = DB::table('app.users')->updateOrInsert(
            ['email' => 'admin@acumenus.net'],
            [
                'name' => 'Aurora Admin',
                'password' => Hash::make('superuser'),
                'must_change_password' => false,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Assign ALL roles to superuser
        $userId = DB::table('app.users')
            ->where('email', 'admin@acumenus.net')
            ->value('id');

        $roleIds = DB::table('app.roles')->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('app.model_has_roles')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId,
                ],
            );
        }
    }
}
```

Update `backend/database/seeders/DatabaseSeeder.php`:
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SuperuserSeeder::class,
        ]);
    }
}
```

**Step 4: Update database config**

Modify `backend/config/database.php` — change the pgsql connection's `search_path`:
```php
'pgsql' => [
    'driver' => 'pgsql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'aurora'),
    'username' => env('DB_USERNAME', 'smudoshi'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8'),
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'app,clinical,public',
    'sslmode' => 'prefer',
],
```

**Step 5: Update User model**

Modify `backend/app/Models/User.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $table = 'app.users';

    protected $guard_name = 'sanctum';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'must_change_password',
        'is_active',
        'institution_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Superuser cannot be deleted or deactivated.
     */
    public function isSuperuser(): bool
    {
        return $this->email === 'admin@acumenus.net';
    }
}
```

**Step 6: Run migrations**

```bash
cd /home/smudoshi/Github/Aurora/backend
php artisan migrate:fresh --seed
# Expected: migrations run, superuser seeded
```

**Step 7: Verify superuser**

```bash
php artisan tinker --execute="echo App\Models\User::where('email', 'admin@acumenus.net')->first()->toJson(JSON_PRETTY_PRINT);"
# Expected: shows admin user with must_change_password: false, is_active: true
```

**Step 8: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add -A
git commit -m "feat: create V2 database schema with multi-schema PostgreSQL and superuser"
```

---

### Task 0.7: Update Apache Config and Deploy Scaffold

**Files:**
- Modify: `/etc/apache2/sites-available/aurora.acumenus.net-le-ssl.conf`
- The DocumentRoot needs to point to `backend/public/`

**Step 1: Update Apache vhost**

The DocumentRoot must change from `/home/smudoshi/Github/Aurora/public` to `/home/smudoshi/Github/Aurora/backend/public`:

```bash
sudo sed -i 's|DocumentRoot /home/smudoshi/Github/Aurora/public|DocumentRoot /home/smudoshi/Github/Aurora/backend/public|g' /etc/apache2/sites-available/aurora.acumenus.net-le-ssl.conf
sudo sed -i 's|<Directory /home/smudoshi/Github/Aurora/public>|<Directory /home/smudoshi/Github/Aurora/backend/public>|g' /etc/apache2/sites-available/aurora.acumenus.net-le-ssl.conf
```

Do the same for the HTTP config:
```bash
sudo sed -i 's|DocumentRoot /home/smudoshi/Github/Aurora/public|DocumentRoot /home/smudoshi/Github/Aurora/backend/public|g' /etc/apache2/sites-available/aurora.acumenus.net.conf
sudo sed -i 's|<Directory /home/smudoshi/Github/Aurora/public>|<Directory /home/smudoshi/Github/Aurora/backend/public>|g' /etc/apache2/sites-available/aurora.acumenus.net.conf
```

**Step 2: Update Laravel to serve the SPA**

Create `backend/resources/views/app.blade.php`:
```blade
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Aurora — Advanced Clinical Case Intelligence Platform">
    <title>Aurora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Source+Sans+3:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @if(file_exists(public_path('build/manifest.json')))
        @php
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        @endphp
        @if(isset($manifest['src/main.tsx']))
            <link rel="stylesheet" href="/build/{{ $manifest['src/main.tsx']['css'][0] ?? '' }}">
        @endif
    @endif
</head>
<body class="bg-surface-base text-text-primary antialiased">
    <div id="root"></div>
    @if(file_exists(public_path('build/manifest.json')))
        @php
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        @endphp
        @if(isset($manifest['src/main.tsx']))
            <script type="module" src="/build/{{ $manifest['src/main.tsx']['file'] }}"></script>
        @endif
    @else
        <script type="module" src="http://localhost:5175/src/main.tsx"></script>
    @endif
</body>
</html>
```

Update `backend/routes/web.php`:
```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
```

**Step 3: Build frontend and copy to backend/public/build/**

```bash
cd /home/smudoshi/Github/Aurora/frontend
npm run build

# Copy built assets to where Apache serves them
mkdir -p /home/smudoshi/Github/Aurora/backend/public/build
cp -r dist/* /home/smudoshi/Github/Aurora/backend/public/build/
```

Update `frontend/vite.config.ts` build output to generate a manifest:
```typescript
// Add to build config:
build: {
    outDir: "dist",
    manifest: true,
    sourcemap: false,
    rollupOptions: {
      input: "src/main.tsx",
      output: {
        manualChunks: {
          vendor: ["react", "react-dom", "react-router-dom"],
          query: ["@tanstack/react-query"],
          state: ["zustand"],
        },
      },
    },
  },
```

Rebuild:
```bash
cd /home/smudoshi/Github/Aurora/frontend
npm run build
cp -r dist/* /home/smudoshi/Github/Aurora/backend/public/build/
```

**Step 4: Reload Apache**

```bash
sudo systemctl reload apache2
```

**Step 5: Verify deployment**

```bash
curl -s https://aurora.acumenus.net | grep "Aurora"
# Expected: HTML containing "Aurora" text
```

**Step 6: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add -A
git commit -m "feat: deploy V2 scaffold to aurora.acumenus.net"
```

---

### Task 0.8: Set Up CI Pipeline

**Files:**
- Create: `.github/workflows/ci.yml`

**Step 1: Create CI workflow**

Create `.github/workflows/ci.yml`:
```yaml
name: Aurora V2 CI

on:
  push:
    branches: [main, "v2/*"]
  pull_request:
    branches: [main]

jobs:
  backend:
    name: Backend (PHP)
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16-alpine
        env:
          POSTGRES_DB: aurora_test
          POSTGRES_USER: aurora
          POSTGRES_PASSWORD: secret
        ports: ["5432:5432"]
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
      redis:
        image: redis:7-alpine
        ports: ["6379:6379"]
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.4"
          extensions: pgsql, pdo_pgsql, redis, zip, bcmath
          coverage: xdebug

      - name: Install dependencies
        working-directory: backend
        run: composer install --no-interaction --prefer-dist

      - name: Code style (Pint)
        working-directory: backend
        run: ./vendor/bin/pint --test

      - name: Static analysis (PHPStan)
        working-directory: backend
        run: ./vendor/bin/phpstan analyse --level=8 app/
        continue-on-error: true  # Will enforce after Phase 1

      - name: Run tests (Pest)
        working-directory: backend
        env:
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_PORT: 5432
          DB_DATABASE: aurora_test
          DB_USERNAME: aurora
          DB_PASSWORD: secret
        run: php artisan test --coverage --min=80
        continue-on-error: true  # Will enforce after Phase 1

  frontend:
    name: Frontend (TypeScript/React)
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: "22"
          cache: "npm"
          cache-dependency-path: frontend/package-lock.json

      - name: Install dependencies
        working-directory: frontend
        run: npm ci

      - name: Type check
        working-directory: frontend
        run: npx tsc --noEmit

      - name: Lint
        working-directory: frontend
        run: npx eslint src/
        continue-on-error: true  # Will enforce after Phase 1

      - name: Unit tests
        working-directory: frontend
        run: npm test
        continue-on-error: true  # Will enforce after Phase 1

      - name: Build
        working-directory: frontend
        run: npm run build

  ai:
    name: AI Service (Python)
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup Python
        uses: actions/setup-python@v5
        with:
          python-version: "3.12"

      - name: Install dependencies
        working-directory: ai
        run: |
          python -m pip install --upgrade pip
          pip install -r requirements.txt

      - name: Type check (mypy)
        working-directory: ai
        run: python -m mypy app/
        continue-on-error: true  # Will enforce after Phase 3

      - name: Run tests
        working-directory: ai
        run: python -m pytest tests/ -v
```

**Step 2: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add -A
git commit -m "ci: add GitHub Actions pipeline for backend, frontend, and AI service"
```

---

## Phase 1: Foundation

**Goal:** Port auth, design system, clinical adapter layer, and Patient Profile from Parthenon. This is the single largest phase.

---

### Task 1.1: Port Auth System from Parthenon

**Files:**
- Create: `backend/app/Http/Controllers/AuthController.php`
- Create: `backend/app/Services/AuthService.php`
- Create: `backend/routes/api.php`
- Create: `backend/app/Http/Middleware/SecurityHeaders.php`
- Test: `backend/tests/Feature/Auth/AuthenticationTest.php`

**Reference:** `/home/smudoshi/Github/Parthenon/backend/app/Http/Controllers/Api/V1/AuthController.php`

**Step 1: Write the failing auth tests**

Create `backend/tests/Feature/Auth/AuthenticationTest.php`:
```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->artisan('migrate:fresh --seed');
});

test('superuser can login', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'admin@acumenus.net',
        'password' => 'superuser',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => ['access_token', 'user' => ['id', 'name', 'email', 'must_change_password', 'roles']],
        ]);

    expect($response->json('data.user.must_change_password'))->toBeFalse();
});

test('registration creates user with temp password and returns success', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Dr. Test User',
        'email' => 'testuser@hospital.org',
        'phone' => '555-0100',
    ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('app.users', [
        'email' => 'testuser@hospital.org',
        'must_change_password' => true,
    ]);
});

test('registration returns same message for existing email (enumeration prevention)', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Duplicate User',
        'email' => 'admin@acumenus.net',
    ]);

    $response->assertOk()
        ->assertJson(['success' => true]);
});

test('inactive user cannot login', function () {
    $user = User::factory()->create([
        'is_active' => false,
        'password' => Hash::make('password123'),
        'must_change_password' => false,
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertForbidden();
});

test('user with must_change_password flag is returned in login response', function () {
    $user = User::factory()->create([
        'password' => Hash::make('temppass123'),
        'must_change_password' => true,
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'temppass123',
    ]);

    $response->assertOk();
    expect($response->json('data.user.must_change_password'))->toBeTrue();
});

test('change password works and clears must_change_password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword'),
        'must_change_password' => true,
    ]);

    $token = $user->createToken('auth-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/auth/change-password', [
        'current_password' => 'oldpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertOk();

    $user->refresh();
    expect($user->must_change_password)->toBeFalse();
});

test('logout revokes token', function () {
    $user = User::factory()->create([
        'must_change_password' => false,
    ]);

    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withToken($token)->postJson('/api/auth/logout')
        ->assertOk();

    $this->withToken($token)->getJson('/api/auth/user')
        ->assertUnauthorized();
});

test('superuser cannot be deleted', function () {
    $admin = User::where('email', 'admin@acumenus.net')->first();
    expect($admin->isSuperuser())->toBeTrue();
});
```

**Step 2: Run tests to verify they fail**

```bash
cd /home/smudoshi/Github/Aurora/backend
php artisan test tests/Feature/Auth/
# Expected: FAIL — routes don't exist yet
```

**Step 3: Create AuthService**

Create `backend/app/Services/AuthService.php`:
```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Generate a 12-char temp password excluding ambiguous characters.
     */
    public function generateTempPassword(): string
    {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%';
        $password = '';
        for ($i = 0; $i < 12; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    /**
     * Register a new user with a temp password.
     */
    public function register(string $name, string $email, ?string $phone = null): bool
    {
        // Check if user already exists — return true either way (enumeration prevention)
        if (User::where('email', $email)->exists()) {
            return true;
        }

        $tempPassword = $this->generateTempPassword();

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($tempPassword),
            'must_change_password' => true,
            'is_active' => true,
        ]);

        $this->sendTempPasswordEmail($user, $tempPassword);

        return true;
    }

    /**
     * Authenticate a user and return token + user data.
     */
    public function login(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        if (! $user->is_active) {
            return ['error' => 'inactive'];
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $user->update(['last_login_at' => now()]);

        return [
            'access_token' => $token,
            'user' => $this->formatUser($user),
        ];
    }

    /**
     * Change user password.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password)) {
            return false;
        }

        $user->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => false,
        ]);

        // Revoke all existing tokens except current
        $user->tokens()->delete();

        return true;
    }

    /**
     * Format user for API response.
     */
    public function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'must_change_password' => $user->must_change_password,
            'is_active' => $user->is_active,
            'last_login_at' => $user->last_login_at,
            'roles' => $user->getRoleNames()->toArray(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    /**
     * Send temp password via Resend API.
     */
    private function sendTempPasswordEmail(User $user, string $tempPassword): void
    {
        $apiKey = config('services.resend.key');
        if (! $apiKey) {
            Log::warning('RESEND_API_KEY not configured — temp password not emailed', [
                'user_email' => $user->email,
            ]);
            return;
        }

        try {
            Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.resend.com/emails', [
                'from' => 'Aurora <noreply@acumenus.net>',
                'to' => [$user->email],
                'subject' => 'Your Aurora Account',
                'html' => "<p>Hello {$user->name},</p>
                    <p>Your Aurora account has been created.</p>
                    <p>Your temporary password is: <strong>{$tempPassword}</strong></p>
                    <p>You will be required to change this password on first login.</p>
                    <p>— Aurora Team</p>",
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send temp password email', [
                'user_email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

**Step 4: Create AuthController**

Create `backend/app/Http/Controllers/AuthController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $this->authService->register(
            $validated['name'],
            $validated['email'],
            $validated['phone'] ?? null,
        );

        // Always return same message (enumeration prevention)
        return ApiResponse::success(
            null,
            'If this email is not already registered, you will receive a temporary password shortly.'
        );
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->login($validated['email'], $validated['password']);

        if ($result === null) {
            return ApiResponse::error('Invalid credentials.', 401);
        }

        if (isset($result['error']) && $result['error'] === 'inactive') {
            return ApiResponse::error('Your account has been deactivated.', 403);
        }

        return ApiResponse::success($result, 'Login successful.');
    }

    public function user(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->authService->formatUser($request->user())
        );
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $success = $this->authService->changePassword(
            $request->user(),
            $validated['current_password'],
            $validated['password'],
        );

        if (! $success) {
            return ApiResponse::error('Current password is incorrect.', 422);
        }

        // Issue new token after password change
        $token = $request->user()->createToken('auth-token')->plainTextToken;

        return ApiResponse::success([
            'access_token' => $token,
            'user' => $this->authService->formatUser($request->user()->fresh()),
        ], 'Password changed successfully.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out.');
    }
}
```

**Step 5: Create API routes**

Create `backend/routes/api.php`:
```php
<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Aurora V2
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'service' => 'aurora-api',
    'version' => '2.0.0',
]));

// Auth (public)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Auth (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
});
```

**Step 6: Ensure ApiResponse helper exists**

Create `backend/app/Helpers/ApiResponse.php` (if not already moved):
```php
<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function error(string $message = 'Error', int $code = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    public static function paginated(mixed $data, array $meta, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ]);
    }
}
```

**Step 7: Create User factory**

Create `backend/database/factories/UserFactory.php`:
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'phone' => fake()->phoneNumber(),
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ];
    }
}
```

**Step 8: Run tests**

```bash
cd /home/smudoshi/Github/Aurora/backend
php artisan test tests/Feature/Auth/
# Expected: All tests pass
```

**Step 9: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add -A
git commit -m "feat: port Parthenon auth system — register, login, change password, logout"
```

---

### Task 1.2: Port Frontend Auth Components

**Files:**
- Create: `frontend/src/stores/authStore.ts`
- Create: `frontend/src/lib/api-client.ts`
- Create: `frontend/src/lib/query-client.ts`
- Create: `frontend/src/features/auth/pages/LoginPage.tsx`
- Create: `frontend/src/features/auth/pages/RegisterPage.tsx`
- Create: `frontend/src/features/auth/components/LoginForm.tsx`
- Create: `frontend/src/features/auth/components/ChangePasswordModal.tsx`
- Create: `frontend/src/components/layouts/DashboardLayout.tsx`
- Create: `frontend/src/components/navigation/TopNavigation.tsx`
- Create: `frontend/src/components/ui/PrivateRoute.tsx`

**Reference files in Parthenon:**
- `/home/smudoshi/Github/Parthenon/frontend/src/stores/authStore.ts`
- `/home/smudoshi/Github/Parthenon/frontend/src/lib/api-client.ts`

**Step 1: Create auth store (Zustand)**

Create `frontend/src/stores/authStore.ts`:
```typescript
import { create } from "zustand";
import { persist } from "zustand/middleware";

interface User {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  avatar: string | null;
  must_change_password: boolean;
  is_active: boolean;
  last_login_at: string | null;
  roles: string[];
  permissions: string[];
  created_at: string;
  updated_at: string;
}

interface AuthState {
  token: string | null;
  user: User | null;
  isAuthenticated: boolean;
  setAuth: (token: string, user: User) => void;
  updateUser: (user: Partial<User>) => void;
  logout: () => void;
  hasRole: (role: string) => boolean;
  hasPermission: (permission: string) => boolean;
  isAdmin: () => boolean;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      token: null,
      user: null,
      isAuthenticated: false,

      setAuth: (token, user) =>
        set({ token, user, isAuthenticated: true }),

      updateUser: (userData) =>
        set((state) => ({
          user: state.user ? { ...state.user, ...userData } : null,
        })),

      logout: () =>
        set({ token: null, user: null, isAuthenticated: false }),

      hasRole: (role) => get().user?.roles.includes(role) ?? false,

      hasPermission: (permission) =>
        get().user?.permissions.includes(permission) ?? false,

      isAdmin: () => get().user?.roles.includes("admin") ?? false,
    }),
    { name: "aurora-auth" }
  )
);
```

**Step 2: Create API client**

Create `frontend/src/lib/api-client.ts`:
```typescript
import axios from "axios";
import { useAuthStore } from "@/stores/authStore";

const apiClient = axios.create({
  baseURL: "/api",
  headers: { "Content-Type": "application/json" },
  withCredentials: true,
});

// Attach Bearer token
apiClient.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle 401 — auto logout
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().logout();
      window.location.href = "/login";
    }
    return Promise.reject(error);
  }
);

export { apiClient };
```

Create `frontend/src/lib/query-client.ts`:
```typescript
import { QueryClient } from "@tanstack/react-query";

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000,
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});
```

**Step 3: Create auth pages and components**

These follow the exact same patterns as Aurora v1 and Parthenon — LoginForm with "Create Account" link, RegisterPage with no password field, non-dismissable ChangePasswordModal, PrivateRoute guard, DashboardLayout that checks must_change_password.

The complete component implementations should be built following TDD — write the Vitest component tests first, then implement. Key behaviors to test:

- `LoginForm`: renders email/password fields, submits to `/api/auth/login`, stores token on success, redirects to dashboard
- `RegisterPage`: renders name/email/phone fields (NO password), submits to `/api/auth/register`
- `ChangePasswordModal`: renders when `user.must_change_password === true`, cannot be dismissed, submits to `/api/auth/change-password`
- `PrivateRoute`: redirects to `/login` when not authenticated
- `DashboardLayout`: renders ChangePasswordModal when `must_change_password` is true

**Step 4: Update App.tsx with routes**

Update `frontend/src/App.tsx` to include auth routes and the DashboardLayout.

**Step 5: Build and verify**

```bash
cd /home/smudoshi/Github/Aurora/frontend
npm run build
npm test
```

**Step 6: Deploy and test login**

```bash
cd /home/smudoshi/Github/Aurora
./deploy.sh
# Test: navigate to aurora.acumenus.net/login, log in with admin@acumenus.net / superuser
```

**Step 7: Commit**

```bash
git add -A
git commit -m "feat: port frontend auth — login, register, change password, dashboard layout"
```

---

### Task 1.3: Build Clinical Adapter Layer (Backend)

**Files:**
- Create: `backend/app/Models/Clinical/ClinicalPatient.php`
- Create: `backend/app/Models/Clinical/Condition.php`
- Create: `backend/app/Models/Clinical/Medication.php`
- Create: `backend/app/Models/Clinical/Procedure.php`
- Create: `backend/app/Models/Clinical/Measurement.php`
- Create: `backend/app/Models/Clinical/Observation.php`
- Create: `backend/app/Models/Clinical/Visit.php`
- Create: `backend/app/Models/Clinical/ClinicalNote.php`
- Create: `backend/app/Models/Clinical/ImagingStudy.php`
- Create: `backend/app/Models/Clinical/GenomicVariant.php`
- Create: `backend/app/Contracts/ClinicalDataAdapter.php`
- Create: `backend/app/Services/Adapters/ManualAdapter.php`
- Create: `backend/app/Services/Adapters/OmopAdapter.php` (stub)
- Create: `backend/app/Services/Adapters/FhirAdapter.php` (stub)
- Create: `backend/database/migrations/2026_03_09_100001_create_clinical_tables.php`
- Create: `backend/app/Http/Controllers/PatientController.php`
- Create: `backend/app/Services/PatientService.php`
- Test: `backend/tests/Feature/Api/PatientTest.php`
- Test: `backend/tests/Unit/Services/ManualAdapterTest.php`

**Step 1: Create clinical schema migrations**

The migration creates all tables in the `clinical` schema matching the internal clinical model from the design doc. Tables: `patients`, `patient_identifiers`, `conditions`, `medications`, `procedures`, `measurements`, `observations`, `visits`, `clinical_notes`, `imaging_studies`, `imaging_series`, `imaging_instances`, `imaging_measurements`, `imaging_segmentations`, `genomic_variants`, `condition_eras`, `drug_eras`, `patient_embeddings`.

Each table has `source_id` and `source_type` columns for provenance tracking.

**Step 2: Create the adapter contract (interface)**

Create `backend/app/Contracts/ClinicalDataAdapter.php`:
```php
<?php

namespace App\Contracts;

interface ClinicalDataAdapter
{
    public function getPatient(string $patientId): ?array;
    public function getConditions(string $patientId): array;
    public function getMedications(string $patientId): array;
    public function getProcedures(string $patientId): array;
    public function getMeasurements(string $patientId): array;
    public function getObservations(string $patientId): array;
    public function getVisits(string $patientId): array;
    public function getNotes(string $patientId, int $page = 1, int $perPage = 50): array;
    public function getImaging(string $patientId): array;
    public function getGenomics(string $patientId): array;
    public function getFullProfile(string $patientId): array;
    public function searchPatients(string $query, int $limit = 20): array;
}
```

**Step 3: Implement ManualAdapter (reads from clinical schema directly)**

**Step 4: Create PatientController with full profile endpoint**

API endpoints:
```
GET  /api/patients/search?q={query}
GET  /api/patients/{id}/profile
GET  /api/patients/{id}/stats
GET  /api/patients/{id}/notes?page={page}
POST /api/patients (manual entry)
```

**Step 5: Write tests, implement, verify**

```bash
cd /home/smudoshi/Github/Aurora/backend
php artisan test tests/Feature/Api/PatientTest.php
php artisan test tests/Unit/Services/ManualAdapterTest.php
```

**Step 6: Commit**

```bash
git add -A
git commit -m "feat: build clinical adapter layer — ManualAdapter, patient models, API endpoints"
```

---

### Task 1.4: Port Patient Profile Frontend from Parthenon

**Files:**
- Create: `frontend/src/features/patient-profile/pages/PatientProfilePage.tsx`
- Create: `frontend/src/features/patient-profile/components/PatientDemographicsCard.tsx`
- Create: `frontend/src/features/patient-profile/components/PatientTimeline.tsx`
- Create: `frontend/src/features/patient-profile/components/PatientLabPanel.tsx`
- Create: `frontend/src/features/patient-profile/components/PatientNotesTab.tsx`
- Create: `frontend/src/features/patient-profile/components/PatientVisitView.tsx`
- Create: `frontend/src/features/patient-profile/components/EraTimeline.tsx`
- Create: `frontend/src/features/patient-profile/components/PatientSearchPanel.tsx`
- Create: `frontend/src/features/patient-profile/components/ClinicalEventCard.tsx`
- Create: `frontend/src/features/patient-profile/components/ConceptDetailDrawer.tsx`
- Create: `frontend/src/features/patient-profile/hooks/useProfiles.ts`
- Create: `frontend/src/features/patient-profile/api/profileApi.ts`
- Create: `frontend/src/features/patient-profile/types/profile.ts`
- Create: `frontend/src/stores/profileStore.ts`

**Reference:** `/home/smudoshi/Github/Parthenon/frontend/src/features/profiles/`

Port each component from Parthenon, adapting:
- API endpoints to Aurora's `/api/patients/` routes instead of Parthenon's `/api/v1/sources/{source}/profiles/`
- Remove OMOP-specific concepts (vocabulary links) — make them generic
- Add Aurora-specific features: case linking, annotation anchoring

**View modes to implement (matching Parthenon):**
1. Timeline (interval-packed, domain-colored)
2. List (domain tabs, group by concept toggle)
3. Labs (measurements with reference ranges)
4. Visits (visit-grouped events)
5. Notes (paginated clinical notes)
6. Eras (condition/drug eras)

**Defer to Phase 4:**
7. Imaging (needs Cornerstone3D)
8. Precision Medicine / Genomics (needs AI service)

**Step 1: Create types and API layer**

**Step 2: Create profile store (Zustand, ported from Parthenon)**

**Step 3: Build components bottom-up — test each**

**Step 4: Wire into App.tsx routing**

```
/patients/:id → PatientProfilePage
```

**Step 5: Build, deploy, test visually**

**Step 6: Commit**

```bash
git add -A
git commit -m "feat: port Patient Profile from Parthenon — timeline, labs, notes, visits, eras"
```

---

### Task 1.5: Parthenon SSO Bridge

**Files:**
- Create: `backend/app/Http/Controllers/SsoController.php`
- Create: `backend/app/Services/SsoService.php`
- Test: `backend/tests/Feature/Auth/SsoTest.php`

**Step 1: Write SSO tests**

```php
test('valid Parthenon JWT creates user and returns Aurora token', function () {
    // Generate a valid JWT signed with the shared secret
    // POST to /api/auth/sso/parthenon
    // Assert: user created, Aurora token returned, redirects correctly
});

test('expired JWT is rejected', function () {
    // Generate JWT with exp in the past
    // Assert: 401
});

test('invalid signature is rejected', function () {
    // Generate JWT signed with wrong secret
    // Assert: 401
});
```

**Step 2: Implement SsoService**

- Validates JWT signature using `AURORA_SSO_PARTHENON_SECRET`
- Checks expiry (max 60 seconds)
- Finds or creates Aurora user matching the JWT email
- Assigns roles from JWT payload
- Issues Aurora Sanctum token
- Returns redirect URL with patient context

**Step 3: Add route**

```php
Route::post('/auth/sso/parthenon', [SsoController::class, 'authenticate']);
```

**Step 4: Test, commit**

```bash
git add -A
git commit -m "feat: implement Parthenon SSO bridge — JWT validation and user provisioning"
```

---

## Phase 2: Collaboration Core

**Goal:** Build the case management, session engine, and decision capture system.

---

### Task 2.1: Case Management Backend

**Files:**
- Create: `backend/database/migrations/2026_03_09_200001_create_case_tables.php`
- Create: `backend/app/Models/ClinicalCase.php`
- Create: `backend/app/Models/CaseTeamMember.php`
- Create: `backend/app/Models/CaseAnnotation.php`
- Create: `backend/app/Models/CaseDocument.php`
- Create: `backend/app/Models/CaseDiscussion.php`
- Create: `backend/app/Http/Controllers/CaseController.php`
- Create: `backend/app/Http/Controllers/CaseDiscussionController.php`
- Create: `backend/app/Services/CaseService.php`
- Test: `backend/tests/Feature/Api/CaseTest.php`

**Migration creates:**
- `app.cases` (title, specialty, urgency, status, patient_id, case_type, clinical_question, created_by, institution_id)
- `app.case_team_members` (case_id, user_id, role, invited_at)
- `app.case_annotations` (case_id, user_id, domain, record_ref, content, anchored_to)
- `app.case_documents` (case_id, filename, filepath, mime_type, size, document_type)
- `app.case_discussions` (case_id, user_id, parent_id, content, created_at)
- `app.discussion_attachments` (discussion_id, filename, filepath, mime_type, size)

**API endpoints:**
```
GET    /api/cases                          # List (filterable, paginated)
POST   /api/cases                          # Create case
GET    /api/cases/{id}                     # Show with relations
PATCH  /api/cases/{id}                     # Update
DELETE /api/cases/{id}                     # Archive

POST   /api/cases/{id}/team               # Add team member
DELETE /api/cases/{id}/team/{userId}       # Remove team member

GET    /api/cases/{id}/annotations         # List annotations
POST   /api/cases/{id}/annotations         # Add annotation

GET    /api/cases/{id}/discussions         # Threaded discussions
POST   /api/cases/{id}/discussions         # Post to discussion
POST   /api/cases/{id}/documents           # Upload document
```

**TDD: Write tests → implement → verify → commit**

---

### Task 2.2: Session Engine Backend

**Files:**
- Create: `backend/database/migrations/2026_03_09_200002_create_session_tables.php`
- Create: `backend/app/Models/Session.php`
- Create: `backend/app/Models/SessionCase.php`
- Create: `backend/app/Models/SessionParticipant.php`
- Create: `backend/app/Http/Controllers/SessionController.php`
- Create: `backend/app/Services/SessionService.php`
- Test: `backend/tests/Feature/Api/SessionTest.php`

**Migration creates:**
- `app.sessions` (title, scheduled_at, duration_minutes, status, session_type, created_by, institution_id)
- `app.session_cases` (session_id, case_id, order, presenter_id, time_allotted_minutes, status)
- `app.session_participants` (session_id, user_id, role, joined_at, left_at)

**API endpoints:**
```
GET    /api/sessions                       # List (upcoming, past, filterable)
POST   /api/sessions                       # Create session
GET    /api/sessions/{id}                  # Show with cases and participants
PATCH  /api/sessions/{id}                  # Update
DELETE /api/sessions/{id}                  # Cancel

POST   /api/sessions/{id}/cases            # Add case to session
PATCH  /api/sessions/{id}/cases/{caseId}   # Update order, presenter
DELETE /api/sessions/{id}/cases/{caseId}   # Remove case

POST   /api/sessions/{id}/start            # Go live
POST   /api/sessions/{id}/end              # End session
```

---

### Task 2.3: Decision Capture Backend

**Files:**
- Create: `backend/database/migrations/2026_03_09_200003_create_decision_tables.php`
- Create: `backend/app/Models/Decision.php`
- Create: `backend/app/Models/DecisionVote.php`
- Create: `backend/app/Models/FollowUp.php`
- Create: `backend/app/Http/Controllers/DecisionController.php`
- Test: `backend/tests/Feature/Api/DecisionTest.php`

**API endpoints:**
```
POST   /api/cases/{id}/decisions            # Propose decision
PATCH  /api/decisions/{id}                  # Update recommendation
POST   /api/decisions/{id}/vote             # Cast vote
POST   /api/decisions/{id}/finalize         # Finalize decision
GET    /api/cases/{id}/decisions            # List decisions for case

POST   /api/decisions/{id}/follow-ups       # Add follow-up task
PATCH  /api/follow-ups/{id}                 # Update status
```

---

### Task 2.4: WebSocket Events (Laravel Reverb)

**Files:**
- Create: `backend/app/Events/SessionJoined.php`
- Create: `backend/app/Events/SessionLeft.php`
- Create: `backend/app/Events/CasePresenting.php`
- Create: `backend/app/Events/CaseViewSync.php`
- Create: `backend/app/Events/AnnotationAdded.php`
- Create: `backend/app/Events/DecisionProposed.php`
- Create: `backend/app/Events/DecisionVoted.php`
- Create: `backend/app/Events/DecisionFinalized.php`
- Modify: `backend/config/broadcasting.php`
- Modify: `backend/routes/channels.php`

**Channels:**
```
presence: session.{sessionId}     # Who's in the live session
private: session.{sessionId}      # Session events
```

---

### Task 2.5: Collaboration Frontend

**Files:**
- Create: `frontend/src/features/collaboration/pages/SessionLobbyPage.tsx`
- Create: `frontend/src/features/collaboration/pages/LiveSessionPage.tsx`
- Create: `frontend/src/features/collaboration/components/CasePresenter.tsx`
- Create: `frontend/src/features/collaboration/components/ParticipantBar.tsx`
- Create: `frontend/src/features/collaboration/components/SharedAnnotations.tsx`
- Create: `frontend/src/features/collaboration/components/DecisionCapture.tsx`
- Create: `frontend/src/features/collaboration/components/SessionTimer.tsx`
- Create: `frontend/src/features/collaboration/components/AgendaPanel.tsx`
- Create: `frontend/src/features/collaboration/hooks/useWebSocket.ts`
- Create: `frontend/src/features/collaboration/hooks/usePresence.ts`
- Create: `frontend/src/features/collaboration/hooks/useSessionState.ts`
- Create: `frontend/src/stores/sessionStore.ts`

---

### Task 2.6: Cases & Dashboard Frontend

**Files:**
- Create: `frontend/src/features/cases/pages/CaseListPage.tsx`
- Create: `frontend/src/features/cases/pages/CaseDetailPage.tsx`
- Create: `frontend/src/features/cases/components/CaseCard.tsx`
- Create: `frontend/src/features/cases/components/CaseForm.tsx`
- Create: `frontend/src/features/cases/components/CaseAnnotationPanel.tsx`
- Create: `frontend/src/features/cases/components/CaseDiscussionThread.tsx`
- Create: `frontend/src/features/dashboard/pages/DashboardPage.tsx`
- Create: `frontend/src/features/dashboard/components/UpcomingSessions.tsx`
- Create: `frontend/src/features/dashboard/components/RecentCases.tsx`
- Create: `frontend/src/features/dashboard/components/ActivityFeed.tsx`

---

## Phase 3: AI & Intelligence (Abby)

**Goal:** Port Abby from Parthenon, build similarity engine, and integrate decision support.

---

### Task 3.1: Abby Copilot Service

**Files:**
- Create: `ai/app/routers/copilot.py`
- Create: `ai/app/services/llm_service.py`
- Create: `ai/app/services/copilot_service.py`
- Create: `ai/app/models/conversation.py`
- Test: `ai/tests/test_copilot.py`

**Endpoints:**
```
POST /api/ai/copilot/chat              # Conversational (streaming)
POST /api/ai/copilot/summarize         # Patient/discussion summary
POST /api/ai/copilot/session-note      # Post-session note generation
```

**LLM abstraction layer:** Same pattern as Parthenon — configurable provider (Ollama default, OpenAI, Anthropic, etc.)

---

### Task 3.2: Similarity Engine ("Patients Like This")

**Files:**
- Create: `ai/app/routers/similarity.py`
- Create: `ai/app/services/embedding_service.py`
- Create: `ai/app/services/similarity_service.py`
- Test: `ai/tests/test_similarity.py`

**Endpoints:**
```
POST /api/ai/similarity/embed          # Compute patient embedding
POST /api/ai/similarity/search         # Find similar patients
POST /api/ai/similarity/federated      # Federated search (Phase 5)
```

**Implementation:**
- Embedding model: sentence-transformers or domain-specific clinical encoder
- pgvector for storage and ANN search
- Re-ranking with domain-specific weights (genomics 0.30, diagnosis 0.25, treatment 0.20, etc.)

---

### Task 3.3: Decision Support Modules

**Files:**
- Create: `ai/app/routers/decision_support.py`
- Create: `ai/app/services/trial_matching.py`
- Create: `ai/app/services/guideline_checker.py`
- Create: `ai/app/services/variant_interpreter.py`
- Create: `ai/app/services/drug_interaction_checker.py`
- Create: `ai/app/services/prognostic_scorer.py`
- Create: `ai/app/services/rare_disease_matcher.py`

**Endpoints:**
```
GET  /api/ai/trials/match/{patientId}
POST /api/ai/genomics/interpret
POST /api/ai/guidelines/check
POST /api/ai/drugs/interactions
POST /api/ai/prognosis/score
POST /api/ai/rare-disease/match
```

---

### Task 3.4: Abby Frontend (Copilot Panel)

**Files:**
- Create: `frontend/src/features/copilot/components/CopilotPanel.tsx`
- Create: `frontend/src/features/copilot/components/CopilotSuggestion.tsx`
- Create: `frontend/src/features/copilot/components/TrialMatchResults.tsx`
- Create: `frontend/src/features/copilot/components/PatientsLikeThis.tsx`
- Create: `frontend/src/features/copilot/hooks/useCopilot.ts`

---

### Task 3.5: Abby Backend Integration (Laravel → FastAPI)

**Files:**
- Create: `backend/app/Services/AbbyService.php`
- Create: `backend/app/Http/Controllers/AbbyController.php`
- Create: `backend/database/migrations/2026_03_09_300001_create_abby_tables.php`

Laravel acts as a proxy/orchestrator — authenticates the request, gathers patient context from the clinical adapter, forwards to the AI service, and returns results.

Tables: `app.abby_conversations`, `app.abby_messages`, `app.ai_provider_settings`

---

## Phase 4: Imaging & Specialty

**Goal:** Integrate DICOM viewer, volumetric analysis, and specialty-specific workflows.

---

### Task 4.1: Cornerstone3D Integration

**Files:**
- Create: `frontend/src/features/patient-profile/components/PatientImagingTab.tsx`
- Create: `frontend/src/features/patient-profile/components/DicomViewer.tsx`
- Create: `frontend/src/features/patient-profile/components/VolumeRenderer.tsx`
- Create: `frontend/src/features/patient-profile/components/MeasurementOverlay.tsx`
- Add dependency: `@cornerstonejs/core`, `@cornerstonejs/tools`, `@cornerstonejs/streaming-image-volume-loader`

Port from Parthenon's imaging implementation.

---

### Task 4.2: Volumetric Analysis Pipeline

**Files:**
- Create: `ai/app/routers/imaging.py`
- Create: `ai/app/services/segmentation_service.py`
- Create: `ai/app/services/volumetric_service.py`
- Create: `ai/app/services/response_assessment.py`

**Endpoints:**
```
POST /api/ai/imaging/segment           # Run segmentation
POST /api/ai/imaging/volume            # Compute volume
POST /api/ai/imaging/response          # Response assessment (RECIST, etc.)
GET  /api/ai/imaging/trends/{patientId} # Longitudinal trends
```

---

### Task 4.3: Genomics Tab (Patient Profile)

**Files:**
- Create: `frontend/src/features/patient-profile/components/PatientGenomicsTab.tsx`
- Create: `frontend/src/features/patient-profile/components/VariantCard.tsx`
- Create: `frontend/src/features/patient-profile/components/ActionableGenes.tsx`

Port from Parthenon's PrecisionMedicineTab.

---

### Task 4.4: Specialty Workflow Templates

**Files:**
- Create: `backend/database/seeders/SpecialtyTemplateSeeder.php`
- Create: `frontend/src/features/cases/components/SpecialtyWorkflow.tsx`

Pre-built case templates for:
- Oncology tumor board (molecular tumor board variant)
- Rare disease diagnostic odyssey
- Complex surgical planning
- Complex medical case review

Each template pre-configures: case_type, clinical_question prompts, relevant data tabs, decision types, guideline sets.

---

## Phase 5: Federation & Scale

**Goal:** Build the federation relay, implement federated similarity, cross-institutional sessions.

---

### Task 5.1: Federation Relay Service

**Files:**
- Create: `federation/relay.py` (or `relay.go`)
- Create: `federation/crypto.py`
- Create: `federation/registry.py`
- Create: `federation/config.py`
- Create: `federation/requirements.txt`
- Create: `federation/tests/test_relay.py`

mTLS-authenticated message relay. Validates institution certificates, routes queries to registered peers, merges responses.

---

### Task 5.2: Federated "Patients Like This"

**Files:**
- Modify: `ai/app/routers/similarity.py` (add federated endpoint)
- Create: `ai/app/services/federation_client.py`
- Create: `backend/app/Services/FederationService.php`

The `/api/ai/similarity/federated` endpoint:
1. Computes local embedding
2. Broadcasts to federation relay
3. Relay fans out to approved peers
4. Each peer runs local ANN search, returns de-identified aggregates
5. Originator merges and re-ranks
6. Returns unified results

---

### Task 5.3: Cross-Institutional Sessions

**Files:**
- Modify: `backend/app/Services/SessionService.php` (add external invites)
- Create: `backend/app/Http/Controllers/FederatedSessionController.php`
- Create: `backend/app/Services/FederatedAuthService.php`

External participants authenticate via their own Aurora instance's federation certificate. They receive a scoped token that only grants access to the specific shared session.

---

### Task 5.4: Cloud Deployment Option

**Files:**
- Create: `docker-compose.prod.yml`
- Create: `docker/Dockerfile.api`
- Create: `docker/Dockerfile.frontend`
- Create: `docker/Dockerfile.ai`
- Create: `.env.docker.example`

Full containerized deployment for institutions that want Docker instead of native Apache.

---

## Phase 6: Polish & Harden

**Goal:** E2E tests, performance, security audit, documentation.

---

### Task 6.1: E2E Test Suite (Playwright)

**Files:**
- Create: `e2e/playwright.config.ts`
- Create: `e2e/tests/auth.spec.ts`
- Create: `e2e/tests/sso.spec.ts`
- Create: `e2e/tests/patient-profile.spec.ts`
- Create: `e2e/tests/case-lifecycle.spec.ts`
- Create: `e2e/tests/live-session.spec.ts`
- Create: `e2e/tests/patients-like-this.spec.ts`
- Create: `e2e/tests/copilot.spec.ts`
- Create: `e2e/tests/imaging.spec.ts`

**8 critical flows** as defined in the design doc.

---

### Task 6.2: Performance Optimization

- Frontend: Code splitting per feature, lazy loading routes, TanStack Virtual for long lists
- Backend: Eager loading on all relationships, database indexes on foreign keys + search columns, Redis caching for patient profiles
- AI: Batch embedding computation, connection pooling for pgvector queries
- Imaging: Progressive DICOM loading, client-side caching

---

### Task 6.3: Security Audit

Run security-reviewer agent against entire codebase:
- OWASP Top 10 compliance
- No hardcoded secrets
- Input validation on all endpoints
- SQL injection prevention (Eloquent parameterized queries)
- XSS prevention (React auto-escaping + CSP headers)
- CSRF protection (Sanctum)
- Rate limiting on auth endpoints
- Audit logging for all clinical data access

---

### Task 6.4: Documentation

**Files:**
- Create: `docs/user-guide/` — User manual (Markdown or Docusaurus)
- Create: `docs/api/` — Auto-generated from Scramble (PHP) + FastAPI (Python)
- Create: `docs/deployment/` — Installation guide for self-hosted
- Create: `docs/federation/` — Federation setup guide
- Update: `README.md` — Project overview, quick start, architecture diagram

---

## Appendix: End-of-Session SOP

**MANDATORY before closing any session on this project:**

1. **Devlog** — Update devlog with what was accomplished
2. **Deploy** — Deploy to aurora.acumenus.net (vhost on this machine)
3. **Commit** — Commit all changes with descriptive message
4. **Push** — Push to remote
5. **Test** — Verify deployment works at aurora.acumenus.net

---

## Appendix: File Count Summary

| Phase | New Files | Modified Files |
|-------|-----------|----------------|
| Phase 0 | ~30 | ~10 |
| Phase 1 | ~45 | ~15 |
| Phase 2 | ~35 | ~10 |
| Phase 3 | ~25 | ~10 |
| Phase 4 | ~20 | ~5 |
| Phase 5 | ~15 | ~10 |
| Phase 6 | ~15 | ~5 |
| **Total** | **~185** | **~65** |

# Technology Stack

**Analysis Date:** 2026-03-24

## Languages

**Primary:**
- PHP 8.2+ - Backend APIs (Laravel)
- TypeScript 5.7 - Frontend application (React)
- Python 3.13+ - AI service (FastAPI)

**Secondary:**
- JavaScript (Node.js 22) - Frontend build tooling
- Shell - Deployment and utility scripts

## Runtime

**Environment:**
- PHP-FPM 8.4-alpine - Backend runtime in Docker
- Node.js 22-alpine - Frontend dev server and build
- Python 3.13 - AI service runtime
- Docker / Docker Compose - Container orchestration

**Package Manager:**
- Composer 2.x - PHP dependency management
- npm 10.x - JavaScript/Node.js dependencies
- pip - Python package management
- Lockfiles: composer.lock, package-lock.json, requirements.txt present

## Frameworks

**Core:**
- Laravel 11.31 - Backend framework, routing, ORM (Eloquent), auth
- React 19.0 - Frontend UI library
- FastAPI 0.115.0 - AI service REST API

**Backend Support:**
- Laravel Sanctum 4.0 - Token-based API authentication
- Spatie Laravel Permission 6.24 - RBAC (role-based access control)
- Laravel Pail 1.1 - Log viewer

**Frontend UI:**
- Tailwind CSS 4.0 - Utility-first CSS framework via @tailwindcss/vite
- Framer Motion 12.35 - Animation library
- Lucide React 0.577 - Icon library
- React Router DOM 6.30 - Client-side routing
- Recharts 3.8 - Data visualization

**State & Data:**
- Zustand 5.0 - Lightweight state management (stores in `src/stores/`)
- TanStack React Query 5.90 - Server state & caching via @tanstack/react-query
- Axios 1.13 - HTTP client for API calls

**Frontend Build/Dev:**
- Vite 6.0 - Build tool with Hot Module Replacement (HMR)
- @vitejs/plugin-react-swc 4.0 - SWC compiler for fast JSX transformation
- TypeScript 5.7 - Static type checking
- ESLint 9.0 - Code linting
- Prettier 3.0 - Code formatting
- Vitest 3.0 - Unit test runner

**Testing:**
- Pest 3.8 - PHP testing framework
- PHPUnit 11.0 - Base testing library for PHP
- Mockery 1.6 - Mocking library for PHP
- Vitest 3.0 - JavaScript/TypeScript unit tests
- Playwright 1.49 - E2E testing (in `e2e/` directory)
- pytest 8.3 - Python unit testing
- pytest-asyncio 0.24 - Async test support for Python

**Backend Services:**
- Redis 7-alpine - In-memory cache, session store, queue backend
- PostgreSQL 16 - Primary relational database
- Meilisearch - Search engine (configured in services.php)

## Key Dependencies

**Backend (Laravel) Critical:**
- resend/resend-php - Email delivery via Resend API (temp password flow)
- spatie/laravel-permission - RBAC with roles, permissions, teams
- nesbot/carbon - DateTime manipulation
- laravel/pint - Code style fixer
- laravel/tinker - REPL for debugging

**AI Service Critical:**
- anthropic 0.40 - Claude API client for LLM routing
- sqlalchemy 2.0.36 - ORM for database queries
- pgvector 0.3.6 - PostgreSQL vector search (embeddings)
- psycopg2-binary 2.9.10 - PostgreSQL adapter
- redis 5.2.1 - Redis client for caching
- fastapi 0.115.0 - REST API framework
- uvicorn[standard] 0.32.0 - ASGI server
- pydantic 2.10.0 - Data validation

**Frontend:**
- react-hot-toast 2.6.0 - Toast notifications
- react-markdown 10.1.0 - Markdown rendering
- rehype-sanitize 6.0.0 - HTML sanitization
- remark-gfm 4.0.1 - GitHub Flavored Markdown support
- cmdk 1.1.0 - Command palette / fuzzy search
- @testing-library/react 16.0.0 - Component testing utilities

**Federation Service:**
- fastapi 0.115.0 - REST API
- httpx 0.28.0 - Async HTTP client
- cryptography 44.0.0 - JWT signing/verification
- pydantic 2.10.0 - Configuration and validation

## Configuration

**Environment:**
- `.env` - Environment variables (not committed)
- `.env.example` - Template with required variables
- `.env.docker.example` - Docker-specific configuration

**Key Configurations Required:**
- `APP_KEY` - Laravel encryption key (base64 encoded)
- `APP_DEBUG` - Debug mode (false in production)
- `DB_*` - PostgreSQL connection (host, port, database, username, password)
- `REDIS_*` - Redis connection
- `RESEND_API_KEY` - Email delivery API key
- `CLAUDE_API_KEY` - Anthropic Claude API key for AI service
- `AI_SERVICE_URL` - FastAPI service endpoint (internal: http://ai:8100)
- `VITE_API_URL` - Frontend API base URL
- `VITE_APP_NAME` - Application name for frontend

**Build Configuration:**
- `frontend/vite.config.ts` - Vite bundler config
- `backend/config/` - Laravel configuration directory:
  - `app.php` - Application settings
  - `database.php` - Database connections (PostgreSQL with pgvector search_path)
  - `cache.php` - Cache stores (Redis, database, file)
  - `mail.php` - Mail driver configuration
  - `services.php` - Third-party services (Resend, OncoKB, AI, Slack)
  - `queue.php` - Job queue configuration
  - `logging.php` - Logging channel configuration (Slack integration available)
- `docker-compose.yml` - Service definitions (nginx, php, node, postgres, redis, mailhog)
- `docker/php/Dockerfile` - PHP 8.4-FPM container with PostgreSQL driver
- `docker/nginx/default.conf` - Nginx reverse proxy configuration
- `.github/workflows/` - CI/CD pipeline definitions

## Platform Requirements

**Development:**
- Docker & Docker Compose
- PHP 8.2+ (local development alternative to Docker)
- Node.js 22+ (or use Docker node service)
- Python 3.13+ (for AI service)
- PostgreSQL 16 (can be Docker or local)
- Redis 7+ (can be Docker or local)

**Production:**
- Deployment target: aurora.acumenus.net (vhost on Linux with Apache)
- Docker Compose or Kubernetes for orchestration
- PostgreSQL 16 managed database
- Redis instance for caching
- Resend account for email delivery
- Anthropic API key (Claude access)
- OncoKB API token (optional, genomics features)

## Deployment

**Build Output:**
- Frontend: `dist/` (built via `npm run build`, copied to `backend/public/build/`)
- Backend: Native PHP (no build step required)
- AI Service: Python source (runs via uvicorn)

**Docker Services:**
- `nginx:1.27-alpine` - Reverse proxy on port 8085 (dev) / 443 (prod)
- `php:8.4-fpm-alpine` - Backend runtime
- `node:22-alpine` - Frontend dev server (Vite) on port 5177 (dev)
- `redis:7-alpine` - Cache and session store on port 6385 (dev) / 6379 (prod)
- `mailhog:latest` - SMTP testing server on port 1030 (dev profile)

**Production Deployment:**
- Script: `deploy.sh` - Handles git pull, install, build, and restart
- Assets: Frontend built artifacts copied from `dist/` to `backend/public/build/`
- Database: Migrations run via `php artisan migrate`

---

*Stack analysis: 2026-03-24*

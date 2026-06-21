/// <reference types="vitest" />
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react-swc';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'path';

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    allowedHosts: ['aurora.acumenus.net'],
  },
  base: process.env.NODE_ENV === 'production' ? '/build/' : '/',
  build: {
    outDir: 'dist',
    manifest: true,
    rollupOptions: {
      input: resolve(__dirname, 'index.html'),
    },
  },
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.ts'],
    include: ['src/**/*.{test,spec}.{ts,tsx}'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html', 'json-summary'],
      include: [
        'src/stores/**/*.{ts,tsx}',
        'src/features/genomics/components/**/*.{ts,tsx}',
        'src/features/genomics/hooks/**/*.{ts,tsx}',
        'src/features/genomics/types/**/*.{ts,tsx}',
        'src/features/auth/**/*.{ts,tsx}',
        'src/lib/**/*.{ts,tsx}',
        'src/hooks/**/*.{ts,tsx}',
      ],
      exclude: [
        'src/test/**',
        'src/**/*.d.ts',
        'src/main.tsx',
        'src/vite-env.d.ts',
        'src/features/genomics/pages/**',
        'src/features/genomics/components/UploadDialog.tsx',
        'src/features/genomics/components/VariantExpandedRow.tsx',
        'src/features/genomics/components/GenomicCriteriaPanel.tsx',
        'src/features/genomics/api/**',
        'src/features/genomics/types/**',
        'src/features/auth/components/ChangePasswordModal.tsx',
        'src/hooks/**',
        'src/lib/echo.ts',
        'src/lib/query-client.ts',
      ],
      // Coverage floor is a ratchet: set just below current coverage (measured
      // 2026-06-21 — lines 69.6%, statements 68.3%, branches 62.6%, funcs 51.8%)
      // so CI enforces "don't regress" without forcing a big test-writing push.
      // CI runs `npm test -- --run --coverage`, which fails the build if any
      // metric drops below these. Ratchet these up over time. [W0-T08]
      thresholds: {
        lines: 60,
        statements: 60,
        branches: 55,
        functions: 45,
      },
    },
  },
});

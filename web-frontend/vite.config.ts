import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// M1 web frontend — no CSS framework dependency to keep the build minimal
// and deterministic (design system is plain CSS, see src/styles/tokens.css).
export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./tests/setup.ts'],
  },
  server: {
    port: 5173,
  },
});

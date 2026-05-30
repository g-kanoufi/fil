import path from 'node:path';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vitest/config';

function vendorChunk(id: string): string | undefined {
  if (!id.includes('node_modules')) {
    return undefined;
  }

  if (id.includes('ag-grid')) {
    return 'ag-grid';
  }

  if (id.includes('recharts') || id.includes('d3-')) {
    return 'recharts';
  }

  if (id.includes('@dwolla')) {
    return 'dwolla';
  }

  if (id.includes('react-plaid-link') || id.includes('/plaid/')) {
    return 'plaid';
  }

  if (id.includes('react-router') || id.includes('@remix-run/router')) {
    return 'router';
  }

  if (id.includes('react-dom') || id.includes('/react/')) {
    return 'react';
  }

  return 'vendor';
}

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
    },
  },
  base: '/fil-assets/',
  build: {
    manifest: true,
    outDir: '../backend/public/fil-assets',
    emptyOutDir: true,
    chunkSizeWarningLimit: 600,
    rollupOptions: {
      input: path.resolve(__dirname, 'src/main.tsx'),
      output: {
        manualChunks: vendorChunk,
      },
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': 'http://127.0.0.1:8000',
      '/documents': 'http://127.0.0.1:8000',
      '/document-exports': 'http://127.0.0.1:8000',
      '/sanctum': 'http://127.0.0.1:8000',
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: './src/test/setup.ts',
  },
});

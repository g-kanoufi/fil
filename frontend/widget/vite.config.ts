import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    outDir: '../../backend/public/widget',
    emptyOutDir: true,
    lib: {
      entry: 'src/main.ts',
      name: 'FilWidget',
      formats: ['iife'],
      fileName: () => 'form.js',
    },
  },
});

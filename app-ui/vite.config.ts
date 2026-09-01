import { fileURLToPath, URL } from 'node:url'

import { defineConfig, type Plugin } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'

const srcDir = fileURLToPath(new URL('./src', import.meta.url))

function resolveAtAlias(): Plugin {
  return {
    name: 'resolve-at-alias',
    enforce: 'pre',
    async resolveId(source, importer, options) {
      if (!source.startsWith('@/')) return null
      const resolved = source.replace(/^@\//, srcDir + '/')
      return this.resolve(resolved, importer, { ...options, skipSelf: true })
    },
  }
}

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const isLib = mode === 'lib'

  return {
    plugins: [
      ...(isLib ? [resolveAtAlias()] : []),
      vue(),
      ...(!isLib ? [vueDevTools()] : []),
      tailwindcss(),
    ],
    resolve: {
      alias: {
        ...(!isLib && { '@': srcDir }),
      },
    },
    ...(isLib
      ? {
          build: {
            lib: {
              entry: fileURLToPath(new URL('./src/index.ts', import.meta.url)),
              formats: ['es'],
            },
            rollupOptions: {
              external: (id: string) =>
                id !== '.' &&
                !id.startsWith('./') &&
                !id.startsWith('../') &&
                !id.startsWith('/') &&
                !id.startsWith('\0') &&
                !id.startsWith('@/'),
              output: {
                preserveModules: true,
                preserveModulesRoot: 'src',
                entryFileNames: '[name].js',
              },
            },
          },
        }
      : {
          server: {
            host: '0.0.0.0',
            port: 3000,
            open: true,
          },
        }),
  }
})

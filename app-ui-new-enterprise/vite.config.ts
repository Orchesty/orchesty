import { fileURLToPath, URL } from 'node:url'
import path from 'node:path'

import { defineConfig, type Plugin } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'

const enterpriseSrcDir = fileURLToPath(new URL('./src', import.meta.url))
const coreSrcDir = fileURLToPath(new URL('../app-ui-new/src', import.meta.url))

function dualAliasPlugin(): Plugin {
  return {
    name: 'vite-plugin-dual-at-alias',
    enforce: 'pre',
    async resolveId(source, importer, options) {
      if (!source.startsWith('@/') || !importer) return null

      const relPath = source.slice(2)
      const isFromCore =
        importer.includes('/app-ui-new/src/') &&
        !importer.includes('/app-ui-new-enterprise/')
      const root = isFromCore ? coreSrcDir : enterpriseSrcDir
      const resolved = path.join(root, relPath)

      return this.resolve(resolved, importer, { ...options, skipSelf: true })
    },
  }
}

// https://vite.dev/config/
export default defineConfig(({ command }) => {
  const isDev = command === 'serve'

  return {
    plugins: [
      ...(isDev ? [dualAliasPlugin()] : []),
      vue(),
      vueDevTools(),
      tailwindcss(),
    ],
    resolve: {
      alias: {
        ...(!isDev && { '@': enterpriseSrcDir }),
        ...(isDev && {
          '@orchesty/ui-core': fileURLToPath(
            new URL('../app-ui-new/src/index.ts', import.meta.url),
          ),
        }),
      },
    },
    server: {
      host: '0.0.0.0',
      port: 3000,
      open: true,
    },
  }
})

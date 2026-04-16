import { fileURLToPath } from 'node:url'
import path from 'node:path'

import { defineConfig, type Plugin, type PluginOption } from 'vite'
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

      if (isFromCore) {
        return this.resolve(path.join(coreSrcDir, relPath), importer, { ...options, skipSelf: true })
      }

      const enterpriseResult = await this.resolve(
        path.join(enterpriseSrcDir, relPath), importer, { ...options, skipSelf: true },
      )
      if (enterpriseResult) return enterpriseResult

      return this.resolve(path.join(coreSrcDir, relPath), importer, { ...options, skipSelf: true })
    },
  }
}

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const plugins: PluginOption[] = [dualAliasPlugin(), vue(), tailwindcss()]

  if (mode !== 'production') {
    plugins.push(vueDevTools())
  }

  return {
    plugins,
    resolve: {
      alias: {
        '@orchesty/ui-core': fileURLToPath(
          new URL('../app-ui-new/src/index.ts', import.meta.url),
        ),
      },
    },
    server: {
      host: '0.0.0.0',
      port: 3000,
      open: true,
    },
  }
})

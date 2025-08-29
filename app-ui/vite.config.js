import { defineConfig } from "vite"
import vuePlugin from "@vitejs/plugin-vue2"
import circularDependency from "vite-plugin-circular-dependency"
import eslintPlugin from "vite-plugin-eslint"
import path from "path"

export default defineConfig({
  plugins: [
    vuePlugin(),
    circularDependency({
      exclude: /node_modules/,
      failOnError: true,
      allowAsyncCycles: false,
      cwd: process.cwd(),
    }),
    eslintPlugin({
      cache: false,
      include: ["src/**/*.js", "src/**/*.vue"],
    }),
  ],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
      vue: "vue/dist/vue.esm.js",
    },
  },
  css: {
    preprocessorOptions: {
      scss: {
        quietDeps: true,
        additionalData: `@use "sass:map"; @use "@/scss/variables.scss" as *;`,
      },
    },
  },
  optimizeDeps: {
    include: ["vuetify"],
  },
  build: {
    target: "es2018",
    sourcemap: false,
    minify: "terser",
  },
  server: {
    port: 3000,
    open: true,
  },
  test: {
    globals: true,
  },
})

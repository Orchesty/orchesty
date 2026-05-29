/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_BACKEND_URL: string
  readonly VITE_STARTING_POINT_URL: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

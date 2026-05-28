function env(value: string | undefined, placeholder: string): string {
  return value || (import.meta.env.PROD ? placeholder : '')
}

export const BACKEND_URL = env(import.meta.env.VITE_BACKEND_URL, '%VITE_BACKEND_URL%')

export const STARTING_POINT_URL = env(
  import.meta.env.VITE_STARTING_POINT_URL,
  '%VITE_STARTING_POINT_URL%',
)

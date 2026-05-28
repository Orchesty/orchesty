import { inject, type Ref, ref } from 'vue'

export const SYSTEM_WORKERS_KEY = Symbol('systemWorkers')

const emptyRef = ref<string[]>([])

export function useSystemWorkers(): Ref<string[]> {
  return inject<Ref<string[]>>(SYSTEM_WORKERS_KEY, emptyRef)
}

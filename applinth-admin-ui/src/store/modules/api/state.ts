export interface RequestDetails {
  id: string
  isSending: boolean
  isError: boolean
  error: string
}

export type ApiState = RequestDetails[]

export const createState = (): ApiState => []

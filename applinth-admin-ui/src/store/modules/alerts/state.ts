import { AlertType } from "../../../enums"

export type Alert = {
  id: string
  message: string
  type: AlertType
  timeout: number
}

export type AlertsState = Alert[]

export const createState = (): AlertsState => []

import { AdminDashboard } from "../../../types/gqlGeneratedPrivate";

export const statusCardsNamespace = "statusCards";

export type StatusCards = Record<
  keyof Omit<AdminDashboard, "__typename">,
  number
>;

export enum StatusCardsMutations {
  Update = "update",
}

export enum StatusCardsActions {
  Fetch = "fetch",
}

export enum StatusCardsGetters {
  GetState = "getState",
}

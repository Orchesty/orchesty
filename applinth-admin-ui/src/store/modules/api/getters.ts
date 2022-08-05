import { Getters } from "../../../types";
import { ApiState, RequestDetails } from "./state";
import { ApiGetters } from "./types";

export const getters: Getters<ApiGetters, ApiState> = {
  getRequestDetails:
    (state) =>
    (id: string): RequestDetails => {
      const requestDetails = state.find(
        (requestDetails) => requestDetails.id === id
      );
      if (requestDetails) {
        return requestDetails;
      } else {
        return {
          id,
          isSending: false,
          isError: false,
          error: "",
        };
      }
    },
  isSending:
    (state) =>
    (ids: string | string[]): boolean => {
      if (!Array.isArray(ids)) {
        const requestDetails = state.find((item) => item.id === ids);
        if (requestDetails) {
          return requestDetails.isSending;
        } else {
          return false;
        }
      } else {
        const requestDetails = state.filter((item) => ids.includes(item.id));
        return requestDetails.some((item) => item.isSending);
      }
    },
};

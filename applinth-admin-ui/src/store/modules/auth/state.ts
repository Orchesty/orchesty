import { Admin } from "../../../types/gqlGeneratedPublic";

export interface AuthState {
  accessToken: string;
  administrator?: Admin & { settings?: any };
}

export const createState = (): AuthState => {
  return {
    accessToken: "",
    administrator: undefined,
  };
};

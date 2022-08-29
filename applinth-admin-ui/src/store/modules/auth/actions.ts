import { transformUser } from "@/firebase";
import { alerts, assignTokenToApiCall, i18n } from "@/utils";
import { getAuth, signInWithEmailAndPassword } from "firebase/auth";
import { TLoginForm } from "../../../components/auth/types";
import { Actions } from "../../../types";
import { AuthState } from "./state";
import { AuthActions, AuthMutations } from "./types";

export const actions: Actions<AuthActions, AuthState> = {
  async login({ commit }, payload: TLoginForm): Promise<boolean> {
    try {
      const auth = getAuth();
      const email = payload.email;
      const password = payload.password;
      auth.tenantId = payload.tenant;
      const userCredential = await signInWithEmailAndPassword(
        auth,
        email,
        password
      );
      const user = userCredential.user;

      if (user) {
        const token = await user.getIdToken();

        commit(AuthMutations.SetUser, transformUser(user));
        commit(AuthMutations.SetAccessToken, token);

        assignTokenToApiCall(token);
        return true;
      } else {
        return false;
      }
    } catch (error: any) {
      if (error.code === "auth/user-not-found") {
        alerts.addErrorAlert(
          "login-not-successful",
          i18n.t("login.failed") as string
        );
      }
      // TODO log errors to error tracking service
      console.error(`${error.code} ${error.message}`);
      return false;
    }
  },
  updateSettings() {
    // TODO not implemented yet
  },
};

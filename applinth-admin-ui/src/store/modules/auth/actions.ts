import { transformUser } from "@/firebase";
import { alerts, i18n, saveUserWithTokenToStore } from "@/utils";
import {
  getAuth,
  signInWithEmailAndPassword,
  updatePassword,
  User,
  EmailAuthProvider,
  reauthenticateWithCredential,
  updateProfile,
  sendPasswordResetEmail,
} from "firebase/auth";
import { TLoginForm, TResetPasswordForm } from "../../../components/auth/types";
import { Actions } from "../../../types";
import { AuthState } from "./state";
import { AuthActions, AuthMutations } from "./types";
import { UpdateUserInfo } from "@/types/CurrentUser";

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
        await saveUserWithTokenToStore(user);

        return true;
      } else {
        return false;
      }
    } catch (error: any) {
      if (
        error.code === "auth/user-not-found" ||
        error.code === "auth/wrong-password" ||
        error.code === "auth/invalid-tenant-id"
      ) {
        alerts.addErrorAlert(
          "login-not-successful",
          i18n.t("login.failed") as string
        );
      } else if (error.code === "auth/user-disabled") {
        alerts.addErrorAlert(
          "login-not-successful",
          i18n.t("login.userDisabled") as string
        );
      } else {
        alerts.addErrorAlert(
            "init-user-not-successful",
            i18n.t("error.errorOccurredTryItLater") as string
        );
      }
      // TODO log errors to error tracking service
      console.error(`${error.code} ${error.message}`);
      return false;
    }
  },
  async updateSettings({ commit }, payload: UpdateUserInfo): Promise<boolean> {
    try {
      const auth = getAuth();
      const user = auth.currentUser;
      if (!user) throw new Error(i18n.t("auth.userNotFound") as string);

      await updateProfile(user, {
        displayName: payload.displayName,
      });

      commit(
        AuthMutations.SetUser,
        transformUser({ ...user, displayName: payload.displayName })
      );

      return true;
    } catch (error: any) {
      alerts.addErrorAlert("update-user-failed", error.message as string);

      return false;
    }
  },
  async changePassword({ commit }, payload: string): Promise<boolean> {
    const auth = getAuth();

    const user = auth.currentUser;

    try {
      await updatePassword(user as User, payload);
      return true;
    } catch (error: any) {
      if (error.code === "auth/invalid-password") {
        alerts.addErrorAlert(
          "invalid-password",
          i18n.t("password.changeFailed") as string
        );
      } else if (error.code === "auth/weak-password") {
        alerts.addErrorAlert(
          "weak-password",
          i18n.t("password.weakPassword") as string
        );
      }

      return false;
    }
  },
  async reauthenticate({ commit }, payload: string) {
    try {
      const auth = getAuth();
      const user = auth.currentUser;

      if (!user) throw new Error(i18n.t("auth.userNotFound") as string);

      const credential = EmailAuthProvider.credential(
        user.email as string,
        payload // password
      );

      await reauthenticateWithCredential(user, credential);

      return true;
    } catch (error: any) {
      if (error.code === "auth/wrong-password") {
        alerts.addErrorAlert(
          "wrong-password",
          i18n.t("login.wrongPassword") as string
        );
      } else
        alerts.addErrorAlert("reauthenticate-failed", error.message as string);

      return false;
    }
  },
  async sendResetPasswordLink(
    { commit },
    payload: TResetPasswordForm
  ): Promise<boolean> {
    try {
      const auth = getAuth();
      auth.tenantId = payload.tenantId;

      await sendPasswordResetEmail(auth, payload.email);

      alerts.addSuccessAlert(
        "reset-password-sent",
        i18n.t("message.resetPasswordLinkSent") as string
      );

      return true;
    } catch (error: any) {
      if (error.code === "auth/user-not-found") {
        alerts.addErrorAlert(
          "reset-password-failed",
          i18n.t("auth.userNotFound") as string
        );
      } else if (error.code === "auth/invalid-tenant-id") {
        alerts.addErrorAlert(
          "reset-password-failed",
          i18n.t("auth.tenantNotFound") as string
        );
      } else
        alerts.addErrorAlert("reset-password-failed", error.message as string);

      console.error(error.code, error);
      return false;
    }
  },
};

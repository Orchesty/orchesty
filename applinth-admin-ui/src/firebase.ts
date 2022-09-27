import { initializeApp } from "firebase/app";
import {
  getAuth,
  onAuthStateChanged,
  User as FirebaseUser,
} from "firebase/auth";
import { User } from "./store/modules/auth";
import { alerts, i18n, saveUserWithTokenToStore } from "@/utils";

const config = {
  apiKey: "AIzaSyANIRePUXX1f8fr-IS2ljSU8FgLkC53a0o",
  authDomain: "orchesty-cloud-stage.firebaseapp.com",
};

export function transformUser(user: FirebaseUser | null): User | null {
  if (!user) return user;
  return {
    id: user.uid,
    name: user.displayName,
    email: user.email,
    tenantId: user.tenantId,
  };
}

export function initializeFirebaseAuth(initVue: () => void) {
  initializeApp(config);
  const auth = getAuth();
  onAuthStateChanged(auth, async (user) => {
    try {
      if (user) await saveUserWithTokenToStore(user);
    } catch (error: any) {
      alerts.addErrorAlert(
        "init-user-not-successful",
        i18n.t("error.errorOccurredTryItLater") as string
      );
    }

    initVue();
  });
}

import { initializeApp } from "firebase/app";
import {
  getAuth,
  onAuthStateChanged,
  User as FirebaseUser,
} from "firebase/auth";
import store from "./store";
import { AuthMutations, authNamespace, User } from "./store/modules/auth";

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
  onAuthStateChanged(auth, (user) => {
    store.commit(
      `${authNamespace}/${AuthMutations.SetUser}`,
      transformUser(user)
    );
    initVue();
  });
}

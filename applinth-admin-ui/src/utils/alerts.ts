import VueI18n from "vue-i18n";
import { Store } from "vuex";
import { AlertType } from "../enums";
import store from "../store";
import { Alert } from "../store/modules/alerts";
import {
  AlertsMutations,
  alertsNamespace,
} from "../store/modules/alerts/types";
import { i18n } from "./vueI18n";

export class Alerts {
  private i18n: VueI18n;
  private store: Store<any>;

  constructor() {
    this.i18n = i18n;
    this.store = store;
  }

  public removeAlert(id: Alert["id"]) {
    this.store.commit(`${alertsNamespace}/${AlertsMutations.Remove}`, id);
  }

  public addErrorAlert(id: Alert["id"], message: string) {
    this.addAlert({
      id,
      message: this.i18n.t(message).toString(),
      type: AlertType.Error,
    });
  }

  public addSuccessAlert(id: Alert["id"], message: string) {
    this.addAlert({
      id,
      message: this.i18n.t(message).toString(),
      type: AlertType.Success,
    });
  }

  public addInfoAlert(id: Alert["id"], message: string) {
    this.addAlert({
      id,
      message: this.i18n.t(message).toString(),
      type: AlertType.Info,
    });
  }

  public addHiddenAlert(id: Alert["id"], message: string) {
    this.addAlert({
      id,
      message: this.i18n.t(message).toString(),
      type: AlertType.Hidden,
    });
  }

  private addAlert({
    id,
    message,
    type,
  }: Pick<Alert, "id" | "message" | "type">) {
    this.store.commit(`${alertsNamespace}/${AlertsMutations.Add}`, {
      id,
      message,
      type,
      timeout: 5000,
    } as Alert);
  }
}

export const alerts = new Alerts();

import { ActionContext } from "vuex";

export type Actions<A extends string, S, RS = any> = {
  [index in A]: (
    context: ActionContext<S, RS>,
    payload: any
  ) => void | any | Promise<any>;
};

export type Mutations<M extends string, S> = {
  [index in M]: (state: S, payload: any) => void;
};

export type Getters<G extends string, S> = {
  [index in G]: (state: S) => any;
};

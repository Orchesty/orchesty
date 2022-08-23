import { Rules } from "../../utils/veeValidate";

export type LoginInputs = "tenant" | "email" | "password";
export type TLoginForm = { [index in LoginInputs]: any };
export type TLoginRules = { [index in LoginInputs]?: Rules };

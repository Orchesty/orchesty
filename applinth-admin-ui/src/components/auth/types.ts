import { Rules } from "../../utils/veeValidate";

export type LoginInputs = "email" | "password";
export type TLoginForm = { [index in LoginInputs]: any };
export type TLoginRules = { [index in LoginInputs]?: Rules };

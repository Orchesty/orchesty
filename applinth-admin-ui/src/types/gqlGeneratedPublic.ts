export type Maybe<T> = T | null;
export type InputMaybe<T> = Maybe<T>;
export type Exact<T extends { [key: string]: unknown }> = {
  [K in keyof T]: T[K];
};
export type MakeOptional<T, K extends keyof T> = Omit<T, K> & {
  [SubKey in K]?: Maybe<T[SubKey]>;
};
export type MakeMaybe<T, K extends keyof T> = Omit<T, K> & {
  [SubKey in K]: Maybe<T[SubKey]>;
};
/** All built-in and custom scalars, mapped to their actual values */
export type Scalars = {
  ID: string;
  String: string;
  Boolean: boolean;
  Int: number;
  Float: number;
};

export type Admin = {
  __typename?: "Admin";
  firstname: Scalars["String"];
  id: Scalars["Int"];
  isSuperAdmin: Scalars["Boolean"];
  surname: Scalars["String"];
  username: Scalars["String"];
};

export type AdminLogin = {
  __typename?: "AdminLogin";
  accessToken: Scalars["String"];
  admin: Admin;
  adminId: Scalars["Int"];
  expiresIn: Scalars["Int"];
};

export type LoginInput = {
  password: Scalars["String"];
  username: Scalars["String"];
};

export type Mutation = {
  __typename?: "Mutation";
  login: AdminLogin;
  refreshToken: AdminLogin;
  resetPassword: Scalars["Boolean"];
  setPassword: Scalars["Boolean"];
};

export type MutationLoginArgs = {
  input: LoginInput;
};

export type MutationResetPasswordArgs = {
  input: ResetPasswordInput;
};

export type MutationSetPasswordArgs = {
  input: SetPasswordInput;
};

export type Query = {
  __typename?: "Query";
  verifyToken: Admin;
};

export type QueryVerifyTokenArgs = {
  input: VerifyTokenInput;
};

export type ResetPasswordInput = {
  username: Scalars["String"];
};

export type SetPasswordInput = {
  password: Scalars["String"];
  token: Scalars["String"];
};

export type VerifyTokenInput = {
  token: Scalars["String"];
};

export type AdministratorFragment = {
  __typename?: "Admin";
  username: string;
  firstname: string;
  surname: string;
  isSuperAdmin: boolean;
};

export type LoginMutationVariables = Exact<{
  username: Scalars["String"];
  password: Scalars["String"];
}>;

export type LoginMutation = {
  __typename?: "Mutation";
  login: {
    __typename?: "AdminLogin";
    accessToken: string;
    expiresIn: number;
    adminId: number;
    admin: {
      __typename?: "Admin";
      username: string;
      firstname: string;
      surname: string;
      isSuperAdmin: boolean;
    };
  };
};

export type RefreshTokenMutationVariables = Exact<{ [key: string]: never }>;

export type RefreshTokenMutation = {
  __typename?: "Mutation";
  refreshToken: {
    __typename?: "AdminLogin";
    accessToken: string;
    expiresIn: number;
    adminId: number;
    admin: {
      __typename?: "Admin";
      username: string;
      firstname: string;
      surname: string;
      isSuperAdmin: boolean;
    };
  };
};

export type ResetPasswordMutationVariables = Exact<{
  input: ResetPasswordInput;
}>;

export type ResetPasswordMutation = {
  __typename?: "Mutation";
  resetPassword: boolean;
};

export type SetPasswordMutationVariables = Exact<{
  input: SetPasswordInput;
}>;

export type SetPasswordMutation = {
  __typename?: "Mutation";
  setPassword: boolean;
};

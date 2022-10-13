export * as GqlPrivate from "./gqlGeneratedPrivate";
export * as GqlPublic from "./gqlGeneratedPublic";
export * from "./Assign";
export * from "./vuex";
export * from "./api";
export * from "../components/commons/tables/types";
export * from "./application";

export enum SorterDirectionEnum {
  Ascending = "ASCENDING",
  Descending = "DESCENDING",
}

export enum FilterOperatorEnum {
  LowerThan = "LOWER_THAN",
  LowerThanOrEqual = "LOWER_THAN_OR_EQUAL",
  Equal = "EQUAL",
  NotEqual = "NOT_EQUAL",
  In = "In =",
  NotIn = "NOT_IN",
  Empty = "EMPTY",
  NotEmpty = "NOT_EMPTY",
  Between = "BETWEEN",
  NotBetween = "NOT_BETWEEN",
  Start = "START",
  Like = "LIKE",
  End = "END",
  GreaterThan = "GREATER_THAN",
  GreaterThanOrEqual = "GREATER_THAN_OR_EQUAL",
}

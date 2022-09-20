import {
  overview,
  timeBucketUsers,
  timeBucketApps,
  installedApps,
} from "./private/overview";
import { customers } from "./private/customers";
import { dashboard } from "./private/dashboard";
import { users } from "./private/users";

export const api = {
  overview,
  users,
  customers,
  dashboard,
  timeBucketUsers,
  installedApps,
  timeBucketApps,
};

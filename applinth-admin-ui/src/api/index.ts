import {
  overview,
  timeBucketUsers,
  timeBucketApps,
  installedApps,
  overviewUsers,
} from "./private/overview";
import { customers } from "./private/customers";
import { dashboard } from "./private/dashboard";
import { users } from "./private/users";
import { tenants } from "./private/tenants";

export const api = {
  overview,
  users,
  customers,
  dashboard,
  timeBucketUsers,
  installedApps,
  timeBucketApps,
  overviewUsers,
  tenants,
};

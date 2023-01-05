import {
  overview,
  timeBucketUsers,
  timeBucketApps,
  installedApps,
  overviewUsers,
  timeBucketHistory,
} from "./private/overview"
import { customers } from "./private/customers"
import { users } from "./private/users"
import { tenants } from "./private/tenants"

export const api = {
  overview,
  users,
  customers,
  timeBucketUsers,
  installedApps,
  timeBucketApps,
  overviewUsers,
  tenants,
  timeBucketHistory,
}

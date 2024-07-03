import {
  overview,
  timeBucketUsers,
  timeBucketApps,
  installedApps,
  overviewUsers,
  timeBucketHistory,
  overviewFull,
} from "./private/overview"
import { customers } from "./private/customers"
import { users } from "./private/users"
import { tenants } from "./private/tenants"

export const api = {
  overview,
  overviewFull,
  users,
  customers,
  timeBucketUsers,
  installedApps,
  timeBucketApps,
  overviewUsers,
  tenants,
  timeBucketHistory,
}

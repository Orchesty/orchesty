import { defineAbility } from '@casl/ability'
import { ACL } from '../enums'

export const ability = defineAbility((can) => {
  can('read', ACL.DASHBOARD_PAGE)
  can('read', ACL.USERS_PAGE)
})

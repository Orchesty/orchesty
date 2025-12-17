<template>
  <content-basic :title="$t('navigation.users')">
    <users-grid />
  </content-basic>
</template>

<script>
import UsersGrid from "../../components/app/users/grid/UsersGrid.vue"
import ContentBasic from "@/components/layout/content/ContentBasic.vue"

export default {
  name: "UsersPage",
  components: { ContentBasic, UsersGrid },
}
</script>

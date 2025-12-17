<template>
  <content-basic :title="$t('navigation.trash')">
    <trash-handler />
  </content-basic>
</template>

<script>
import ContentBasic from "@/components/layout/content/ContentBasic.vue"
import TrashHandler from "@/components/app/trash/grid/TrashHandler.vue"
export default {
  name: "TrashPage",
  components: { TrashHandler, ContentBasic },
}
</script>

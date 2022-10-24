<template>
  <content-basic :title="$t('navigation.trash')">
    <trash-handler />
  </content-basic>
</template>

<script>
import ContentBasic from "@/components/layout/content/ContentBasic"
import TrashHandler from "@/components/app/trash/grid/TrashHandler"
export default {
  name: "TrashPage",
  components: { TrashHandler, ContentBasic },
}
</script>

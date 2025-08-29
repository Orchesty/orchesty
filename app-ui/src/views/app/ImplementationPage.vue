<template>
  <content-basic :title="$t('navigation.implementations')">
    <template #default>
      <implementation-grid />
    </template>
    <template #nav-buttons>
      <implementation-create-modal />
    </template>
  </content-basic>
</template>

<script>
import ContentBasic from "@/components/layout/content/ContentBasic.vue"
import ImplementationGrid from "@/components/app/implementations/grid/ImplementationGrid.vue"
import ImplementationCreateModal from "@/components/app/implementations/modal/ImplementationCreateModal.vue"

export default {
  name: "ImplementationPage",
  components: { ImplementationCreateModal, ContentBasic, ImplementationGrid },
}
</script>

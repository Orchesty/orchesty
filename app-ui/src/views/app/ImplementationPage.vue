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
import ContentBasic from "@/components/layout/content/ContentBasic"
import ImplementationGrid from "@/components/app/implementations/grid/ImplementationGrid"
import ImplementationCreateModal from "@/components/app/implementations/modal/ImplementationCreateModal"

export default {
  name: "ImplementationPage",
  components: { ImplementationCreateModal, ContentBasic, ImplementationGrid },
}
</script>

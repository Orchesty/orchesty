<template>
  <CenteredLayout>
    <AuthSplitLayout>
      <template #heading>
        {{ $t('resetPasswordPage.header') }}
      </template>
      <template #form>
        {{ $t('resetPasswordPage.body') }}
      </template>
    </AuthSplitLayout>
  </CenteredLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import CenteredLayout from "@/components/commons/layouts/CenteredLayout.vue";
import AuthSplitLayout from "@/components/commons/layouts/AuthSplitLayout.vue";

@Component({
  components: {
    AuthSplitLayout,
    CenteredLayout,
  },
})
export default class ResetPasswordPage extends Vue {}
</script>

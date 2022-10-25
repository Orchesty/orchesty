<template>
  <auth-layout>
    <auth-split-layout>
      <template #form>
        <h1 class="text-center">{{ $t("page.text.pageNotFound") }}</h1>
      </template>
    </auth-split-layout>
  </auth-layout>
</template>

<script>
import AuthSplitLayout from "@/components/app/auth/layout/AuthSplitLayout"
import AuthLayout from "@/components/layout/auth/AuthLayout"

export default {
  name: "NotFoundPage",
  components: { AuthLayout, AuthSplitLayout },
}
</script>

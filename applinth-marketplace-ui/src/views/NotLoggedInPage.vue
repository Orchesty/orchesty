<template>
  <div class="text-center">
    <heading>
      {{ $t("notLoggedInPage.heading") }}
    </heading>
    <sub-heading class="mt-5">
      {{ $t("notLoggedInPage.redirectNotSet") }}
    </sub-heading>
  </div>
</template>

<script>
import Heading from "@/components/commons/Heading.vue"
import SubHeading from "@/components/commons/SubHeading.vue"

export default {
  name: "NotLoggedInPage",
  components: { Heading, SubHeading },
}
</script>

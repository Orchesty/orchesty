<template>
  <v-row>
    <v-col cols="12" class="text-right"> <JwtTokenFormModal /> </v-col>
    <v-col><TokensList /></v-col>
  </v-row>
</template>

<script>
import TokensList from "@/components/app/jwtToken/list/TokensList.vue"
import JwtTokenFormModal from "@/components/app/jwtToken/modal/JwtTokenFormModal.vue"

export default {
  name: "JWTTokensHandler",
  components: { JwtTokenFormModal, TokensList },
}
</script>

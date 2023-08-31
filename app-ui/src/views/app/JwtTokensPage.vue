<template>
  <ContentBasic :title="$t('navigation.jwtTokens')">
    <v-row>
      <v-col cols="12">
        <JWTTokensHandler />
      </v-col>
    </v-row>
  </ContentBasic>
</template>

<script>
import JWTTokensHandler from "@/components/app/jwtToken/JWTTokensHandler.vue"
import ContentBasic from "@/components/layout/content/ContentBasic"

export default {
  name: "JwtTokensPage",
  components: {
    JWTTokensHandler,
    ContentBasic,
  },
}
</script>

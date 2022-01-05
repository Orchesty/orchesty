<template>
  <v-footer app inset>
    <v-row>
      <v-col class="text-center subtitle-2"> &copy;{{ year }} — HANABOSO, s.r.o. </v-col>
    </v-row>
  </v-footer>
</template>

<script>
export default {
  name: 'PageFooter',
  computed: {
    year() {
      const startYear = 2018
      const currentYear = new Date().getFullYear()

      return currentYear !== startYear ? `${startYear} - ${currentYear}` : startYear
    },
  },
}
</script>

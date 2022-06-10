<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h3 class="title font-weight-bold">{{ $t('profile.license.title') }}</h3>
      </v-col>
    </v-row>
    <v-row v-for="(value, key) in licenseData" :key="key" dense>
      <v-col v-if="licenseEnums[key]" class="mb-1">
        <span class="font-weight-bold"> {{ licenseEnums[key] }}: </span>
      </v-col>
      <v-col v-if="licenseEnums[key]" class="mb-1">
        <span>{{
          licenseEnums[key].endsWith('date') ? $options.filters.internationalFormatTimestamp(value) : value
        }}</span>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import { AUTH } from '@/store/modules/auth/types'
import jwtDecode from 'jwt-decode'
import { internationalFormatTimestamp } from '@/services/utils/dateFilters'

export default {
  name: 'License',
  data() {
    return {
      licenseData: null,
      licenseEnums: {
        type: 'License type',
        number: 'License number',
        email: 'Email',
        name: 'Name',
        iss: 'Issued',
        iat: 'Acquisition date',
        exp: 'Expiration date',
        users: 'Allowed users',
        applications: 'Allowed apps',
      },
    }
  },
  computed: {
    ...mapGetters(AUTH.NAMESPACE, { token: AUTH.GETTERS.GET_TOKEN }),
  },
  watch: {
    token: {
      immediate: true,
      deep: true,
      handler(token) {
        this.licenseData = jwtDecode(jwtDecode(token).license)
      },
    },
  },
  filters: {
    internationalFormatTimestamp,
  },
}
</script>

<style scoped></style>

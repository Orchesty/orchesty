<template>
  <v-card>
    <v-container>
      <v-row>
        <v-col cols="12" class="mb-2">
          <h3 class="title font-weight-normal">{{ $t('profile.license.title') }}</h3>
        </v-col>
      </v-row>
      <v-row v-for="(value, key) in licenseData" :key="key" dense>
        <v-col v-if="licenseEnums[key]" cols="12" class="mb-1">
          <span class="subtitle-2">
            {{ licenseEnums[key] }}:
            {{ licenseEnums[key].endsWith('date') ? $options.filters.internationalFormatTimestamp(value) : value }}
          </span>
        </v-col>
      </v-row>
    </v-container>
  </v-card>
</template>

<script>
import { mapState } from 'vuex'
import { AUTH } from '@/store/modules/auth/types'
import jwtDecode from 'jwt-decode'
import { internationalFormatTimestamp } from '@/filters'

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
    ...mapState(AUTH.NAMESPACE, ['user']),
  },
  watch: {
    user: {
      immediate: true,
      deep: true,
      handler(val) {
        this.licenseData = jwtDecode(jwtDecode(val.token).license)
      },
    },
  },
  filters: {
    internationalFormatTimestamp,
  },
}
</script>

<style scoped></style>

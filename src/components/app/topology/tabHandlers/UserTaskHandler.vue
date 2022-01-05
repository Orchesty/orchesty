<template>
  <v-row>
    <v-col cols="12">
      <user-task-grid />
    </v-col>
  </v-row>
</template>

<script>
import UserTaskGrid from '@/components/app/userTasks/grid/UserTaskGrid'
export default {
  name: 'UserTaskHandler',
  components: { UserTaskGrid },
}
</script>

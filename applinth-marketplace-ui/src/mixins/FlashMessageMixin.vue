<script>
import { mapActions } from 'vuex'

export default {
  name: 'FlashMessageMixin',
  methods: {
    ...mapActions('flashMessages', ['flashMessageSet']),
    showFlashMessage(message) {
      this.flashMessageSet(message)
    },
  },
}
</script>

<style scoped></style>

<script>
export default {
  name: 'FormMixin',
  props: {
    onSubmit: {
      type: Function,
      required: false,
      default: null,
    },
    isSending: {
      type: Boolean,
      required: false,
      default: false,
    },
    isError: {
      type: Boolean,
      default: false,
    },
    errors: {
      type: Array,
      default: () => [],
    },
    readonly: {
      type: Boolean,
      required: false,
      default: false,
    },
  },
}
</script>

<template>
  <TextField
    v-model="value"
    type="email"
    :name="name"
    :label="label"
    :float-label="floatLabel"
    :disabled="disabled"
    :disabled-text="disabledText"
    :hint="hint"
    :prefix-text="prefixText"
  />
</template>

<script>
export default {
  name: "EmailField",
  props: {
    name: {
      type: String,
      required: true,
    },
    modelValue: {
      type: String,
      required: false,
      default: '',
    },
    label: {
      type: [String, Boolean],
      required: false,
      default: '',
    },
    floatLabel: {
      type: Boolean,
      default: () => false,
    },
    disabled: {
      type: Boolean,
      required: false,
      default: () => false,
    },
    disabledText: {
      type: String,
      required: false,
      default: '',
    },
    hint: {
      type: String,
      required: false,
      default: '',
    },
    prefixText: {
      type: String,
      required: false,
      default: '',
    },
  },
  emits: ['update:modelValue'],
  
  computed: {
    value: {
      get () {
        return this.modelValue;
      },
      set (value) {
        this.$emit('update:modelValue', value);
      },
    },
  },
};
</script>

<template>
  <TextField
    v-model="value"
    type="datetime-local"
    :name="name"
    :label="label"
    :float-label="floatLabel"
    :disabled="disabled"
    :disabled-text="disabledText"
    :hint="hint"
    :prefix-text="prefixText"
    :min="min"
    :max="max"
  />
</template>

<script>
export default {
  name: "DateTimeField",
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
    min: {
      type: String,
      required: false,
      default: undefined,
    },
    max: {
      type: String,
      required: false,
      default: undefined,
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

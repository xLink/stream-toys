<template>
  <TextField
    v-model="value"
    type="number"
    :name="name"
    :label="label"
    :float-label="floatLabel"
    :disabled="disabled"
    :disabled-text="disabledText"
    :hint="hint"
    :prefix-text="prefixText"
    :min="min"
    :max="max"
    :step="step"
  />
</template>

<script>
export default {
  name: "NumberField",
  props: {
    name: {
      type: String,
      required: true,
    },
    modelValue: {
      type: [Number, String],
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
      type: [Number, String],
      required: false,
      default: undefined,
    },
    max: {
      type: [Number, String],
      required: false,
      default: undefined,
    },
    step: {
      type: [Number, String],
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

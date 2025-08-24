<template>
  <label
    v-if="label"
    :for="fieldName"
    class="form-label cursor-pointer"
    @click="model = value"
  >
    <input
      ref="inputValue"
      v-model="model"
      type="radio"
      :name="fieldName"
      :value="value"
      :checked="model === value"
      :disabled="disabled"
      :class="{
        'opacity-50 cursor-not-allowed': disabled,
        'checked': model,
        'hidden': hideInput,
      }"
    >
    {{ label }}
  </label>
</template>

<script>
export default {
  name: 'RadioField',
  props: {
    name: {
      type: String,
      required: true,
    },
    modelValue: {
      type: [String, Number, Boolean],
      default: () => false,
    },
    value: {
      type: String,
      required: true,
    },
    label: {
      type: String,
      required: false,
      default: '',
    },
    disabled: {
      type: Boolean,
      required: false,
      default: () => false,
    },
    hideInput: {
      type: Boolean,
      required: false,
      default: () => false,
    },
  },
  emits: ['update:modelValue'],

  methods: {
    toggleCheckbox() {
      this.model = !this.model;
    },
  },

  computed: {
    fieldName() {
      return this.name || null;
    },
    model:{
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit('update:modelValue', value);
      },
    },
  },
};
</script>

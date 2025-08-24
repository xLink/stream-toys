<template>
  <div
    class="flex flex-row group field-wrapper select-none gap-3"
  >
    <input
      ref="inputValue"
      v-model="model"
      type="checkbox"
      :name="fieldName"
      :checked="model"
      :disabled="disabled"
      :class="{
        'opacity-50 cursor-not-allowed': disabled,
        'checked': model,
      }"
    >
    <label
      v-if="label"
      :for="fieldName"
      class="form-label mb-1"
      @click="toggleCheckbox"
    >
      {{ label }}
    </label>
  </div>
</template>

<script>
export default {
  name: 'CheckboxField',
  props: {
    name: {
      type: String,
      required: true,
    },
    modelValue: {
      type: [Array, Boolean],
      default: () => false,
    },
    label: {
      type: [String, Boolean],
      required: false,
      default: '',
    },
    disabled: {
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

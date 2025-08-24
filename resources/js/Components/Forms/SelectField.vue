<template>
  <div
    class="form-element flex flex-col group field-wrapper select-none gap-3"
  >
    <label
      v-if="floatingLabel && label"
      :for="fieldName"
      class="form-label mb-1"
      @click="$refs.inputValue.focus()"
    >
      {{ label }}
    </label>
    <select
      ref="inputValue"
      v-model="value"
      :name="fieldName"
      :disabled="disabled"
      :class="[ 'select', fieldStyleClasses, {
        'opacity-50 cursor-not-allowed': disabled,
      }]"
    >
      <option
        v-if="placeholder"
        :value="false"
        selected
      >
        {{ placeholder }}
      </option>
      <option
        v-for="data in dataset"
        :key="data[0]"
        :value="data[0]"
        :selected="data[0] === value"
        :class="{
          'selected': data[0] === value,
        }"
      >
        {{ data[1] }}
      </option>
    </select>
    <span
      v-if="helpText"
      class="text-red-500 text-sm inset-0 italic"
    >
      {{ helpText }}
    </span>
  </div>
</template>

<script>
export default {
  name: 'SelectField',
  props: {
    name: {
      type: String,
      required: true,
    },
    modelValue: {
      type: [Number, String, Boolean],
      default: () => false,
    },
    options: {
      type: [Array, Object],
      required: true,
    },
    label: {
      type: [String, Boolean],
      required: false,
      default: '',
    },
    placeholder: {
      type: String,
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
    hint: {
      type: String,
      required: false,
      default: '',
    },
  },
  emits: ['update:modelValue'],

  computed: {
    fieldName() {
      return this.name || null;
    },

    value: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit('update:modelValue', value);
      },
    },

    dataset() {
      if (Array.isArray(this.options)) {
        return this.options.map((option) => {
          return [option, option];
        });
      } else if (typeof this.options === 'object') {
        return Object.entries(this.options);
      }

      return [];
    },

    helpText () {
      if (this.disabled && this.disabledText) return this.disabledText;
      if (this.hint) return this.hint;
      return null;
    },

    fieldStyleClasses() {
      let classes = [];

      if (this.disabled) {
        classes = [...classes, 'opacity-50', 'cursor-not-allowed'];
      }

      if (this.helpText) {
        classes = [...classes, 'border-red-500'];
      } else {
        classes = [...classes, 'border-gray-400'];
      }

      return classes;
    },

    floatingLabel () {
      return this.floatLabel ? this.label : null;
    },
  },
};
</script>

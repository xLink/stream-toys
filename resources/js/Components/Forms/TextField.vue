<template>
  <div
    class="form-element flex flex-col group w-full field-wrapper select-none"
    :class="[ 'input-' + type, {focused: labelIsActive, 'hasPrefix': !!prefixText }]"
  >
    <label
      v-if="floatingLabel && label"
      :for="fieldName"
      class="form-label mb-1"
      @click="$refs.inputValue.focus()"
    >
      {{ label }}
    </label>
    <input
      ref="inputValue"
      v-model="value"
      :name="fieldName"
      :disabled="disabled"
      :type="type"
      class="input"
      :class="fieldStyleClasses"
      :style="inputStyles"
      :placeholder="placeholderText"
      :list="list.length ? `${fieldName}-list` : ''"
      @focus="focused = true"
      @blur="focused = false"
    >
    <span
      v-if="prefixText"
      ref="prefix"
      class="prefixText"
      :class="prefixStyleClasses"
    >{{ prefixText }}</span>
    <span
      v-if="helpText"
      class="text-red-500 text-sm inset-0 italic"
    >
      {{ helpText }}
    </span>
    <datalist v-if="list.length" :id="`${fieldName}-list`">
      <option
        v-for="option in list"
        :key="option"
        :value="option"
      />
    </datalist>
  </div>
</template>

<script>
export default {
  name: 'TextField',
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
    type: {
      type: String,
      required: false,
      default: 'text',
    },
    floatLabel: {
      type: Boolean,
      default () {
        return false;
      },
    },
    disabled: {
      type: Boolean,
      required: false,
      default () {
        return false;
      },
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
    list: {
      type: Array,
      required: false,
      default: () => [],
    },
  },
  emits: ['update:modelValue'],

  data () {
    return {
      focused: false,
    };
  },

  computed: {
    fieldName () {
      return this.name || null;
    },

    labelIsActive () {
      return this.focused === true ||
        (this.$refs.inputValue && this.$refs.inputValue.value !== '');
    },

    helpText () {
      if (this.disabled && this.disabledText) return this.disabledText;
      if (this.hint) return this.hint;
      return null;
    },

    fieldStyleClasses() {
      let classes = [];

      if (this.disabled)
        classes = [...classes, 'opacity-50', 'cursor-not-allowed'];

      if (this.helpText) {
        classes = [...classes, 'border-red-500'];
      } else {
        classes = [...classes, 'border-gray-400'];
      }

      if (this.type === 'color') {
        classes = [...classes, 'h-12', 'cursor-pointer', 'p-1'];
      } else {
        classes = [...classes, 'px-3', 'py-2'];
      }

      return classes;
    },

    prefixStyleClasses () {
      return 'text-gray-700 py-3 px-3';
    },

    inputStyles () {
      if (!this.prefixText) return;

      return 'padding-left: 85px !important;';
    },

    floatingLabel () {
      return this.floatLabel ? this.label : null;
    },

    placeholderText () {
      return this.floatLabel ? null : this.label;
    },

    value: {
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

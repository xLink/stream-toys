<template>
  <div
    class="group w-full field-wrapper select-none mb-2"
    :class="{
      focused: labelIsActive,
      'hasPrefix': !!this.prefixText
    }"
  >
    <label :for="fieldName"
      @click="$refs.modelValue.focus()"
      v-if="floatingLabel && label"
      class="text-black dark:text-white"
    >
      {{ label }} ({{ modelValue }})
    </label>
    <input
      :name="fieldName"
      :disabled="disabled"
      type="range"
      ref="modelValue"
      @change="updateInput"
      @focus="onFocus"
      @blur="onBlur"
      @keyup.enter="onEnter"
      class="custom-range"
      :class="fieldRangeStyleClasses"
      :style="inputStyles"
      :placeholder="placeholderText"
      :value="modelValue"
      :min="min"
      :max="max"
      :step="step"
    />
    <span v-if="prefixText"
      class="prefixText"
      :class="prefixStyleClasses"
      ref="prefix"
    >
      {{ prefixText }}
    </span>
    <span
      class="text-gray-400 text-sm inset-0 italic"
      v-if="helpText"
      v-html="helpText"
    ></span>
  </div>
</template>

<script>
export default {
  name: 'RangeField2',
  emits: ['update:modelValue', 'inputChange'],
  data() {
    return {
      focused: false,
      oldValue: null
    }
  },
  mounted () {
    if (this.modelValue !== null && this.modelValue !== '') {
      this.focused = true
    }
  },
  props: {
    name: {
      type: String,
      required: true
    },
    modelValue: {
      type: [String, Number],
      required: false
    },
    label: {
      type: String,
      required: false
    },
    floatLabel: {
      type: Boolean,
      default () {
        return false
      }
    },
    autoFocus: {
      type: Boolean,
      required: false,
      default () {
        return false
      }
    },
    disabled: {
      type: Boolean,
      required: false,
      default () {
        return false
      }
    },
    disabledText: {
      type: String,
      required: false
    },
    hint: {
      type: String,
      required: false
    },
    prefixText: {
      type: String,
      required: false
    },
    min: {
      type: Number,
      required: false,
      default() {
        return 0;
      }
    },
    max: {
      type: Number,
      required: false,
      default() {
        return 100;
      }
    },
    step: {
      type: Number,
      required: false,
      default() {
        return 1;
      }
    },
    inputClass: {
      type: String,
      required: false,
      default() {
        return 'bg-blue-300';
      }
    },
    size: {
      type: String,
      required: false,
      default() {
        return '';
      }
    }
  },
  methods: {
    updateInput(event) {
      this.oldValue = this.modelValue;
      this.$emit('update:modelValue', event.target.value);
      this.saveInputValue(event);
    },
    onBlur(event) {
      this.focused = false;
      this.saveInputValue(event);
    },
    onFocus() {
      this.focused = true;
    },
    onEnter(event) {
      this.saveInputValue(event);
      this.$refs.modelValue.blur();
    },
    saveInputValue(event) {
      let value = event.target.value;
      if (this.oldValue === value) {
        // console.log('[RangeField] no updates needed for '+this.$refs.modelValue.name);
        return;
      }

      // console.log('[RangeField] updated ', {
      //   name: this.$refs.modelValue.name,
      //   value: value
      // });
      this.$emit('inputChange', {
        name: this.$refs.modelValue.name,
        value: value
      });
      this.oldValue = value;
    }
  },
  computed: {
    fieldName () {
      return this.name || null
    },
    labelIsActive () {
      if (this.focused === true) {
        return true;
      }
      if (this.$refs.modelValue && this.$refs.modelValue.value.trim() !== '') {
        return true;
      }

      return false;
    },
    helpText () {
      if (this.disabled && this.disabledText) return this.disabledText
      if (this.hint) return this.hint
      return null
    },
    fieldRangeStyleClasses () {
      let inputClass = this.inputClass.split(' ');
      let classes = 'form-range appearance-none w-full h-2 p-0  focus:outline-none focus:ring-0 focus:shadow-none h-12 range-lg'.split(' ')

      classes = [...classes, ...inputClass];

      if (this.disabled) classes = [...classes, 'opacity-50', 'cursor-not-allowed']

      if (this.prefixText) classes = [...classes, '']

      return classes
    },
    fieldStyleClasses () {
      let classes = 'appearance-none block w-full text-white rounded leading-tight outline-none focus:ring-lightBlue-100 focus:bg-slate-900 bg-slate-800 border border-gray-600';

      switch(this.size) {
        default:
          classes += ' py-3 px-4 h-12';
        break;
        case 'sm':
          classes += ' py-1 px-2 h-10';
        break;
        case 'xs':
          classes += ' px-2 h-8';
        break;
      }

      classes = classes.split(' ');

      if (this.disabled) classes = [...classes, 'opacity-50', 'cursor-not-allowed']

      if (this.prefixText) classes = [...classes, '']

      return classes
    },
    prefixStyleClasses () {
      return '';//'text-gray-700 py-3 px-3'
    },
    inputStyles () {
      if (!this.prefixText) return

      return 'padding-left: 85px !important;'
    },
    floatingLabel () {
      return this.floatLabel ? this.label : null
    },
    placeholderText () {
      return this.floatLabel ? null : this.label
    }
  }
}
</script>

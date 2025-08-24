<template>
  <div
    class="form-radio-group flex group field-wrapper select-none gap-1"
    :class="{
      'flex-col': floatLabel,
      'flex-row': !floatLabel,
    }"
  >
    <label
      v-if="label"
      :for="fieldName"
      class="form-label"
      :class="{
        'mr-1': display === Display.COLUMN,
      }"
      @click="toggleCheckbox"
    >
      {{ label }}
    </label>

    <div
      class="flex flex-wrap rounded border-2 border-zinc-500"
      :class="{
        'flex-col': display === Display.COLUMN,
        'flex-row w-full': display === Display.ROW,
      }"
    >
      <template
        v-for="data in dataset"
        :key="'radiogroup_' + data[0]"
      >
        <RadioField
          :name="fieldName + '[]'"
          :model-value="model"
          :value="data[0]"
          :label="data[1]"
          :disabled="disabled"
          :hide-input="true"
          class="form-radio-group-input flex-1 border border-zinc-500 text-center p-2 cursor-pointer"
          :class="{
            'active': model === data[0],
          }"
          @update:modelValue="model = $event"
        />
      </template>
    </div>
  </div>
</template>

<script>
import Display from '@/Helpers/Enum/Display.js';

export default {
  name: 'RadioGroup',
  props: {
    name: {
      type: String,
      required: true,
    },
    modelValue: {
      type: [String, Number, Boolean],
      default: () => false,
    },
    label: {
      type: String,
      required: false,
      default: '',
    },
    floatLabel: {
      type: Boolean,
      required: false,
      default: () => false,
    },
    disabled: {
      type: Boolean,
      required: false,
      default: () => false,
    },
    display: {
      type: [Number, String],
      required: false,
      default: Display.ROW,
      validate(value) {
        return Object.values(Display).includes(value);
      },
    },
    options: {
      type: [Array, Object],
      required: true,
    },
  },
  emits: ['update:modelValue'],

  data() {
    return {
      Display: Display,
    };
  },

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

  },
};
</script>

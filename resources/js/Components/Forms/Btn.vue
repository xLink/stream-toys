<template>
  <button
    :class="[buttonClasses]"
    :disabled="disabled"
    :type="getType"
  >
    <div v-if="icon.length > 0" class="icon">
      <fa :icon="icon" />
    </div>
    <div class="content">
      <slot />
    </div>
  </button>
</template>

<script>
import ButtonTypes from '@/Helpers/Enum/Buttons.js';
import ButtonSizes from '@/Helpers/Enum/Sizes.js';

export default {
  name: 'Btn',
  props: {
    type: {
      type: String,
      required: true,
      validator (value) {
        return value.toUpperCase() in ButtonTypes;
      },
    },
    disabled: {
      type: Boolean,
      required: false,
      default: () => false,
    },
    icon: {
      type: Array,
      required: false,
      default: () => [],
    },
    iconOnly: {
      type: Boolean,
      required: false,
      default: () => false,
    },
    size: {
      type: String,
      required: false,
      default: ButtonSizes.MEDIUM,
    },
  },

  data () {
    return {
      buttonData: {
        DEFAULT: {
          title: ButtonTypes.SUBMIT,
          classes: 'buttons submit',
        },
        WARNING: {
          title: ButtonTypes.WARNING,
          classes: 'buttons warning',
        },
        INFO: {
          title: ButtonTypes.INFO,
          classes: 'buttons info',
        },
        SUCCESS: {
          title: ButtonTypes.SUCCESS,
          classes: 'buttons success',
        },
        SUBMIT: {
          title: ButtonTypes.SUBMIT,
          classes: 'buttons submit',
        },
      },
    };
  },

  computed: {
    typeName () {
      return this.type.toUpperCase();
    },
    buttonClasses () {
      let classes = [];

      if (this.iconOnly) {
        classes = [...classes, 'icon-only'];
      }

      if (this.icon.length) {
        classes = [...classes, 'icon'];
      }

      if (this.disabled) {
        classes = [...classes, 'disabled'];
      }

      if (this.buttonData.hasOwnProperty(this.typeName)) {
        classes = [...classes, this.buttonData[this.typeName].classes];
      }

      if (this.size) {
        classes = [...classes, this.size];
      }

      return classes;
    },
    getType () {
      if (this.typeName == 'SUBMIT') {
        return 'submit';
      }
      return 'button';
    },
  },
};
</script>

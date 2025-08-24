<template>
  <div class="alert w-full p-4 rounded-md border-l-4" :class="classes" role="alert">
    <p class="font-bold mb-0">
      {{ title }}
    </p>
    <p class="text-sm mb-0">
      <slot />
    </p>
  </div>
</template>

<script>
import AlertTypes from '@/Helpers/Enum/Alerts.js';

export default {
  name: 'Alert',
  props: {
    type: {
      type: String,
      required: true,
      validator (value) {
        return value.toUpperCase() in AlertTypes;
      },
    },
  },

  data () {
    return {
      alertTypes: {
        DEFAULT: {
          title: 'Alert!',
          classes:
            'bg-slate-200 border-slate-500 text-slate-700 ' +
            'dark:bg-slate-600 dark:border-slate-400 dark:text-slate-100',
        },
        INFO: {
          title: 'Info!',
          classes:
            'bg-sky-200 border-sky-500 text-sky-700 ' +
            'dark:bg-sky-800 dark:border-sky-600 dark:text-sky-100',
        },
        SUCCESS: {
          title: 'Success!',
          classes:
            'bg-green-200 border-green-500 text-green-700 ' +
            'dark:bg-green-800 dark:border-green-600 dark:text-green-100',
        },
        WARNING: {
          title: 'Warning!',
          classes:
            'bg-gold-200 border-gold-500 text-gold-700 ' +
            'dark:bg-gold-800 dark:border-gold-600 dark:text-gold-100',
        },
        ERROR: {
          title: 'ERROR!',
          classes:
            'bg-red-200 border-red-500 text-red-700 ' +
            'dark:bg-red-800 dark:border-red-600 dark:text-red-200',
        },
      },
    };
  },

  computed: {
    classes () {
      return this.alertTypes[this.type.toUpperCase()].classes;
    },
    title () {
      return this.alertTypes[this.type.toUpperCase()].title;
    },
  },
};
</script>

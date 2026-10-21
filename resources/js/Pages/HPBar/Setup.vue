<template>
  <Layout>
    <div class="flex flex-col gap-4">
      <div class="p-4 bg-white dark:bg-slate-800 shadow sm:rounded-lg">
        <h2 class="text-2xl font-bold mb-2">
          Preview
        </h2>
        <Gen3HP
          :bar="name || 'preview'"
          :max="previewMax"
          :current="previewCurrent"
          :shield="Math.max(parseInt(shield) || 0, 0)"
          :options="options"
        />
      </div>

      <form
        class="flex flex-col gap-6 p-4 bg-white dark:bg-slate-800 shadow sm:rounded-lg"
        @submit.prevent="create"
      >
        <h2 class="text-2xl font-bold">
          Create HP Bar
        </h2>

        <OptionsForm v-model="options">
          <template #general>
            <TextField
              v-model="name"
              name="name"
              label="Bar Name (used in the URL)"
              :float-label="true"
              :hint="errors.name || ''"
            />
            <NumberField
              v-model="max"
              name="max"
              label="Max HP"
              :float-label="true"
              :min="1"
              :hint="errors.max || ''"
            />
            <NumberField
              v-model="current"
              name="current"
              label="Current HP"
              :float-label="true"
              :min="0"
              :max="max"
              :hint="errors.current || ''"
            />
            <NumberField
              v-model="shield"
              name="shield"
              label="Shield"
              :float-label="true"
              :min="0"
              :hint="errors.shield || ''"
            />
          </template>
        </OptionsForm>

        <ul
          v-if="optionErrors.length"
          class="text-red-500 text-sm list-disc pl-5"
        >
          <li
            v-for="error in optionErrors"
            :key="error"
          >
            {{ error }}
          </li>
        </ul>

        <div class="flex">
          <Btn
            type="submit"
            class="!bg-green-600 hover:!bg-green-700"
            :disabled="processing"
          >
            Create
          </Btn>
        </div>
      </form>
    </div>
  </Layout>
</template>

<script>
import { router } from '@inertiajs/vue3';
import Btn from '@/Components/Forms/Btn.vue';
import Gen3HP from './Gen3HP.vue';
import OptionsForm from './OptionsForm.vue';

export default {
  name: 'HPBarSetup',
  components: {
    Btn,
    Gen3HP,
    OptionsForm,
  },

  props: {
    defaults: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      name: '',
      max: 100,
      current: 100,
      shield: 0,
      options: JSON.parse(JSON.stringify(this.defaults)),
      errors: {},
      processing: false,
    };
  },

  computed: {
    previewMax() {
      return Math.max(parseInt(this.max) || 1, 1);
    },
    previewCurrent() {
      return Math.min(Math.max(parseInt(this.current) || 0, 0), this.previewMax);
    },
    optionErrors() {
      // listed under the form so errors on other tabs are still visible
      return Object.values(this.errors);
    },
  },

  methods: {
    create() {
      this.processing = true;
      router.post(this.route('hp-bar.setup'), {
        name: this.name,
        max: parseInt(this.max) || 0,
        current: parseInt(this.current) || 0,
        shield: parseInt(this.shield) || 0,
        options: this.options,
      }, {
        onError: (errors) => {
          this.errors = errors;
        },
        onFinish: () => {
          this.processing = false;
        },
      });
    },
  },
};
</script>

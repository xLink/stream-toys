<template>
  <Layout>
    <Gen3HP :bar="bar" :max="maxHP" :current="currentHP" :shield="currentShield" :options="localOptions" />

    <template v-if="showOptions">
      <div class="flex flex-col gap-4 mt-4 p-4 bg-white dark:bg-slate-800 shadow sm:rounded-lg">
        <OptionsForm v-model="localOptions">
          <template #general>
            <NumberField
              v-model="maxHP"
              name="options_max"
              label="Max HP"
              :float-label="true"
              :min="1"
            />
            <NumberField
              v-model="currentHP"
              name="options_current"
              label="Current HP"
              :float-label="true"
              :min="0"
              :max="maxHP"
            />
            <NumberField
              v-model="currentShield"
              name="options_shield"
              label="Shield"
              :float-label="true"
              :min="0"
              @change="sendUpdates"
            />

            <div class="flex flex-col gap-2 pt-2 border-t border-zinc-300 dark:border-zinc-600">
              <div class="font-bold">Modify HP</div>
              <div class="flex flex-row items-center gap-2">
                <Btn type="info" @click="addHP(optionsModifyHP)">+</Btn>
                <NumberField
                  v-model="optionsModifyHP"
                  name="options_modifyHP"
                  :min="0"
                  :max="maxHP"
                  class="!w-32"
                  @keyup.enter="modHP(optionsModifyHP)"
                />
                <Btn type="info" @click="removeHP(optionsModifyHP)">-</Btn>
              </div>
            </div>
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

        <div class="flex items-center gap-4">
          <Btn type="submit" class="!bg-green-600 hover:!bg-green-700" :disabled="savingOptions" @click="saveOptions">
            Save Options
          </Btn>
          <span v-if="optionsMessage" class="text-sm">{{ optionsMessage }}</span>
        </div>
      </div>
    </template>

    <template v-if="update">
      <div class="flex flex-row mt-4 p-4 bg-white dark:bg-slate-800 shadow sm:rounded-lg">
        <form @submit.prevent>
          <div class="flex flex-col w-full gap-4">
            <div class="flex flex-row gap-4">
              <div class="flex flex-row">
                <NumberField
                  v-model="currentHP"
                  name="currentHP"
                  label="Current HP"
                  :float-label="true"
                  :min="0"
                  :max="maxHP"
                  class="!w-32"
                  @change="sendUpdates"
                />
              </div>
              <div>
                <NumberField
                  v-model="maxHP"
                  name="maxHP"
                  label="Max HP"
                  :float-label="true"
                  :min="currentHP"
                  class="!w-32"
                  @change="sendUpdates"
                />
              </div>
              <div>
                <NumberField
                  v-model="currentShield"
                  name="shield"
                  label="Shield"
                  :float-label="true"
                  :min="0"
                  class="!w-32"
                  @change="sendUpdates"
                />
              </div>
            </div>

            <div class="flex flex-row gap-4">
              Modify HP
            </div>

            <div class="flex flex-row gap-4 ml-[23%]">
              <div class="pt-1">
                <Btn type="info" @click="addHP(modifyHP)">
                  +
                </Btn>
              </div>
              <div>
                <NumberField
                  v-model="modifyHP"
                  name="modifyHP"
                  :min="0"
                  :max="maxHP"
                  class="!w-32"
                  @keyup.enter="modHP(modifyHP)"
                />
              </div>
              <div class="pt-1">
                <Btn type="info" @click="removeHP(modifyHP)">
                  -
                </Btn>
              </div>
            </div>

            <div class="flex">
              <Btn type="submit" class="mt-4 !bg-green-600 hover:!bg-green-700" @click="sendUpdates">
                Update
              </Btn>
            </div>
          </div>
        </form>
      </div>
    </template>
  </Layout>
</template>

<script>
import Btn from '@/Components/Forms/Btn.vue';
import Gen3HP from './HPBar/Gen3HP.vue';
import OptionsForm from './HPBar/OptionsForm.vue';
import { applyDamage } from '@/Helpers/hpBar.js';
import axios from 'axios';

export default {
  name: 'HPBarIndex',
  components: {
    Btn,
    Gen3HP,
    OptionsForm,
  },

  props: {
    bar: {
      type: String,
      required: false,
      default: 'default',
    },
    max: {
      type: Number,
      required: true,
      default: 100,
    },
    current: {
      type: Number,
      required: true,
      default: 100,
    },
    shield: {
      type: Number,
      required: false,
      default: 0,
    },
    update: {
      type: Boolean,
      required: false,
      default: false,
    },
    options: {
      type: Object,
      required: true,
    },
    showOptions: {
      type: Boolean,
      required: false,
      default: false,
    },
  },

  data() {
    return {
      currentHP: 0,
      maxHP: 0,
      currentShield: 0,
      modifyHP: 0,
      optionsModifyHP: 0,
      localOptions: JSON.parse(JSON.stringify(this.options)),
      savingOptions: false,
      optionsMessage: '',
      optionErrors: [],
    }
  },

  beforeMount() {
    this.currentHP = this.current;
    this.maxHP = this.max;
    this.currentShield = this.shield;

    let channelString = ['App', 'HPBar', this.bar].join('.');
    window.Echo.channel(channelString)
      .listen('HPBar\\Update', (event) => {
        this.currentHP = parseInt(event.hpBar.current_hp);
        this.maxHP = parseInt(event.hpBar.max_hp);
        this.currentShield = parseInt(event.hpBar.shield);
        if (event.hpBar.options) {
          this.localOptions = event.hpBar.options;
        }
      })
    ;
  },

  methods: {
    sendUpdates() {
      axios.post(this.route('hp-bar.update', { bar: this.bar }), {
        current: this.currentHP,
        max: this.maxHP,
        shield: this.currentShield,
        update: true,
      });
    },
    saveOptions() {
      this.savingOptions = true;
      this.optionsMessage = '';
      this.optionErrors = [];

      axios.post(this.route('hp-bar.options', { bar: this.bar }), {
        max: parseInt(this.maxHP) || 0,
        current: parseInt(this.currentHP) || 0,
        shield: parseInt(this.currentShield) || 0,
        options: this.localOptions,
      })
        .then(() => {
          this.optionsMessage = 'Options saved!';
        })
        .catch((error) => {
          const errors = error.response?.data?.errors;
          this.optionErrors = errors
            ? Object.values(errors).flat()
            : ['Failed to save options.'];
        })
        .finally(() => {
          this.savingOptions = false;
        });
    },
    addHP(amount) {
      amount = parseInt(amount) || 0;
      if (amount <= 0) {
        return;
      }
      this.currentHP = Math.min(this.currentHP + amount, this.maxHP);
      this.sendUpdates();
      this.modifyHP = 0;
      this.optionsModifyHP = 0;
    },
    removeHP(amount) {
      amount = parseInt(amount) || 0;
      if (amount <= 0) {
        return;
      }

      // shield first, then HP (clamped to the current bar when damage is capped)
      const result = applyDamage({
        current: this.currentHP,
        max: this.maxHP,
        shield: this.currentShield,
      }, amount, this.localOptions);
      this.currentHP = result.current;
      this.currentShield = result.shield;

      this.sendUpdates();
      this.modifyHP = 0;
      this.optionsModifyHP = 0;
    },
    modHP(amount) {
      if (parseInt(amount) > 0) {
        this.addHP(amount);
      } else if (parseInt(amount) < 0) {
        this.removeHP(-amount);
      }
    },
  },
  
};
</script>

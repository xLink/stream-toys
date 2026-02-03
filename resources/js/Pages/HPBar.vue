<template>
  <Layout>
    <Gen3HP :bar="bar" :max="maxHP" :current="currentHP" :shield="currentShield" />

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
                  :min="currentHP"
                  :max="maxHP"
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
import axios from 'axios';

export default {
  name: 'HPBarIndex',
  components: {
    Gen3HP,
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
  },

  data() {
    return {
      currentHP: 0,
      maxHP: 0,
      currentShield: 0,
      modifyHP: 0,
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
    addHP(amount) {
      if (amount <= 0) {
        amount = 1;
      }
      this.currentHP = Math.min(this.currentHP + parseInt(amount), this.maxHP);
      this.sendUpdates();
      this.modifyHP = 0;
    },
    removeHP(amount) {
      if (amount <= 0) {
        amount = 1;
      }

      if (this.currentShield > 0) {
        // get current shield value
        let shieldLeft = this.currentShield - parseInt(amount);
        if (shieldLeft < 0) {
          // if we go below 0 shield, remove the rest from HP
          this.currentShield = 0;
          this.currentHP = Math.max(this.currentHP + shieldLeft, 0);
        } else {
          this.currentShield = shieldLeft;
        }
      } else {
        this.currentHP = Math.max(this.currentHP - parseInt(amount), 0);
      }

      this.sendUpdates();
      this.modifyHP = 0;
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

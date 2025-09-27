<template>
  <Layout>
    <Gen3HP :bar="bar" :max="maxHP" :current="currentHP" />

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
                  min="0"
                  :max="maxHP"
                  class="!w-32"
                  @change="sendUpdates"
                />
              </div>
              <div>
                <RangeField
                  v-model="currentHP"
                  name="currentHPRange"
                  label="Current HP"
                  :float-label="true"
                  :min="0"
                  :max="maxHP"
                  class="!text-black !w-96"
                  @change="sendUpdates"
                />
              </div>
            </div>

            <div class="flex flex-row gap-4">
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
              <div >
                <RangeField
                  v-model="maxHP"
                  name="maxHPRange"
                  label="Max HP"
                  :float-label="true"
                  min="0"
                  max="0"
                  class="!text-black !w-96"
                  @change="sendUpdates"
                />
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
import RangeField from '@/Components/Forms/RangeField.vue';
import Gen3HP from './HPBar/Gen3HP.vue';

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
    }
  },

  beforeMount() {
    this.currentHP = this.current;
    this.maxHP = this.max;

    let channelString = ['App', 'HPBar', this.bar].join('.');
    window.Echo.channel(channelString)
      .listen('HPBar\\Update', (event) => {
        this.currentHP = parseInt(event.hpBar.current_hp);
        this.maxHP = parseInt(event.hpBar.max_hp);
      })
    ;
  },

  methods: {
    sendUpdates() {
      this.$inertia.post(this.route('hpbar.update', { bar: this.bar }), {
        current: this.currentHP,
        max: this.maxHP,
        update: true,
      }, {
        preserveState: true,
        preserveScroll: true,
        only: ['current', 'max'],
      });
    },
  },
  
};
</script>

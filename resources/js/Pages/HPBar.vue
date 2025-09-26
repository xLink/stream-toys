<template>
  <Layout>
    <Gen3HP :bar="bar" :max="maxHP" :current="currentHP" />
  </Layout>
</template>

<script>
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
        console.log('UpdateBoard event received:', event);
        this.currentHP = parseInt(event.hpBar.current_hp);
        this.maxHP = parseInt(event.hpBar.max_hp);
        console.log('Setting HP to ', this.currentHP, '/', this.maxHP);
      })
      .listenToAll((event, data) => {
        console.log('Uncaught event received:', event, data);
      });
      
  },
  
};
</script>

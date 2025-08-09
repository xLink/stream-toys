<template>
  <Layout>
    <h3 class="text-4xl">Pokédex</h3>

    <div class="flex flex-wrap gap-4">
      <div
        v-for="pokemon in pokedexData"
        :key="pokemon.id"
        class="p-2"
      >
        <img
          :src="pokemon.image"
          :title="getTitle(pokemon)"
          class="h-24 w-24"
          :class="{
            'grayscale contrast-0 brightness-50': !pokemon.is_owned,
          }"
        />
      </div>
    </div>
    <div id="bottom"></div>
  </Layout>
</template>

<script>
export default {
  name: 'PokedexIndex',

  props: {
    pokedexData: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      canScroll: true,
    }
  },

  mounted() {
    this.scrollToBottom();

    // window.addEventListener('scroll', () => {
    //   this.canScroll = false;
    // });
  },

  methods: {
    getTitle(pokemon) {
      return pokemon.name + ' (' + pokemon.id + ')';
    },
    
    scrollToBottom() {
      if (!this.canScroll) { return; }
      
      if (parseInt(window.scrollY) === window.scrollMaxY) {
        window.scrollTo({ top: 0, behavior: "smooth" });
        this.scrollToBottom();
        return;
      }
      
      window.scrollTo({ top: window.scrollY + 125, behavior: "smooth" });
      // setTimeout(() => {
      //   this.scrollToBottom();
      // }, 600);
    },
  },
};
</script>

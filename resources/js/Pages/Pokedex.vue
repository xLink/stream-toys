<template>
  <Layout>
    
    <div class="flex items-center justify-center bg-slate-700 h-20 gap-x-2">
      <div class="flex">
        <h1 class="text-4xl font-bold text-center">Pokédex</h1>
      </div>
    </div>

    <div class="flex w-[90%] mx-auto mt-4 flex-wrap gap-2">
      <div
        v-for="pokemon in pokedexData"
        :key="pokemon.id"
        class="rounded mb-1 min-w-[--width] h-[--height] bg-[--backgroundColor] border border-[--borderColor] transition-all duration-200 ease-in-out"
        style="--width: 71px; --height: 71px; --backgroundColor: #000000; --borderColor: transparent; --hoverBorderColor: #FF00EC;"
      >
        <img
          :src="pokemon.image"
          :title="getTitle(pokemon)"
          class="w-full h-full object-contain"
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

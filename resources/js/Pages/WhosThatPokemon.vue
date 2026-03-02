<template>
  <Layout>
    <div class="flex items-center justify-center bg-slate-700 h-20 gap-x-2">
      <div class="flex">
        <h1 class="text-4xl font-bold text-center">xLinks Who's That Pokémon</h1>
      </div>
    </div>

    <div class="absolute top-1 right-2">
      <div class="star"></div>
      <div class="relative z-50 text-6xl -top-40 -left-1 text-center my-auto ">
        {{ guessedPokemon.length }}
      </div>
    </div>

    <div class="flex flex-col w-[80%] mx-auto mt-4 p-2">
      <div v-if="stage === 1" class="flex w-full flex-wrap gap-2 justify-center">
        <div v-for="(option, key) in genOptions" class="flex flex-col w-[30%]">
          <Btn
            type="info"
            @click="loadPokedex(key)"
            class="flex w-full ml-2"
          >
            {{ option }}
          </Btn>
        </div>
      </div>

      <div v-if="stage === 2" class="flex flex-col w-full">
        <div v-if="currentPokemon" class="flex flex-col w-full">
          <div class="flex justify-center">
            <img
              :src="pokemonImage"
              alt="Who's that Pokémon?"
              class="h-64"
              :class="randomClasses"
            />
          </div>

          <div class="flex justify-center mt-4">
            <TextField
              name="playerGuess"
              v-model="playerGuess"
              label="Who's that Pokémon?"
              float-label
              placeholder="Who's that Pokémon?"
              class="px-4 py-2 w-full max-w-md text-black"
              @keyup.enter="processGuess"
              :list="pokedexOptions"
            />
          </div>
        </div>

        <div class="flex w-[40%] mx-auto">
          <Alert v-if="guessSuccess === 1" type="success">
            Correct!
          </Alert>
          <Alert v-if="guessSuccess === 2" type="error">
            Incorrect! That was {{ currentPokemon.name }}.
          </Alert>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script>
export default {
  name: 'WhosThatPokemonIndex',

  props: {
    generation: {
      type: String,
      required: false,
    },
    pokedexData: {
      type: Object,
      required: true,
    },
    byGen: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      counter: 0,
      form: {
        generation: null,
      },

      genOptions: {
        'kanto':  'Generation 1 (Kanto)',
        'johto':  'Generation 2 (Johto)',
        'hoenn':  'Generation 3 (Hoenn)',
        'sinnoh': 'Generation 4 (Sinnoh)',
        'unova':  'Generation 5 (Unova)',
        'kalos':  'Generation 6 (Kalos)',
        'alola':  'Generation 7 (Alola)',
        'galar':  'Generation 8 (Galar)',
        'paldea': 'Generation 9 (Paldea)',
        'all':    'All Generations',
      },

      guessedPokemon: [],
      currentPokemon: null,
      playerGuess: null,
      guessSuccess: null,
    };
  },

  beforeMount() {
    let gen = this.generation?.toString() ?? null;
    if (gen && Object.keys(this.genOptions).includes(gen)) {
      this.form.generation = gen;
    }

    let guessed = localStorage.getItem('guessed_pokemon');
    if (guessed?.trim().length > 0) {
      this.guessedPokemon = JSON.parse(guessed);
    }

    this.currentPokemon = this.getRandomPokemon();
  },

  methods: {
    getRandomPokemon() {
      const pokedex = Object.values(this.activePokedex);

      let pokemonIdx;
      do {
        pokemonIdx = Math.floor(Math.random() * pokedex.length);
      } while(Object.values(this.guessedPokemon).includes(pokedex[pokemonIdx]?.id));

      return pokedex[pokemonIdx];
    },
    
    processGuess() {
      if (!this.playerGuess || !this.currentPokemon) {
        return;
      }

      const allPokemon = Object.values(this.pokedexData);

      const searchedPokemon = allPokemon.find(pokemon => pokemon.name.toLowerCase() === this.playerGuess.toLowerCase());
      if (searchedPokemon && parseInt(searchedPokemon.id) === parseInt(this.currentPokemon.id)) {
        this.guessedPokemon.push(searchedPokemon.id);
        this.guessSuccess = 1;
      } else {
        this.guessedPokemon = [];
        this.guessSuccess = 2;
      }

      this.playerGuess = null;
      localStorage.setItem('guessed_pokemon', JSON.stringify(this.guessedPokemon));
      setTimeout(() => {
        this.guessSuccess = null;
        this.currentPokemon = this.getRandomPokemon();
      }, 800);
    },
    loadPokedex(gen) {
      window.location = `/whos-that-pokemon/${gen}`;
    }
  },

  computed: {
    stage() {
      if (this.form.generation === null) {
        return 1;
      }
      if (this.form.generation !== null) {
        return 2;
      }
    },

    generationOptions() {
      return Object.entries(this.genOptions)
        .map(
          ([key, value]) => value + (key > 0 ? ' ('+ this.byGen[key] +')' : '')
        )
      ;
    },

    guessedPokemonJSON() {
      return JSON.stringify(this.guessedPokemon);
    },

    randomClasses() {
      let guesses = this.guessedPokemon.length;
      let classes = {
        '': guesses < 10,
        'grayscale': guesses >= 10,
        'grayscale contrast-0 brightness-50': guesses >= 25,
      };

      return classes;
    },

    pokedexOptions() {
      return Object.values(this.pokedexData).map(pokemon => pokemon.name.toLowerCase());
    },

    activePokedex() {
      return Object.values(this.pokedexData).filter(pokemon => {
        return !this.guessedPokemon?.includes(pokemon.id);
      });
    },

    pokemonImage() {
      let guesses = this.guessedPokemon.length;

      let id = this.currentPokemon.id;
      let paddedId = String(id).padStart(3, '0');
      let name = this.currentPokemon.name.toLowerCase();


      let urls = [];
      urls.push(`/images/sprites/${id}.png`);

      if (guesses <= 35) {
        return `/images/sprites/${this.currentPokemon.id}.png`;
      } 

      if (guesses <= 50) {
        return `/images/sprites/${this.currentPokemon.id}.png`;
      }
    }
  },
}
</script>

<style>
.star {
 --star-color:purple;
 margin: 1em auto;
 font-size: 6em;
 position: relative;
 display: block;
 width: 0px;
 height: 0px;
 border-right: 1em solid transparent;
 border-bottom: 0.7em solid var(--star-color);
 border-left: 1em solid transparent;
 transform: rotate(35deg);
}
.star:before {
 border-bottom: 0.8em solid var(--star-color);
 border-left: 0.3em solid transparent;
 border-right: 0.3em solid transparent;
 position: absolute;
 height: 0;
 width: 0;
 top: -0.45em;
 left: -0.65em;
 display: block;
 content:"";
 transform: rotate(-35deg);
}
.star:after {
 position: absolute;
 display: block;
 top: 0.03em;
 left: -1.05em;
 width: 0;
 height: 0;
 border-right: 1em solid transparent;
 border-bottom: 0.7em solid var(--star-color);
 border-left: 1em solid transparent;
 transform: rotate(-70deg);
 content: "";
}
@keyframes horizontal-shaking {
  0% { transform: translateX(0) }
  25% { transform: translateX(5px) }
  50% { transform: translateX(-5px) }
  75% { transform: translateX(5px) }
  100% { transform: translateX(0) }
}
</style>
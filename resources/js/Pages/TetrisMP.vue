<template>
  <Layout>
    <div class="absolute top-4 right-4 cursor-pointer z-50">
      <MenuOpenIcon v-if="showOptions" class="text-white" @click="showOptions = !showOptions" />
      <MenuCloseIcon v-if="!showOptions" class="text-white" @click="showOptions = !showOptions" />
    </div>

    <div class="flex items-center justify-center bg-slate-700 h-20 gap-x-2">
      <div class="flex">
        <h1 class="text-4xl font-bold text-center">xLinks Catch 'em all</h1>
      </div>
    </div>

    <section class="flex flex-col gap-2 mt-6 items-center justify-center">
      <div class="flex flex-col">
        <h2 class="text-2xl font-bold text-center">
          Multiplayer Tetris
        </h2>
        <p class="text-center">
          Play Catch 'em all with your friends! Create a room, share the URL, and start playing together. 
        </p>
        <p class="text-center">
          You can configure the room using the form below, once you're ready, click the "Start Game" button and have fun!
        </p>
      </div>

      <form class="flex flex-col" @submit.prevent="createRoom()">
        <div class="flex flex-col rounded-lg bg-slate-800 p-4 gap-4 mb-4">
          <div class="flex">
            <h3 class="text-xl font-bold text-center">User Info</h3>
          </div>
          <div class="flex flex-col gap-2">
            <a 
              v-if="$attrs.auth.user === null"
              class="flex py-2 bg-indigo-500 hover:bg-indigo-700 cursor-pointer justify-center items-center gap-1 text-2xl rounded-md" 
              :href="route('auth.discord')"
            >
              <fa icon="fa-brands fa-discord" size="0.5x" class="text-white" />
              Login with Discord
            </a>

            <TextField
              v-else
              v-model="roomConfig.username"
              name="username"
              label="Username"
              :disabled="true"
              float-label
            />

            <RadioGroup
              v-model="roomConfig.color"
              :options="options.colors"
              name="color"
              label="Select Color"
              float-label
            />
          </div>
        </div>

        <div class="flex flex-col rounded-lg bg-slate-800 p-4 gap-4 mb-4">
          <div class="flex">
            <h3 class="text-xl font-bold text-center">Room Info</h3>
          </div>
          <div class="flex flex-col gap-2">
            <TextField
              v-model="roomConfig.name"
              name="name"
              label="Room Name"
              float-label
            />

            <PasswordField
              v-model="roomConfig.password"
              name="password"
              label="Room Password (empty for no password)"
              float-label
            />

            <div class="flex gap-4 items-center w-full">
              <div class="flex gap-2 items-center justify-center w-full">
                <TextField
                  v-model="roomConfig.seed"
                  name="seed"
                  label="Room Seed (leave empty for random)"
                  float-label
                />
                <Btn type="warning" class="bg-red-500 text-white h-11 mt-8 p-2 rounded" @click="() => randomSeed()">
                  <RefreshIcon />
                </Btn>
              </div>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-lg">Select Pokedex(es):</label>
              <div class="flex space-x-4">
                <div v-for="(label, key) in options.pokedexes" :key="'dex'+key" class="flex items-center">
                  <label :for="'dexc'+key">
                    <input type="checkbox" :id="'dexc'+key" v-model="roomConfig.pokedex" :value="key">
                    {{ label }}
                  </label>
                </div>
              </div>
            </div>

          </div>

          <div class="flex">
            <h3 class="text-xl font-bold text-center">Board Info</h3>
          </div>
          <div class="flex flex-col gap-2">
            <RangeField
              v-model="roomConfig.tetriminosToGenerate"
              name="tetriminosToGenerate"
              id="tetriminosToGenerate"
              label="Tetriminos to Generate"
              float-label
              :min="1"
              :max="10"
            />
            
            <RangeField
              v-model="roomConfig.perRow"
              name="perRow"
              id="perRow"
              label="Pokémon Per Row"
              float-label
              :min="1"
              :max="40"
              :step="1"
            />

            <RangeField
              v-model="roomConfig.cellSize"
              name="cellSize"
              id="cellSize"
              label="Pokémon Cell Size"
              float-label
              :min="10"
              :max="100"
              :step="1"
            />

            <RangeField
              v-model="roomConfig.cellSpacing"
              name="cellSpacing"
              id="cellSpacing"
              label="Pokémon Cell Spacing"
              float-label
              :min="0"
              :max="20"
              :step="1"
            />

          </div>

          <Btn 
            type="success" 
            class="self-center mt-1 w-full" 
            @click.prevent="createRoom()"
          >
            Start Game
          </Btn>
        </div>

      </form>
    </section>

  </Layout>
</template>

<script>
export default {
  name: 'TetrisMP',

  props: {
    optionsObjects: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      showOptions: false,
      roomConfig: {
        name: null,
        seed: null,
        mode: null,

        username: null,
        color: '#ff0000',
        pokedex: ['gen1'],
        tetriminosToGenerate: 3,
        perRow: 10,
        cellSize: 30,
        cellSpacing: 2,
      },
      options: {
        ...this.optionsObjects,
        pieces: [
          { name: 'i', color: '#00ffff' },
          { name: 'j', color: '#0000ff' },
          { name: 'l', color: '#ffa500' },
          { name: 'o', color: '#ffff00' },
          { name: 's', color: '#00ff00' },
          { name: 't', color: '#800080' },
          { name: 'z', color: '#ff0000' },
          { name: '.', color: '#138e57' },
        ]
      },
      hints: {},
    };
  },

  beforeMount() {
    let lsUser = localStorage.getItem('user') || {};

    try {
      lsUser = JSON.parse(lsUser);
    
      this.roomConfig.color = lsUser.color || 'red';
    } catch (e) {
      lsUser = {};
    }
    this.roomConfig.username = this.$attrs.auth.user?.username || '';

    this.randomSeed();
  },

  methods: {
    createRoom() {
      this.$store.dispatch('tetrismp/createRoom', this.roomConfig)
        .then((response) => {
          console.log('Room created:', response.data['room-uuid']);
          this.$inertia.visit(
            route('tetris-mp.room', { 
              room: response.data['room-uuid'] 
            }), 
          );
        })
        .catch((error) => {
          console.error('Failed to create room:', error);
          alert('Failed to create room. Please try again.');
        });
    },
    saveUserToLS() {
      const userData = {
        name: this.roomConfig.username,
        color: this.roomConfig.color,
      };
      localStorage.setItem('user', JSON.stringify(userData));
    },
    randomSeed() {
      this.roomConfig.seed = Math.floor(Math.random() * 1000000);
    },
  },

  computed: {
  },

  watch: {
    'roomConfig.username'() {
      this.saveUserToLS();
    },
    'roomConfig.color'() {
      this.saveUserToLS();
    },
  },
}
</script>
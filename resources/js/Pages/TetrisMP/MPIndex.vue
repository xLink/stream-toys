<template>
  <layout>
    <div class="absolute top-4 right-4 cursor-pointer z-50">
      <OptionsIcon class="text-white" @click="toggleOptions" />
      <MenuOpenIcon v-if="showSidebar" class="text-white" @click="toggleSidebar" />
      <MenuCloseIcon v-if="!showSidebar" class="text-white" @click="toggleSidebar" />
    </div>

    <div class="flex items-center justify-center bg-slate-700 h-20 gap-x-2">
      <div class="flex flex-col">
        <h1 class="text-4xl font-bold text-center">xLinks Catch 'em all</h1>
        <h5 v-if="loadedUserInfo" class="text-lg text-center">{{ state.name }} - {{ username }}</h5>
      </div>
    </div>

    <section 
      v-if="loadedUserInfo === null || !loadedUserInfo" 
      class="flex flex-col gap-2 mt-6 items-center justify-center"
    >
      <div class="flex flex-col">
        <h2 class="text-2xl font-bold text-center">
          Welcome to Multiplayer Tetris!
        </h2>
        <p class="text-center">
          Please enter a username and select a color to join the game. Your username and color will be saved for future sessions.
        </p>
      </div>

      <div class="flex flex-col rounded-lg bg-slate-800 p-4 gap-4 mb-4">

        <TextField
          v-model="state.name"
          name="roomname"
          label="Room Name"
          :disabled="true"
          float-label
          class="w-64"
        />

        <TextField
          v-model="getOwner"
          name="createdby"
          label="Created By"
          :disabled="true"
          float-label
          class="w-64"
        />

        <a 
          v-if="$attrs.auth.user === null"
          class="flex py-2 bg-indigo-500 hover:bg-indigo-700 cursor-pointer justify-center items-center gap-1 text-2xl rounded-md" 
          :href="route('auth.discord')"
        >
          <fa icon="fa-brands fa-discord" size="sm" class="text-white" />
          Login with Discord
        </a>

        <TextField
          v-else
          v-model="username"
          name="username"
          label="Username"
          :disabled="true"
          float-label
          class="w-64"
        />

        <RadioGroup
          v-model="color"
          :options="optionsObjects.colors"
          :use-color-value="true"
          name="color"
          label="Select Color"
          float-label
          class="w-64 mt-4"
        />
        <Btn type="success" @click="saveUserToLS()">
          Join Room
        </Btn>
      </div>
    </section>
    <section v-else class="flex flex-row w-full">
      <div class="flex h-screen flex-col transition-all"
        :class="{
          'w-10/12': showSidebar,
          'w-full': !showSidebar,
        }"
      >
        <MPOptions v-if="showOptions" :options-objects="optionsObjects" />
        <MPBoard />
        <code>
          <pre>{{ debug }}</pre>
        </code>
      </div>
      <div v-if="showSidebar" class="relative h-screen bg-slate-700 w-2/12">
        <MPSidebar />
      </div>

    </section>
    

  </layout>
</template>

<script>
import { mapFields } from 'vuex-map-fields';
import { mapGetters } from 'vuex';
import MPSidebar from '@/Pages/TetrisMP/MPSidebar.vue';
import MPBoard from '@/Pages/TetrisMP/MPBoard.vue';
import MPOptions from '@/Pages/TetrisMP/MPOptions.vue';

export default {
  name: 'MPIndex',
  components: {
    MPSidebar, MPBoard, MPOptions,
  },

  props: {
    pokedexData: {
      type: Object,
      required: true,
    },
    optionsObjects: {
      type: Object,
      required: true,
    },
    uuid: {
      type: String,
      required: true,
    },
    players: {
      type: Object,
      required: true,
    },
    state: {
      type: Object,
      required: true,
    },
    debug: {
      type: Object,
      required: false,
      default: null,
    }
  },

  data() {
    return {
      username: '',
      color: 'red',
      showOptions: false,
      showSidebar: true,

      onlineUsers: [],
    };
  },

  beforeMount() {
    this.username = this.$attrs.auth.user?.username || '';
    this.$store.dispatch('tetrismp/setCurrentPlayer', this.username);

    this.$store.dispatch('tetrismp/setPieceDef', this.defaultTetriminos);
    this.$store.dispatch('tetrismp/setUuid', this.uuid);
    this.$store.dispatch('tetrismp/setPlayers', this.players);
    this.$store.dispatch('tetrismp/setState', this.state);
    this.$store.dispatch('tetrismp/setBoard', {
      board: Object.values(this.pokedexData),
      perRow: this.state.perRow,
    });

    this.$store.dispatch('tetrismp/regeneratePieces');


    if (this.username !== '') {
      // check this.players for username
      let userInPlayers = false;
      Object.values(this.players).forEach(player => {
        if (player.username === this.username) {
          userInPlayers = true;
        }
      });

      this.color = localStorage.getItem('color') || this.color;

      if (!userInPlayers) {
        this.$store.dispatch('tetrismp/newPlayer', {
          room: this.uuid,
          player: {
            username: this.username,
            color: this.color,
          },
        });
      }
    }

    this.showSidebar = (localStorage.getItem('showSidebar') === 'true');
    
    this.joinRoom();
  },

  methods: {
    joinRoom() {
      let channelString = ['App', 'TetrisMP', this.uuid].join('.');
      window.Echo.join(channelString)
        .here((users) => {
          this.onlineUsers = users;
        })
        .joining((user) => {
          this.onlineUsers.push(user);
        })
        .leaving((user) => {
          this.onlineUsers = this.onlineUsers.filter(u => u.id !== user.id);
        })
        .listen('Tetris\\UpdateBoard', (event) => {
          this.$store.dispatch('tetrismp/setState', event.objRoom.state);
          this.$store.dispatch('tetrismp/setPlayers', event.objRoom.players);
        })
        .listen('Tetris\\ClearBoard', (event) => {
          this.$store.dispatch('tetrismp/clearBoard');
        })
        .listen('Tetris\\UpdateHistory', (event) => {
          this.$store.dispatch('tetrismp/setHistory', event.history);
        })
        .listen('Tetris\\UpdateUsers', (event) => {
          this.$store.dispatch('tetrismp/setPlayers', event.users);
        })
      ;
    },

    saveUserToLS() {
      localStorage.setItem('color', this.color);
    },

    toggleSidebar() {
      localStorage.setItem('showSidebar', !this.showSidebar);
      this.showSidebar = !this.showSidebar;
    },

    toggleOptions() {
      localStorage.setItem('showOptions', !this.showOptions);
      this.showOptions = !this.showOptions;
    },
  },

  computed: {
    ...mapFields('app', ['defaultTetriminos']),
    ...mapFields('tetrismp', [
      'pieceDef',
      'pieceGeneration',
      'currentPlayer',
    ]),

    loadedUserInfo() {
      if (Object.values(this.$attrs.auth).length === 0 || this.$attrs.auth.user === null) {
        return false;
      }

      return true;
    },

    getOwner() {
      return Object.values(this.players)
        .find(player => player.owner === true)
        ?.username || '';
    }
  }
};
</script>
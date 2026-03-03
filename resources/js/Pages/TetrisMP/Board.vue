<template>
  <layout>
    <div class="absolute top-4 right-4 cursor-pointer z-50">
      <MenuOpenIcon v-if="showOptions" class="text-white" @click="showOptions = !showOptions" />
      <MenuCloseIcon v-if="!showOptions" class="text-white" @click="showOptions = !showOptions" />
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
          name="color"
          label="Select Color"
          float-label
          class="w-64 mt-4"
        />
        <Btn type="success" @click="saveUserToLS()">
          Save
        </Btn>
      </div>
    </section>
    <section v-else>

      <div class="flex flex-col">
        <h2 class="text-2xl font-bold text-center">
          Online Users
        </h2>
        <ul class="flex flex-col items-center gap-2 mt-4">
          <li 
            v-for="user in players" 
            :key="user.id" 
            class="flex gap-x-2 px-4 py-2 w-64 bg-slate-800 rounded-lg"
          >
            <div 
              class="w-4 h-4 mt-1 rounded-lg" 
              :style="{ 
                'backgroundColor': user.color 
              }"
            ></div>
            <span>{{ user.username }}</span>
          </li>
        </ul>
      </div>

      <pre><code>{{ user }}</code></pre>
      <pre><code>{{ players }}</code></pre>
      <pre><code>{{ state }}</code></pre>
    </section>



  </layout>
</template>

<script>
import { mapFields } from 'vuex-map-fields';

export default {
  name: 'TetrisMPBoard',

  props: {
    optionsObjects: {
      type: Object,
      required: true,
    },
    user: {
      type: Object,
      required: false,
      default: null,
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
  },

  data() {
    return {
      showOptions: false,
      username: '',
      color: 'red',

      onlineUsers: [],
    };
  },

  beforeMount() {
    this.$store.dispatch('tetrismp/setPlayers', this.players);
    this.$store.dispatch('tetrismp/setState', this.state);

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
      .listen('HPBar\\Update', (event) => {
        this.currentHP = parseInt(event.hpBar.current_hp);
        this.maxHP = parseInt(event.hpBar.max_hp);
        this.currentShield = parseInt(event.hpBar.shield);
      })
    ;
  },

  methods: {
    saveUserToLS() {
      localStorage.setItem('color', this.color);
    },
  },

  computed: {
    loadedUserInfo() {
      if (this.$attrs.auth.user === null) {
        return false;
      }

      return true;
    }
  }
};
</script>
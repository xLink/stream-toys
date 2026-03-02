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

    <div class="flex">
      <div class="flex items-center w-1/3">
        <Alert type="info">
          Choose your setup
        </Alert>
      </div>
    </div>

    <div class="flex gap-2">
      <div class="flex flex-col w-1/2 items-end justify-center"> 
        <Tile label="Single Player (local)" />
      </div>
      <div class="flex flex-col w-1/2 gap-1">
        <Tile label="Set User Details" @click="() => setUserDetails()" />
        <Tile label="Create New Room" @click="() => newRoom()" />
      </div>
    </div>

  </Layout>
</template>

<script>
export default {
  name: 'TetrisMP',
  props: {
    pokedexData: {
      type: Object,
      required: true,
    },
    room: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      showOptions: false,
    };
  },

  methods: {
    setUserDetails() {
      console.log('showing userdetails');
      this.$eventBus.$emit('modal-open', 'UserDetails');
    },
  }
}
</script>
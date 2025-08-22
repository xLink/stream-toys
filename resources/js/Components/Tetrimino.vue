<template>
  <div 
    class="flex flex-row rounded-md border-2 border-transparent p-2 cursor-pointer gap-[0.2rem]"
    :class="{
      'border-yellow-500': active
    }"
  >
    <div 
      v-for="(rows, x) in activePiece" 
      class="flex flex-col gap-[0.2rem]"
    >
      <div 
        v-for="(block, y) in rows"
        class="p-1 w-[--size] h-[--size] rounded-sm border-2 border-transparent bg-[--backgroundColor] transition-all ease-in-out"
        :style="{
          '--backgroundColor': block === 1 ? blockColor : 'rgba(0,0,0,0.4)',
          '--size': '20px',
        }"
      />
    </div>
  </div>
</template>

<script>
import { mapFields } from 'vuex-map-fields';

export default {
  name: 'Tetrimino',

  props: {
    type: {
      type: String,
      required: true,
      validate: function(value) {
        return Object.keys(this.pieceDef).includes(value);
      },
    },
    rotation: {
      type: Number,
      required: true,
      validate: function(value) {
        return [0, 1, 2, 3].includes(value);
      },
    },
    active: {
      type: Boolean,
      default: false,
    },
  },

  computed: {
    ...mapFields('tetris2', [
      'pieceDef',
      'colors',
    ]),

    blockColor() {
      if (!this.type) { return '#000000'; }
      if (!this.pieceDef || !this.pieceDef[this.type]) {
        return '#000000';
      }

      if (this.type === '.') {
        return this.colors.singleSelect;
      }

      return this.pieceDef[this.type].color;
    },

    activePiece() {
      if (!this.type || !this.pieceDef || !this.pieceDef[this.type]) {
        return this.activePiece;
      }
      return this.pieceDef[this.type].blocks[this.rotation];
    },
  },
};
</script>
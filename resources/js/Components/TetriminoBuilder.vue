<template>
  <div class="flex flex-row rounded-sm gap-1">
    <div v-for="(rows, x) in activePiece" class="flex flex-col gap-1">
      <div 
        v-for="(block, y) in rows"
        class="p-1 w-[--size] h-[--size] rounded-sm border-2 border-transparent bg-[--backgroundColor] transition-all ease-in-out"
        :style="{
          '--backgroundColor': block === 1 ? blockColor : '#000000',
          '--size': '25px',
        }"
        @click="handleClick(x, y)"
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
  },

  data() {
    return {
      activePiece: [
        [0,0,0,0],
        [0,0,0,0],
        [0,0,0,0],
        [0,0,0,0]
      ],
    };
  },

  created() {
    this.buildPiece(this.type);
  },

  methods: {
    buildPiece (type) {
      this.activePiece = this.pieceDef[type].blocks[this.rotation];
    },

    random: function(mn, mx) {
      return Math.random() * (mx - mn) + mn;
    },

    rebuildPiece() {
      this.buildPiece(this.type);
    },

    handleClick(x, y) {
      this.activePiece[x][y] = this.activePiece[x][y] === 1 ? 0 : 1;
    },
  },

  computed: {
    ...mapFields('tetris2', [
      'pieceDef',
    ]),

    blockColor() {
      return [
        this.pieceDef[this.type].color
      ];
    },
  },

  watch: {
    'type': function() {
      this.rebuildPiece();
    },
    'rotation': function() {
      this.rebuildPiece();
    },
  },
};
</script>
<template>
  <div class="flex flex-row rounded-sm">
    <div v-for="rows in activePiece" class="flex flex-col">
      <div 
        v-for="block in rows"
        class="p-1 w-[--size] h-[--size] rounded-sm border-2 border-transparent bg-[--backgroundColor] transition-all ease-in-out"
        :style="{
          '--backgroundColor': block === 1 ? blockColor : 'transparent',
          '--size': '5px',
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
      let dir = typeof this.rotation == 'undefined' ? parseInt(this.random(0, 3)) : this.rotation;
      let bit, result, row = 0, col = 0, blocks = this.pieceDef[type]?.blocks[dir];
      for(bit = 0x8000 ; bit > 0 ; bit = bit >> 1) {
        if (blocks & bit) {
          this.activePiece[col][row] = 1;
        }
        if (++col === 4) {
          col = 0;
          ++row;
        }
      }
    },

    random: function(mn, mx) {
      return Math.random() * (mx - mn) + mn;
    },

    rebuildPiece() {
      this.activePiece = [
        [0,0,0,0],
        [0,0,0,0],
        [0,0,0,0],
        [0,0,0,0]
      ];
      this.buildPiece(this.type);
    }
  },

  computed: {
    ...mapFields('tetris', [
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
<template>
  <div
    class="flex p-4 w-full pr-6 gap-2"
    :class="{
      'flex-col': true,
      'items-center h-[80vh]': vertical,
    }"
    :style="{ fontSize: fontSize + 'px' }"
  >
    <div
      class="flex gap-2"
      :class="{
        'flex-row w-full': !vertical,
        'flex-col items-center flex-1 min-h-0': vertical,
      }"
    >
      <div class="flex text-transparent bg-clip-text font-extrabold bg-gradient-to-b from-red-300 to-red-800 items-center leading-none">
        HP:
      </div>

      <div
        class="relative flex gap-2"
        :class="{
          'flex-col w-full': !vertical,
          'flex-row h-full min-h-0': vertical,
        }"
      >
        <!-- stacked: one track per bar (single bar is just a stack of one) -->
        <template v-if="layout === 'stacked'">
          <div
            v-for="(seg, i) in bars"
            :key="'bar_' + i"
            class="relative overflow-hidden"
            :style="trackStyle"
          >
            <div
              class="absolute"
              :class="animationClass"
              :style="fillStyle(0, seg.fill, seg.color)"
            />
            <div
              v-if="i === activeIndex && shieldPercent(seg) > 0"
              class="absolute shield-pattern"
              :class="animationClass"
              :style="shieldStyle(seg)"
            />
          </div>
        </template>

        <!-- segmented: one track, bars laid out inside it with dividers -->
        <div
          v-else
          class="relative overflow-hidden"
          :style="trackStyle"
        >
          <template
            v-for="(seg, i) in bars"
            :key="'seg_' + i"
          >
            <div
              class="absolute"
              :style="regionStyle(seg)"
            >
              <div
                class="absolute"
              :class="animationClass"
                :style="fillStyle(0, seg.fill, seg.color)"
              />
              <div
                v-if="i === activeIndex && shieldPercent(seg) > 0"
                class="absolute shield-pattern"
              :class="animationClass"
                :style="shieldStyle(seg)"
              />
            </div>
            <div
              v-if="seg.from > 0"
              class="absolute bg-black/70"
              :style="dividerStyle(seg)"
            />
          </template>
        </div>

        <div
          v-if="inlineText"
          class="absolute inset-0 flex items-center justify-center pointer-events-none"
        >
          <div class="hp-text font-extrabold text-white leading-none">
            {{ hpText }}
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="!inlineText"
      class="flex justify-center"
    >
      <div class="hp-text flex font-extrabold text-white items-center">
        {{ hpText }}
      </div>
    </div>
  </div>
</template>

<script>
import { activeSegments, segmentFill, phaseColor } from '@/Helpers/hpBar.js';

export default {
  name: 'Gen3HP',

  props: {
    bar: {
      type: String,
      required: false,
      default: 'default',
    },
    max: {
      type: Number,
      required: true,
      default: 100,
    },
    current: {
      type: Number,
      required: true,
      default: 100,
    },
    shield: {
      type: Number,
      required: false,
      default: 0,
    },
    options: {
      type: Object,
      required: false,
      default: null,
    },
  },

  methods: {
    // positions a fill inside its track, growing from the left (horizontal) or bottom (vertical)
    fillStyle(start, size, color) {
      const style = { backgroundColor: color };
      if (this.vertical) {
        return { ...style, left: 0, right: 0, bottom: start + '%', height: size + '%' };
      }
      return { ...style, top: 0, bottom: 0, left: start + '%', width: size + '%' };
    },
    regionStyle(seg) {
      const start = (seg.from / this.safeMax) * 100;
      const size = ((seg.to - seg.from) / this.safeMax) * 100;
      if (this.vertical) {
        return { left: 0, right: 0, bottom: start + '%', height: size + '%' };
      }
      return { top: 0, bottom: 0, left: start + '%', width: size + '%' };
    },
    dividerStyle(seg) {
      const pos = (seg.from / this.safeMax) * 100;
      if (this.vertical) {
        return { left: 0, right: 0, bottom: pos + '%', height: '3px' };
      }
      return { top: 0, bottom: 0, left: pos + '%', width: '3px' };
    },
    shieldPercent(seg) {
      const size = seg.to - seg.from;
      if (size <= 0 || this.shield <= 0) {
        return 0;
      }
      return Math.min((this.shield / size) * 100, seg.fill);
    },
    // shield sits at the leading edge of the fill
    shieldStyle(seg) {
      const size = this.shieldPercent(seg);
      return this.fillStyle(seg.fill - size, size, 'transparent');
    },
  },

  computed: {
    safeMax() {
      return this.max > 0 ? this.max : 1;
    },
    vertical() {
      return this.options?.orientation === 'vertical';
    },
    layout() {
      return this.options?.multi?.enabled ? this.options.multi.layout : 'stacked';
    },
    inlineText() {
      return this.options?.text?.position === 'inline';
    },
    fontSize() {
      return this.options?.text?.font_size ?? 36;
    },
    // the track clips its fills, so rounding it rounds everything inside
    animationClass() {
      return (this.options?.bar?.animate ?? true) ? 'transition-all duration-500 ease-in-out' : '';
    },
    radius() {
      const bar = this.options?.bar;
      if (!(bar?.rounded ?? true)) {
        return 0;
      }
      return bar?.radius ?? 999;
    },
    thickness() {
      return this.options?.bar?.thickness ?? 80;
    },
    trackStyle() {
      const style = {
        backgroundColor: this.options?.colors?.background ?? '#d1d5db',
        borderRadius: this.radius + 'px',
      };
      if (this.vertical) {
        return { ...style, width: this.thickness + 'px', height: '100%' };
      }
      return { ...style, height: this.thickness + 'px', width: '100%' };
    },
    bars() {
      return activeSegments(this.options, this.safeMax).map((seg) => {
        const fill = segmentFill(seg, this.current);
        return { ...seg, fill, color: phaseColor(this.options, fill) };
      });
    },
    // the bar currently taking damage
    activeIndex() {
      const index = this.bars.findIndex((seg) => this.current > seg.from);
      return index === -1 ? this.bars.length - 1 : index;
    },
    hpText() {
      return this.shield > 0
        ? `${this.current} / ${this.max} (+${this.shield})`
        : `${this.current} / ${this.max}`;
    },
  },
};
</script>

<style scoped>
.shield-pattern {
  background-image: repeating-linear-gradient(45deg, #e1e1e1 0, #e1e1e1 2px, transparent 0, transparent 50%);
  background-size: 20px 20px;
}
.hp-text {
  text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000;
}
</style>

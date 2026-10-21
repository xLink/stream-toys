<template>
  <div class="flex flex-col gap-4 w-full">
    <!-- tab bar -->
    <div class="flex flex-wrap gap-1 border-b border-zinc-500">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        type="button"
        class="px-4 py-2 -mb-px border-b-2 font-bold transition-colors"
        :class="activeTab === tab.key
          ? 'border-green-500 text-green-500'
          : 'border-transparent opacity-75 hover:opacity-100'"
        @click="activeTab = tab.key"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- General: name / HP values, supplied by the page -->
    <section
      v-if="activeTab === 'general'"
      class="flex flex-col gap-4 w-full max-w-md"
    >
      <slot name="general" />
    </section>

    <!-- Bar -->
    <section
      v-if="activeTab === 'bar'"
      class="flex flex-col gap-4 w-full max-w-md"
    >
      <RadioGroup
        v-model="form.orientation"
        name="orientation"
        label="Orientation"
        :float-label="true"
        :options="{ horizontal: 'Horizontal', vertical: 'Vertical' }"
      />
      <RangeField
        v-model="form.bar.thickness"
        name="thickness"
        label="Bar Thickness px"
        :float-label="true"
        :min="10"
        :max="200"
      />
      <CheckboxField
        v-model="form.bar.rounded"
        name="rounded"
        label="Rounded corners"
      />
      <RangeField
        v-if="form.bar.rounded"
        v-model="form.bar.radius"
        name="radius"
        label="Corner Radius px"
        :float-label="true"
        :min="0"
        :max="999"
      />
      <CheckboxField
        v-model="disableAnimation"
        name="disable_animation"
        label="Disable animations"
      />
      <RadioGroup
        v-model="form.shield.display"
        name="shield_display"
        label="Shield Display"
        :float-label="true"
        :options="{ overlay: 'Overlay on HP bar', separate: 'Separate bar', none: 'Hidden' }"
      />
    </section>

    <!-- Text -->
    <section
      v-if="activeTab === 'text'"
      class="flex flex-col gap-4 w-full max-w-md"
    >
      <RadioGroup
        v-model="form.text.position"
        name="text_position"
        label="HP Text Position"
        :float-label="true"
        :options="{ outside: 'Outside', inline: 'Inline' }"
      />
      <RadioGroup
        v-model="form.text.shield_position"
        name="shield_position"
        label="Shield Text"
        :float-label="true"
        :options="{ bar: 'On HP bar', text: 'In HP text', none: 'Hidden' }"
      />
      <NumberField
        v-model="form.text.font_size"
        name="font_size"
        label="Font Size (px)"
        :float-label="true"
        :min="8"
        :max="200"
      />
    </section>

    <!-- Multiple bars -->
    <section
      v-if="activeTab === 'multi'"
      class="flex flex-col gap-4 w-full max-w-md"
    >
      <CheckboxField
        v-model="form.multi.enabled"
        name="multi_enabled"
        label="Split HP across multiple bars"
      />

      <template v-if="form.multi.enabled">
        <RadioGroup
          v-model="form.multi.layout"
          name="multi_layout"
          label="Style"
          :float-label="true"
          :options="{ stacked: 'Stacked bars', segmented: 'Segments in one bar' }"
        />
        <RadioGroup
          v-model="cappedChoice"
          name="multi_capped"
          label="Damage"
          :float-label="true"
          :options="{ uncapped: 'Uncapped (spills over)', capped: 'Capped (per bar)' }"
        />

        <div class="flex flex-col gap-2">
          <div class="form-label">
            Bar Splits
          </div>
          <div class="text-sm opacity-75">
            % of total HP per bar. Bar 1 takes damage first.
          </div>
          <div
            v-for="(split, i) in form.multi.splits"
            :key="'split_' + i"
            class="flex items-center gap-2"
          >
            <div class="w-14 shrink-0">
              Bar {{ i + 1 }}
            </div>
            <NumberField
              v-model="form.multi.splits[i]"
              :name="'split_' + i"
              :min="1"
              :max="99"
            />
            <button
              type="button"
              class="px-2 text-red-400 hover:text-red-600 disabled:opacity-25 disabled:cursor-not-allowed"
              title="Remove bar"
              :disabled="form.multi.splits.length <= 2"
              @click="removeSplit(i)"
            >
              &times;
            </button>
          </div>
          <div
            class="text-sm font-bold"
            :class="splitTotal === 100 ? 'text-green-500' : 'text-red-500'"
          >
            Total: {{ splitTotal }}% {{ splitTotal === 100 ? '' : '(must be 100%)' }}
          </div>
          <div class="flex gap-2">
            <Btn
              v-if="form.multi.splits.length < 10"
              type="info"
              @click="addSplit"
            >
              + Bar
            </Btn>
            <Btn type="info" @click="evenSplits">
              Even split
            </Btn>
          </div>
        </div>
      </template>
    </section>

    <!-- Colours -->
    <section
      v-if="activeTab === 'colors'"
      class="flex flex-col gap-4 w-full max-w-md"
    >
      <RadioGroup
        v-model="form.colors.mode"
        name="colors_mode"
        label="Bar Colour"
        :float-label="true"
        :options="{ solid: 'Single colour', phases: 'Colour per phase' }"
      />
      <ColorField
        v-if="form.colors.mode === 'solid'"
        v-model="form.colors.solid"
        name="colors_solid"
        label="Bar Colour"
        :float-label="true"
      />
      <ColorField
        v-model="form.colors.background"
        name="colors_background"
        label="Background Colour"
        :float-label="true"
      />
      <ColorField
        v-model="form.colors.shield_background"
        name="colors_shield_background"
        label="Shield Background Colour"
        :float-label="true"
      />
      <ColorField
        v-model="form.colors.shield_foreground"
        name="colors_shield_foreground"
        label="Shield Foreground Colour"
        :float-label="true"
      />

      <div
        v-if="form.colors.mode === 'phases'"
        class="flex flex-col gap-2"
      >
        <div class="form-label">
          Phase Colours
        </div>
        <div class="text-sm opacity-75">
          At or below the threshold %, the bar turns that colour. The lowest matching threshold wins.
        </div>
        <div class="grid grid-cols-[auto_auto_1fr_6rem] gap-x-4 gap-y-2 items-center">
          <div class="text-sm font-bold">
            Slot
          </div>
          <div class="text-sm font-bold">
            On
          </div>
          <div class="text-sm font-bold">
            At or below %
          </div>
          <div class="text-sm font-bold">
            Colour
          </div>
          <template
            v-for="(phase, i) in visiblePhases"
            :key="'phase_' + i"
          >
            <div>{{ i + 1 }}</div>
            <CheckboxField
              v-model="phase.enabled"
              :name="'phase_enabled_' + i"
            />
            <NumberField
              v-model="phase.threshold"
              :name="'phase_threshold_' + i"
              :min="0"
              :max="100"
              :disabled="!phase.enabled"
            />
            <ColorField
              v-model="phase.color"
              :name="'phase_color_' + i"
              :disabled="!phase.enabled"
            />
          </template>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import Btn from '@/Components/Forms/Btn.vue';

const clone = (value) => JSON.parse(JSON.stringify(value));

export default {
  name: 'HPBarOptionsForm',
  components: {
    Btn,
  },

  props: {
    modelValue: {
      type: Object,
      required: true,
    },
  },
  emits: ['update:modelValue'],

  data() {
    return {
      form: clone(this.modelValue),
      activeTab: this.$slots.general ? 'general' : 'bar',
    };
  },

  computed: {
    tabs() {
      return [
        this.$slots.general ? { key: 'general', label: 'General' } : null,
        { key: 'bar', label: 'Bar' },
        { key: 'text', label: 'Text' },
        { key: 'multi', label: 'Multiple Bars' },
        { key: 'colors', label: 'Colours' },
      ].filter(Boolean);
    },
    // RadioGroup works with string keys
    // stored as `animate` (default on); shown as an opt-out
    disableAnimation: {
      get() {
        return !this.form.bar.animate;
      },
      set(value) {
        this.form.bar.animate = !value;
      },
    },
    cappedChoice: {
      get() {
        return this.form.multi.capped ? 'capped' : 'uncapped';
      },
      set(value) {
        this.form.multi.capped = value === 'capped';
      },
    },
    // slots up to the last enabled one, plus one spare to add another
    visiblePhases() {
      const phases = this.form.colors.phases;
      const lastEnabled = phases.reduce((last, phase, i) => (phase.enabled ? i : last), -1);
      return phases.slice(0, Math.min(lastEnabled + 2, phases.length));
    },
    splitTotal() {
      return this.form.multi.splits.reduce((sum, value) => sum + (parseInt(value) || 0), 0);
    },
  },

  watch: {
    // emit a normalised copy whenever the form changes
    form: {
      deep: true,
      handler(value) {
        const normalised = this.normalise(value);
        if (JSON.stringify(normalised) !== JSON.stringify(this.modelValue)) {
          this.$emit('update:modelValue', normalised);
        }
      },
    },
    // pick up external changes (e.g. another tab saved options)
    modelValue: {
      deep: true,
      handler(value) {
        if (JSON.stringify(value) !== JSON.stringify(this.normalise(this.form))) {
          this.form = clone(value);
        }
      },
    },
  },

  methods: {
    normalise(value) {
      const options = clone(value);
      const toInt = (v) => parseInt(v) || 0;

      options.text.font_size = toInt(options.text.font_size);
      const clamp = (v, min, max) => Math.min(Math.max(toInt(v), min), max);
      options.bar.thickness = clamp(options.bar.thickness, 10, 200);
      options.bar.rounded = !!options.bar.rounded;
      options.bar.radius = clamp(options.bar.radius, 0, 999);
      options.bar.animate = !!options.bar.animate;
      options.multi.splits = options.multi.splits.map(toInt);
      options.multi.capped = !!options.multi.capped;
      options.multi.enabled = !!options.multi.enabled;
      options.colors.phases = options.colors.phases.map((phase) => ({
        ...phase,
        enabled: !!phase.enabled,
        threshold: toInt(phase.threshold),
      }));

      return options;
    },
    addSplit() {
      this.form.multi.splits.push(0);
      this.evenSplits();
    },
    removeSplit(index) {
      this.form.multi.splits.splice(index, 1);
      this.evenSplits();
    },
    evenSplits() {
      const count = this.form.multi.splits.length;
      const base = Math.floor(100 / count);
      // the last bar takes the remainder so the total is exactly 100
      this.form.multi.splits = this.form.multi.splits.map((_, i) =>
        i === count - 1 ? 100 - base * (count - 1) : base
      );
    },
  },
};
</script>

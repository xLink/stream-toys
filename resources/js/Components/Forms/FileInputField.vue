<template>
  <div
    class="form-element flex flex-col p-6 gap-4 rounded input-file"
    @drag.prevent
    @dragstart.prevent
    @dragend.prevent
    @dragenter.prevent
    @dragleave.prevent
    @dragover.prevent
    @drop.prevent="dragDoc($event.dataTransfer.files)"
  >
    <label
      :for="name"
      class="flex cursor-pointer justify-center items-center border-2 rounded border-dashed border-zinc-300 dark:border-zinc-500 w-full"
      :class="{
        'h-64': !uploadedFile || !uploadedFile.length,
        'h-full': uploadedFile && uploadedFile.length
      }"
    />
    <div class="italic">
      <label :for="name" class="form-label">{{ $t('drag_to_upload_message') }}</label>
      <input
        :id="name"
        ref="fileInput"
        :name="name"
        type="file"
        required
        :disabled="disabled"
        :accept="accept"
        @change="updateValue()"
      >
    </div>
    <span v-if="helpText" class="text-red-500 text-sm inset-0 italic">
      {{ helpText }}
    </span>
  </div>
</template>

<script>
export default {
  name: 'FileInputField',
  props: {
    disabled: {
      type: Boolean,
      default: false,
    },
    name: {
      type: String,
      required: true,
    },
    accept: {
      type: String,
      required: false,
      default: '',
    },
    hint: {
      type: String,
      required: false,
      default: '',
    },
  },
  emits: ['update:modelValue'],

  data() {
    return {
      uploadedFile: null,
    };
  },

  methods: {
    dragDoc(files) {
      this.$refs.fileInput.files = files;
      this.updateValue();
    },
    updateValue () {
      this.uploadedFile = this.$refs.fileInput.files;
      this.$emit('update:modelValue', this.uploadedFile);
    },
  },
  
  computed: {
    helpText() {
      if (this.disabled && this.disabledText) return this.disabledText;
      if (this.hint) return this.hint;
      return null;
    },
  },
};
</script>


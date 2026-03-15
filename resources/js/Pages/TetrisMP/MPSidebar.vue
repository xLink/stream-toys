<template>
<div class="flex flex-col gap-4 p-1">
  <div class="flex flex-col">
    <h2 class="text-2xl font-bold text-center">
      Online Users ({{ Object.values(players).length }})
    </h2>
    <ul class="flex flex-col items-center gap-1 mt-4">
      <li 
        v-for="user in players" 
        :key="user.id" 
        class="flex gap-x-2 px-4 py-2 w-full bg-slate-800 rounded-lg text-ellipsis whitespace-nowrap overflow-hidden"
      >
        <div 
          class="min-w-4 h-4 mt-1 rounded-lg" 
          :style="{ 
            'backgroundColor': user.color 
          }"
        ></div>
        <span>{{ user.username }}</span>
      </li>
    </ul>
  </div>

  <div 
    ref="history"
    class="flex flex-col grow-0 text-white overflow-auto scrollbar-thin max-h-[--calcHeight] transition-all duration-200 ease-in-out"
    :class="{
      '!w-0': showHistory === false,
    }"
  >
    <div class="flex flex-col">
      <h2 class="text-2xl font-bold text-center">
        History
      </h2>
      <p>Seed: {{ seed +':'+ step }}</p>
    </div>
    <ul
      class="flex flex-col gap-x-2 px-2 py-2 w-full bg-slate-800 rounded-lg text-ellipsis whitespace-nowrap overflow-hidden"
    >
      <li 
        v-for="(item, index) in getReverseHistory" 
        :key="index"
        class="flex flex-row p-1 rounded-md cursor-pointer hover:bg-blue-800 w-full"
        :class="{
          'bg-blue-500': selectedHistoryId === getReverseHistory.length - (index+1)
        }"
        :data-index="getReverseHistory.length - (index+1)"
        @mouseover="selectedHistoryId = parseInt(getReverseHistory.length - (index+1));"
        @mouseleave="selectedHistoryId = null;"
      >
        <div class="flex w-full text-ellipsis whitespace-nowrap overflow-hidden">
          <span>{{ (getReverseHistory.length - index).toString().padStart(2, '0') }}: </span>
          <span class="ml-1">{{ item.type.toUpperCase() }} ({{ item.x.toString().padStart(2, '0') }}, {{ item.y.toString().padStart(2, '0') }})</span> 
          <span>: ({{ item.username }})</span>
        </div>
      </li>
    </ul>
  </div>
</div>
</template>

<script>
import { mapFields } from 'vuex-map-fields';
import { mapGetters } from 'vuex';

export default {
  name: 'MPSidebar',

  computed: {
    ...mapGetters('tetrismp', [
      'getReverseHistory',
    ]),
    ...mapFields('tetrismp', [
      'players',
      'history',
      'seed',
      'step',
      'showHistory',
      'selectedHistoryId',
    ]),
  },
}
</script>
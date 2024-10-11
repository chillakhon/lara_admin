<script setup>
import {ref} from "vue";

const props = defineProps({
    title: String,
    links: Object, Array
})

const isOpen = ref(false)
const toggleDropDown = ()=> {
    isOpen.value = !isOpen.value
}
</script>

<template>
    <button type="button" @click="toggleDropDown"
            class="flex items-center p-2 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
           >
        <slot name="prefix"></slot>
        <span class="flex-1 ml-3 text-left whitespace-nowrap">{{ title }}</span>
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd"
                  d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                  clip-rule="evenodd"></path>
        </svg>
    </button>
    <ul :class="isOpen ? '' : 'hidden'" class=" py-2 space-y-2 ">
        <li v-for="link in links">
            <a :href="route(link.route)"
               :class="route().current(link.route) ? 'bg-gray-100' : ''"
               class="flex items-center p-2 pl-11 text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 ">{{ link.title }}</a>
        </li>
    </ul>
</template>

<style scoped>

</style>

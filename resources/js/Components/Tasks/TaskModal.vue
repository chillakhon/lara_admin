<script setup>
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectDropdown from '@/Components/SelectDropdown.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

// ... остальной код из вашего текущего модального окна
</script>

<template>
    <!-- Используем разметку из примера Flowbite, но адаптируем под наши данные -->
    <Modal :show="show" @close="$emit('close')" max-width="2xl">
        <!-- Добавляем содержимое модального окна из примера Flowbite -->
        <!-- ... -->
    </Modal>
</template> 
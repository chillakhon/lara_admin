<template>
    <div class="space-y-6">
        <!-- Информация о клиенте -->
        <div class="flex items-center">
            <div class="flex-shrink-0 h-12 w-12">
                <img v-if="review.client.avatar" 
                     :src="review.client.avatar" 
                     class="h-12 w-12 rounded-full"
                     :alt="review.client.name">
                <div v-else 
                     class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center">
                    <span class="text-lg font-medium text-gray-600">
                        {{ review.client.name.charAt(0) }}
                    </span>
                </div>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ review.client.name }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ review.created_at }}
                </p>
            </div>
        </div>

        <!-- Рейтинг и атрибуты -->
        <div class="space-y-4">
            <div class="flex items-center">
                <StarIcon v-for="i in 5" :key="i"
                        :class="[
                            'w-6 h-6',
                            i <= review.rating 
                                ? 'text-yellow-400' 
                                : 'text-gray-300 dark:text-gray-600'
                        ]"/>
                <span class="ml-2 text-lg font-medium text-gray-900 dark:text-white">
                    {{ review.rating }}/5
                </span>
            </div>
            
            <div v-if="review.attributes?.length" class="grid grid-cols-2 gap-4">
                <div v-for="attr in review.attributes" :key="attr.id"
                     class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ attr.name }}
                    </span>
                    <div class="flex items-center">
                        <StarIcon v-for="i in 5" :key="i"
                                :class="[
                                    'w-4 h-4',
                                    i <= attr.rating 
                                        ? 'text-yellow-400' 
                                        : 'text-gray-300 dark:text-gray-600'
                                ]"/>
                    </div>
                </div>
            </div>
        </div>

        <!-- Текст отзыва -->
        <div class="prose dark:prose-invert max-w-none">
            {{ review.content }}
        </div>

        <!-- Изображения -->
        <div v-if="review.images?.length" class="grid grid-cols-4 gap-4">
            <div v-for="image in review.images" :key="image.id"
                 class="relative group cursor-pointer"
                 @click="openImageViewer(image)">
                <img :src="image.thumbnail" 
                     :alt="review.client.name"
                     class="rounded-lg object-cover w-full h-32">
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-opacity rounded-lg flex items-center justify-center">
                    <EyeIcon class="w-6 h-6 text-white opacity-0 group-hover:opacity-100"/>
                </div>
            </div>
        </div>

        <!-- Форма модерации -->
        <div v-if="canModerate" class="space-y-4 border-t pt-4 dark:border-gray-700">
            <div class="flex items-center space-x-4">
                <Toggle v-model="form.is_verified" label="Проверено"/>
                <Toggle v-model="form.is_published" label="Опубликовано"/>
            </div>
            
            <TextArea
                v-model="form.response"
                label="Ответ на отзыв"
                placeholder="Введите ответ..."
            />
            
            <div class="flex justify-end space-x-4">
                <PrimaryButton
                    type="default"
                    @click="submitModeration"
                    :loading="form.processing"
                >
                    Сохранить
                </PrimaryButton>
            </div>
        </div>
    </div>
</template>

<script setup>
// ... код компонента ...
</script> 
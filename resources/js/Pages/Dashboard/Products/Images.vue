<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    product: Object
});

const imageFiles = ref(null);
const previewImages = ref([]);
const selectedVariants = ref([]);

const canUpload = computed(() => {
    return imageFiles.value && imageFiles.value.length > 0 && selectedVariants.value.length > 0;
});

const handleFileUpload = (event) => {
    imageFiles.value = event.target.files;
    previewImages.value = [];
    for (let i = 0; i < imageFiles.value.length; i++) {
        previewImages.value.push(URL.createObjectURL(imageFiles.value[i]));
    }
};

const uploadImages = () => {
    const form = useForm({
        images: imageFiles.value,
        variants: selectedVariants.value
    });

    form.post(route('product.images.store', { product: props.product.id }), {
        forceFormData: true
    });
};

const getImagesForVariant = (variantId) => {
    return props.product.images.filter(image => image.pivot.product_variant_id === variantId);
};

const deleteImage = (imageId, variantId) => {
    useForm().delete(route('product.images.destroy', {
        product: props.product.id,
        image: imageId,
        variant: variantId
    }));
};

const setMainImage = (imageId, variantId) => {
    useForm().patch(route('product.images.setMain', {
        product: props.product.id,
        image: imageId,
        variant: variantId
    }));
};
</script>

<template>
    <div>
        <h2>Product Images</h2>
        <form @submit.prevent="uploadImages" enctype="multipart/form-data">
            <input type="file" multiple @change="handleFileUpload">
            <div v-if="previewImages.length">
                <img v-for="(image, index) in previewImages" :key="index" :src="image" class="preview-image" style="width: 100px; height: 100px; object-fit: cover; margin: 5px;" alt="">
            </div>
            <div>
                <h3>Select Variants</h3>
                <div v-for="variant in product.variants" :key="variant.id">
                    <input type="checkbox" :id="variant.id" :value="variant.id" v-model="selectedVariants">
                    <label :for="variant.id">{{ variant.name }}</label>
                </div>
            </div>
            <button type="submit" :disabled="!canUpload">Upload Images</button>
        </form>

        <div v-for="variant in product.variants" :key="variant.id">
            <h3>{{ variant.name }}</h3>
            <div v-for="image in getImagesForVariant(variant.id)" :key="image.id">
                <img :src="image.url" :alt="product.name" style="width: 200px; height: 200px; object-fit: cover;">
                <button @click="deleteImage(image.id, variant.id)">Delete</button>
                <button @click="setMainImage(image.id, variant.id)" :disabled="image.pivot.is_main">Set as Main</button>
            </div>
        </div>
    </div>
</template>

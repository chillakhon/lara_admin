<script setup>
import {ref, computed,  reactive} from 'vue';
import {router, useForm} from '@inertiajs/vue3';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import ProductVariant from "@/Components/ProductVariant.vue";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

const props = defineProps({
    product: Object,
    materials: Array,
    categories: Array,
    colors: Array
});

const markup = ref(20);
const newSizeName = ref('');
const newComponent = ref({
    material_id: '',
    quantity: null,
});

const selectedColorOptions = reactive({});

// Инициализируем selectedColorOptions сразу
props.product.color_options.forEach(option => {
    selectedColorOptions[option.id] = {};
    option.color_option_values.forEach(colorValue => {
        selectedColorOptions[option.id][colorValue.id] = false;
    });
});

const newColorOption = ref({ title: '', category_id: '' });
const newColor = ref({});

const selectedColor = ref(null);
const imageFiles = ref(null);
const previewImages = ref([]);

const form = useForm({
    color_option_value_id: '',
    variants: [],
    images: [],
});

const calculateComponentCost = (component) => {
    return (component.quantity * component.material.cost_per_unit).toFixed(2);
};

const calculateSizeTotalCost = (size) => {
    return size.components.reduce((total, component) => {
        return total + (component.quantity * component.material.cost_per_unit);
    }, 0).toFixed(2);
};

const calculateSizePriceWithMarkup = (size) => {
    const totalCost = parseFloat(calculateSizeTotalCost(size));
    return (totalCost * (1 + markup.value / 100)).toFixed(2);
};

const addSize = () => {
    useForm({
        name: newSizeName.value,
    }).post(route('dashboard.products.sizes.store', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            newSizeName.value = '';
        },
    });
};

const removeSize = (sizeId) => {
    if (confirm('Are you sure you want to remove this size? All associated components will be deleted.')) {
        router.delete(route('dashboard.products.sizes.destroy', [props.product.id, sizeId]), {
            preserveScroll: true,
        });
    }
};

const addComponent = (sizeId) => {
    useForm({
        material_id: newComponent.value.material_id,
        quantity: newComponent.value.quantity,
    }).post(route('dashboard.products.sizes.components.store', [props.product.id, sizeId]), {
        preserveScroll: true,
        onSuccess: () => {
            newComponent.value = {material_id: '', quantity: null};
        },
    });
};

const removeComponent = (sizeId, componentId) => {
    if (confirm('Are you sure you want to remove this component?')) {
        router.delete(route('dashboard.products.sizes.components.destroy', [props.product.id, sizeId, componentId]), {
            preserveScroll: true,
        });
    }
};

// Функция для генерации всех возможных комбинаций цветов
const generateColorCombinations = () => {
    const combinations = [{}];
    props.product.color_options.forEach(option => {
        const newCombinations = [];
        combinations.forEach(combo => {
            const selectedColors = Object.entries(selectedColorOptions[option.id])
                .filter(([, isSelected]) => isSelected)
                .map(([colorValueId]) => parseInt(colorValueId));

            if (selectedColors.length === 0) {
                newCombinations.push(combo);
            } else {
                selectedColors.forEach(colorValueId => {
                    newCombinations.push({...combo, [option.id]: colorValueId});
                });
            }
        });
        combinations.splice(0, combinations.length, ...newCombinations);
    });
    return combinations.filter(combo => Object.keys(combo).length > 0);
};

const createProductVariants = () => {
    if (!imageFiles.value) {
        alert('Please upload images before creating variants.');
        return;
    }

    const colorCombinations = generateColorCombinations();
    if (colorCombinations.length === 0) {
        alert('Please select at least one color for each color option.');
        return;
    }

    form.color_combinations = colorCombinations;
    form.images = imageFiles.value;
    form.variants = props.product.sizes.flatMap(size =>
        colorCombinations.map(combo => ({
            size_id: size.id,
            price: calculateSizePriceWithMarkup(size),
            stock: 0,
            color_combination: combo
        }))
    );

    form.post(route('dashboard.products.variants.store', props.product.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            // Сбрасываем selectedColorOptions к исходному состоянию
            props.product.color_options.forEach(option => {
                option.color_option_values.forEach(colorValue => {
                    selectedColorOptions[option.id][colorValue.id] = false;
                });
            });
            imageFiles.value = null;
            previewImages.value = [];
            form.reset();
        },
    });
};

const canCreateVariants = computed(() => {
    return imageFiles.value &&
        imageFiles.value.length > 0 &&
        Object.values(selectedColorOptions).some(option =>
            Object.values(option).some(isSelected => isSelected)
        );
});

const handleFileUpload = (event) => {
    imageFiles.value = event.target.files;
    previewImages.value = [];
    for (let i = 0; i < imageFiles.value.length; i++) {
        previewImages.value.push(URL.createObjectURL(imageFiles.value[i]));
    }
};

const deleteVariant = (variantId) => {
    if (confirm('Are you sure you want to delete this variant?')) {
        router.delete(route('dashboard.products.variants.destroy', [props.product.id, variantId]), {
            preserveScroll: true,
        });
    }
};


const variantsWithImages = computed(() => {
    return props.product.variants.map(variant => ({
        ...variant,
        images: props.product.images.filter(image => image.pivot.product_variant_id === variant.id)
    }));
})

const addColorOption = () => {
    useForm(newColorOption.value).post(route('dashboard.products.color-options.store', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            newColorOption.value = {title: '', category_id: ''};
        },
    });
};

const removeColorOption = (colorOptionId) => {
    if (confirm('Are you sure you want to remove this color option?')) {
        router.delete(route('dashboard.products.color-options.destroy', [props.product.id, colorOptionId]), {
            preserveScroll: true,
        });
    }
};

const addColorToOption = (colorOptionId) => {
    useForm({color_id: newColor.value[colorOptionId]})
        .post(route('dashboard.products.color-options.colors.store', [props.product.id, colorOptionId]), {
            preserveScroll: true,
            onSuccess: () => {
                newColor.value[colorOptionId] = '';
            },
        });
};

const removeColorFromOption = (colorOptionId, colorValueId) => {
    if (confirm('Are you sure you want to remove this color?')) {
        router.delete(route('dashboard.products.color-options.colors.destroy', [props.product.id, colorOptionId, colorValueId]), {
            preserveScroll: true,
        });
    }
};


const editingVariant = ref(null);
const variantForm = useForm({
    id: null,
    name: '',
    price: '',
    stock: '',
});

const startEditingVariant = (variant) => {
    editingVariant.value = variant.id;
    variantForm.id = variant.id;
    variantForm.name = variant.name;
    variantForm.price = variant.price;
    variantForm.stock = variant.stock;
};

const updateVariant = () => {
    variantForm.put(route('dashboard.products.variants.update', variantForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingVariant.value = null;
            variantForm.reset();
        },
    });
};

const setMainImage = (variantId, imageId) => {
    router.patch(route('dashboard.product.images.setMain', {
        product: props.product.id,
        image: imageId,
        variant: variantId
    }), {}, {
        preserveScroll: true,
    });
};

;
</script>

<template>
    <DashboardLayout>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-semibold mb-4">Product Sizes and Components</h3>
                        <!-- Поле для ввода наценки -->
                        <div class="mb-4">
                            <label for="markup" class="block text-sm font-medium text-gray-700">Markup
                                Percentage</label>
                            <input type="number" id="markup" v-model.number="markup" min="0" step="0.1"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <div class="mt-8">
                            <h3 class="text-lg font-semibold mb-4">Color Options</h3>

                            <!-- Form to add new color option -->
                            <form @submit.prevent="addColorOption" class="mb-4">
                                <input v-model="newColorOption.title" placeholder="Color option title" class="mr-2">
                                <select v-model="newColorOption.category_id" class="mr-2">
                                    <option value="">Select Category</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                                <PrimaryButton type="submit">Add Color Option</PrimaryButton>
                            </form>

                            <!-- List of color options -->
                            <div v-for="colorOption in product.color_options" :key="colorOption.id" class="mb-4">
                                <div class="flex justify-between items-center">
                                    <h4 class="text-md font-semibold">{{ colorOption.title }}</h4>
                                    <button @click="removeColorOption(colorOption.id)"
                                            class="text-red-600 hover:text-red-800">
                                        Remove Option
                                    </button>
                                </div>

                                <!-- Form to add color to option -->
                                <form @submit.prevent="addColorToOption(colorOption.id)" class="mt-2">
                                    <select v-model="newColor[colorOption.id]" class="mr-2">
                                        <option value="">Select Color</option>
                                        <option v-for="category in colorCategories" :key="category.id" :value="null"
                                                disabled>
                                            {{ category.title }}
                                        </option>
                                        <option v-for="color in colors" :key="color.id" :value="color.id">
                                            &nbsp;&nbsp;{{ color.title }}
                                        </option>
                                    </select>
                                    <PrimaryButton type="submit">Add Color</PrimaryButton>
                                </form>

                                <!-- List of colors in option -->
                                <div v-for="colorValue in colorOption.color_option_values" :key="colorValue.id"
                                     class="mt-2 flex items-center">
                                    <div class="w-6 h-6 rounded-full mr-2"
                                         :style="{ backgroundColor: `#${colorValue.color.code}` }"></div>
                                    <span>{{ colorValue.color.title }}</span>
                                    <button @click="removeColorFromOption(colorOption.id, colorValue.id)"
                                            class="ml-2 text-red-600 hover:text-red-800">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Form to add new size -->
                        <form @submit.prevent="addSize" class="mb-6">
                            <input v-model="newSizeName" placeholder="New size name" class="mr-2">
                            <PrimaryButton type="submit">Add Size</PrimaryButton>
                        </form>

                        <!-- Sizes and their components -->
                        <div v-for="size in product.sizes" :key="size.id" class="mb-8">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="text-md font-semibold">Size: {{ size.name }}</h4>
                                <button @click="removeSize(size.id)" class="text-red-600 hover:text-red-800">
                                    Remove Size
                                </button>
                            </div>

                            <!-- Form to add component to size -->
                            <form @submit.prevent="addComponent(size.id)" class="mb-4">
                                <select v-model="newComponent.material_id" class="mr-2">
                                    <option value="">Select Material</option>
                                    <option v-for="material in materials" :key="material.id" :value="material.id">
                                        {{ material.title }}
                                    </option>
                                </select>
                                <input v-model.number="newComponent.quantity" type="number" min="0" step="0.01"
                                       placeholder="Quantity" class="mr-2">
                                <PrimaryButton type="submit">Add Component</PrimaryButton>
                            </form>

                            <!-- Components table -->
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th>Material</th>
                                    <th>Quantity</th>
                                    <th>Cost per unit</th>
                                    <th>Total Cost</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="component in size.components" :key="component.id">
                                    <td>{{ component.material.title }}</td>
                                    <td>{{ component.quantity }}</td>
                                    <td>{{ component.material.cost_per_unit }}</td>
                                    <td>{{ calculateComponentCost(component) }}</td>
                                    <td>
                                        <button @click="removeComponent(size.id, component.id)" class="text-red-600">
                                            Remove
                                        </button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <div class="mt-4 text-right">
                                <strong>Total Cost for {{ size.name }}: {{ calculateSizeTotalCost(size) }}</strong><br>
                                <strong>Price with Markup for {{ size.name }}: {{
                                        calculateSizePriceWithMarkup(size)
                                    }}</strong>
                            </div>

                            <!--                            &lt;!&ndash; Кнопка для создания вариантов товара &ndash;&gt;-->
                            <!--                            <div class="mt-4">-->
                            <!--                                <PrimaryButton @click="createProductVariants(size)" :disabled="size.components.length === 0">-->
                            <!--                                    Create Product Variants-->
                            <!--                                </PrimaryButton>-->
                            <!--                            </div>-->

                        </div>

                        <!-- Variant creation form -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold mb-4">Create Product Variants</h3>
                            <div v-for="colorOption in product.color_options" :key="colorOption.id" class="mb-4">
                                <h4 class="font-medium">{{ colorOption.title }}</h4>
                                <div class="flex flex-wrap gap-2">
                                    <label v-for="colorValue in colorOption.color_option_values" :key="colorValue.id" class="flex items-center">
                                        <input type="checkbox"
                                               v-model="selectedColorOptions[colorOption.id][colorValue.id]"
                                               class="mr-2">
                                        <span>{{ colorValue.color.title }}</span>
                                    </label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <InputLabel for="images" value="Upload Images"/>
                                <input type="file" id="images" @change="handleFileUpload" multiple accept="image/*" class="mt-1 block w-full"/>
                            </div>
                            <div v-if="previewImages.length" class="mb-4">
                                <h4 class="text-sm font-medium mb-2">Image Previews:</h4>
                                <div class="flex flex-wrap">
                                    <img v-for="(image, index) in previewImages" :key="index" :src="image" class="w-24 h-24 object-cover m-1 rounded"/>
                                </div>
                            </div>
                            <PrimaryButton @click="createProductVariants" :disabled="!canCreateVariants">
                                Create Product Variants
                            </PrimaryButton>
                        </div>

                        <!-- Display variants -->
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold mb-4">Product Variants</h3>
                            <div class="space-y-6">
                                <ProductVariant
                                    v-for="variant in variantsWithImages"
                                    :key="variant.id"
                                    :variant="variant"
                                    :productId="product.id"
                                    @delete-variant="deleteVariant"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>


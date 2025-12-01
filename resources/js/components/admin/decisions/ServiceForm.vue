<template>
    <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
            <label class="text-sm font-medium mb-1 block">Название *</label>
            <input
                v-model="localForm.name"
                type="text"
                required
                class="w-full h-10 px-3 border border-border rounded bg-background focus:outline-none focus:ring-2 focus:ring-accent"
                placeholder="Введите название услуги"
            />
        </div>
        <div>
            <label class="text-sm font-medium mb-1 block">Slug</label>
            <input
                v-model="localForm.slug"
                type="text"
                class="w-full h-10 px-3 border border-border rounded bg-background focus:outline-none focus:ring-2 focus:ring-accent"
                placeholder="Автоматически генерируется из названия"
            />
            <p class="text-xs text-muted-foreground mt-1">Если не указан, будет создан автоматически</p>
        </div>
        <div>
            <label class="text-sm font-medium mb-1 block">Раздел</label>
            <select
                v-model="localForm.chapter_id"
                class="w-full h-10 px-3 border border-border rounded bg-background focus:outline-none focus:ring-2 focus:ring-accent"
            >
                <option :value="null">Не выбран</option>
                <option
                    v-for="chapter in chapters"
                    :key="chapter.id"
                    :value="chapter.id"
                >
                    {{ chapter.name }}
                </option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium mb-1 block">Изображение</label>
            <div class="flex items-center gap-3">
                <div v-if="selectedImage" class="flex-1">
                    <img
                        :src="selectedImage.url"
                        :alt="selectedImage.original_name"
                        class="w-20 h-20 object-cover rounded border border-border"
                    />
                </div>
                <button
                    type="button"
                    @click="openImageModal"
                    class="px-4 py-2 border border-border bg-background hover:bg-muted/10 rounded-lg transition-colors text-sm"
                >
                    {{ selectedImage ? 'Изменить' : 'Выбрать изображение' }}
                </button>
                <button
                    v-if="selectedImage"
                    type="button"
                    @click="removeImage"
                    class="px-4 py-2 border border-destructive text-destructive hover:bg-destructive/10 rounded-lg transition-colors text-sm"
                >
                    Удалить
                </button>
            </div>
        </div>
        <div>
            <label class="text-sm font-medium mb-1 block">Иконка</label>
            <div class="flex items-center gap-3">
                <div v-if="selectedIcon" class="flex-1">
                    <img
                        :src="selectedIcon.url"
                        :alt="selectedIcon.original_name"
                        class="w-16 h-16 object-cover rounded border border-border"
                    />
                </div>
                <button
                    type="button"
                    @click="openIconModal"
                    class="px-4 py-2 border border-border bg-background hover:bg-muted/10 rounded-lg transition-colors text-sm"
                >
                    {{ selectedIcon ? 'Изменить' : 'Выбрать иконку' }}
                </button>
                <button
                    v-if="selectedIcon"
                    type="button"
                    @click="removeIcon"
                    class="px-4 py-2 border border-destructive text-destructive hover:bg-destructive/10 rounded-lg transition-colors text-sm"
                >
                    Удалить
                </button>
            </div>
        </div>
        <div>
            <label class="text-sm font-medium mb-1 block">Порядок</label>
            <input
                v-model.number="localForm.order"
                type="number"
                min="0"
                class="w-full h-10 px-3 border border-border rounded bg-background focus:outline-none focus:ring-2 focus:ring-accent"
            />
        </div>
        <div>
            <label class="text-sm font-medium mb-1 block">Продукты</label>
            <div class="border border-border rounded-lg p-4 max-h-64 overflow-y-auto">
                <div v-if="loadingProducts" class="text-sm text-muted-foreground">
                    Загрузка продуктов...
                </div>
                <div v-else-if="products.length === 0" class="text-sm text-muted-foreground">
                    Продукты не найдены
                </div>
                <div v-else class="space-y-2">
                    <label
                        v-for="product in products"
                        :key="product.id"
                        class="flex items-center gap-2 cursor-pointer hover:bg-muted/10 p-2 rounded"
                    >
                        <input
                            type="checkbox"
                            :value="product.id"
                            v-model="selectedProducts"
                            class="w-4 h-4 rounded border-border"
                        />
                        <span class="text-sm">{{ product.name }}</span>
                    </label>
                </div>
            </div>
        </div>
        <div>
            <label class="text-sm font-medium mb-1 block">Опции</label>
            <div class="border border-border rounded-lg p-4 max-h-64 overflow-y-auto">
                <div v-if="loadingOptions" class="text-sm text-muted-foreground">
                    Загрузка опций...
                </div>
                <div v-else-if="options.length === 0" class="text-sm text-muted-foreground">
                    Опции не найдены
                </div>
                <div v-else class="space-y-2">
                    <label
                        v-for="option in options"
                        :key="option.id"
                        class="flex items-center gap-2 cursor-pointer hover:bg-muted/10 p-2 rounded"
                    >
                        <input
                            type="checkbox"
                            :value="option.id"
                            v-model="selectedOptions"
                            class="w-4 h-4 rounded border-border"
                        />
                        <span class="text-sm">{{ option.name }}</span>
                    </label>
                </div>
            </div>
        </div>
        <div>
            <label class="text-sm font-medium mb-1 block">Деревья опций</label>
            <div class="border border-border rounded-lg p-4 max-h-64 overflow-y-auto">
                <div v-if="loadingOptionTrees" class="text-sm text-muted-foreground">
                    Загрузка деревьев опций...
                </div>
                <div v-else-if="optionTrees.length === 0" class="text-sm text-muted-foreground">
                    Деревья опций не найдены
                </div>
                <div v-else class="space-y-2">
                    <label
                        v-for="tree in optionTrees"
                        :key="tree.id"
                        class="flex items-center gap-2 cursor-pointer hover:bg-muted/10 p-2 rounded"
                    >
                        <input
                            type="checkbox"
                            :value="tree.id"
                            v-model="selectedOptionTrees"
                            class="w-4 h-4 rounded border-border"
                        />
                        <span class="text-sm">{{ tree.name }}</span>
                    </label>
                </div>
            </div>
        </div>
        <div>
            <label class="text-sm font-medium mb-1 block">Экземпляры</label>
            <div class="border border-border rounded-lg p-4 max-h-64 overflow-y-auto">
                <div v-if="loadingInstances" class="text-sm text-muted-foreground">
                    Загрузка экземпляров...
                </div>
                <div v-else-if="instances.length === 0" class="text-sm text-muted-foreground">
                    Экземпляры не найдены
                </div>
                <div v-else class="space-y-2">
                    <label
                        v-for="instance in instances"
                        :key="instance.id"
                        class="flex items-center gap-2 cursor-pointer hover:bg-muted/10 p-2 rounded"
                    >
                        <input
                            type="checkbox"
                            :value="instance.id"
                            v-model="selectedInstances"
                            class="w-4 h-4 rounded border-border"
                        />
                        <span class="text-sm">{{ instance.name }}</span>
                    </label>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <input
                v-model="localForm.is_active"
                type="checkbox"
                id="is_active"
                class="w-4 h-4 rounded border-border"
            />
            <label for="is_active" class="text-sm font-medium">
                Активна
            </label>
        </div>
        <div v-if="error" class="p-3 bg-destructive/10 border border-destructive/20 rounded text-sm text-destructive">
            {{ error }}
        </div>
        <div class="flex gap-2 pt-4">
            <button
                type="button"
                @click="handleCancel"
                class="flex-1 h-10 px-4 border border-border bg-background/50 hover:bg-accent/10 rounded-lg transition-colors"
            >
                Отмена
            </button>
            <button
                type="submit"
                :disabled="saving"
                class="flex-1 h-10 px-4 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg transition-colors disabled:opacity-50"
            >
                {{ saving ? 'Сохранение...' : 'Сохранить' }}
            </button>
        </div>
    </form>

    <!-- Media Modal for Image -->
    <div v-if="showImageMediaModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-6xl max-h-[90vh] flex flex-col m-4">
            <div class="flex items-center justify-between p-4 border-b border-border">
                <h3 class="text-lg font-semibold">Выбрать изображение</h3>
                <button
                    @click="showImageMediaModal = false"
                    class="text-muted-foreground hover:text-foreground w-8 h-8 flex items-center justify-center rounded hover:bg-muted/10"
                >
                    ✕
                </button>
            </div>
            <div class="flex-1 overflow-auto h-full">
                <Media
                    :selection-mode="true"
                    :count-file="1"
                    @file-selected="handleImageSelected"
                />
            </div>
        </div>
    </div>

    <!-- Media Modal for Icon -->
    <div v-if="showIconMediaModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-6xl max-h-[90vh] flex flex-col m-4">
            <div class="flex items-center justify-between p-4 border-b border-border">
                <h3 class="text-lg font-semibold">Выбрать иконку</h3>
                <button
                    @click="showIconMediaModal = false"
                    class="text-muted-foreground hover:text-foreground w-8 h-8 flex items-center justify-center rounded hover:bg-muted/10"
                >
                    ✕
                </button>
            </div>
            <div class="flex-1 overflow-auto h-full">
                <Media
                    :selection-mode="true"
                    :count-file="1"
                    @file-selected="handleIconSelected"
                />
            </div>
        </div>
    </div>
</template>

<script>
import { ref, watch, onMounted } from 'vue';
import { apiGet } from '../../../utils/api';
import Media from '../../../pages/admin/Media.vue';

export default {
    name: 'ServiceForm',
    components: {
        Media,
    },
    props: {
        initialData: {
            type: Object,
            default: () => ({
                name: '',
                slug: '',
                chapter_id: null,
                image_id: null,
                icon_id: null,
                order: 0,
                is_active: true,
            }),
        },
        saving: {
            type: Boolean,
            default: false,
        },
        error: {
            type: String,
            default: null,
        },
    },
    emits: ['submit', 'cancel'],
    setup(props, { emit }) {
        const chapters = ref([]);
        const products = ref([]);
        const options = ref([]);
        const optionTrees = ref([]);
        const instances = ref([]);
        const loadingProducts = ref(false);
        const loadingOptions = ref(false);
        const loadingOptionTrees = ref(false);
        const loadingInstances = ref(false);
        const selectedProducts = ref([]);
        const selectedOptions = ref([]);
        const selectedOptionTrees = ref([]);
        const selectedInstances = ref([]);
        const showImageMediaModal = ref(false);
        const showIconMediaModal = ref(false);
        const selectedImage = ref(null);
        const selectedIcon = ref(null);
        const localForm = ref({
            name: props.initialData.name || '',
            slug: props.initialData.slug || '',
            chapter_id: props.initialData.chapter_id || null,
            image_id: props.initialData.image_id || null,
            icon_id: props.initialData.icon_id || null,
            order: props.initialData.order ?? 0,
            is_active: props.initialData.is_active !== false,
        });

        const fetchChapters = async () => {
            try {
                const response = await apiGet('/chapters');
                if (response.ok) {
                    const data = await response.json();
                    chapters.value = data.data || [];
                }
            } catch (err) {
                console.error('Error fetching chapters:', err);
            }
        };

        const fetchProducts = async () => {
            loadingProducts.value = true;
            try {
                const response = await apiGet('/products?active=1');
                if (response.ok) {
                    const data = await response.json();
                    products.value = data.data || [];
                }
            } catch (err) {
                console.error('Error fetching products:', err);
            } finally {
                loadingProducts.value = false;
            }
        };

        const fetchOptions = async () => {
            loadingOptions.value = true;
            try {
                const response = await apiGet('/options?active=1');
                if (response.ok) {
                    const data = await response.json();
                    options.value = data.data || [];
                }
            } catch (err) {
                console.error('Error fetching options:', err);
            } finally {
                loadingOptions.value = false;
            }
        };

        const fetchOptionTrees = async () => {
            loadingOptionTrees.value = true;
            try {
                const response = await apiGet('/option-trees?active=1');
                if (response.ok) {
                    const data = await response.json();
                    optionTrees.value = data.data || [];
                }
            } catch (err) {
                console.error('Error fetching option trees:', err);
            } finally {
                loadingOptionTrees.value = false;
            }
        };

        const fetchInstances = async () => {
            loadingInstances.value = true;
            try {
                const response = await apiGet('/instances?active=1');
                if (response.ok) {
                    const data = await response.json();
                    instances.value = data.data || [];
                }
            } catch (err) {
                console.error('Error fetching instances:', err);
            } finally {
                loadingInstances.value = false;
            }
        };


        const openImageModal = () => {
            showImageMediaModal.value = true;
        };

        const openIconModal = () => {
            showIconMediaModal.value = true;
        };

        const handleImageSelected = (file) => {
            selectedImage.value = file;
            localForm.value.image_id = file.id;
            showImageMediaModal.value = false;
        };

        const handleIconSelected = (file) => {
            selectedIcon.value = file;
            localForm.value.icon_id = file.id;
            showIconMediaModal.value = false;
        };

        const removeImage = () => {
            selectedImage.value = null;
            localForm.value.image_id = null;
        };

        const removeIcon = () => {
            selectedIcon.value = null;
            localForm.value.icon_id = null;
        };

        // Синхронизируем с изменениями props
        watch(() => props.initialData, (newData) => {
            localForm.value = {
                name: newData.name || '',
                slug: newData.slug || '',
                chapter_id: newData.chapter_id || null,
                image_id: newData.image_id || null,
                icon_id: newData.icon_id || null,
                order: newData.order ?? 0,
                is_active: newData.is_active !== false,
            };
            if (newData.image) {
                selectedImage.value = newData.image;
            }
            if (newData.icon) {
                selectedIcon.value = newData.icon;
            }
            if (newData.products && Array.isArray(newData.products)) {
                selectedProducts.value = newData.products.map(p => p.id || p);
            }
            if (newData.options && Array.isArray(newData.options)) {
                selectedOptions.value = newData.options.map(o => o.id || o);
            }
            if (newData.option_trees && Array.isArray(newData.option_trees)) {
                selectedOptionTrees.value = newData.option_trees.map(t => t.id || t);
            }
            if (newData.instances && Array.isArray(newData.instances)) {
                selectedInstances.value = newData.instances.map(i => i.id || i);
            }
        }, { deep: true });

        const handleSubmit = () => {
            emit('submit', {
                name: localForm.value.name,
                slug: localForm.value.slug || null,
                chapter_id: localForm.value.chapter_id || null,
                image_id: localForm.value.image_id || null,
                icon_id: localForm.value.icon_id || null,
                order: localForm.value.order,
                is_active: localForm.value.is_active,
                products: selectedProducts.value,
                options: selectedOptions.value,
                option_trees: selectedOptionTrees.value,
                instances: selectedInstances.value,
            });
        };

        const handleCancel = () => {
            emit('cancel');
        };

        onMounted(() => {
            fetchChapters();
            fetchProducts();
            fetchOptions();
            fetchOptionTrees();
            fetchInstances();
            if (props.initialData.image) {
                selectedImage.value = props.initialData.image;
            }
            if (props.initialData.icon) {
                selectedIcon.value = props.initialData.icon;
            }
            if (props.initialData.products && Array.isArray(props.initialData.products)) {
                selectedProducts.value = props.initialData.products.map(p => p.id || p);
            }
            if (props.initialData.options && Array.isArray(props.initialData.options)) {
                selectedOptions.value = props.initialData.options.map(o => o.id || o);
            }
            if (props.initialData.option_trees && Array.isArray(props.initialData.option_trees)) {
                selectedOptionTrees.value = props.initialData.option_trees.map(t => t.id || t);
            }
            if (props.initialData.instances && Array.isArray(props.initialData.instances)) {
                selectedInstances.value = props.initialData.instances.map(i => i.id || i);
            }
        });

        return {
            chapters,
            products,
            options,
            optionTrees,
            instances,
            loadingProducts,
            loadingOptions,
            loadingOptionTrees,
            loadingInstances,
            selectedProducts,
            selectedOptions,
            selectedOptionTrees,
            selectedInstances,
            localForm,
            showImageMediaModal,
            showIconMediaModal,
            selectedImage,
            selectedIcon,
            openImageModal,
            openIconModal,
            handleImageSelected,
            handleIconSelected,
            removeImage,
            removeIcon,
            handleSubmit,
            handleCancel,
        };
    },
};
</script>


<template>
    <div class="faq-settings-page space-y-6">
        <div>
            <h1 class="text-3xl font-semibold text-foreground">Настройки блока FAQ</h1>
            <p class="text-muted-foreground mt-1">Управление блоком "Часто задаваемые вопросы" на главной странице</p>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка настроек...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Settings Form -->
        <div v-if="!loading" class="space-y-6">
            <!-- General Settings -->
            <div class="bg-card rounded-lg border border-border p-6 space-y-6">
                <h2 class="text-xl font-semibold text-foreground">Общие настройки</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Заголовок блока
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            placeholder="Часто задаваемые вопросы"
                            class="w-full px-4 py-2 bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            id="is_active"
                            class="w-4 h-4 rounded border-border"
                        />
                        <label for="is_active" class="text-sm font-medium text-foreground">
                            Блок активен на главной странице
                        </label>
                    </div>
                </div>
            </div>

            <!-- FAQ Items -->
            <div class="bg-card rounded-lg border border-border p-6 space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-foreground">Вопросы и ответы</h2>
                    <button
                        @click="addFaqItem"
                        class="px-4 py-2 bg-accent text-accent-foreground rounded-lg hover:bg-accent/90 transition-colors text-sm font-medium"
                    >
                        + Добавить вопрос
                    </button>
                </div>

                <div v-if="form.faq_items && form.faq_items.length === 0" class="text-center py-8 text-muted-foreground">
                    <p>Нет вопросов. Добавьте первый вопрос.</p>
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="(item, index) in form.faq_items"
                        :key="index"
                        class="border border-border rounded-lg p-4 space-y-4"
                    >
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-foreground">Вопрос {{ index + 1 }}</h3>
                            <button
                                @click="removeFaqItem(index)"
                                class="px-3 py-1 text-sm text-destructive hover:bg-destructive/10 rounded transition-colors"
                            >
                                Удалить
                            </button>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Вопрос *
                            </label>
                            <input
                                v-model="item.question"
                                type="text"
                                placeholder="Введите вопрос"
                                class="w-full px-4 py-2 bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">
                                Ответ *
                            </label>
                            <textarea
                                v-model="item.answer"
                                rows="3"
                                placeholder="Введите ответ на вопрос"
                                class="w-full px-4 py-2 bg-background border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent resize-none"
                            ></textarea>
                        </div>

                        <!-- Кнопки перемещения -->
                        <div class="flex gap-2">
                            <button
                                v-if="index > 0"
                                @click="moveFaqItem(index, 'up')"
                                class="px-3 py-1 text-sm border border-border hover:bg-accent/10 rounded transition-colors"
                                title="Переместить вверх"
                            >
                                ↑ Вверх
                            </button>
                            <button
                                v-if="index < form.faq_items.length - 1"
                                @click="moveFaqItem(index, 'down')"
                                class="px-3 py-1 text-sm border border-border hover:bg-accent/10 rounded transition-colors"
                                title="Переместить вниз"
                            >
                                ↓ Вниз
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-4 border-t border-border">
                <button
                    @click="saveSettings"
                    :disabled="saving"
                    class="px-6 py-2 bg-accent text-accent-foreground rounded-lg hover:bg-accent/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium"
                >
                    {{ saving ? 'Сохранение...' : 'Сохранить настройки' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
    name: 'FaqSettings',
    setup() {
        const loading = ref(true);
        const saving = ref(false);
        const error = ref(null);
        const form = ref({
            title: '',
            is_active: true,
            faq_items: [],
        });

        const fetchSettings = async () => {
            loading.value = true;
            error.value = null;
            try {
                const response = await axios.get('/api/v1/faq-block-settings');
                if (response.data && response.data.data) {
                    form.value = {
                        title: response.data.data.title || '',
                        is_active: response.data.data.is_active !== false,
                        faq_items: response.data.data.faq_items || [],
                    };
                }
            } catch (err) {
                error.value = err.response?.data?.message || 'Ошибка загрузки настроек';
                console.error('Error fetching settings:', err);
            } finally {
                loading.value = false;
            }
        };

        const addFaqItem = () => {
            if (!form.value.faq_items) {
                form.value.faq_items = [];
            }
            form.value.faq_items.push({
                question: '',
                answer: '',
            });
        };

        const removeFaqItem = (index) => {
            if (form.value.faq_items && form.value.faq_items.length > index) {
                form.value.faq_items.splice(index, 1);
            }
        };

        const moveFaqItem = (index, direction) => {
            if (!form.value.faq_items || form.value.faq_items.length <= 1) return;
            
            const newIndex = direction === 'up' ? index - 1 : index + 1;
            if (newIndex >= 0 && newIndex < form.value.faq_items.length) {
                const item = form.value.faq_items[index];
                form.value.faq_items[index] = form.value.faq_items[newIndex];
                form.value.faq_items[newIndex] = item;
            }
        };

        const saveSettings = async () => {
            saving.value = true;
            error.value = null;
            try {
                const response = await axios.put('/api/v1/faq-block-settings', form.value);
                
                await Swal.fire({
                    title: 'Настройки сохранены',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } catch (err) {
                error.value = err.response?.data?.message || 'Ошибка сохранения настроек';
                await Swal.fire({
                    title: 'Ошибка',
                    text: error.value,
                    icon: 'error',
                    confirmButtonText: 'ОК'
                });
            } finally {
                saving.value = false;
            }
        };

        onMounted(() => {
            fetchSettings();
        });

        return {
            loading,
            saving,
            error,
            form,
            addFaqItem,
            removeFaqItem,
            moveFaqItem,
            saveSettings,
        };
    },
};
</script>

<style scoped>
/* Дополнительные стили при необходимости */
</style>


<template>
    <div class="footer-settings-page space-y-6">
        <div>
            <h1 class="text-3xl font-semibold text-foreground">Настройки футера</h1>
            <p class="text-muted-foreground mt-1">
                Редактирование настроек футера сайта
            </p>
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
        <div v-if="!loading && settings" class="space-y-6">
            <!-- Basic Information -->
            <div class="bg-card rounded-lg border border-border p-6 space-y-6">
                <h2 class="text-xl font-semibold text-foreground">Основная информация</h2>
                
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Заголовок
                    </label>
                    <input
                        type="text"
                        v-model="settings.title"
                        placeholder="Контакты"
                        class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Копирайт
                    </label>
                    <input
                        type="text"
                        v-model="settings.copyright"
                        placeholder="MNKA 2025. Все права защищены"
                        class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">
                        Ссылка на политику конфиденциальности
                    </label>
                    <input
                        type="text"
                        v-model="settings.privacy_policy_link"
                        placeholder="/police"
                        class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                    />
                </div>
            </div>

            <!-- Contact Information -->
            <div class="bg-card rounded-lg border border-border p-6 space-y-6">
                <h2 class="text-xl font-semibold text-foreground">Контактная информация</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Метка отдела
                        </label>
                        <input
                            type="text"
                            v-model="settings.department_label"
                            placeholder="Отдел"
                            class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Телефон отдела
                        </label>
                        <input
                            type="text"
                            v-model="settings.department_phone"
                            placeholder="+7 (495) 123-45-67"
                            class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Метка объектов
                        </label>
                        <input
                            type="text"
                            v-model="settings.objects_label"
                            placeholder="Объекты"
                            class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Телефон объектов
                        </label>
                        <input
                            type="text"
                            v-model="settings.objects_phone"
                            placeholder="+7 (495) 123-45-68"
                            class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Метка вопросов
                        </label>
                        <input
                            type="text"
                            v-model="settings.issues_label"
                            placeholder="Вопросы"
                            class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Email для вопросов
                        </label>
                        <input
                            type="email"
                            v-model="settings.issues_email"
                            placeholder="info@example.com"
                            class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                        />
                    </div>
                </div>
            </div>

            <!-- Social Networks -->
            <div class="bg-card rounded-lg border border-border p-6 space-y-6">
                <h2 class="text-xl font-semibold text-foreground">Социальные сети</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            VK
                        </label>
                        <input
                            type="url"
                            v-model="settings.social_networks.vk"
                            placeholder="https://vk.com/example"
                            class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Instagram
                        </label>
                        <input
                            type="url"
                            v-model="settings.social_networks.instagram"
                            placeholder="https://instagram.com/example"
                            class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">
                            Telegram
                        </label>
                        <input
                            type="url"
                            v-model="settings.social_networks.telegram"
                            placeholder="https://t.me/example"
                            class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                        />
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button
                    @click="saveSettings"
                    :disabled="saving"
                    class="px-6 py-2 bg-accent text-accent-foreground rounded hover:bg-accent/90 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium"
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
    name: 'FooterSettings',
    setup() {
        const loading = ref(true);
        const saving = ref(false);
        const error = ref(null);
        const settings = ref(null);

        const fetchSettings = async () => {
            loading.value = true;
            error.value = null;
            
            try {
                const response = await axios.get('/api/v1/footer-settings');
                settings.value = response.data.data;
                
                // Инициализируем social_networks, если его нет
                if (!settings.value.social_networks || typeof settings.value.social_networks !== 'object') {
                    settings.value.social_networks = {
                        vk: null,
                        instagram: null,
                        telegram: null,
                    };
                }
            } catch (err) {
                error.value = err.response?.data?.message || 'Ошибка загрузки настроек';
                console.error('Error fetching footer settings:', err);
            } finally {
                loading.value = false;
            }
        };

        const saveSettings = async () => {
            saving.value = true;
            error.value = null;
            
            try {
                const response = await axios.put('/api/v1/footer-settings', settings.value);
                
                await Swal.fire({
                    icon: 'success',
                    title: 'Успешно!',
                    text: 'Настройки футера успешно сохранены.',
                    timer: 2000,
                    showConfirmButton: false,
                });
            } catch (err) {
                error.value = err.response?.data?.message || 'Ошибка сохранения настроек';
                await Swal.fire({
                    icon: 'error',
                    title: 'Ошибка',
                    text: error.value,
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
            settings,
            saveSettings,
        };
    },
};
</script>


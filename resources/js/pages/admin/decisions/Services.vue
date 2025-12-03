<template>
    <div class="services-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Услуги</h1>
                <p class="text-muted-foreground mt-1">Управление услугами для блока решений</p>
            </div>
            <div class="flex items-center gap-3">
                <button
                    @click="exportServices('csv')"
                    :disabled="loading || exporting"
                    class="h-11 px-4 border border-border bg-background hover:bg-muted/10 rounded-lg transition-colors inline-flex items-center justify-center gap-2 disabled:opacity-50"
                >
                    <span v-if="!exporting">📥</span>
                    <span v-else>...</span>
                    <span>{{ exporting ? 'Экспорт...' : 'Экспорт CSV' }}</span>
                </button>
                <button
                    @click="exportServices('zip')"
                    :disabled="loading || exporting"
                    class="h-11 px-4 border border-border bg-background hover:bg-muted/10 rounded-lg transition-colors inline-flex items-center justify-center gap-2 disabled:opacity-50"
                >
                    <span v-if="!exporting">📦</span>
                    <span v-else>...</span>
                    <span>{{ exporting ? 'Экспорт...' : 'Экспорт ZIP' }}</span>
                </button>
                <label class="h-11 px-4 border border-border bg-background hover:bg-muted/10 rounded-lg transition-colors inline-flex items-center justify-center gap-2 cursor-pointer">
                    <input
                        type="file"
                        accept=".zip,.csv,.txt"
                        @change="handleImportFile"
                        class="hidden"
                        :disabled="loading || importing"
                    />
                    <span v-if="!importing">📤</span>
                    <span v-else>...</span>
                    <span>{{ importing ? 'Импорт...' : 'Импорт ZIP/CSV' }}</span>
                </label>
                <router-link
                    :to="{ name: 'admin.decisions.services.create' }"
                    class="h-11 px-6 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-2xl shadow-lg shadow-accent/10 inline-flex items-center justify-center gap-2"
                >
                    <span>+</span>
                    <span>Создать услугу</span>
                </router-link>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка услуг...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Services List -->
        <div v-if="!loading && services.length > 0" class="bg-card rounded-lg border border-border overflow-hidden">
            <div class="divide-y divide-border">
                <div
                    v-for="service in services"
                    :key="service.id"
                    class="p-4 hover:bg-muted/10 transition-colors flex items-center justify-between"
                >
                    <div class="flex-1">
                        <h3 class="font-medium text-foreground">{{ service.name }}</h3>
                        <p class="text-sm text-muted-foreground">
                            Slug: {{ service.slug }} | 
                            Порядок: {{ service.order }} | 
                            {{ service.is_active ? 'Активна' : 'Неактивна' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <router-link
                            :to="{ name: 'admin.decisions.services.edit', params: { id: service.id } }"
                            class="px-3 py-1.5 text-sm bg-muted hover:bg-muted/80 rounded-lg transition-colors inline-block"
                        >
                            Редактировать
                        </router-link>
                        <button
                            @click="deleteService(service)"
                            class="px-3 py-1.5 text-sm bg-destructive/10 text-destructive hover:bg-destructive/20 rounded-lg transition-colors"
                        >
                            Удалить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && services.length === 0" class="text-center py-12 bg-card rounded-lg border border-border">
            <p class="text-muted-foreground">Услуги не найдены</p>
        </div>

    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { apiGet, apiDelete } from '../../../utils/api';
import Swal from 'sweetalert2';

export default {
    name: 'DecisionServices',
    setup() {
        const loading = ref(false);
        const exporting = ref(false);
        const importing = ref(false);
        const error = ref(null);
        const services = ref([]);

        const fetchServices = async () => {
            loading.value = true;
            error.value = null;
            try {
                const response = await apiGet('/services');
                if (!response.ok) {
                    throw new Error('Ошибка загрузки услуг');
                }
                const data = await response.json();
                services.value = data.data || [];
            } catch (err) {
                error.value = err.message || 'Ошибка загрузки услуг';
                console.error('Error fetching services:', err);
            } finally {
                loading.value = false;
            }
        };

        const exportServices = async (format = 'csv') => {
            exporting.value = true;
            try {
                const token = localStorage.getItem('token');
                const url = `/api/v1/services/export${format === 'zip' ? '?format=zip' : ''}`;
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': format === 'zip' ? 'application/zip' : 'text/csv',
                    },
                });

                if (!response.ok) {
                    throw new Error('Ошибка экспорта услуг');
                }

                const blob = await response.blob();
                const url_download = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url_download;
                const extension = format === 'zip' ? 'zip' : 'csv';
                a.download = `services_${new Date().toISOString().split('T')[0]}.${extension}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url_download);
                document.body.removeChild(a);

                await Swal.fire({
                    title: 'Экспорт завершен',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } catch (err) {
                await Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка экспорта услуг',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                });
            } finally {
                exporting.value = false;
            }
        };

        const handleImportFile = async (event) => {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }

            // Проверяем размер файла на клиенте (100MB = 100 * 1024 * 1024 байт)
            const maxSize = 100 * 1024 * 1024; // 100MB
            if (file.size > maxSize) {
                await Swal.fire({
                    title: 'Файл слишком большой',
                    html: `
                        <p>Размер файла: <strong>${(file.size / 1024 / 1024).toFixed(2)} MB</strong></p>
                        <p>Максимальный размер: <strong>100 MB</strong></p>
                        <p class="mt-4 text-sm text-gray-600">Если файл меньше 100MB, проверьте настройки сервера:</p>
                        <ul class="mt-2 text-sm text-left list-disc list-inside text-gray-600">
                            <li>PHP: upload_max_filesize, post_max_size</li>
                            <li>Веб-сервер: client_max_body_size (nginx) или LimitRequestBody (apache)</li>
                        </ul>
                    `,
                    icon: 'error',
                    confirmButtonText: 'ОК'
                });
                event.target.value = '';
                return;
            }

            importing.value = true;
            try {
                const formData = new FormData();
                formData.append('file', file);

                const token = localStorage.getItem('token');
                const response = await fetch('/api/v1/services/import', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                    },
                    body: formData,
                });

                // Проверяем статус ответа
                if (response.status === 413) {
                    throw new Error('Файл слишком большой. Максимальный размер: 100MB. Проверьте настройки сервера (upload_max_filesize, post_max_size).');
                }

                let result;
                try {
                    result = await response.json();
                } catch (e) {
                    if (response.status === 413) {
                        throw new Error('Файл слишком большой (413 Content Too Large). Увеличьте лимиты загрузки на сервере.');
                    }
                    throw new Error('Ошибка обработки ответа сервера');
                }

                if (!response.ok) {
                    const errorMessage = result?.message || result?.errors?.[0] || 'Ошибка импорта услуг';
                    throw new Error(errorMessage);
                }

                await Swal.fire({
                    title: 'Импорт завершен',
                    html: `
                        <p>${result.message}</p>
                        ${result.errors && result.errors.length > 0 ? `
                            <details class="mt-4 text-left">
                                <summary class="cursor-pointer text-sm">Ошибки (${result.errors.length})</summary>
                                <div class="mt-2 max-h-40 overflow-y-auto text-xs">
                                    ${result.errors.map(e => `<p>Строка ${e.row}: ${e.errors.join(', ')}</p>`).join('')}
                                </div>
                            </details>
                        ` : ''}
                    `,
                    icon: result.success_count > 0 ? 'success' : 'warning',
                    confirmButtonText: 'ОК'
                });

                // Очищаем input
                event.target.value = '';
                await fetchServices();
            } catch (err) {
                await Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка импорта услуг',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                });
            } finally {
                importing.value = false;
            }
        };

        const deleteService = async (service) => {
            const result = await Swal.fire({
                title: 'Удалить услугу?',
                text: `Вы уверены, что хотите удалить услугу "${service.name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Да, удалить',
                cancelButtonText: 'Отмена',
                confirmButtonColor: '#dc2626',
            });

            if (!result.isConfirmed) {
                return;
            }

            try {
                const response = await apiDelete(`/services/${service.id}`);
                if (!response.ok) {
                    throw new Error('Ошибка удаления услуги');
                }

                await Swal.fire({
                    title: 'Услуга удалена',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });

                await fetchServices();
            } catch (err) {
                await Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка удаления услуги',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                });
            }
        };

        onMounted(() => {
            fetchServices();
        });

        return {
            loading,
            exporting,
            importing,
            error,
            services,
            fetchServices,
            deleteService,
            exportServices,
            handleImportFile,
        };
    },
};
</script>


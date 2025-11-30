<template>
    <section v-if="settings && settings.is_active" class="w-full px-3 sm:px-4 md:px-5 py-8 sm:py-12 md:py-16 lg:py-20">
        <div class="w-full max-w-[1200px] mx-auto">
            <!-- Заголовок -->
            <div v-if="settings.title" class="flex justify-center mb-8 sm:mb-12">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-semibold text-foreground">
                    {{ settings.title }}
                </h2>
            </div>

            <!-- FAQ Items -->
            <div v-if="settings.faq_items && settings.faq_items.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <div
                    v-for="(item, index) in settings.faq_items"
                    :key="index"
                    class="bg-[rgba(244,246,252,0.8)] backdrop-blur-[25.73px] rounded-[10px] sm:rounded-lg p-4 sm:p-6"
                >
                    <!-- Question -->
                    <button
                        @click="toggleAnswer(index)"
                        class="w-full flex items-center justify-between gap-4 cursor-pointer text-left"
                    >
                        <div class="flex-1 text-base sm:text-lg font-normal text-foreground leading-[22px]">
                            {{ item.question }}
                        </div>
                        <svg
                            :class="[
                                'w-3 h-3 flex-shrink-0 transition-transform duration-300',
                                openItems[index] ? 'rotate-180' : ''
                            ]"
                            width="11"
                            height="12"
                            viewBox="0 0 11 12"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <mask id="mask0_230_3650" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="11" height="12">
                                <rect x="10.9831" y="0.949219" width="10.9398" height="10.9398" transform="rotate(90 10.9831 0.949219)" fill="#000"/>
                            </mask>
                            <g mask="url(#mask0_230_3650)">
                                <path d="M0.954872 8.24186L5.51314 3.68359L10.0714 8.24186L9.26232 9.05096L5.51314 5.30178L1.76396 9.05096L0.954872 8.24186Z" fill="currentColor" class="text-foreground"/>
                            </g>
                        </svg>
                    </button>

                    <!-- Answer -->
                    <div
                        v-show="openItems[index]"
                        class="mt-4 text-sm sm:text-base font-medium text-foreground leading-[15px] sm:leading-[20px]"
                    >
                        {{ item.answer }}
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-else class="text-center py-12 text-muted-foreground">
                <p>Вопросы не настроены. Добавьте вопросы в настройках блока.</p>
            </div>
        </div>
    </section>
</template>

<script>
import { ref, onMounted } from 'vue';

export default {
    name: 'Faq',
    setup() {
        const settings = ref(null);
        const loading = ref(true);
        const openItems = ref({});

        const fetchSettings = async () => {
            try {
                const response = await fetch('/api/public/faq-block/settings');
                if (response.ok) {
                    const data = await response.json();
                    if (data.data) {
                        settings.value = data.data;
                        // Инициализируем состояние открытых элементов
                        if (data.data.faq_items) {
                            data.data.faq_items.forEach((_, index) => {
                                openItems.value[index] = false;
                            });
                        }
                    }
                }
            } catch (error) {
                console.error('Error fetching FAQ block settings:', error);
            } finally {
                loading.value = false;
            }
        };

        const toggleAnswer = (index) => {
            openItems.value[index] = !openItems.value[index];
        };

        onMounted(() => {
            fetchSettings();
        });

        return {
            settings,
            loading,
            openItems,
            toggleAnswer,
        };
    },
};
</script>

<style scoped>
/* Дополнительные стили при необходимости */
</style>


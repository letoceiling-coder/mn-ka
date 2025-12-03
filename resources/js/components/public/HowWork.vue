<template>
    <section v-if="settings && settings.is_active" class="w-full px-3 sm:px-4 md:px-5 py-8 sm:py-12 md:py-16 lg:py-20">
        <div class="w-full max-w-[1200px] mx-auto">
            <!-- Заголовок -->
            <div v-if="settings.title" class="flex justify-center mb-8 sm:mb-12">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-semibold text-foreground">
                    {{ settings.title }}
                </h2>
            </div>

            <!-- Основной контент: две колонки -->
            <div class="flex flex-col md:flex-row gap-6 md:gap-8">
                <!-- Левая колонка: Шаги (на мобильных - внизу) -->
                <div class="w-full md:w-1/2 order-2 md:order-1">
                    <div class="space-y-0">
                        <div
                            v-for="(step, index) in settings.steps"
                            :key="index"
                            class="flex gap-4 relative"
                        >
                            <!-- Вертикальная линия и иконка -->
                            <div class="flex flex-col items-center flex-shrink-0">
                                <!-- Иконка (круг или звезда) -->
                                <div class="w-5 h-5 flex-shrink-0">
                                    <!-- Круг -->
                                    <svg
                                        v-if="step.point === 'disc'"
                                        width="20"
                                        height="20"
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <rect width="20" height="20" rx="10" fill="#6C7B6D"/>
                                    </svg>
                                    <!-- Звезда -->
                                    <svg
                                        v-else-if="step.point === 'star'"
                                        width="20"
                                        height="20"
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            d="M3.825 20L5.45 12.6053L0 7.63158L7.2 6.97368L10 0L12.8 6.97368L20 7.63158L14.55 12.6053L16.175 20L10 16.0789L3.825 20Z"
                                            fill="#6C7B6D"
                                        />
                                    </svg>
                                </div>
                                
                                <!-- Вертикальная линия (кроме последнего элемента) -->
                                <div
                                    v-if="index < settings.steps.length - 1"
                                    class="w-0.5 bg-[#306221] flex-1 min-h-[50px] mt-2"
                                ></div>
                            </div>

                            <!-- Текст шага -->
                            <div class="flex-1 pb-6 md:pb-8">
                                <h3
                                    v-if="step.title"
                                    class="text-base sm:text-lg font-medium text-[#424448] leading-[22px] mb-2"
                                    v-html="step.title"
                                ></h3>
                                <p
                                    v-if="step.description"
                                    class="text-sm sm:text-base font-normal text-[#424448] leading-[20px]"
                                    v-html="step.description"
                                ></p>
                            </div>
                        </div>
                    </div>

                    <!-- Кнопка на мобильных (под шагами) -->
                    <button
                        v-if="settings.button_text"
                        @click="handleButtonClick"
                        class="mt-6 w-full md:hidden px-6 py-3 bg-[#6C7B6D] hover:bg-[#55695a] text-white font-medium rounded-lg transition-colors"
                    >
                        {{ settings.button_text }}
                    </button>
                </div>

                <!-- Правая колонка: Изображение и кнопка (на мобильных - сверху) -->
                <div class="w-full md:w-1/2 order-1 md:order-2">
                    <!-- Изображение -->
                    <div v-if="settings.image && imageUrl" class="overflow-hidden rounded-[18px] mb-6">
                        <img
                            :src="imageUrl"
                            :alt="settings.image_alt || 'Как мы работаем'"
                            class="w-full h-auto object-cover"
                        />
                    </div>

                    <!-- Кнопка на десктопе (под изображением) -->
                    <button
                        v-if="settings.button_text"
                        @click="handleButtonClick"
                        class="hidden md:block w-full px-6 py-3 bg-[#6C7B6D] hover:bg-[#55695a] text-white font-medium rounded-lg transition-colors"
                    >
                        {{ settings.button_text }}
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';

export default {
    name: 'HowWork',
    setup() {
        const router = useRouter();
        const settings = ref(null);
        const loading = ref(true);

        const fetchSettings = async () => {
            try {
                const response = await fetch('/api/public/how-work-block/settings');
                if (response.ok) {
                    const data = await response.json();
                    if (data.data) {
                        settings.value = data.data;
                    }
                }
            } catch (error) {
                console.error('Error fetching HowWork block settings:', error);
            } finally {
                loading.value = false;
            }
        };

        const imageUrl = computed(() => {
            if (!settings.value?.image) return null;
            const img = settings.value.image;
            // Если это путь к файлу
            if (typeof img === 'string') {
                return img.startsWith('/') ? img : `/${img}`;
            }
            return null;
        });

        const handleButtonClick = () => {
            if (!settings.value) return;
            
            if (settings.value.button_type === 'url' && settings.value.button_value) {
                if (settings.value.button_value.startsWith('http')) {
                    window.open(settings.value.button_value, '_blank');
                } else {
                    router.push(settings.value.button_value);
                }
            } else if (settings.value.button_type === 'method') {
                // TODO: Реализовать вызов метода/popup
                console.log('Method button clicked:', settings.value.button_value);
            }
        };

        onMounted(() => {
            fetchSettings();
        });

        return {
            settings,
            loading,
            imageUrl,
            handleButtonClick,
        };
    },
};
</script>

<style scoped>
/* Дополнительные стили при необходимости */
</style>

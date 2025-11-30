<template>
    <div class="w-full px-3 sm:px-4 md:px-5 mt-8 sm:mt-12">
        <div class="w-full max-w-[1200px] mx-auto">
            <!-- Заголовок -->
            <div class="flex justify-center mb-6 sm:mb-8">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-semibold text-gray-900">
                    {{ title }}
                </h2>
            </div>

            <!-- Загрузка -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="text-gray-500">Загрузка...</div>
            </div>

            <!-- Ошибка -->
            <div v-else-if="error" class="flex justify-center items-center py-12">
                <div class="text-red-500">{{ error }}</div>
            </div>

            <!-- Контент -->
            <template v-else>
                <!-- Кнопки категорий -->
                <div v-if="chapters.length > 0" class="flex flex-col sm:flex-row gap-3 sm:gap-0 mb-6 sm:mb-8">
                    <button
                        v-for="(chapter, index) in chapters"
                        :key="chapter.id"
                        @click="setActiveChapter(chapter)"
                        :class="[
                            'flex-1 px-4 sm:px-6 py-2.5 sm:py-3 text-sm sm:text-base font-normal text-center transition-all duration-200',
                            'focus:outline-none focus:ring-2 focus:ring-offset-2',
                            // Мобильные стили - всегда закругленные углы
                            'rounded-lg sm:rounded-none',
                            // Стили для активной кнопки
                            chapter.active
                                ? 'bg-[#657C6C] text-white border border-[#657C6C] hover:bg-[#55695a] focus:ring-[#657C6C] shadow-md'
                                : 'bg-white text-black border border-[#657C6C] hover:bg-[#D7D7D7] focus:ring-[#657C6C] shadow-sm',
                            // Стили для десктопа - первая кнопка
                            index === 0 ? (chapter.active ? 'sm:rounded-l-lg sm:rounded-r-none' : 'sm:rounded-l-lg sm:rounded-r-none sm:border-r-0') : '',
                            // Стили для десктопа - последняя кнопка
                            index === chapters.length - 1 ? (chapter.active ? 'sm:rounded-r-lg sm:rounded-l-none' : 'sm:rounded-r-lg sm:rounded-l-none sm:border-l-0') : '',
                        ]"
                    >
                        {{ chapter.name }}
                    </button>
                </div>

                <!-- Сетка карточек с анимацией -->
                <Transition
                    name="fade-slide"
                    mode="out-in"
                >
                    <div v-if="activeItems.length > 0" :key="`content-${activeChapter?.id}`" class="relative">
                        <TransitionGroup
                            name="stagger"
                            tag="div"
                            class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6"
                        >
                            <DecisionCard
                                v-for="(item, index) in activeItems"
                                :key="`${item.id}-${item.category}-${activeChapter?.id}`"
                                :decision="item"
                                :slug="item.category === 'products' ? 'products' : 'services'"
                            />
                        </TransitionGroup>
                    </div>
                    <div v-else :key="`empty-${activeChapter?.id}`" class="flex justify-center items-center py-12">
                        <div class="text-gray-500">В этом разделе пока нет элементов</div>
                    </div>
                </Transition>
            </template>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, Transition, TransitionGroup } from 'vue';
import DecisionCard from './DecisionCard.vue';

export default {
    name: 'Decisions',
    components: {
        DecisionCard,
        Transition,
        TransitionGroup,
    },
    setup() {
        const title = ref('Выберите решение под ваш участок');
        const chapters = ref([]);
        const loading = ref(true);
        const error = ref(null);

        // Загрузка настроек блока
        const loadSettings = async () => {
            try {
                const response = await fetch('/api/public/decision-block/settings');
                if (!response.ok) {
                    throw new Error('Ошибка загрузки настроек');
                }
                const result = await response.json();
                if (result.data && result.data.title) {
                    title.value = result.data.title;
                }
            } catch (err) {
                console.error('Ошибка загрузки настроек:', err);
                // Используем значение по умолчанию при ошибке
            }
        };

        // Загрузка разделов с продуктами и услугами
        const loadChapters = async () => {
            loading.value = true;
            error.value = null;
            
            try {
                const response = await fetch('/api/public/decision-block/chapters');
                if (!response.ok) {
                    throw new Error('Ошибка загрузки данных');
                }
                
                const result = await response.json();
                const loadedChapters = result.data || [];
                
                // Преобразуем данные для работы компонента
                chapters.value = loadedChapters.map((chapter, index) => ({
                    id: chapter.id,
                    name: chapter.name,
                    active: index === 0, // Первый раздел активен по умолчанию
                    // Объединяем продукты и услуги в один массив
                    items: [
                        ...(chapter.products || []).map(product => ({
                            ...product,
                            category: 'products',
                        })),
                        ...(chapter.services || []).map(service => ({
                            ...service,
                            category: 'services',
                        })),
                    ],
                }));
                
                // Если нет активного раздела, делаем первый активным
                if (chapters.value.length > 0 && !chapters.value.find(ch => ch.active)) {
                    chapters.value[0].active = true;
                }
            } catch (err) {
                console.error('Ошибка загрузки разделов:', err);
                error.value = 'Не удалось загрузить данные. Попробуйте обновить страницу.';
            } finally {
                loading.value = false;
            }
        };

        const activeChapter = computed(() => {
            return chapters.value.find((chapter) => chapter.active) || chapters.value[0];
        });

        const activeItems = computed(() => {
            return activeChapter.value?.items || [];
        });

        const setActiveChapter = (chapter) => {
            chapters.value.forEach((ch) => {
                ch.active = ch.id === chapter.id;
            });
        };

        // Загрузка данных при монтировании
        onMounted(async () => {
            await Promise.all([
                loadSettings(),
                loadChapters(),
            ]);
        });

        return {
            title,
            chapters,
            activeChapter,
            activeItems,
            setActiveChapter,
            loading,
            error,
        };
    },
};
</script>

<style scoped>
/* Анимация fade и slide для переключения разделов */
.fade-slide-enter-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-slide-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(30px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}

/* Анимация для карточек с задержкой (stagger effect) */
.stagger-move {
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.stagger-enter-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.stagger-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.stagger-enter-from {
    opacity: 0;
    transform: translateY(20px) scale(0.96);
}

.stagger-leave-to {
    opacity: 0;
    transform: scale(0.96);
}

/* Добавляем задержку для каждой карточки */
.stagger-enter-active:nth-child(1) { transition-delay: 0.03s; }
.stagger-enter-active:nth-child(2) { transition-delay: 0.06s; }
.stagger-enter-active:nth-child(3) { transition-delay: 0.09s; }
.stagger-enter-active:nth-child(4) { transition-delay: 0.12s; }
.stagger-enter-active:nth-child(5) { transition-delay: 0.15s; }
.stagger-enter-active:nth-child(6) { transition-delay: 0.18s; }
.stagger-enter-active:nth-child(7) { transition-delay: 0.21s; }
.stagger-enter-active:nth-child(8) { transition-delay: 0.24s; }
.stagger-enter-active:nth-child(9) { transition-delay: 0.27s; }
.stagger-enter-active:nth-child(10) { transition-delay: 0.3s; }
.stagger-enter-active:nth-child(n+11) { transition-delay: 0.33s; }
</style>


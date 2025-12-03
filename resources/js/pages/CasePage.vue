<template>
    <div class="case-page min-h-screen bg-white">
        <div class="w-full px-3 sm:px-4 md:px-5">
            <div class="w-full max-w-[1200px] mx-auto">
                <!-- Skeleton Loader -->
                <div v-if="loading && !caseItem" class="py-12 space-y-8">
                    <div class="h-8 bg-gray-200 rounded w-1/3 animate-pulse"></div>
                    <div class="h-64 bg-gray-200 rounded animate-pulse"></div>
                    <div class="space-y-4">
                        <div class="h-4 bg-gray-200 rounded animate-pulse"></div>
                        <div class="h-4 bg-gray-200 rounded w-5/6 animate-pulse"></div>
                    </div>
                </div>

                <!-- Ошибка -->
                <div v-if="error && !loading" class="py-12">
                    <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-600">{{ error }}</p>
                        <router-link
                            to="/cases"
                            class="mt-4 inline-block text-sm text-[#688E67] hover:underline"
                        >
                            Вернуться к списку кейсов
                        </router-link>
                    </div>
                </div>

                <!-- Контент кейса -->
                <div v-if="caseItem && !loading">
                    <!-- Хлебные крошки -->
                    <nav class="hidden md:flex items-center gap-2 text-sm text-gray-600 mt-4">
                        <router-link 
                            to="/" 
                            class="hover:text-black transition-colors"
                        >
                            Главная
                        </router-link>
                        <span>/</span>
                        <router-link 
                            to="/cases" 
                            class="hover:text-black transition-colors"
                        >
                            Кейсы и объекты
                        </router-link>
                        <span>/</span>
                        <span class="text-black">{{ caseItem.name }}</span>
                    </nav>

                    <!-- Заголовок -->
                    <div class="mt-6 md:mt-8">
                        <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-black mb-3 md:mb-4">
                            {{ caseItem.name }}
                        </h1>
                    </div>

                    <!-- Описание -->
                    <div 
                        v-if="caseDescription"
                        class="mt-3 text-base md:text-lg text-gray-600"
                    >
                        {{ caseDescription }}
                    </div>

                    <!-- Два изображения рядом -->
                    <div 
                        v-if="heroImages && heroImages.length > 0"
                        :class="[
                            'grid gap-4 md:gap-6 mt-6 md:mt-8',
                            heroImages.length >= 2 ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-1'
                        ]"
                    >
                        <div 
                            v-for="(image, index) in heroImages"
                            :key="image.id || index"
                            :class="[
                                'relative w-full bg-gray-100 rounded-lg overflow-hidden',
                                heroImages.length >= 2 ? 'aspect-[50/33]' : 'aspect-[100/51]'
                            ]"
                        >
                            <img
                                v-if="image.url || image.webp"
                                :src="image.webp || image.url"
                                :alt="`${caseItem.name} - изображение ${index + 1}`"
                                class="w-full h-full object-cover"
                                loading="lazy"
                                decoding="async"
                                @error="handleImageError"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center bg-gray-100">
                                <span class="text-gray-400 text-sm">Изображение не загружено</span>
                            </div>
                        </div>
                    </div>

                    <!-- HTML контент -->
                    <div 
                        v-if="caseHtml"
                        class="mt-6 md:mt-8 prose prose-sm md:prose-base max-w-none prose-headings:text-black prose-p:text-gray-700 prose-a:text-[#688E67]"
                        v-html="caseHtml"
                    ></div>

                    <!-- Галерея изображений (карусель) -->
                    <div 
                        v-if="caseItem.images && caseItem.images.length > 0"
                        class="mt-6 md:mt-8 relative"
                    >
                        <CaseImageCarousel :images="caseItem.images" :case-name="caseItem.name" />
                    </div>

                    <!-- Информация о публикации и шаринг -->
                    <div class="mt-6 md:mt-8 pt-6 border-t border-gray-200">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <!-- Дата публикации -->
                            <div v-if="caseItem.published || caseItem.created_at" class="flex flex-col gap-1">
                                <span class="text-sm text-gray-600">Опубликован</span>
                                <span class="text-base text-black font-medium">
                                    {{ caseItem.published || formatDate(caseItem.created_at) }}
                                </span>
                            </div>

                            <!-- Кнопка поделиться -->
                            <button
                                @click="handleShare"
                                class="inline-flex items-center gap-2 px-4 py-2 text-[#688E67] hover:bg-[#688E67]/10 rounded-lg transition-colors font-medium self-start md:self-auto cursor-pointer"
                            >
                                <span>Поделиться</span>
                                <svg 
                                    width="29" 
                                    height="32" 
                                    viewBox="0 0 29 32" 
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-6"
                                >
                                    <path
                                        d="M23.498 10C25.9833 10 27.998 7.98528 27.998 5.5C27.998 3.01472 25.9833 1 23.498 1C21.0128 1 18.998 3.01472 18.998 5.5C18.998 7.98528 21.0128 10 23.498 10Z"
                                        stroke="#688E67"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M5.5 20.5001C7.98528 20.5001 10 18.4854 10 16.0001C10 13.5148 7.98528 11.5001 5.5 11.5001C3.01472 11.5001 1 13.5148 1 16.0001C1 18.4854 3.01472 20.5001 5.5 20.5001Z"
                                        stroke="#688E67"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M23.498 31C25.9833 31 27.998 28.9853 27.998 26.5C27.998 24.0147 25.9833 22 23.498 22C21.0128 22 18.998 24.0147 18.998 26.5C18.998 28.9853 21.0128 31 23.498 31Z"
                                        stroke="#688E67"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <path 
                                        d="M9.24805 18.25L19.748 24.25M19.748 7.75L9.24805 13.75" 
                                        stroke="#688E67"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Похожие кейсы -->
        <div v-if="caseItem && !loading" class="mt-12">
            <SimilarCases 
                :chapter-id="caseItem.chapter_id"
                :exclude-case-id="caseItem.id"
                :limit="2"
            />
        </div>

        <!-- Форма обратной связи -->
        <div v-if="caseItem && !loading" class="w-full px-3 sm:px-4 md:px-5 mt-12 pb-12">
            <div class="w-full max-w-[1200px] mx-auto">
                <FeedbackForm 
                    title="Оставьте заявку"
                    description="Мы бесплатно подберём участок и покажем, как его оформить под ваш проект"
                />
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import LazyImage from '../components/public/LazyImage.vue';
import CaseImageCarousel from '../components/public/CaseImageCarousel.vue';
import SimilarCases from '../components/public/SimilarCases.vue';
import FeedbackForm from '../components/public/FeedbackForm.vue';

export default {
    name: 'CasePage',
    components: {
        LazyImage,
        CaseImageCarousel,
        SimilarCases,
        FeedbackForm,
    },
    setup() {
        const route = useRoute();
        const loading = ref(false);
        const error = ref(null);
        const caseItem = ref(null);

        // Вычисляем изображения для отображения в hero-секции (первые 2)
        const heroImages = computed(() => {
            if (!caseItem.value || !caseItem.value.images) {
                console.log('No images in caseItem:', caseItem.value);
                return [];
            }
            
            // Фильтруем и возвращаем первые 2 изображения с валидными URL
            const validImages = caseItem.value.images
                .slice(0, 2)
                .filter(img => {
                    const hasUrl = img && (img.url || img.webp);
                    if (!hasUrl) {
                        console.warn('Image without URL:', img);
                    }
                    return hasUrl;
                });
            
            console.log('Hero images computed:', {
                total: caseItem.value.images.length,
                valid: validImages.length,
                images: validImages
            });
            
            return validImages;
        });

        // Вычисляем описание для отображения
        const caseDescription = computed(() => {
            if (!caseItem.value) return null;
            
            // Пробуем получить описание
            if (caseItem.value.description) {
                if (typeof caseItem.value.description === 'string') {
                    return caseItem.value.description;
                }
                if (typeof caseItem.value.description === 'object') {
                    // Если это объект, пробуем получить полное описание
                    if (caseItem.value.description.full) {
                        return caseItem.value.description.full;
                    }
                    if (caseItem.value.description.short) {
                        return caseItem.value.description.short;
                    }
                    if (caseItem.value.description.ru) {
                        return caseItem.value.description.ru;
                    }
                    if (Array.isArray(caseItem.value.description) && caseItem.value.description.length > 0) {
                        return caseItem.value.description[0];
                    }
                    // Если это объект, пробуем преобразовать в строку
                    const descString = JSON.stringify(caseItem.value.description);
                    if (descString && descString !== '{}') {
                        return descString.replace(/[{}"]/g, '');
                    }
                }
            }
            
            return null;
        });

        // Вычисляем HTML контент
        const caseHtml = computed(() => {
            if (!caseItem.value || !caseItem.value.html) return null;
            
            if (typeof caseItem.value.html === 'string') {
                return caseItem.value.html;
            }
            
            if (typeof caseItem.value.html === 'object') {
                // Если это объект, пробуем получить полный HTML
                if (caseItem.value.html.content) {
                    return caseItem.value.html.content;
                }
                if (caseItem.value.html.lead) {
                    return caseItem.value.html.lead;
                }
                if (caseItem.value.html.ru) {
                    return caseItem.value.html.ru;
                }
                if (Array.isArray(caseItem.value.html) && caseItem.value.html.length > 0) {
                    return caseItem.value.html[0];
                }
            }
            
            return null;
        });

        const formatDate = (dateString) => {
            if (!dateString) return '';
            
            try {
                const date = new Date(dateString);
                const day = date.getDate();
                const months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
                const month = months[date.getMonth()];
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                
                return `${day} ${month} ${hours}:${minutes}`;
            } catch (e) {
                return dateString;
            }
        };

        const handleImageError = (event) => {
            console.error('Error loading image:', event.target.src);
            // Можно добавить fallback изображение или скрыть элемент
            event.target.style.display = 'none';
        };

        const handleShare = async () => {
            const url = window.location.href;
            const title = caseItem.value?.name || 'Кейс';

            if (navigator.share) {
                try {
                    await navigator.share({
                        title,
                        text: caseDescription.value || '',
                        url,
                    });
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error('Error sharing:', err);
                    }
                }
            } else {
                // Fallback: копируем в буфер обмена
                try {
                    await navigator.clipboard.writeText(url);
                    alert('Ссылка скопирована в буфер обмена');
                } catch (err) {
                    console.error('Failed to copy:', err);
                }
            }
        };

        const fetchCase = async () => {
            loading.value = true;
            error.value = null;

            try {
                const slug = route.params.slug;
                const url = `/api/public/cases/${slug}`;

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                });

                if (!response.ok) {
                    if (response.status === 404) {
                        throw new Error('Кейс не найден');
                    }
                    throw new Error('Ошибка загрузки кейса');
                }

                const data = await response.json();
                
                if (data.data) {
                    caseItem.value = Array.isArray(data.data) ? data.data[0] : data.data;
                    
                    // Отладка: выводим полную информацию о кейсе
                    console.log('Case loaded:', {
                        name: caseItem.value.name,
                        description: caseItem.value.description,
                        html: caseItem.value.html,
                        hasImages: !!caseItem.value.images,
                        imagesCount: caseItem.value.images?.length,
                        images: caseItem.value.images,
                        hasImage: !!caseItem.value.image,
                        image: caseItem.value.image,
                        chapter: caseItem.value.chapter,
                    });
                } else {
                    throw new Error('Кейс не найден');
                }
            } catch (err) {
                console.error('Error fetching case:', err);
                error.value = err.message || 'Ошибка загрузки кейса';
            } finally {
                loading.value = false;
            }
        };

        // Отслеживаем изменения slug для перезагрузки кейса
        watch(() => route.params.slug, (newSlug, oldSlug) => {
            if (newSlug !== oldSlug) {
                caseItem.value = null;
                fetchCase();
            }
        });

        onMounted(() => {
            fetchCase();
        });

        return {
            loading,
            error,
            caseItem,
            heroImages,
            caseDescription,
            caseHtml,
            formatDate,
            handleImageError,
            handleShare,
        };
    },
};
</script>

<style scoped>
:deep(.prose) {
    color: #374151;
}

:deep(.prose h1),
:deep(.prose h2),
:deep(.prose h3),
:deep(.prose h4) {
    color: #000000;
    font-weight: 600;
}

:deep(.prose p) {
    color: #374151;
    line-height: 1.75;
}

:deep(.prose a) {
    color: #688E67;
}

:deep(.prose a:hover) {
    text-decoration: underline;
}

:deep(.prose img) {
    border-radius: 0.75rem;
    margin-top: 1.5rem;
    margin-bottom: 1.5rem;
}

:deep(.prose ul),
:deep(.prose ol) {
    color: #374151;
}
</style>

<template>
    <div class="products-page min-h-screen bg-background">
        <SEOHead
            title="Продукты и услуги - Lagom | Каталог земельных участков"
            description="Полный каталог продуктов и услуг по подбору, оформлению и работе с земельными участками. Профессиональные решения для складов, производства, придорожного сервиса и недвижимости."
            keywords="продукты, услуги, земельные участки, недвижимость, кадастр, оформление земли, подбор участка"
            :canonical="canonicalUrl"
            :schema="breadcrumbSchema"
        />
        
        <div class="w-full px-3 sm:px-4 md:px-5">
            <div class="w-full max-w-[1200px] mx-auto">
                <!-- Хлебные крошки -->
                <div class="hidden md:block mt-4">
                    <nav class="flex items-center gap-2 text-sm text-muted-foreground">
                        <router-link to="/" class="hover:text-foreground transition-colors">Главная</router-link>
                        <span>/</span>
                        <span class="text-foreground">Продукты</span>
                    </nav>
                </div>

                <!-- Заголовок -->
                <div class="mt-6 md:mt-8 mb-8">
                    <h1 class="text-2xl md:text-3xl font-semibold text-foreground text-center md:text-left">
                        Наши продукты и услуги
                    </h1>
                    <p class="text-base md:text-lg text-muted-foreground mt-2 text-center md:text-left">
                        Выберите продукт или услугу, которая подходит именно вам
                    </p>
                </div>

                <!-- Загрузка -->
                <div v-if="loadingProducts || loadingServices" class="py-12 flex items-center justify-center">
                    <p class="text-muted-foreground">Загрузка...</p>
                </div>

                <!-- Ошибка -->
                <div v-if="error && !loadingProducts && !loadingServices" class="py-12">
                    <div class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
                        <p class="text-destructive">{{ error }}</p>
                    </div>
                </div>

                <!-- Контент -->
                <div v-if="!loadingProducts && !loadingServices">
                    <!-- Продукты -->
                    <div v-if="products.length > 0" class="mb-12">
                        <h2 class="text-xl md:text-2xl font-semibold text-foreground mb-6">Продуктовые направления</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-5">
                            <ProductCard
                                v-for="product in products"
                                :key="`product-${product.id}`"
                                :decision="product"
                                slug="products"
                                class="h-full"
                            />
                        </div>
                    </div>

                    <!-- Услуги -->
                    <div v-if="services.length > 0" class="mb-12">
                        <h2 class="text-xl md:text-2xl font-semibold text-foreground mb-6">Все услуги</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-5">
                            <ProductCard
                                v-for="service in services"
                                :key="`service-${service.id}`"
                                :decision="service"
                                slug="services"
                                class="h-full"
                            />
                        </div>
                    </div>

                    <!-- Пустое состояние -->
                    <div v-if="products.length === 0 && services.length === 0" class="py-12 text-center">
                        <p class="text-muted-foreground">Продукты и услуги пока не добавлены</p>
                    </div>

                    <!-- Форма обратной связи -->
                    <div class="mt-12 pb-12">
                        <FeedbackForm 
                            title="Остались вопросы?"
                            description="Напишите нам, и мы с удовольствием ответим на все ваши вопросы"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import ProductCard from '../components/public/ProductCard.vue';
import FeedbackForm from '../components/public/FeedbackForm.vue';
import SEOHead from '../components/SEOHead.vue';

export default {
    name: 'ProductsPage',
    components: {
        ProductCard,
        FeedbackForm,
        SEOHead,
    },
    setup() {
        const loadingProducts = ref(false);
        const loadingServices = ref(false);
        const error = ref(null);
        const products = ref([]);
        const services = ref([]);

        const fetchProducts = async () => {
            loadingProducts.value = true;
            error.value = null;
            try {
                const response = await fetch('/api/public/products?active=1', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                });
                
                if (response.ok) {
                    const data = await response.json();
                    products.value = data.data || [];
                } else {
                    throw new Error('Ошибка загрузки продуктов');
                }
            } catch (err) {
                console.error('Error fetching products:', err);
                error.value = err.message || 'Ошибка загрузки продуктов';
            } finally {
                loadingProducts.value = false;
            }
        };

        const fetchServices = async () => {
            loadingServices.value = true;
            try {
                const response = await fetch('/api/public/services?active=1&minimal=1', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                });
                
                if (response.ok) {
                    const data = await response.json();
                    const servicesList = data.data || [];
                    // Сортируем услуги по полю order (если есть)
                    services.value = servicesList.sort((a, b) => {
                        const orderA = a.order ?? 999999;
                        const orderB = b.order ?? 999999;
                        return orderA - orderB;
                    });
                }
            } catch (err) {
                console.error('Error fetching services:', err);
                // Не показываем ошибку для услуг, если продукты загрузились
                if (products.value.length === 0) {
                    error.value = err.message || 'Ошибка загрузки услуг';
                }
            } finally {
                loadingServices.value = false;
            }
        };

        onMounted(() => {
            // Загружаем параллельно
            Promise.all([
                fetchProducts(),
                fetchServices(),
            ]);
        });

        const canonicalUrl = computed(() => window.location.origin + '/products');
        const breadcrumbSchema = computed(() => ({
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',
            'itemListElement': [
                {
                    '@type': 'ListItem',
                    'position': 1,
                    'name': 'Главная',
                    'item': window.location.origin,
                },
                {
                    '@type': 'ListItem',
                    'position': 2,
                    'name': 'Продукты',
                    'item': window.location.origin + '/products',
                },
            ],
        }));

        return {
            loadingProducts,
            loadingServices,
            error,
            products,
            services,
            canonicalUrl,
            breadcrumbSchema,
        };
    },
};
</script>


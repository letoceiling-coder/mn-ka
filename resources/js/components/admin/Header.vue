<template>
    <header class="relative flex h-16 items-center justify-between border-b border-border bg-card backdrop-blur-xl px-4 sm:px-6 gap-2 sm:gap-4 z-30">
        <div class="flex items-center gap-2 sm:gap-3 min-w-0">
            <button class="lg:hidden flex-shrink-0 h-11 w-11 flex items-center justify-center rounded-md hover:bg-accent/10 transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <div class="hidden sm:flex items-center gap-2 text-sm min-w-0">
                <router-link
                    to="/admin"
                    class="text-muted-foreground hover:text-foreground transition-colors truncate"
                >
                    Панель управления
                </router-link>
                <template v-for="(crumb, index) in breadcrumbs" :key="index">
                    <span class="text-muted-foreground">/</span>
                    <router-link
                        v-if="crumb.path && index < breadcrumbs.length - 1"
                        :to="crumb.path"
                        class="text-muted-foreground hover:text-foreground transition-colors truncate"
                    >
                        {{ crumb.title }}
                    </router-link>
                    <span
                        v-else
                        class="font-semibold text-foreground truncate"
                    >
                        {{ crumb.title }}
                    </span>
                </template>
            </div>
            <div class="flex sm:hidden items-center text-sm min-w-0">
                <span class="font-semibold text-foreground truncate">{{ currentPageTitle }}</span>
            </div>
        </div>
        <div class="flex items-center gap-2 sm:gap-3">
            <div class="relative hidden md:block">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input
                    type="search"
                    placeholder="Поиск..."
                    class="w-48 lg:w-64 pl-9 bg-input border-border rounded-xl h-11 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                />
            </div>
            <button class="md:hidden h-11 w-11 flex items-center justify-center rounded-md hover:bg-accent/10 transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </button>
            <NotificationDropdown />
            <button
                @click="toggleTheme"
                class="h-11 w-11 flex items-center justify-center rounded-md hover:bg-accent/10 transition-colors"
                :title="isDarkMode ? 'Переключить на светлую тему' : 'Переключить на темную тему'"
            >
                <svg v-if="isDarkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                </svg>
            </button>
            <div class="hidden lg:flex items-center gap-2">
                <button class="flex items-center gap-2 h-11 px-6 bg-accent text-accent-foreground rounded-xl hover:bg-accent/90 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Сохранить
                </button>
                <button class="flex items-center gap-2 h-11 px-6 bg-accent text-accent-foreground rounded-xl hover:bg-accent/90 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Опубликовать
                </button>
            </div>
            <div class="relative lg:hidden">
                <button class="h-11 w-11 flex items-center justify-center bg-accent text-accent-foreground rounded-xl hover:bg-accent/90 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </button>
            </div>
            <div class="h-9 w-9 sm:h-10 sm:w-10 rounded-full bg-accent/20 border border-accent/30 flex items-center justify-center text-sm font-bold text-accent flex-shrink-0">
                {{ userInitials }}
            </div>
        </div>
    </header>
</template>

<script>
import { computed } from 'vue';
import { useStore } from 'vuex';
import { useRoute } from 'vue-router';
import NotificationDropdown from './NotificationDropdown.vue';

export default {
    name: 'Header',
    components: {
        NotificationDropdown,
    },
    setup() {
        const store = useStore();
        const route = useRoute();
        const user = computed(() => store.getters.user);
        const isDarkMode = computed(() => store.getters.isDarkMode);
        const userInitials = computed(() => {
            if (!user.value?.name) return 'U';
            const names = user.value.name.split(' ');
            return names.map(n => n[0]).join('').toUpperCase().substring(0, 2);
        });
        const currentPageTitle = computed(() => {
            return route.meta?.title || 'Панель управления';
        });

        // Маппинг названий для роутов
        const routeTitles = {
            'admin.dashboard': 'Главная',
            'admin.documentation': 'Документация',
            'admin.products': 'Продукты',
            'admin.categories': 'Категории',
            'admin.services': 'Услуги',
            'admin.cases': 'Кейсы',
            'admin.media': 'Медиа',
            'admin.users': 'Пользователи',
            'admin.roles': 'Роли',
            'admin.subscription': 'Подписка',
            'admin.versions': 'Версии',
            'admin.settings': 'Настройки',
            'admin.settings.telegram': 'Telegram',
            'admin.settings.contacts': 'Контакты',
            'admin.settings.about': 'О нас',
            'admin.settings.footer': 'Футер',
            'admin.settings.case-cards': 'Карточки кейсов',
            'admin.menus': 'Меню',
            'admin.banners.home': 'Баннер главной',
            'admin.notifications': 'Уведомления',
            'admin.decisions.chapters': 'Разделы',
            'admin.decisions.products': 'Продукты',
            'admin.decisions.services': 'Услуги',
            'admin.decisions.cases': 'Случаи',
            'admin.decisions.options': 'Опции',
            'admin.decisions.option-trees': 'Деревья опций',
            'admin.decisions.instances': 'Экземпляры',
            'admin.decisions.settings': 'Настройки',
            'admin.quizzes.index': 'Опросы',
            'admin.quizzes.settings': 'Настройки опросов',
            'admin.blocks.how-work': 'Как это работает',
            'admin.blocks.faq': 'FAQ',
            'admin.blocks.why-choose-us': 'Почему выбирают нас',
            'admin.blocks.cases': 'Кейсы',
            'admin.modal-settings': 'Настройки модальных окон',
            'admin.product-requests': 'Заявки на продукты',
            'admin.pages': 'Страницы',
            'admin.pages.home': 'Главная страница',
            'admin.seo-settings': 'SEO настройки',
        };

        // Маппинг секций для группировки
        const sectionTitles = {
            'decisions': 'Решения',
            'settings': 'Настройки',
            'blocks': 'Блоки',
            'quizzes': 'Опросы',
        };

        // Построение хлебных крошек
        const breadcrumbs = computed(() => {
            const crumbs = [];
            const routeName = route.name || '';
            const routePath = route.path;

            // Если это главная страница админки
            if (routeName === 'admin.dashboard' || routePath === '/admin') {
                return [];
            }

            // Разбираем имя роута на части
            const parts = routeName.split('.');
            
            // Пропускаем 'admin'
            if (parts[0] === 'admin') {
                parts.shift();
            }

            // Строим путь для каждой части
            let currentPath = '/admin';
            
            for (let i = 0; i < parts.length; i++) {
                const part = parts[i];
                const isLast = i === parts.length - 1;
                
                // Определяем название для части
                let title = '';
                let path = null;

                // Если это секция (decisions, settings, blocks, quizzes)
                if (sectionTitles[part]) {
                    title = sectionTitles[part];
                    currentPath += '/' + part;
                    path = currentPath;
                }
                // Если это конкретный роут
                else if (routeTitles[routeName]) {
                    // Для последнего элемента используем название роута
                    if (isLast) {
                        title = routeTitles[routeName];
                        // Для страниц создания/редактирования
                        if (part === 'create') {
                            title = 'Создать';
                            currentPath += '/create';
                            path = currentPath;
                        } else if (part === 'edit' && route.params.id) {
                            title = 'Редактировать';
                            // path остается null, так как это динамический роут
                        } else {
                            path = routePath;
                        }
                    } else {
                        // Для промежуточных элементов используем общее название
                        const routeKey = 'admin.' + parts.slice(0, i + 1).join('.');
                        title = routeTitles[routeKey] || part;
                        currentPath += '/' + part;
                        path = currentPath;
                    }
                }
                // Если название не найдено, используем часть как есть
                else {
                    if (part === 'create') {
                        title = 'Создать';
                        currentPath += '/create';
                        path = currentPath;
                    } else if (part === 'edit') {
                        title = 'Редактировать';
                        // path остается null
                    } else {
                        title = part.charAt(0).toUpperCase() + part.slice(1);
                        currentPath += '/' + part;
                        path = currentPath;
                    }
                }

                // Добавляем крошку только если есть название
                if (title) {
                    crumbs.push({
                        title,
                        path: isLast ? null : path,
                    });
                }
            }

            return crumbs;
        });

        const toggleTheme = () => {
            store.dispatch('toggleTheme');
        };

        return {
            user,
            userInitials,
            currentPageTitle,
            isDarkMode,
            toggleTheme,
            breadcrumbs,
        };
    },
};
</script>


<template>
    <footer class="bg-[#6C7B6D] mt-auto">
        <div class="w-full px-3 sm:px-4 md:px-5 pt-5 pb-5">
            <div class="w-full max-w-[1200px] mx-auto">
                <!-- Заголовок -->
                <div v-if="settings && settings.title" class="flex justify-center mb-5">
                    <h2 class="text-2xl md:text-3xl font-semibold text-white text-center">
                        {{ settings.title }}
                    </h2>
                </div>

                <!-- Контент футера -->
                <div v-if="settings" class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 mt-5">
                    <!-- Левая колонка: Контакты и соцсети -->
                    <div>
                        <!-- Отдел -->
                        <div v-if="settings.department_label || settings.department_phone" class="flex flex-col gap-[15px]">
                            <label class="text-base sm:text-lg text-white font-normal leading-[22px]">
                                {{ settings.department_label }}
                            </label>
                            <a 
                                v-if="settings.department_phone"
                                :href="`tel:${settings.department_phone}`"
                                class="text-xl sm:text-2xl md:text-2xl text-white font-semibold leading-[29px] hover:opacity-80 transition-opacity"
                            >
                                {{ settings.department_phone }}
                            </a>
                        </div>

                        <!-- Объекты -->
                        <div v-if="settings.objects_label || settings.objects_phone" class="flex flex-col gap-[15px] mt-4">
                            <label class="text-base sm:text-lg text-white font-normal leading-[22px]">
                                {{ settings.objects_label }}
                            </label>
                            <a 
                                v-if="settings.objects_phone"
                                :href="`tel:${settings.objects_phone}`"
                                class="text-xl sm:text-2xl md:text-2xl text-white font-semibold leading-[29px] hover:opacity-80 transition-opacity"
                            >
                                {{ settings.objects_phone }}
                            </a>
                        </div>

                        <!-- Вопросы -->
                        <div v-if="settings.issues_label || settings.issues_email" class="flex flex-col gap-[15px] mt-4">
                            <label class="text-base sm:text-lg text-white font-normal leading-[22px]">
                                {{ settings.issues_label }}
                            </label>
                            <a 
                                v-if="settings.issues_email"
                                :href="`mailto:${settings.issues_email}`"
                                class="text-xl sm:text-2xl md:text-2xl text-white font-semibold leading-[29px] hover:opacity-80 transition-opacity"
                            >
                                {{ settings.issues_email }}
                            </a>
                        </div>

                        <!-- Социальные сети -->
                        <div
                            v-if="hasSocialIcons"
                            class="flex gap-5 items-center mt-5 mb-3"
                        >
                            <!-- VK -->
                            <a 
                                v-if="settings.social_networks?.vk && (settings.vk_icon_svg || settings.vk_icon?.url)"
                                :href="settings.social_networks.vk"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="hover:opacity-80 transition-opacity flex items-center justify-center social-icon-link"
                            >
                                <img
                                    v-if="settings.vk_icon?.url"
                                    :src="settings.vk_icon.url"
                                    alt="VK"
                                    class="social-icon-img"
                                />
                                <div v-else-if="settings.vk_icon_svg" v-html="settings.vk_icon_svg" class="social-icon-svg"></div>
                            </a>

                            <!-- Instagram -->
                            <a 
                                v-if="settings.social_networks?.instagram && (settings.instagram_icon_svg || settings.instagram_icon?.url)"
                                :href="settings.social_networks.instagram"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="hover:opacity-80 transition-opacity flex items-center justify-center social-icon-link"
                            >
                                <img
                                    v-if="settings.instagram_icon?.url"
                                    :src="settings.instagram_icon.url"
                                    alt="Instagram"
                                    class="social-icon-img"
                                />
                                <div v-else-if="settings.instagram_icon_svg" v-html="settings.instagram_icon_svg" class="social-icon-svg"></div>
                            </a>

                            <!-- Telegram -->
                            <a 
                                v-if="settings.social_networks?.telegram && (settings.telegram_icon_svg || settings.telegram_icon?.url)"
                                :href="settings.social_networks.telegram"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="hover:opacity-80 transition-opacity flex items-center justify-center social-icon-link"
                            >
                                <img
                                    v-if="settings.telegram_icon?.url"
                                    :src="settings.telegram_icon.url"
                                    alt="Telegram"
                                    class="social-icon-img"
                                />
                                <div v-else-if="settings.telegram_icon_svg" v-html="settings.telegram_icon_svg" class="social-icon-svg"></div>
                            </a>
                        </div>
                    </div>

                    <!-- Правая колонка: Меню -->
                    <div v-if="menuItems && menuItems.length > 0" class="w-full">
                        <div class="grid grid-cols-1 md:grid-cols-2">
                            <router-link
                                v-for="(menuItem, index) in menuItems"
                                :key="index"
                                :to="menuItem.slug || menuItem.url || '#'"
                                class="footer-menu-item"
                            >
                                {{ menuItem.title || menuItem.name }}
                            </router-link>
                        </div>
                    </div>
                </div>

                <!-- Разделитель -->
                <div class="border-t border-white/20 mt-3"></div>

                <!-- Политика конфиденциальности и копирайт -->
                <div class="mt-3 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- Политика конфиденциальности -->
                    <div class="flex justify-end sm:justify-start w-full sm:w-auto">
                        <router-link
                            v-if="settings && settings.privacy_policy_link"
                            :to="settings.privacy_policy_link"
                            class="text-white hover:opacity-80 transition-opacity text-sm sm:text-base"
                        >
                            Политика конфиденциальности
                        </router-link>
                    </div>

                    <!-- Копирайт -->
                    <div v-if="settings && settings.copyright" class="flex justify-center sm:justify-end text-white text-sm sm:text-base">
                        {{ settings.copyright }}
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>

<script>
import { ref, computed, onMounted } from 'vue';

export default {
    name: 'Footer',
    setup() {
        const settings = ref(null);
        const menuItems = ref([]);
        const loading = ref(true);

        const hasSocialIcons = computed(() => {
            if (!settings.value || !settings.value.social_networks) return false;
            const socials = settings.value.social_networks;
            const data = settings.value;
            return !!(
                (socials.vk && (data.vk_icon_svg || (data.vk_icon && data.vk_icon.url))) ||
                (socials.instagram && (data.instagram_icon_svg || (data.instagram_icon && data.instagram_icon.url))) ||
                (socials.telegram && (data.telegram_icon_svg || (data.telegram_icon && data.telegram_icon.url)))
            );
        });

        const fetchSettings = async () => {
            try {
                const response = await fetch('/api/public/footer/settings');
                if (response.ok) {
                    const data = await response.json();
                    if (data.data) {
                        settings.value = data.data;
                    }
                }
            } catch (error) {
                console.error('Error fetching footer settings:', error);
            } finally {
                loading.value = false;
            }
        };

        const fetchMenu = async () => {
            try {
                const response = await fetch('/api/public/menus/footer');
                if (response.ok) {
                    const data = await response.json();
                    if (data.data && Array.isArray(data.data)) {
                        // Преобразуем дерево в плоский массив
                        const flattenMenu = (items) => {
                            let result = [];
                            items.forEach(item => {
                                result.push(item);
                                if (item.children && item.children.length > 0) {
                                    result = result.concat(flattenMenu(item.children));
                                }
                            });
                            return result;
                        };
                        menuItems.value = flattenMenu(data.data);
                    }
                }
            } catch (error) {
                console.error('Error fetching footer menu:', error);
            }
        };

        onMounted(() => {
            fetchSettings();
            fetchMenu();
        });

        return {
            settings,
            menuItems,
            loading,
            hasSocialIcons,
        };
    },
};
</script>

<style scoped>
/* Адаптивные стили для социальных сетей */
@media only screen and (max-width: 767px) {
    svg {
        width: 33px;
        height: 22px;
    }
    
    .flex.justify-end {
        justify-content: center !important;
    }
}

/* Стили для ссылок */
a {
    color: #FFFFFF;
}

/* Адаптивные размеры текста */
@media only screen and (max-width: 767px) {
    .text-xl,
    .text-2xl {
        font-size: 1.125rem; /* 18px */
    }
}

/* Стили для меню футера - как на фото */
.footer-menu-item {
    color: #FFFFFF;
    text-decoration: none;
    font-size: 1rem;
    line-height: 1;
    padding: 0;
    margin: 0;
    display: block;
    transition: opacity 0.3s;
    margin-bottom: 0.75rem;
}

.footer-menu-item:hover {
    opacity: 0.8;
}

@media (min-width: 768px) {
    .footer-menu-item {
        font-size: 1.125rem;
    }
}

/* Стили для иконок соцсетей */
.social-icon-link {
    width: 60px;
    height: 60px;
    flex-shrink: 0;
}

.social-icon-svg {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.social-icon-svg svg {
    width: 100%;
    height: 100%;
    max-width: 60px;
    max-height: 60px;
    object-fit: contain;
}

.social-icon-img {
    max-width: 60px;
    max-height: 60px;
    width: auto;
    height: auto;
    object-fit: contain;
}

</style>

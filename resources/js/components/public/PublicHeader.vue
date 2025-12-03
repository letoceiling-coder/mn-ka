<template>
    <div class="w-full px-3 sm:px-4 md:px-5">
        <header class="rounded-lg h-[60px] flex items-center px-4 sm:px-5 gap-3 sm:gap-5 bg-[#D2E8BE] mt-3 w-full max-w-[1200px] mx-auto shadow-sm">
            <!-- Logo -->
            <div class="flex items-center mr-3 sm:mr-5 flex-shrink-0">
                <router-link to="/" class="font-semibold text-base sm:text-lg text-black no-underline">
                    mnka
                </router-link>
            </div>
            
            <!-- Burger Menu Button -->
            <button 
                type="button" 
                @click="toggleBurger"
                class="flex items-center gap-2 sm:gap-[10px] ml-auto md:ml-5 cursor-pointer bg-transparent border-0 p-2 sm:p-0 -mr-2 sm:mr-0 flex-shrink-0"
            >
                <svg width="25" height="17" viewBox="0 0 25 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <line x1="1.05" y1="1.95" x2="23.95" y2="1.95" stroke="black" stroke-width="2.1" stroke-linecap="round"/>
                    <line x1="1.05" y1="8.95" x2="23.95" y2="8.95" stroke="black" stroke-width="2.1" stroke-linecap="round"/>
                    <line x1="1.05" y1="15.9501" x2="23.95" y2="15.9501" stroke="black" stroke-width="2.1" stroke-linecap="round"/>
                </svg>
                <span class="font-medium text-base leading-5 text-black hidden md:block">Меню</span>
            </button>

            <!-- Middle Section (Desktop) -->
            <div class="hidden md:flex bg-[#C9E1B5] rounded-lg flex-1 items-center px-[15px] gap-3 sm:gap-5 min-w-0">
                <!-- Menu List / Select -->
                <div class="flex-1 min-w-0 relative" ref="menuContainer">
                    <ul 
                        v-if="showMenuList"
                        ref="menuList"
                        class="menu-list flex items-center gap-3 sm:gap-5 list-none m-0 p-0 flex-wrap h-[50px] relative"
                    >
                        <!-- Анимированный фон -->
                        <div 
                            ref="menuBackground"
                            class="menu-background"
                            :style="backgroundStyle"
                        ></div>
                        
                        <li 
                            v-for="(item, index) in visibleMenuItems" 
                            :key="item.id || item.slug || index"
                            class="menu-item-wrapper font-medium text-xs leading-[15px] list-none m-0 p-0 relative z-10"
                        >
                            <router-link 
                                :to="item.url || item.slug || '#'" 
                                class="menu-link text-black no-underline whitespace-nowrap"
                                @mouseenter="handleLinkHover($event)"
                                @mouseleave="handleLinkLeave"
                            >
                                {{ item.title || item.name }}
                            </router-link>
                        </li>
                    </ul>
                    
                    <!-- Select для меню если не помещается -->
                    <select
                        v-if="showMenuSelect"
                        v-model="selectedMenuItem"
                        @change="navigateToMenu"
                        class="font-medium text-xs leading-[15px] text-black bg-transparent border-0 outline-none cursor-pointer w-full"
                    >
                        <option value="">Выберите...</option>
                        <option 
                            v-for="item in hiddenMenuItems" 
                            :key="item.id || item.slug"
                            :value="item.url || item.slug"
                        >
                            {{ item.title || item.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Search Button (Desktop) - иконка вместо поля на средних экранах -->
            <div class="hidden md:flex items-center ml-auto flex-shrink-0">
                <!-- Иконка поиска для средних экранов -->
                <button
                    v-if="showSearchIcon"
                    @click="showSearchModal = true"
                    type="button"
                    class="p-2 hover:bg-white/50 rounded-lg transition-colors"
                    aria-label="Поиск"
                >
                    <svg 
                        width="20" 
                        height="20"
                        viewBox="0 0 20 20" 
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        class="text-black"
                    >
                        <path 
                            fill-rule="evenodd" 
                            clip-rule="evenodd"
                            d="M12.5885 13.8064C11.0409 15.0431 9.07854 15.6401 7.10447 15.475C5.13041 15.3098 3.2945 14.3951 1.97381 12.9185C0.653118 11.4419 -0.0520973 9.5157 0.00300163 7.53539C0.0581006 5.55508 0.869331 3.67104 2.27008 2.27021C3.67083 0.869381 5.55476 0.0581039 7.53496 0.00300181C9.51515 -0.0521003 11.4413 0.653156 12.9178 1.97392C14.3942 3.29469 15.309 5.1307 15.4741 7.10488C15.6392 9.07906 15.0422 11.0415 13.8057 12.5893L19.7259 18.5087C19.8105 18.5875 19.8784 18.6826 19.9254 18.7883C19.9725 18.8939 19.9978 19.0079 19.9999 19.1236C20.0019 19.2392 19.9806 19.3541 19.9373 19.4613C19.894 19.5686 19.8295 19.666 19.7478 19.7478C19.666 19.8295 19.5686 19.894 19.4614 19.9373C19.3541 19.9806 19.2393 20.0019 19.1236 19.9999C19.008 19.9978 18.894 19.9725 18.7883 19.9254C18.6827 19.8784 18.5876 19.8105 18.5088 19.7259L12.5885 13.8064ZM3.48769 12.0128C2.64494 11.1699 2.07095 10.0961 1.83828 8.92709C1.6056 7.75806 1.72467 6.54629 2.18045 5.44492C2.63622 4.34355 3.40824 3.40202 4.39894 2.73932C5.38963 2.07661 6.55454 1.72248 7.74643 1.72168C8.93832 1.72088 10.1037 2.07344 11.0953 2.73481C12.0869 3.39618 12.8602 4.33667 13.3174 5.43743C13.7747 6.53818 13.8954 7.74979 13.6643 8.91913C13.4332 10.0885 12.8606 11.1631 12.019 12.0071L12.0133 12.0128L12.0075 12.0174C10.8766 13.1457 9.34413 13.779 7.74666 13.7782C6.14918 13.7773 4.61737 13.1424 3.48769 12.0128Z"
                            fill="currentColor"
                        />
                    </svg>
                </button>
                
                <!-- Полное поле поиска для больших экранов -->
                <div v-if="showSearchInput" class="relative flex items-center h-[38px] bg-white rounded-lg min-w-[250px] px-[15px] pr-10">
                    <input 
                        type="text" 
                        v-model="searchQuery"
                        @keypress.enter="handleSearch"
                        placeholder="Поиск по сайту" 
                        class="outline-none border-0 w-full p-0 bg-transparent text-sm text-black placeholder:text-[#999]"
                    >
                    <button
                        @click="handleSearch"
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer"
                    >
                        <svg 
                            width="20" 
                            height="20"
                            viewBox="0 0 20 20" 
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path 
                                fill-rule="evenodd" 
                                clip-rule="evenodd"
                                d="M12.5885 13.8064C11.0409 15.0431 9.07854 15.6401 7.10447 15.475C5.13041 15.3098 3.2945 14.3951 1.97381 12.9185C0.653118 11.4419 -0.0520973 9.5157 0.00300163 7.53539C0.0581006 5.55508 0.869331 3.67104 2.27008 2.27021C3.67083 0.869381 5.55476 0.0581039 7.53496 0.00300181C9.51515 -0.0521003 11.4413 0.653156 12.9178 1.97392C14.3942 3.29469 15.309 5.1307 15.4741 7.10488C15.6392 9.07906 15.0422 11.0415 13.8057 12.5893L19.7259 18.5087C19.8105 18.5875 19.8784 18.6826 19.9254 18.7883C19.9725 18.8939 19.9978 19.0079 19.9999 19.1236C20.0019 19.2392 19.9806 19.3541 19.9373 19.4613C19.894 19.5686 19.8295 19.666 19.7478 19.7478C19.666 19.8295 19.5686 19.894 19.4614 19.9373C19.3541 19.9806 19.2393 20.0019 19.1236 19.9999C19.008 19.9978 18.894 19.9725 18.7883 19.9254C18.6827 19.8784 18.5876 19.8105 18.5088 19.7259L12.5885 13.8064ZM3.48769 12.0128C2.64494 11.1699 2.07095 10.0961 1.83828 8.92709C1.6056 7.75806 1.72467 6.54629 2.18045 5.44492C2.63622 4.34355 3.40824 3.40202 4.39894 2.73932C5.38963 2.07661 6.55454 1.72248 7.74643 1.72168C8.93832 1.72088 10.1037 2.07344 11.0953 2.73481C12.0869 3.39618 12.8602 4.33667 13.3174 5.43743C13.7747 6.53818 13.8954 7.74979 13.6643 8.91913C13.4332 10.0885 12.8606 11.1631 12.019 12.0071L12.0133 12.0128L12.0075 12.0174C10.8766 13.1457 9.34413 13.779 7.74666 13.7782C6.14918 13.7773 4.61737 13.1424 3.48769 12.0128Z"
                                fill="black"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </header>
    </div>

    <!-- Burger Menu -->
    <BurgerMenu 
        v-if="burgerMenuOpen"
        :is-open="burgerMenuOpen"
        @close="toggleBurger"
    />

    <!-- Search Modal -->
    <SearchModal
        v-if="showSearchModal"
        @close="showSearchModal = false"
        @search="handleSearch"
    />
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import BurgerMenu from './BurgerMenu.vue';
import SearchModal from './SearchModal.vue';

export default {
    name: 'PublicHeader',
    components: {
        BurgerMenu,
        SearchModal,
    },
    setup() {
        const router = useRouter();
        const route = useRoute();
        const burgerMenuOpen = ref(false);
        const showSearchModal = ref(false);
        const searchQuery = ref('');
        const menuItems = ref([]);
        const menuContainer = ref(null);
        const menuList = ref(null);
        const menuBackground = ref(null);
        const selectedMenuItem = ref('');
        const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1920);
        const backgroundStyle = ref({
            left: '0px',
            width: '0px',
            opacity: '0',
        });
        const hoveredLink = ref(null);
        const activeLink = ref(null);

        // Определяем что показывать для поиска
        const showSearchInput = computed(() => windowWidth.value >= 1024);
        const showSearchIcon = computed(() => windowWidth.value >= 768 && windowWidth.value < 1024);

        // Загрузка меню из API
        const fetchMenu = async () => {
            try {
                const response = await fetch('/api/public/menus/header');
                if (response.ok) {
                    const data = await response.json();
                    menuItems.value = data.data || [];
                } else {
                    menuItems.value = [
                        { title: 'Продуктовые направления', url: '/products', slug: '/products' },
                        { title: 'Кейсы', url: '/cases', slug: '/cases' },
                        { title: 'О нас', url: '/about', slug: '/about' },
                        { title: 'Контакты', url: '/contact', slug: '/contact' }
                    ];
                }
            } catch (error) {
                console.error('Error fetching menu:', error);
                menuItems.value = [
                    { title: 'Продуктовые направления', url: '/products', slug: '/products' },
                    { title: 'Кейсы', url: '/cases', slug: '/cases' },
                    { title: 'О нас', url: '/about', slug: '/about' },
                    { title: 'Контакты', url: '/contact', slug: '/contact' }
                ];
            }
        };

        // Определяем, сколько пунктов меню помещается
        const visibleMenuItems = ref([]);
        const hiddenMenuItems = ref([]);
        const showMenuSelect = ref(false);
        const showMenuList = ref(true);

        // Проверка, сколько пунктов меню помещается в контейнер
        const calculateVisibleMenuItems = async () => {
            if (!menuContainer.value || menuItems.value.length === 0) {
                visibleMenuItems.value = menuItems.value;
                hiddenMenuItems.value = [];
                showMenuSelect.value = false;
                showMenuList.value = true;
                return;
            }

            await nextTick();
            
            const tempContainer = document.createElement('ul');
            tempContainer.className = 'flex items-center gap-3 sm:gap-5 list-none m-0 p-0 flex-wrap';
            tempContainer.style.position = 'absolute';
            tempContainer.style.top = '-9999px';
            tempContainer.style.opacity = '0';
            tempContainer.style.pointerEvents = 'none';
            document.body.appendChild(tempContainer);

            const containerWidth = menuContainer.value.offsetWidth - 50;
            let visibleCount = menuItems.value.length;

            for (let i = 0; i < menuItems.value.length; i++) {
                const item = menuItems.value[i];
                const li = document.createElement('li');
                li.className = 'font-medium text-xs leading-[15px] list-none m-0 p-0';
                const a = document.createElement('a');
                a.className = 'text-black pb-[3px] border-b border-transparent transition-all duration-300 no-underline whitespace-nowrap';
                a.textContent = item.title || item.name;
                li.appendChild(a);
                tempContainer.appendChild(li);

                if (tempContainer.scrollWidth > containerWidth) {
                    visibleCount = i;
                    break;
                }
            }

            document.body.removeChild(tempContainer);

            if (visibleCount >= menuItems.value.length) {
                visibleMenuItems.value = menuItems.value;
                hiddenMenuItems.value = [];
                showMenuSelect.value = false;
                showMenuList.value = true;
            } else if (visibleCount > 0) {
                visibleMenuItems.value = menuItems.value.slice(0, visibleCount);
                hiddenMenuItems.value = menuItems.value.slice(visibleCount);
                showMenuSelect.value = hiddenMenuItems.value.length > 0;
                showMenuList.value = visibleMenuItems.value.length > 0;
            } else {
                visibleMenuItems.value = [];
                hiddenMenuItems.value = menuItems.value;
                showMenuSelect.value = true;
                showMenuList.value = false;
            }
        };

        const toggleBurger = () => {
            burgerMenuOpen.value = !burgerMenuOpen.value;
            if (burgerMenuOpen.value) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        };

        const handleSearch = () => {
            if (searchQuery.value.trim()) {
                console.log('Search:', searchQuery.value);
            }
            showSearchModal.value = false;
        };

        const navigateToMenu = () => {
            if (selectedMenuItem.value) {
                router.push(selectedMenuItem.value);
                selectedMenuItem.value = '';
            }
        };

        const updateWindowWidth = () => {
            windowWidth.value = window.innerWidth;
        };

        // Обновление позиции фона
        const updateBackgroundPosition = (linkElement) => {
            if (!linkElement || !menuList.value || !menuBackground.value) {
                return;
            }

            const linkRect = linkElement.getBoundingClientRect();
            const listRect = menuList.value.getBoundingClientRect();
            
            if (!listRect || !linkRect) return;
            
            const left = linkRect.left - listRect.left;
            const width = linkRect.width;
            
            backgroundStyle.value = {
                left: `${left}px`,
                width: `${width}px`,
                opacity: '1',
            };
        };

        // Поиск активной ссылки
        const findActiveLink = () => {
            if (!menuList.value) return null;
            
            const links = menuList.value.querySelectorAll('.menu-link');
            for (let link of links) {
                if (link.classList.contains('router-link-active') || link.classList.contains('router-link-exact-active')) {
                    return link;
                }
            }
            return null;
        };

        // Инициализация активной ссылки
        const initActiveLink = () => {
            setTimeout(() => {
                const link = findActiveLink();
                if (link) {
                    activeLink.value = link;
                    if (!hoveredLink.value) {
                        updateBackgroundPosition(link);
                    }
                }
            }, 150);
        };

        // Обработка наведения на ссылку
        const handleLinkHover = (event) => {
            const link = event.currentTarget;
            if (!link) return;
            
            hoveredLink.value = link;
            updateBackgroundPosition(link);
        };

        // Обработка ухода курсора со ссылки
        const handleLinkLeave = () => {
            hoveredLink.value = null;
            if (activeLink.value) {
                updateBackgroundPosition(activeLink.value);
            } else {
                backgroundStyle.value = {
                    ...backgroundStyle.value,
                    opacity: '0',
                };
            }
        };

        // Обновление активной ссылки при изменении роута
        const updateActiveLink = () => {
            setTimeout(() => {
                const link = findActiveLink();
                if (link) {
                    activeLink.value = link;
                    if (!hoveredLink.value) {
                        updateBackgroundPosition(link);
                    }
                } else {
                    activeLink.value = null;
                    if (!hoveredLink.value) {
                        backgroundStyle.value = {
                            ...backgroundStyle.value,
                            opacity: '0',
                        };
                    }
                }
            }, 100);
        };

        onMounted(async () => {
            await fetchMenu();
            window.addEventListener('resize', updateWindowWidth);
            window.addEventListener('resize', calculateVisibleMenuItems);
            await nextTick();
            updateWindowWidth();
            await calculateVisibleMenuItems();
            initActiveLink();
        });

        onUnmounted(() => {
            window.removeEventListener('resize', updateWindowWidth);
            document.body.style.overflow = '';
        });

        watch(menuItems, async () => {
            await nextTick();
            await calculateVisibleMenuItems();
            initActiveLink();
        });

        watch(() => route.path, () => {
            updateActiveLink();
        });

        watch(windowWidth, async () => {
            await nextTick();
            await calculateVisibleMenuItems();
            if (hoveredLink.value) {
                updateBackgroundPosition(hoveredLink.value);
            } else if (activeLink.value) {
                updateBackgroundPosition(activeLink.value);
            }
        });

        return {
            burgerMenuOpen,
            showSearchModal,
            searchQuery,
            menuItems,
            menuContainer,
            menuList,
            menuBackground,
            selectedMenuItem,
            showSearchInput,
            showSearchIcon,
            visibleMenuItems,
            hiddenMenuItems,
            showMenuList,
            showMenuSelect,
            backgroundStyle,
            handleLinkHover,
            handleLinkLeave,
            toggleBurger,
            handleSearch,
            navigateToMenu,
        };
    },
};
</script>

<style scoped>
/* Кастомные стили для select меню */
select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L6 6L11 1' stroke='black' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right center;
    padding-right: 20px;
}

/* Анимированный фон меню */
.menu-background {
    position: absolute;
    top: 2.5px;
    height: 45px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(65, 132, 144, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
    transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
    pointer-events: none;
    z-index: 1;
}

/* Стили для списка меню */
.menu-list {
    position: relative;
}

/* Стили для обертки пункта меню */
.menu-item-wrapper {
    position: relative;
    z-index: 2;
}

/* Стили для ссылок меню */
.menu-link {
    display: inline-flex;
    align-items: center;
    padding: 0 12px;
    min-height: 45px;
    font-size: 12px;
    line-height: 15px;
    position: relative;
    z-index: 2;
    border-bottom: 1px solid transparent;
    transition: border-color 0.3s;
}

.menu-link:hover {
    border-bottom-color: transparent;
}
</style>

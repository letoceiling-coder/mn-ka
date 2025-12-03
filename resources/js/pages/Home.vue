<template>
    <div class="home-page">
        <component
            v-for="(block, index) in orderedBlocks"
            :key="block.key"
            :is="block.component"
            v-bind="block.props"
        />
    </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import HeroBanner from '../components/public/HeroBanner.vue';
import HowWork from '../components/public/HowWork.vue';
import Decisions from '../components/public/Decisions.vue';
import Quiz from '../components/public/Quiz.vue';
import Faq from '../components/public/Faq.vue';
import WhyChooseUs from '../components/public/WhyChooseUs.vue';
import CasesBlock from '../components/public/CasesBlock.vue';
import FeedbackForm from '../components/public/FeedbackForm.vue';

// Маппинг компонентов
const componentMap = {
    'HeroBanner': HeroBanner,
    'HowWork': HowWork,
    'Decisions': Decisions,
    'Quiz': Quiz,
    'Faq': Faq,
    'WhyChooseUs': WhyChooseUs,
    'CasesBlock': CasesBlock,
    'FeedbackForm': FeedbackForm,
};

export default {
    name: 'Home',
    setup() {
        const blocks = ref([]);
        const loading = ref(true);

        const orderedBlocks = computed(() => {
            return blocks.value
                .map(block => {
                    const component = componentMap[block.component];
                    if (!component) {
                        console.warn(`Component ${block.component} not found`);
                        return null;
                    }
                    // Для FeedbackForm передаем title и description как props
                    const props = block.settings || {};
                    return {
                        key: block.key,
                        component,
                        props,
                    };
                })
                .filter(block => block !== null);
        });

        const fetchBlocks = async () => {
            try {
                const response = await fetch('/api/public/home-page-blocks');
                if (response.ok) {
                    const data = await response.json();
                    if (data.data) {
                        blocks.value = data.data;
                    }
                }
            } catch (error) {
                console.error('Error fetching home page blocks:', error);
                // Fallback к дефолтному порядку при ошибке
                blocks.value = [
                    { key: 'hero_banner', component: 'HeroBanner', settings: {} },
                    { key: 'decisions', component: 'Decisions', settings: {} },
                    { key: 'quiz', component: 'Quiz', settings: {} },
                    { key: 'how_work', component: 'HowWork', settings: {} },
                    { key: 'faq', component: 'Faq', settings: {} },
                    { key: 'why_choose_us', component: 'WhyChooseUs', settings: {} },
                    { key: 'cases_block', component: 'CasesBlock', settings: {} },
                    { key: 'feedback_form', component: 'FeedbackForm', settings: { title: 'Остались вопросы?', description: 'Напишите нам, и мы с удовольствием ответим на все ваши вопросы' } },
                ];
            } finally {
                loading.value = false;
            }
        };

        onMounted(() => {
            fetchBlocks();
        });

        return {
            blocks,
            loading,
            orderedBlocks,
        };
    },
};
</script>


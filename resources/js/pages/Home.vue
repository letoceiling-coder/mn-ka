<template>
    <div class="home-page">
        <HeroBanner />
        <Decisions />
        <Quiz />
        <HowWork />
        <Faq />
        <WhyChooseUs />
        <FeedbackForm
            title="Остались вопросы?"
            description="Напишите нам, и мы с удовольствием ответим на все ваши вопросы"
        />
    </div>
</template>

<script>
import HeroBanner from '../components/public/HeroBanner.vue';
import HowWork from '../components/public/HowWork.vue';
import Decisions from '../components/public/Decisions.vue';
import Quiz from '../components/public/Quiz.vue';
import Faq from '../components/public/Faq.vue';
import WhyChooseUs from '../components/public/WhyChooseUs.vue';
import FeedbackForm from '../components/public/FeedbackForm.vue';

export default {
    name: 'Home',
    components: {
        HeroBanner,
        HowWork,
        Decisions,
        Quiz,
        Faq,
        WhyChooseUs,
        FeedbackForm,
    },
};
</script>


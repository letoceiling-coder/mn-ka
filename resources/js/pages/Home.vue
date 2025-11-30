<template>
    <div class="home-page">
        <HeroBanner />
        <Decisions />
        <Quiz />
        <HowWork />
    </div>
</template>

<script>
import HeroBanner from '../components/public/HeroBanner.vue';
import HowWork from '../components/public/HowWork.vue';
import Decisions from '../components/public/Decisions.vue';
import Quiz from '../components/public/Quiz.vue';

export default {
    name: 'Home',
    components: {
        HeroBanner,
        HowWork,
        Decisions,
        Quiz,
    },
};
</script>


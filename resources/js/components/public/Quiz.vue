<template>
    <div v-if="isActive" class="w-full px-3 sm:px-4 md:px-5 mt-8 sm:mt-12 mb-8 sm:mb-12">
        <div class="w-full max-w-[1200px] mx-auto">
            <!-- Заголовок -->
            <div class="flex flex-col items-center mb-6 sm:mb-8">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-semibold text-gray-900 text-center mb-3">
                    {{ quizData?.title || title }}
                </h2>
                <p v-if="quizData?.description || description" class="text-gray-600 text-center text-base sm:text-lg">
                    {{ quizData?.description || description }}
                </p>
            </div>

            <!-- Загрузка -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="text-gray-500">Загрузка квиза...</div>
            </div>

            <!-- Ошибка -->
            <div v-else-if="error" class="flex justify-center items-center py-12">
                <div class="text-red-500">{{ error }}</div>
            </div>

            <!-- Компонент текущего вопроса -->
            <Transition
                v-else-if="currentQuestion"
                name="quiz-fade"
                mode="out-in"
            >
                <component
                    :is="currentQuestion.question_type"
                    :key="currentQuestion.id"
                    :data="formatQuestionData(currentQuestion)"
                    :number-question="currentQuestion.id"
                    @next="goToNext"
                    @answer="handleAnswer"
                    @submit="handleSubmit"
                />
            </Transition>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import ImagesCollect from './quiz/ImagesCollect.vue';
import Selects from './quiz/Selects.vue';
import Inputs from './quiz/Inputs.vue';
import Forms from './quiz/Forms.vue';
import Thank from './quiz/Thank.vue';

export default {
    name: 'Quiz',
    components: {
        'images-collect': ImagesCollect,
        'selects': Selects,
        'inputs': Inputs,
        'forms': Forms,
        'thank': Thank,
    },
    props: {
        title: {
            type: String,
            default: 'Подберите идеальное решение'
        },
        description: {
            type: String,
            default: 'Ответьте на несколько вопросов, и мы предложим оптимальный вариант'
        },
    },
    setup() {
        const loading = ref(true);
        const error = ref(null);
        const quizData = ref(null);
        const questions = ref([]);
        const currentQuestionNumber = ref(1);
        const isActive = ref(false);
        const answers = ref({}); // Хранилище всех ответов
        const isCompleted = ref(false);
        const isSubmitting = ref(false);

        // Загрузка настроек блока квиза
        const loadSettings = async () => {
            try {
                const response = await fetch('/api/public/quiz-block/settings');
                if (!response.ok) {
                    throw new Error('Ошибка загрузки настроек');
                }
                const result = await response.json();
                if (result.data) {
                    isActive.value = result.data.is_active === true;
                    if (result.data.quiz_id) {
                        await loadQuiz(result.data.quiz_id);
                    } else {
                        loading.value = false;
                    }
                } else {
                    loading.value = false;
                }
            } catch (err) {
                console.error('Ошибка загрузки настроек блока квиза:', err);
                loading.value = false;
            }
        };

        // Загрузка данных квиза
        const loadQuiz = async (quizId) => {
            loading.value = true;
            error.value = null;
            try {
                const response = await fetch(`/api/public/quiz-block/quiz/${quizId}`);
                if (!response.ok) {
                    throw new Error('Ошибка загрузки квиза');
                }
                const result = await response.json();
                if (result.data) {
                    quizData.value = {
                        id: result.data.id,
                        title: result.data.title,
                        description: result.data.description,
                    };
                    questions.value = (result.data.questions || [])
                        .filter(q => q.is_active !== false)
                        .sort((a, b) => (a.order || 0) - (b.order || 0))
                        .map((q, index) => ({
                            ...q,
                            displayId: index + 1, // Добавляем displayId для отображения
                        }));
                    
                    // Начинаем с первого вопроса (используем displayId)
                    if (questions.value.length > 0) {
                        currentQuestionNumber.value = questions.value[0].displayId;
                    }
                }
            } catch (err) {
                error.value = err.message || 'Ошибка загрузки квиза';
                console.error('Ошибка загрузки квиза:', err);
            } finally {
                loading.value = false;
            }
        };

        // Преобразование данных вопроса из БД в формат компонентов
        const formatQuestionData = (question) => {
            const data = {
                id: question.displayId || question.id || question.order,
                question: question.question_text || '',
                type: question.question_type,
                ...(question.question_data || {}),
            };

            // Если тип images-collect или selects, преобразуем question_data.selects
            if ((question.question_type === 'images-collect' || question.question_type === 'selects') && question.question_data?.selects) {
                data.selects = question.question_data.selects.map((select, index) => ({
                    id: select.id || index + 1,
                    name: select.name || '',
                    src: select.src || '',
                    title: select.title || select.name || '',
                    child: select.child || null,
                }));
            }

            // Если тип inputs, добавляем поля из question_data
            if (question.question_type === 'inputs' && question.question_data) {
                data.label = question.question_data.label || '';
                data.placeholder = question.question_data.placeholder || '';
                data.child = question.question_data.child || null;
                data.answer = '';
            }

            // Если тип forms, добавляем форму
            if (question.question_type === 'forms' && question.question_data) {
                data.form = question.question_data.form || {
                    name: '',
                    email: '',
                    phone: '',
                    check: false,
                };
                data.child = question.question_data.child || null;
                data.question = question.question_text || question.question_data.question || '';
            }

            return data;
        };

        const currentQuestion = computed(() => {
            return questions.value.find(q => q.displayId === currentQuestionNumber.value);
        });

        const goToNext = (nextId) => {
            if (nextId) {
                // Если указан nextId (child из question_data), ищем вопрос с таким order
                // nextId обычно указывает на порядковый номер следующего вопроса
                const currentIndex = questions.value.findIndex(q => q.displayId === currentQuestionNumber.value);
                
                // Если nextId - это номер следующего вопроса (1-based)
                if (nextId > 0 && nextId <= questions.value.length) {
                    currentQuestionNumber.value = nextId;
                } else {
                    // Если не найден по ID, переходим к следующему по порядку
                    if (currentIndex < questions.value.length - 1) {
                        currentQuestionNumber.value = questions.value[currentIndex + 1].displayId;
                    }
                }
            } else {
                // Если nextId не указан, переходим к следующему по порядку
                const currentIndex = questions.value.findIndex(q => q.displayId === currentQuestionNumber.value);
                if (currentIndex < questions.value.length - 1) {
                    currentQuestionNumber.value = questions.value[currentIndex + 1].displayId;
                }
            }
        };

        const handleAnswer = (answer) => {
            // Сохраняем ответ с привязкой к текущему вопросу
            const currentQ = questions.value.find(q => q.displayId === currentQuestionNumber.value);
            if (currentQ) {
                answers.value[currentQ.id] = {
                    question_id: currentQ.id,
                    question_text: currentQ.question_text,
                    question_type: currentQ.question_type,
                    answer: answer,
                };
            }
        };

        // Отправка результатов квиза на сервер
        const submitQuiz = async (contactInfo) => {
            if (isSubmitting.value || isCompleted.value) return;

            isSubmitting.value = true;
            try {
                // Формируем массив ответов в порядке вопросов
                const answersArray = questions.value
                    .filter(q => answers.value[q.id])
                    .map(q => answers.value[q.id].answer);

                const response = await fetch('/api/public/quiz/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        quiz_id: quizData.value.id,
                        answers: answersArray,
                        contact: contactInfo,
                    }),
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    isCompleted.value = true;
                    return { success: true, message: result.message };
                } else {
                    throw new Error(result.message || 'Ошибка отправки результатов квиза');
                }
            } catch (err) {
                console.error('Ошибка отправки результатов квиза:', err);
                return { success: false, message: err.message || 'Ошибка отправки результатов квиза' };
            } finally {
                isSubmitting.value = false;
            }
        };

        // Обработка отправки формы (вызывается из компонента Forms)
        const handleSubmit = async (contactInfo) => {
            const result = await submitQuiz(contactInfo);
            if (result.success) {
                // Переходим на страницу благодарности
                goToNext(); // Переход к следующему вопросу (который должен быть "thank")
            } else {
                alert(result.message || 'Произошла ошибка при отправке результатов. Пожалуйста, попробуйте еще раз.');
            }
        };

        onMounted(() => {
            loadSettings();
        });

        return {
            loading,
            error,
            quizData,
            questions,
            currentQuestionNumber,
            currentQuestion,
            isActive,
            isCompleted,
            isSubmitting,
            formatQuestionData,
            goToNext,
            handleAnswer,
            handleSubmit,
            submitQuiz,
        };
    },
};
</script>

<style scoped>
.quiz-fade-enter-active,
.quiz-fade-leave-active {
    transition: all 0.3s ease;
}

.quiz-fade-enter-from {
    opacity: 0;
    transform: translateY(20px);
}

.quiz-fade-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}
</style>

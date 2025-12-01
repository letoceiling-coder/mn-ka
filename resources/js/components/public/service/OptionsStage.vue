<template>
    <div class="w-full max-w-4xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
            <!-- Категория заявителя (options) -->
            <div class="md:col-span-1">
                <div class="flex items-center gap-3 mb-3">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-[#688E67] text-white font-semibold text-sm flex-shrink-0">1</span>
                    <span class="text-sm md:text-base font-medium text-foreground">Категория заявителя</span>
                </div>
                <select
                    v-model="selectedOption"
                    @change="onOptionChange"
                    class="w-full h-12 px-4 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-[#688E67] focus:border-transparent transition-colors"
                >
                    <option value="">Выберите категорию</option>
                    <option
                        v-for="option in service.options"
                        :key="option.id"
                        :value="option.id"
                    >
                        {{ option.name }}
                    </option>
                </select>
            </div>

            <!-- Цель вашего обращения (optionTrees) -->
            <div class="md:col-span-1">
                <div class="flex items-center gap-3 mb-3">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-[#688E67] text-white font-semibold text-sm flex-shrink-0">2</span>
                    <span class="text-sm md:text-base font-medium text-foreground">Цель вашего обращения</span>
                </div>
                <select
                    v-model="selectedOptionTree"
                    @change="onOptionTreeChange"
                    class="w-full h-12 px-4 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-[#688E67] focus:border-transparent transition-colors"
                >
                    <option value="">Выберите цель</option>
                    <option
                        v-for="tree in service.option_trees"
                        :key="tree.id"
                        :value="tree.id"
                    >
                        {{ tree.name }}
                    </option>
                </select>
            </div>

            <!-- Подходящий случай (instances из выбранного optionTree) -->
            <div v-if="selectedOptionTree && availableInstances.length > 0" class="md:col-span-1">
                <div class="flex items-center gap-3 mb-3">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-[#688E67] text-white font-semibold text-sm flex-shrink-0">3</span>
                    <span class="text-sm md:text-base font-medium text-foreground">Подходящий случай</span>
                </div>
                <select
                    v-model="selectedInstance"
                    @change="onInstanceChange"
                    class="w-full h-12 px-4 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-[#688E67] focus:border-transparent transition-colors"
                >
                    <option value="">Выберите случай</option>
                    <option
                        v-for="instance in availableInstances"
                        :key="instance.id"
                        :value="instance.id"
                    >
                        {{ instance.name }}
                    </option>
                </select>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, watch } from 'vue';

export default {
    name: 'ServiceOptionsStage',
    props: {
        service: {
            type: Object,
            required: true,
        },
    },
    emits: ['update-options'],
    setup(props, { emit }) {
        const selectedOption = ref(null);
        const selectedOptionTree = ref(null);
        const selectedInstance = ref(null);

        // Найти доступные instances из выбранного optionTree
        const availableInstances = computed(() => {
            if (!selectedOptionTree.value || !props.service.option_trees) {
                return [];
            }

            const selectedTree = props.service.option_trees.find(
                tree => tree.id === selectedOptionTree.value
            );

            if (!selectedTree || !selectedTree.items || selectedTree.items.length === 0) {
                // Если у выбранного дерева нет items, используем все instances услуги
                return props.service.instances || [];
            }

            // Возвращаем items из выбранного дерева как instances
            return selectedTree.items.map(item => ({
                id: item.id,
                name: item.name,
            }));
        });

        const onOptionChange = () => {
            emitOptions();
        };

        const onOptionTreeChange = () => {
            // Сбрасываем instance при изменении optionTree
            selectedInstance.value = null;
            emitOptions();
        };

        const onInstanceChange = () => {
            emitOptions();
        };

        const emitOptions = () => {
            emit('update-options', {
                option: selectedOption.value,
                optionTree: selectedOptionTree.value,
                instance: selectedInstance.value,
            });
        };

        // Сбрасываем при изменении услуги
        watch(() => props.service?.id, () => {
            selectedOption.value = null;
            selectedOptionTree.value = null;
            selectedInstance.value = null;
        });

        return {
            selectedOption,
            selectedOptionTree,
            selectedInstance,
            availableInstances,
            onOptionChange,
            onOptionTreeChange,
            onInstanceChange,
        };
    },
};
</script>


<script setup>
import { computed } from 'vue';
import SectionShell from '../ui/SectionShell.vue';
import ModuleGroup from '../modules/ModuleGroup.vue';
import ModuleButtons from '../modules/ModuleButtons.vue';

const props = defineProps({
    section: { type: Object, required: true },
});

/**
 * Free-form section: every module is rendered with the presentation type the
 * administrator picked. Consecutive modules sharing a layout are grouped so a
 * run of cards becomes one grid and a run of gallery items becomes one gallery.
 */
const groups = computed(() => {
    const result = [];

    (props.section.modules ?? []).forEach((module) => {
        const last = result[result.length - 1];

        if (last && last.layout === module.layout) {
            last.modules.push(module);

            return;
        }

        result.push({ layout: module.layout, modules: [module] });
    });

    return result;
});

const columns = computed(() => Number(props.section.settings?.columns ?? 3));
const align = computed(() => (props.section.settings?.align === 'center' ? 'center' : 'left'));
</script>

<template>
    <SectionShell :section="section" :align="align">
        <template #default="{ visible, isDark }">
            <div class="space-y-12 md:space-y-16">
                <div
                    v-for="(group, i) in groups"
                    :key="`${group.layout}-${i}`"
                    :class="['hf-reveal', visible && 'hf-reveal-in']"
                    :style="{ transitionDelay: `${i * 80}ms` }"
                >
                    <ModuleGroup
                        :layout="group.layout"
                        :modules="group.modules"
                        :dark="isDark"
                        :columns="columns"
                    />
                </div>
            </div>

            <ModuleButtons :buttons="section.buttons" :dark="isDark" class="mt-12" />
        </template>
    </SectionShell>
</template>

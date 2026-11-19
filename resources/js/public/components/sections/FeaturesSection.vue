<script setup>
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useSiteStore } from '../../stores/site';
import SectionShell from '../ui/SectionShell.vue';
import IconGlyph from '../ui/IconGlyph.vue';

const props = defineProps({
    section: { type: Object, required: true },
});

const store = useSiteStore();
const { features } = storeToRefs(store);

/** Default labels; a section may override them in its settings. */
const CATEGORY_LABELS = {
    nucleo: 'Lo que viene con cualquier paquete',
    canales: 'Canales de venta',
    operacion: 'Operación del local',
    cobros: 'Cobros',
    integraciones: 'Integraciones',
    administracion: 'Administración y control',
};

/** Categories whose modules go included in every package. */
const INCLUDED = ['nucleo'];

const settings = computed(() => props.section.settings ?? {});
const columns = computed(() => Number(settings.value.columns ?? 3));
const grouped = computed(() => settings.value.group_by_category === true);

const labels = computed(() => ({ ...CATEGORY_LABELS, ...(settings.value.category_labels ?? {}) }));

/** A section may show only some categories, so one catalogue feeds several. */
const visibleFeatures = computed(() => {
    const allowed = settings.value.categories;

    if (!Array.isArray(allowed) || !allowed.length) {
        return features.value;
    }

    return features.value.filter((feature) => allowed.includes(feature.category));
});

const highlighted = computed(() => visibleFeatures.value.filter((feature) => feature.is_highlighted));
const rest = computed(() => visibleFeatures.value.filter((feature) => !feature.is_highlighted));

/** Groups in the order declared by the section, extras appended at the end. */
const groups = computed(() => {
    const order = Array.isArray(settings.value.categories) ? settings.value.categories : [];
    const buckets = new Map();

    order.forEach((category) => buckets.set(category, []));

    visibleFeatures.value.forEach((feature) => {
        const key = feature.category || 'otros';

        if (!buckets.has(key)) {
            buckets.set(key, []);
        }

        buckets.get(key).push(feature);
    });

    return [...buckets.entries()]
        .filter(([, items]) => items.length > 0)
        .map(([category, items]) => ({
            category,
            label: labels.value[category] ?? category,
            included: INCLUDED.includes(category),
            items,
        }));
});

const gridClass = computed(() => {
    const map = {
        2: 'sm:grid-cols-2',
        3: 'sm:grid-cols-2 lg:grid-cols-3',
        4: 'sm:grid-cols-2 lg:grid-cols-4',
    };

    return ['grid gap-5', map[columns.value] ?? map[3]].join(' ');
});
</script>

<template>
    <SectionShell :section="section">
        <template #default="{ visible, isDark }">
            <!-- Catálogo agrupado por categoría -->
            <div v-if="grouped && groups.length" class="space-y-12">
                <section v-for="(group, g) in groups" :key="group.category">
                    <header
                        :class="['hf-reveal mb-6 flex flex-wrap items-center gap-3 border-b pb-4', isDark ? 'border-white/10' : 'border-charcoal/10', visible && 'hf-reveal-in']"
                        :style="{ transitionDelay: `${g * 60}ms` }"
                    >
                        <h3 class="text-lg">{{ group.label }}</h3>

                        <span
                            v-if="group.included"
                            class="ml-auto rounded-full bg-honey px-3 py-1 font-sans text-[0.68rem] font-bold uppercase tracking-wider text-charcoal"
                        >
                            Incluido en todos los paquetes
                        </span>
                        <span v-else class="ml-auto font-sans text-xs font-semibold opacity-50">
                            {{ group.items.length }} {{ group.items.length === 1 ? 'módulo' : 'módulos' }}
                        </span>
                    </header>

                    <ul :class="gridClass">
                        <li
                            v-for="(feature, i) in group.items"
                            :key="feature.id"
                            :class="[
                                'hf-reveal flex h-full flex-col rounded-[1.25rem] border p-6 transition-all duration-400 hover:-translate-y-1',
                                isDark
                                    ? 'border-white/10 bg-white/[0.03] hover:border-white/25'
                                    : 'border-charcoal/10 bg-white hover:border-honey hover:shadow-[var(--shadow-soft)]',
                                visible && 'hf-reveal-in',
                            ]"
                            :style="{ transitionDelay: `${g * 60 + i * 50}ms` }"
                        >
                            <span
                                :class="[
                                    'grid h-11 w-11 place-items-center rounded-xl',
                                    isDark ? 'bg-white/8 text-honey' : 'bg-ivory text-charcoal',
                                ]"
                            >
                                <IconGlyph :name="feature.icon || 'spark'" :size="20" />
                            </span>

                            <h4 class="mt-4 font-sans text-base font-bold">{{ feature.name }}</h4>
                            <p class="mt-2 flex-1 text-sm leading-relaxed opacity-70">
                                {{ feature.short_description }}
                            </p>

                            <ul
                                v-if="feature.bullets?.length"
                                :class="['mt-4 space-y-1.5 border-t pt-4', isDark ? 'border-white/10' : 'border-charcoal/8']"
                            >
                                <li v-for="bullet in feature.bullets" :key="bullet" class="flex items-start gap-2 text-xs opacity-70">
                                    <IconGlyph name="check" :size="13" class="mt-0.5 shrink-0 text-coral" />
                                    {{ bullet }}
                                </li>
                            </ul>
                        </li>
                    </ul>
                </section>
            </div>

            <!-- Presentación simple: destacadas arriba y el resto en rejilla -->
            <template v-else>
                <div v-if="highlighted.length" class="grid gap-6 md:grid-cols-3">
                    <article
                        v-for="(feature, i) in highlighted"
                        :key="feature.id"
                        :class="['hf-reveal hf-card group flex flex-col p-7 hover:-translate-y-1.5 hover:shadow-[var(--shadow-lift)]', visible && 'hf-reveal-in']"
                        :style="{ transitionDelay: `${i * 90}ms` }"
                    >
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-charcoal text-honey">
                            <IconGlyph :name="feature.icon || 'spark'" :size="22" />
                        </span>

                        <h3 class="mt-5 text-lg">{{ feature.name }}</h3>
                        <p class="mt-2.5 flex-1 text-sm leading-relaxed opacity-70">
                            {{ feature.short_description }}
                        </p>

                        <ul v-if="feature.bullets?.length" class="mt-5 space-y-2 border-t border-charcoal/8 pt-5">
                            <li v-for="bullet in feature.bullets" :key="bullet" class="flex items-center gap-2 text-sm opacity-75">
                                <IconGlyph name="check" :size="15" class="shrink-0 text-coral" />
                                {{ bullet }}
                            </li>
                        </ul>
                    </article>
                </div>

                <div
                    v-if="rest.length"
                    :class="[
                        'grid gap-x-8 gap-y-9',
                        highlighted.length && 'mt-12 border-t border-charcoal/10 pt-12',
                        columns === 4 ? 'sm:grid-cols-2 lg:grid-cols-4' : 'sm:grid-cols-2 lg:grid-cols-3',
                    ]"
                >
                    <article
                        v-for="(feature, i) in rest"
                        :key="feature.id"
                        :class="['hf-reveal flex gap-4', visible && 'hf-reveal-in']"
                        :style="{ transitionDelay: `${i * 60}ms` }"
                    >
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-honey-soft text-charcoal">
                            <IconGlyph :name="feature.icon || 'spark'" :size="19" />
                        </span>
                        <div>
                            <h3 class="font-sans text-base font-bold">{{ feature.name }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed opacity-70">{{ feature.short_description }}</p>
                        </div>
                    </article>
                </div>
            </template>

            <p v-if="!visibleFeatures.length" class="text-sm opacity-60">
                Aún no hay módulos publicados para esta sección.
            </p>
        </template>
    </SectionShell>
</template>

<script setup>
import { computed } from 'vue';
import SectionShell from '../ui/SectionShell.vue';
import IconGlyph from '../ui/IconGlyph.vue';
import ProductScreen from '../product/ProductScreen.vue';
import ModuleButtons from '../modules/ModuleButtons.vue';

const props = defineProps({
    section: { type: Object, required: true },
});

/**
 * Cada módulo es una pantalla real del producto.
 *
 * La captura sube por el campo de imagen del módulo; mientras no exista, se
 * dibuja una maqueta fiel de esa misma pantalla ("settings.screen").
 */
const screens = computed(() =>
    (props.section.modules ?? []).map((module, i) => ({
        ...module,
        screen: module.settings?.screen ?? 'orders',
        // La composición alterna el lado del dispositivo.
        reversed: module.settings?.reversed ?? i % 2 === 1,
    })),
);
</script>

<template>
    <SectionShell :section="section">
        <template #default="{ visible, isDark }">
            <div v-if="screens.length" class="space-y-20 md:space-y-28">
                <article
                    v-for="(item, i) in screens"
                    :key="item.id"
                    :class="['hf-reveal grid items-center gap-10 lg:grid-cols-2 lg:gap-16', visible && 'hf-reveal-in']"
                    :style="{ transitionDelay: `${i * 70}ms` }"
                >
                    <!-- Texto -->
                    <div :class="item.reversed ? 'lg:order-2' : ''">
                        <p
                            v-if="item.eyebrow"
                            :class="[
                                'inline-flex items-center gap-2 rounded-full px-3 py-1.5 font-sans text-[0.7rem] font-bold uppercase tracking-[0.14em]',
                                isDark ? 'bg-white/8 text-honey' : 'bg-charcoal text-honey',
                            ]"
                        >
                            <IconGlyph :name="item.icon || 'spark'" :size="14" />
                            {{ item.eyebrow }}
                        </p>

                        <h3 class="mt-5 text-[1.75rem] leading-[1.1] md:text-[2.25rem]">{{ item.title }}</h3>

                        <p v-if="item.subtitle" class="mt-3 font-sans text-lg font-semibold opacity-80">
                            {{ item.subtitle }}
                        </p>

                        <p v-if="item.description" class="mt-4 max-w-xl text-base leading-relaxed opacity-70 md:text-lg">
                            {{ item.description }}
                        </p>

                        <ul v-if="item.bullets?.length" class="mt-7 space-y-3">
                            <li
                                v-for="bullet in item.bullets"
                                :key="bullet"
                                class="flex items-start gap-3 text-[0.95rem] opacity-80"
                            >
                                <span
                                    :class="[
                                        'mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full',
                                        isDark ? 'bg-honey/20 text-honey' : 'bg-honey text-charcoal',
                                    ]"
                                >
                                    <IconGlyph name="check" :size="12" :stroke="3" />
                                </span>
                                {{ bullet }}
                            </li>
                        </ul>

                        <ModuleButtons :buttons="item.buttons" :dark="isDark" class="mt-8" />
                    </div>

                    <!-- Pantalla del producto -->
                    <div :class="['relative', item.reversed ? 'lg:order-1' : '']">
                        <!-- Halo de fondo para despegar el dispositivo del fondo -->
                        <div
                            :class="[
                                'absolute inset-6 -z-10 rounded-[3rem] blur-3xl',
                                i % 2 === 0 ? 'bg-honey/25' : 'bg-coral/20',
                            ]"
                            aria-hidden="true"
                        />

                        <ProductScreen
                            :screen="item.screen"
                            :image="item.image"
                            :alt="item.image_alt || item.title"
                        />
                    </div>
                </article>
            </div>

            <p v-else class="text-sm opacity-60">
                Aún no hay pantallas configuradas. Agrégalas como módulos de esta sección.
            </p>
        </template>
    </SectionShell>
</template>

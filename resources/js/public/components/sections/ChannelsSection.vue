<script setup>
import { computed } from 'vue';
import SectionShell from '../ui/SectionShell.vue';
import IconGlyph from '../ui/IconGlyph.vue';

const props = defineProps({
    section: { type: Object, required: true },
});

const channels = computed(() => props.section.modules ?? []);

/** The first channel is presented as the main one. */
const featured = computed(() => channels.value[0] ?? null);
const rest = computed(() => channels.value.slice(1));

/** What every channel ends up sharing, editable from the section settings. */
const converge = computed(() => {
    const configured = props.section.settings?.converge;

    if (Array.isArray(configured) && configured.length) {
        return configured;
    }

    return [
        { icon: 'list', title: 'Un solo tablero', description: 'Tu equipo atiende todos los pedidos en la misma pantalla.' },
        { icon: 'receipt', title: 'La misma comanda', description: 'La cocina recibe e imprime igual, venga de donde venga.' },
        { icon: 'chart', title: 'Los mismos reportes', description: 'Cierras el día con todos los canales sumados.' },
    ];
});
</script>

<template>
    <SectionShell :section="section">
        <template #default="{ visible, isDark }">
            <div v-if="channels.length" class="space-y-6">
                <!-- Canal principal -->
                <article
                    v-if="featured"
                    :class="[
                        'hf-reveal grid gap-7 rounded-[1.5rem] p-7 md:grid-cols-[auto_1fr] md:p-9',
                        isDark ? 'bg-white/[0.05] ring-1 ring-white/12' : 'bg-charcoal text-ivory',
                        visible && 'hf-reveal-in',
                    ]"
                >
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-honey text-charcoal">
                        <IconGlyph :name="featured.icon || 'chat'" :size="26" />
                    </span>

                    <div>
                        <p v-if="featured.eyebrow" class="hf-eyebrow text-honey">{{ featured.eyebrow }}</p>
                        <h3 class="mt-3 text-2xl md:text-[1.75rem]">{{ featured.title }}</h3>
                        <p class="mt-3 max-w-2xl text-base leading-relaxed text-ivory/75">{{ featured.description }}</p>

                        <ul v-if="featured.bullets?.length" class="mt-6 grid gap-2.5 sm:grid-cols-2">
                            <li
                                v-for="bullet in featured.bullets"
                                :key="bullet"
                                class="flex items-start gap-2.5 text-sm text-ivory/80"
                            >
                                <IconGlyph name="check" :size="15" class="mt-0.5 shrink-0 text-honey" />
                                {{ bullet }}
                            </li>
                        </ul>
                    </div>
                </article>

                <!-- Resto de canales -->
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <li
                        v-for="(channel, i) in rest"
                        :key="channel.id"
                        :class="[
                            'hf-reveal group flex h-full flex-col rounded-[1.25rem] border p-6 transition-all duration-400 hover:-translate-y-1',
                            isDark
                                ? 'border-white/10 bg-white/[0.03] hover:border-white/25'
                                : 'border-charcoal/10 bg-white hover:border-honey hover:shadow-[var(--shadow-soft)]',
                            visible && 'hf-reveal-in',
                        ]"
                        :style="{ transitionDelay: `${80 + i * 70}ms` }"
                    >
                        <span
                            :class="[
                                'grid h-11 w-11 place-items-center rounded-xl transition-colors',
                                isDark ? 'bg-white/8 text-honey' : 'bg-ivory text-charcoal group-hover:bg-honey',
                            ]"
                        >
                            <IconGlyph :name="channel.icon || 'storefront'" :size="20" />
                        </span>

                        <h3 class="mt-4 text-base">{{ channel.title }}</h3>
                        <p class="mt-2 flex-1 text-sm leading-relaxed opacity-70">{{ channel.description }}</p>

                        <ul v-if="channel.bullets?.length" class="mt-4 space-y-1.5 border-t pt-4" :class="isDark ? 'border-white/10' : 'border-charcoal/8'">
                            <li v-for="bullet in channel.bullets" :key="bullet" class="flex items-start gap-2 text-xs opacity-70">
                                <IconGlyph name="check" :size="13" class="mt-0.5 shrink-0 text-coral" />
                                {{ bullet }}
                            </li>
                        </ul>
                    </li>
                </ul>

                <!-- Convergencia: el punto de toda la sección -->
                <div
                    :class="['hf-reveal pt-4', visible && 'hf-reveal-in']"
                    style="transition-delay: 240ms"
                >
                    <div class="flex items-center gap-4" aria-hidden="true">
                        <span :class="['h-px flex-1', isDark ? 'bg-white/15' : 'bg-charcoal/12']" />
                        <span class="flex items-center gap-2 font-sans text-xs font-bold uppercase tracking-[0.16em] text-coral">
                            <IconGlyph name="chevron-down" :size="15" />
                            Todas las líneas terminan aquí
                        </span>
                        <span :class="['h-px flex-1', isDark ? 'bg-white/15' : 'bg-charcoal/12']" />
                    </div>

                    <ul class="mt-6 grid gap-4 sm:grid-cols-3">
                        <li
                            v-for="item in converge"
                            :key="item.title"
                            class="rounded-[1.25rem] bg-honey/15 p-6 ring-1 ring-honey/35"
                        >
                            <span class="grid h-10 w-10 place-items-center rounded-xl bg-honey text-charcoal">
                                <IconGlyph :name="item.icon || 'check'" :size="19" />
                            </span>
                            <p class="mt-4 font-sans text-base font-bold">{{ item.title }}</p>
                            <p class="mt-1.5 text-sm leading-relaxed opacity-75">{{ item.description }}</p>
                        </li>
                    </ul>
                </div>
            </div>

            <p v-else class="text-sm opacity-60">
                Aún no hay canales configurados. Agrégalos como módulos de esta sección.
            </p>
        </template>
    </SectionShell>
</template>

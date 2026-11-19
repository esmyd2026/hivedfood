<script setup>
import { storeToRefs } from 'pinia';
import { useSiteStore } from '../../stores/site';
import SectionShell from '../ui/SectionShell.vue';
import IconGlyph from '../ui/IconGlyph.vue';

defineProps({
    section: { type: Object, required: true },
});

const store = useSiteStore();
const { testimonials } = storeToRefs(store);
</script>

<template>
    <SectionShell v-if="testimonials.length" :section="section">
        <template #default="{ visible }">
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <figure
                    v-for="(testimonial, i) in testimonials"
                    :key="testimonial.id"
                    :class="['hf-reveal hf-card flex flex-col p-7', visible && 'hf-reveal-in']"
                    :style="{ transitionDelay: `${i * 80}ms` }"
                >
                    <IconGlyph name="quote" :size="28" class="text-honey" :stroke="0" style="fill: currentColor" />

                    <blockquote class="mt-4 flex-1 text-base leading-relaxed opacity-80">
                        {{ testimonial.quote }}
                    </blockquote>

                    <div v-if="testimonial.rating" class="mt-5 flex gap-1 text-honey" :aria-label="`${testimonial.rating} de 5`">
                        <IconGlyph v-for="n in testimonial.rating" :key="n" name="star" :size="15" :stroke="0" style="fill: currentColor" />
                    </div>

                    <figcaption class="mt-5 flex items-center gap-3 border-t border-charcoal/8 pt-5">
                        <img
                            v-if="testimonial.avatar"
                            :src="testimonial.avatar"
                            :alt="testimonial.author"
                            class="h-10 w-10 rounded-full object-cover"
                            width="40"
                            height="40"
                            loading="lazy"
                        />
                        <span v-else class="grid h-10 w-10 place-items-center rounded-full bg-charcoal font-sans text-sm font-bold text-honey">
                            {{ testimonial.author.charAt(0) }}
                        </span>
                        <span>
                            <span class="block font-sans text-sm font-bold">{{ testimonial.author }}</span>
                            <span class="block text-xs opacity-60">
                                {{ [testimonial.role, testimonial.business].filter(Boolean).join(' · ') }}
                            </span>
                        </span>
                    </figcaption>
                </figure>
            </div>
        </template>
    </SectionShell>
</template>

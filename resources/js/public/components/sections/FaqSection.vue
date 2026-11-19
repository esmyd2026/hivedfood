<script setup>
import { ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useSiteStore } from '../../stores/site';
import SectionShell from '../ui/SectionShell.vue';
import IconGlyph from '../ui/IconGlyph.vue';

defineProps({
    section: { type: Object, required: true },
});

const store = useSiteStore();
const { faqs } = storeToRefs(store);

const open = ref(null);

function toggle(id) {
    open.value = open.value === id ? null : id;
}
</script>

<template>
    <SectionShell :section="section">
        <template #default="{ visible }">
            <div class="mx-auto max-w-3xl">
                <div
                    v-for="(faq, i) in faqs"
                    :key="faq.id"
                    :class="['hf-reveal border-b border-charcoal/10 first:border-t', visible && 'hf-reveal-in']"
                    :style="{ transitionDelay: `${i * 45}ms` }"
                >
                    <h3>
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-6 py-6 text-left transition-colors hover:text-coral"
                            :aria-expanded="open === faq.id"
                            :aria-controls="`faq-${faq.id}`"
                            @click="toggle(faq.id)"
                        >
                            <span class="font-sans text-base font-bold md:text-lg">{{ faq.question }}</span>
                            <span
                                :class="[
                                    'grid h-9 w-9 shrink-0 place-items-center rounded-full border border-charcoal/15 transition-transform duration-300',
                                    open === faq.id && 'rotate-180 border-coral bg-coral text-white',
                                ]"
                            >
                                <IconGlyph name="chevron-down" :size="17" />
                            </span>
                        </button>
                    </h3>

                    <div
                        :id="`faq-${faq.id}`"
                        :class="['grid transition-all duration-400 ease-[cubic-bezier(0.22,1,0.36,1)]', open === faq.id ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0']"
                    >
                        <div class="overflow-hidden">
                            <p class="pb-6 pr-14 text-base leading-relaxed opacity-72">{{ faq.answer }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </SectionShell>
</template>

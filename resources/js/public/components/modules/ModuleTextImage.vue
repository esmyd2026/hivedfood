<script setup>
import IconGlyph from '../ui/IconGlyph.vue';
import ModuleButtons from './ModuleButtons.vue';

const props = defineProps({
    module: { type: Object, required: true },
    dark: { type: Boolean, default: false },
    /** When true the image sits on the left (the "image_text" layout). */
    reversed: { type: Boolean, default: false },
});
</script>

<template>
    <article class="grid items-center gap-8 md:grid-cols-2 md:gap-12">
        <div :class="reversed ? 'md:order-2' : ''">
            <span
                v-if="module.icon"
                :class="[
                    'mb-5 grid h-12 w-12 place-items-center rounded-2xl',
                    dark ? 'bg-honey text-charcoal' : 'bg-honey-soft text-charcoal',
                ]"
            >
                <IconGlyph :name="module.icon" :size="22" />
            </span>

            <p v-if="module.eyebrow" :class="['hf-eyebrow', dark ? 'text-honey' : 'text-coral']">
                {{ module.eyebrow }}
            </p>

            <h3 v-if="module.title" class="mt-4 text-2xl md:text-3xl">{{ module.title }}</h3>
            <p v-if="module.subtitle" class="mt-2 font-sans text-base font-semibold opacity-75">{{ module.subtitle }}</p>
            <p v-if="module.description" class="mt-4 text-base leading-relaxed opacity-70">{{ module.description }}</p>

            <ul v-if="module.bullets?.length" class="mt-5 space-y-2.5">
                <li v-for="bullet in module.bullets" :key="bullet" class="flex items-start gap-2.5 text-sm opacity-80">
                    <IconGlyph name="check" :size="16" class="mt-0.5 shrink-0 text-coral" />
                    {{ bullet }}
                </li>
            </ul>

            <ModuleButtons :buttons="module.buttons" :dark="dark" class="mt-7" />
        </div>

        <div :class="reversed ? 'md:order-1' : ''">
            <img
                v-if="module.image"
                :src="module.image"
                :alt="module.image_alt || module.title || ''"
                class="w-full rounded-[1.5rem] object-cover"
                width="900"
                height="700"
                loading="lazy"
                decoding="async"
            />
            <div
                v-else
                :class="[
                    'grid aspect-[4/3] place-items-center rounded-[1.5rem]',
                    dark ? 'bg-white/[0.04] text-ivory/20' : 'bg-ivory-dark text-charcoal/20',
                ]"
                aria-hidden="true"
            >
                <IconGlyph :name="module.icon || 'image'" :size="48" />
            </div>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useSiteStore } from '../../stores/site';
import IconGlyph from '../ui/IconGlyph.vue';

defineProps({
    section: { type: Object, default: () => ({}) },
});

const store = useSiteStore();
const { settings } = storeToRefs(store);

const year = new Date().getFullYear();

const columns = computed(() => settings.value.footer_links ?? []);

const socials = computed(() =>
    [
        { key: 'facebook_url', label: 'Facebook', icon: 'globe' },
        { key: 'instagram_url', label: 'Instagram', icon: 'image' },
        { key: 'linkedin_url', label: 'LinkedIn', icon: 'users' },
        { key: 'tiktok_url', label: 'TikTok', icon: 'spark' },
        { key: 'youtube_url', label: 'YouTube', icon: 'chevron-right' },
    ].filter((social) => !!settings.value[social.key]),
);

const contacts = computed(() =>
    [
        settings.value.email ? { icon: 'mail', label: settings.value.email, href: `mailto:${settings.value.email}` } : null,
        settings.value.phone ? { icon: 'phone', label: settings.value.phone, href: `tel:${settings.value.phone.replace(/\s+/g, '')}` } : null,
        settings.value.whatsapp_url ? { icon: 'whatsapp', label: 'WhatsApp', href: settings.value.whatsapp_url } : null,
        settings.value.address ? { icon: 'pin', label: [settings.value.address, settings.value.city].filter(Boolean).join(', '), href: null } : null,
        settings.value.schedule ? { icon: 'clock', label: settings.value.schedule, href: null } : null,
    ].filter(Boolean),
);
</script>

<template>
    <footer id="footer" class="bg-charcoal text-ivory">
        <div class="hf-shell py-16 md:py-20">
            <div class="grid gap-12 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
                <div>
                    <a href="/" class="inline-flex items-center gap-3" aria-label="Inicio HIVEDFOOD">
                        <img
                            v-if="settings.logo_light || settings.logo"
                            :src="settings.logo_light || settings.logo"
                            :alt="settings.site_name || 'HIVEDFOOD'"
                            class="h-10 w-auto"
                            width="200"
                            height="40"
                        />
                        <span v-else class="font-sans text-xl font-extrabold">{{ settings.site_name || 'HIVEDFOOD' }}</span>
                    </a>

                    <p class="mt-6 max-w-sm text-sm leading-relaxed text-ivory/65">
                        {{ settings.footer_text }}
                    </p>

                    <div v-if="socials.length" class="mt-7 flex gap-2">
                        <a
                            v-for="social in socials"
                            :key="social.key"
                            :href="settings[social.key]"
                            target="_blank"
                            rel="noopener noreferrer"
                            :aria-label="social.label"
                            class="grid h-10 w-10 place-items-center rounded-full border border-white/12 text-ivory/70 transition hover:border-honey hover:text-honey"
                        >
                            <IconGlyph :name="social.icon" :size="17" />
                        </a>
                    </div>
                </div>

                <div v-for="column in columns" :key="column.title">
                    <p class="font-sans text-xs font-bold uppercase tracking-[0.16em] text-honey">{{ column.title }}</p>
                    <ul class="mt-5 space-y-3">
                        <li v-for="link in column.links" :key="link.url">
                            <a :href="link.url" class="text-sm text-ivory/70 transition hover:text-ivory">{{ link.label }}</a>
                        </li>
                    </ul>
                </div>

                <div v-if="contacts.length">
                    <p class="font-sans text-xs font-bold uppercase tracking-[0.16em] text-honey">Contacto</p>
                    <ul class="mt-5 space-y-3">
                        <li v-for="contact in contacts" :key="contact.label" class="flex items-start gap-2.5 text-sm text-ivory/70">
                            <IconGlyph :name="contact.icon" :size="16" class="mt-0.5 shrink-0 text-honey" />
                            <a
                                v-if="contact.href"
                                :href="contact.href"
                                :target="contact.href.startsWith('http') ? '_blank' : null"
                                rel="noopener noreferrer"
                                class="transition hover:text-ivory"
                            >
                                {{ contact.label }}
                            </a>
                            <span v-else>{{ contact.label }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-14 flex flex-col gap-4 border-t border-white/10 pt-7 text-xs text-ivory/45 sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ year }} {{ settings.legal_name || settings.site_name || 'HIVEDFOOD' }}. Todos los derechos reservados.</p>
                <p>{{ store.text('footer_tagline', '') }}</p>
            </div>
        </div>
    </footer>
</template>

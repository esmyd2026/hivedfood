<script setup>
import { onMounted, reactive, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useSiteStore } from '../../stores/site';
import http, { parseError } from '../../../lib/http';
import BaseButton from '../ui/BaseButton.vue';
import IconGlyph from '../ui/IconGlyph.vue';

const props = defineProps({
    source: { type: String, default: 'demo_request' },
    quotationToken: { type: String, default: null },
    compact: { type: Boolean, default: false },
});

const store = useSiteStore();
const { quoter, settings } = storeToRefs(store);

const form = reactive({
    name: '',
    company: '',
    email: '',
    phone: '',
    business_type_id: '',
    message: '',
    accepts_marketing: false,
    website: '', // honeypot
});

const errors = ref({});
const message = ref('');
const status = ref('idle'); // idle | loading | success | error
const mountedAt = ref(Date.now());

onMounted(() => {
    mountedAt.value = Date.now();
});

async function submit() {
    if (status.value === 'loading') {
        return;
    }

    status.value = 'loading';
    errors.value = {};
    message.value = '';

    try {
        const { data } = await http.post(store.routes.leads ?? '/api/leads', {
            ...form,
            business_type_id: form.business_type_id || null,
            source: props.source,
            quotation_token: props.quotationToken,
            elapsed: Date.now() - mountedAt.value,
            page: window.location.pathname,
        });

        status.value = 'success';
        message.value = data.message;

        Object.assign(form, {
            name: '',
            company: '',
            email: '',
            phone: '',
            business_type_id: '',
            message: '',
            accepts_marketing: false,
        });
    } catch (error) {
        const parsed = parseError(error);
        status.value = 'error';
        errors.value = parsed.errors;
        message.value = parsed.message;
    }
}
</script>

<template>
    <form novalidate class="relative" @submit.prevent="submit">
        <!-- Trampa anti-spam: invisible para personas, obligatoria vacía en el backend -->
        <div class="absolute h-0 w-0 overflow-hidden opacity-0" aria-hidden="true">
            <label for="website-hp">No completar</label>
            <input id="website-hp" v-model="form.website" type="text" tabindex="-1" autocomplete="off" />
        </div>

        <div :class="['grid gap-4', compact ? '' : 'sm:grid-cols-2']">
            <div>
                <label class="hf-label" for="lead-name">Nombre *</label>
                <input id="lead-name" v-model="form.name" type="text" class="hf-field" required autocomplete="name" />
                <p v-if="errors.name" class="mt-1.5 text-xs font-semibold text-coral">{{ errors.name[0] }}</p>
            </div>

            <div>
                <label class="hf-label" for="lead-company">Negocio</label>
                <input id="lead-company" v-model="form.company" type="text" class="hf-field" autocomplete="organization" />
                <p v-if="errors.company" class="mt-1.5 text-xs font-semibold text-coral">{{ errors.company[0] }}</p>
            </div>

            <div>
                <label class="hf-label" for="lead-email">Correo *</label>
                <input id="lead-email" v-model="form.email" type="email" class="hf-field" required autocomplete="email" />
                <p v-if="errors.email" class="mt-1.5 text-xs font-semibold text-coral">{{ errors.email[0] }}</p>
            </div>

            <div>
                <label class="hf-label" for="lead-phone">Teléfono</label>
                <input id="lead-phone" v-model="form.phone" type="tel" class="hf-field" autocomplete="tel" />
                <p v-if="errors.phone" class="mt-1.5 text-xs font-semibold text-coral">{{ errors.phone[0] }}</p>
            </div>

            <div :class="compact ? '' : 'sm:col-span-2'">
                <label class="hf-label" for="lead-type">Tipo de negocio</label>
                <select id="lead-type" v-model="form.business_type_id" class="hf-field">
                    <option value="">Selecciona una opción</option>
                    <option v-for="type in quoter.business_types" :key="type.id" :value="type.id">
                        {{ type.name }}
                    </option>
                </select>
            </div>

            <div :class="compact ? '' : 'sm:col-span-2'">
                <label class="hf-label" for="lead-message">
                    {{ store.text('form_question_label', '¿Qué necesitas resolver?') }}
                </label>
                <textarea id="lead-message" v-model="form.message" rows="4" class="hf-field resize-y" />
                <p v-if="errors.message" class="mt-1.5 text-xs font-semibold text-coral">{{ errors.message[0] }}</p>
            </div>
        </div>

        <label class="mt-4 flex cursor-pointer items-start gap-2.5 text-xs leading-relaxed opacity-75">
            <input v-model="form.accepts_marketing" type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 accent-[var(--color-coral)]" />
            <span>{{ store.text('form_consent_label', 'Quiero recibir novedades y material comercial de HIVEDFOOD.') }}</span>
        </label>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <BaseButton type="submit" variant="dark" :loading="status === 'loading'" trailing-icon="arrow-right">
                {{ store.text('cta_submit_label', 'Enviar solicitud') }}
            </BaseButton>

            <BaseButton v-if="settings.whatsapp_url" :href="settings.whatsapp_url" variant="ghost" icon="whatsapp">
                {{ store.text('cta_whatsapp_label', 'Prefiero WhatsApp') }}
            </BaseButton>
        </div>

        <Transition name="fade">
            <p
                v-if="message"
                :class="[
                    'mt-5 flex items-start gap-2 rounded-xl px-4 py-3 text-sm font-medium',
                    status === 'success' ? 'bg-charcoal text-ivory' : 'bg-coral-soft text-coral-dark',
                ]"
                role="status"
            >
                <IconGlyph :name="status === 'success' ? 'check-badge' : 'close'" :size="17" class="mt-0.5 shrink-0" />
                {{ message }}
            </p>
        </Transition>
    </form>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>

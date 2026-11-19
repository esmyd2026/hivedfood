<script setup>
import { computed, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useQuoterStore } from '../../stores/quoter';
import { useSiteStore } from '../../stores/site';
import BaseButton from '../ui/BaseButton.vue';
import IconGlyph from '../ui/IconGlyph.vue';

const quoter = useQuoterStore();
const site = useSiteStore();
const { settings } = storeToRefs(site);

const sendEmail = ref(true);
const emailTo = ref('');

const result = computed(() => quoter.result);
const submitted = computed(() => quoter.submitted);

const includedLines = computed(() => (result.value?.lines ?? []).filter((line) => line.is_included));
const chargedLines = computed(() => (result.value?.lines ?? []).filter((line) => !line.is_included));
const autoAdded = computed(() => result.value?.dependencies?.auto_added ?? []);
const usage = computed(() => result.value?.usage ?? null);
const notes = computed(() => result.value?.dependencies?.notes ?? []);

watch(submitted, (value) => {
    if (value) {
        emailTo.value = quoter.contact.email;
    }
});

const whatsappUrl = computed(() => {
    const number = (settings.value.whatsapp_number || '').replace(/\D+/g, '');

    if (!number) {
        return null;
    }

    const reference = submitted.value?.code ? ` (referencia ${submitted.value.code})` : '';
    const text = `Hola, acabo de configurar mi plataforma en el cotizador de HIVEDFOOD${reference}. Me gustaría recibir asesoría.`;

    return `https://wa.me/${number}?text=${encodeURIComponent(text)}`;
});

function fieldError(key) {
    return quoter.submitErrors[`contact.${key}`]?.[0] ?? quoter.submitErrors[key]?.[0] ?? null;
}
</script>

<template>
    <div>
        <h3 class="text-xl text-ivory md:text-2xl">
            {{ site.text('quoter_result_title', 'Esto es lo que estás contratando') }}
        </h3>
        <p v-if="site.text('quoter_result_description')" class="mt-2 text-sm text-ivory/60">
            {{ site.text('quoter_result_description') }}
        </p>

        <!-- Conceptos cotizados -->
        <div v-if="chargedLines.length" class="mt-7">
            <p class="font-sans text-xs font-bold uppercase tracking-[0.18em] text-honey">Detalle de tu inversión</p>

            <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-[30rem] text-sm">
                    <thead>
                        <tr class="text-[0.65rem] uppercase tracking-wider text-ivory/40">
                            <th class="pb-2 text-left font-semibold">Concepto</th>
                            <th class="pb-2 text-right font-semibold">Implementación</th>
                            <th class="pb-2 text-right font-semibold">Mensualidad</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/8">
                        <tr v-for="line in chargedLines" :key="`${line.slug}-${line.sort_order}`">
                            <td class="py-3 pr-4 text-ivory/80">
                                {{ line.name }}
                                <span v-if="line.quantity > 1" class="text-ivory/45">× {{ line.quantity }}</span>
                                <span v-if="line.description" class="mt-0.5 block text-xs text-ivory/45">
                                    {{ line.description }}
                                </span>
                            </td>
                            <td class="py-3 text-right font-sans font-bold text-ivory">
                                {{ line.is_priced ? site.money(line.setup_price) : 'A cotizar' }}
                            </td>
                            <td class="py-3 text-right font-sans font-bold text-ivory">
                                {{ line.is_priced ? site.money(line.monthly_price) : 'A cotizar' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Lo que ya viene dentro del paquete -->
        <div v-if="includedLines.length" class="mt-8">
            <p class="font-sans text-xs font-bold uppercase tracking-[0.18em] text-honey">
                Ya incluido en tu paquete
            </p>
            <ul class="mt-3 flex flex-wrap gap-2">
                <li
                    v-for="line in includedLines"
                    :key="line.slug"
                    class="flex items-center gap-1.5 rounded-full border border-white/12 px-3 py-1.5 text-xs text-ivory/70"
                >
                    <IconGlyph name="check" :size="12" :stroke="3" class="text-honey" />
                    {{ line.name }}
                </li>
            </ul>
        </div>

        <!-- Dependencias resueltas automáticamente -->
        <div v-if="autoAdded.length" class="mt-6 rounded-2xl border border-honey/30 bg-honey/[0.08] p-5">
            <p class="font-sans text-xs font-bold uppercase tracking-wider text-honey">Activado automáticamente</p>
            <ul class="mt-2 space-y-1.5">
                <li v-for="row in autoAdded" :key="row.slug" class="text-xs leading-relaxed text-ivory/70">
                    · {{ row.reason }}
                </li>
            </ul>
        </div>

        <ul v-if="notes.length" class="mt-5 space-y-1.5">
            <li v-for="(row, i) in notes" :key="i" class="text-xs text-ivory/50">
                · <strong class="font-sans font-bold text-ivory/65">{{ row.module }}:</strong> {{ row.note }}
            </li>
        </ul>

        <!-- Consumo por pedidos: variable, siempre aparte del cargo fijo -->
        <section v-if="usage" class="mt-9 rounded-2xl border border-honey/30 bg-honey/[0.07] p-5">
            <h4 class="font-sans text-xs font-bold uppercase tracking-[0.18em] text-honey">
                Consumo por pedidos
            </h4>
            <p class="mt-2 text-xs leading-relaxed text-ivory/60">
                Elegiste {{ result.billing_model?.name }}. Este importe es variable: se calcula con los pedidos
                que de verdad proceses y se factura al cierre de cada mes, aparte de la mensualidad fija.
            </p>

            <table v-if="usage.tiers.length" class="mt-4 w-full text-sm">
                <thead>
                    <tr class="text-[0.65rem] uppercase tracking-wider text-ivory/40">
                        <th class="pb-2 text-left font-semibold">Tramo</th>
                        <th class="pb-2 text-right font-semibold">Pedidos</th>
                        <th class="pb-2 text-right font-semibold">Por pedido</th>
                        <th class="pb-2 text-right font-semibold">Importe</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/8">
                    <tr v-for="tier in usage.tiers" :key="tier.label">
                        <td class="py-2.5 pr-4 text-ivory/75">{{ tier.label }}</td>
                        <td class="py-2.5 text-right text-ivory/75">{{ tier.orders.toLocaleString('es') }}</td>
                        <td class="py-2.5 text-right text-ivory/75">{{ site.money(tier.price_per_order) }}</td>
                        <td class="py-2.5 text-right font-sans font-bold text-ivory">{{ site.money(tier.amount) }}</td>
                    </tr>
                </tbody>
            </table>

            <p v-else class="mt-4 text-xs text-ivory/50">
                Las tarifas por pedido se confirman con el equipo comercial.
            </p>

            <p class="mt-4 border-t border-white/10 pt-3 text-xs leading-relaxed text-ivory/50">
                Cada pedido paga el precio del tramo en el que cae: pasar de un tramo abarata solo los pedidos
                siguientes, nunca los que ya hiciste.
                <template v-if="usage.branches > 1">
                    El volumen se cuenta por cada uno de tus {{ usage.branches }} locales.
                </template>
            </p>
        </section>

        <!-- Servicios externos: exclusiones, nunca mensualidades inventadas -->
        <section v-if="quoter.externalCosts.length" class="mt-9 rounded-2xl border border-white/10 bg-white/[0.03] p-5">
            <h4 class="font-sans text-xs font-bold uppercase tracking-[0.18em] text-coral-soft">
                Servicios externos no incluidos
            </h4>
            <p class="mt-2 text-xs text-ivory/50">
                Se facturan por separado según el consumo real. No forman parte de la mensualidad.
            </p>

            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                <li v-for="cost in quoter.externalCosts" :key="cost.title" class="flex items-start gap-3">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white/8 text-ivory/60">
                        <IconGlyph :name="cost.icon || 'layers'" :size="15" />
                    </span>
                    <span class="min-w-0">
                        <span class="block font-sans text-xs font-bold text-ivory">{{ cost.title }}</span>
                        <span class="mt-0.5 block text-xs leading-relaxed text-ivory/50">{{ cost.description }}</span>
                    </span>
                </li>
            </ul>
        </section>

        <ul v-if="result?.conditions?.length" class="mt-7 space-y-1.5 border-t border-white/10 pt-6 text-xs text-ivory/50">
            <li v-for="condition in result.conditions" :key="condition">· {{ condition }}</li>
        </ul>

        <!-- ----------------------------------------------------------------
             Guardar la propuesta
        ----------------------------------------------------------------- -->
        <div class="mt-9 border-t border-white/10 pt-8">
            <Transition name="swap" mode="out-in">
                <div v-if="!submitted" key="form">
                    <h4 class="text-lg text-ivory">
                        {{ site.text('quoter_contact_title', '¿Enviamos tu propuesta?') }}
                    </h4>
                    <p class="mt-2 text-sm text-ivory/60">
                        La guardamos a nombre de
                        <strong class="font-sans font-bold text-ivory">{{ quoter.contact.name || '—' }}</strong>
                        y te la enviamos a
                        <strong class="font-sans font-bold text-ivory">{{ quoter.contact.email || '—' }}</strong>.
                    </p>

                    <p v-if="fieldError('name') || fieldError('email')" class="mt-3 text-xs font-semibold text-coral">
                        {{ fieldError('name') || fieldError('email') }}
                        <button type="button" class="ml-1 underline" @click="quoter.goTo(0)">Corregir mis datos</button>
                    </p>

                    <label class="mt-5 flex cursor-pointer items-start gap-2.5 text-xs text-ivory/70">
                        <input v-model="sendEmail" type="checkbox" class="mt-0.5 h-4 w-4 accent-[var(--color-honey)]" />
                        <span>Enviarme una copia de la cotización por correo.</span>
                    </label>

                    <label class="mt-2.5 flex cursor-pointer items-start gap-2.5 text-xs text-ivory/70">
                        <input
                            v-model="quoter.contact.accepts_marketing"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 accent-[var(--color-honey)]"
                        />
                        <span>{{ site.text('form_consent_label', 'Quiero recibir novedades de HIVEDFOOD.') }}</span>
                    </label>

                    <div class="mt-7 flex flex-wrap gap-3">
                        <BaseButton
                            variant="primary"
                            :loading="quoter.submitting"
                            trailing-icon="arrow-right"
                            @click="quoter.submit(sendEmail)"
                        >
                            Guardar y solicitar propuesta
                        </BaseButton>
                        <BaseButton v-if="whatsappUrl" :href="whatsappUrl" variant="ghost" icon="whatsapp">
                            Asesoría por WhatsApp
                        </BaseButton>
                    </div>

                    <p v-if="quoter.submitError" class="mt-5 rounded-xl bg-coral-soft px-4 py-3 text-sm font-medium text-coral-dark">
                        {{ quoter.submitError }}
                    </p>
                </div>

                <!-- Confirmación -->
                <div v-else key="done" class="rounded-[1.5rem] border border-honey/30 bg-honey/10 p-7">
                    <div class="flex items-start gap-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-honey text-charcoal">
                            <IconGlyph name="check-badge" :size="24" />
                        </span>
                        <div>
                            <h4 class="text-xl text-ivory">Cotización registrada</h4>
                            <p class="mt-2 text-sm text-ivory/70">
                                Tu referencia es
                                <strong class="font-sans font-extrabold text-honey">{{ submitted.code }}</strong>.
                                La guardamos y nuestro equipo comercial la revisará contigo.
                            </p>
                        </div>
                    </div>

                    <div class="mt-7 flex flex-wrap gap-3">
                        <BaseButton :href="submitted.pdf_url" variant="primary" icon="download">
                            Descargar PDF
                        </BaseButton>
                        <BaseButton v-if="whatsappUrl" :href="whatsappUrl" variant="ghost" icon="whatsapp">
                            Hablar con un asesor
                        </BaseButton>
                    </div>

                    <div class="mt-7 border-t border-white/10 pt-6">
                        <label class="hf-label text-ivory" for="q-resend">Enviar la cotización a otro correo</label>
                        <div class="flex flex-wrap gap-2">
                            <input
                                id="q-resend"
                                v-model="emailTo"
                                type="email"
                                class="hf-field max-w-xs flex-1"
                                placeholder="correo@negocio.com"
                            />
                            <BaseButton
                                variant="dark"
                                :loading="quoter.emailing"
                                icon="mail"
                                @click="quoter.emailQuotation(emailTo)"
                            >
                                Enviar
                            </BaseButton>
                        </div>
                        <p v-if="quoter.emailMessage" class="mt-3 text-sm text-ivory/70">{{ quoter.emailMessage }}</p>
                    </div>

                    <button
                        type="button"
                        class="mt-7 font-sans text-sm font-bold text-honey underline-offset-4 hover:underline"
                        @click="quoter.reset()"
                    >
                        Configurar otra plataforma
                    </button>
                </div>
            </Transition>
        </div>
    </div>
</template>

<style scoped>
.swap-enter-active,
.swap-leave-active {
    transition:
        opacity 0.3s ease,
        transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.swap-enter-from {
    opacity: 0;
    transform: translateY(12px);
}

.swap-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>

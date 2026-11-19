<script setup>
import { storeToRefs } from 'pinia';
import { useQuoterStore } from '../../stores/quoter';
import { useSiteStore } from '../../stores/site';
import IconGlyph from '../ui/IconGlyph.vue';

const quoter = useQuoterStore();
const site = useSiteStore();
const { quoter: config } = storeToRefs(site);

function fieldError(key) {
    return quoter.submitErrors[`contact.${key}`]?.[0] ?? null;
}
</script>

<template>
    <div>
        <h3 class="text-xl text-ivory md:text-2xl">
            {{ site.text('quoter_business_title', 'Cuéntanos de tu negocio') }}
        </h3>
        <p v-if="site.text('quoter_business_description')" class="mt-2 text-sm text-ivory/60">
            {{ site.text('quoter_business_description') }}
        </p>

        <!-- Honeypot: invisible para una persona, irresistible para un bot. -->
        <div class="absolute h-0 w-0 overflow-hidden opacity-0" aria-hidden="true">
            <label for="quoter-hp">No completar</label>
            <input id="quoter-hp" v-model="quoter.contact.website" type="text" tabindex="-1" autocomplete="off" />
        </div>

        <div class="mt-7 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="hf-label text-ivory" for="q-company">Nombre comercial</label>
                <input
                    id="q-company"
                    v-model="quoter.contact.company"
                    type="text"
                    class="hf-field"
                    autocomplete="organization"
                    placeholder="Ej. Burger House"
                />
            </div>

            <div>
                <label class="hf-label text-ivory" for="q-name">Nombre del contacto *</label>
                <input id="q-name" v-model="quoter.contact.name" type="text" class="hf-field" autocomplete="name" required />
                <p v-if="fieldError('name')" class="mt-1.5 text-xs font-semibold text-coral">{{ fieldError('name') }}</p>
            </div>

            <div>
                <label class="hf-label text-ivory" for="q-email">Correo electrónico *</label>
                <input id="q-email" v-model="quoter.contact.email" type="email" class="hf-field" autocomplete="email" required />
                <p v-if="fieldError('email')" class="mt-1.5 text-xs font-semibold text-coral">{{ fieldError('email') }}</p>
            </div>

            <div>
                <label class="hf-label text-ivory" for="q-phone">Teléfono</label>
                <input id="q-phone" v-model="quoter.contact.phone" type="tel" class="hf-field" autocomplete="tel" />
            </div>

            <div>
                <label class="hf-label text-ivory" for="q-city">Ciudad</label>
                <input id="q-city" v-model="quoter.contact.city" type="text" class="hf-field" autocomplete="address-level2" />
            </div>

            <div>
                <label class="hf-label text-ivory" for="q-branches">Número de sucursales</label>
                <input
                    id="q-branches"
                    type="number"
                    class="hf-field [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none"
                    min="1"
                    max="200"
                    :value="quoter.scalability.branches"
                    @change="quoter.setScale('branches', $event.target.value)"
                />
                <p class="mt-1.5 text-xs text-ivory/45">La primera sucursal va incluida en todos los paquetes.</p>
            </div>
        </div>

        <!-- Tipo de negocio -->
        <p class="mt-9 hf-label text-ivory">Tipo de negocio *</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <button
                v-for="type in config.business_types"
                :key="type.id"
                type="button"
                :class="[
                    'flex items-center gap-3.5 rounded-2xl border p-4 text-left transition-all duration-300',
                    quoter.businessTypeId === type.id
                        ? 'border-honey bg-honey/12 ring-1 ring-honey'
                        : 'border-white/12 hover:border-white/30 hover:bg-white/[0.04]',
                ]"
                :aria-pressed="quoter.businessTypeId === type.id"
                @click="quoter.selectBusinessType(type.id)"
            >
                <span
                    :class="[
                        'grid h-11 w-11 shrink-0 place-items-center rounded-xl transition-colors',
                        quoter.businessTypeId === type.id ? 'bg-honey text-charcoal' : 'bg-white/8 text-ivory/70',
                    ]"
                >
                    <IconGlyph :name="type.icon || 'restaurant'" :size="20" />
                </span>

                <span class="min-w-0">
                    <span class="block font-sans text-sm font-bold text-ivory">{{ type.name }}</span>
                    <span v-if="type.short_description" class="mt-0.5 block text-xs leading-snug text-ivory/55">
                        {{ type.short_description }}
                    </span>
                </span>
            </button>
        </div>

        <div v-if="quoter.businessType?.allow_free_text" class="mt-5 max-w-md">
            <label class="hf-label text-ivory" for="quoter-note">Cuéntanos qué tipo de negocio es</label>
            <input
                id="quoter-note"
                v-model="quoter.businessTypeNote"
                type="text"
                class="hf-field"
                placeholder="Ej. food truck, panadería, dark kitchen…"
                maxlength="160"
            />
        </div>
    </div>
</template>

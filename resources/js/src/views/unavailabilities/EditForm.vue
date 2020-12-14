<template>
    <vs-prompt
        title="Edition d'une indisponibilité"
        accept-text="Modifier"
        cancel-text="Annuler"
        button-cancel="border"
        @cancel="init"
        @accept="submitItem"
        @close="init"
        :is-valid="validateForm"
        :active.sync="activePrompt"
    >
        <div>
            <form autocomplete="off">
                <div class="vx-row">
                    <div class="vx-col w-full">
                        <v-select
                                v-validate="'required'"
                                name="reason"
                                label="name"
                                :multiple="false"
                                v-model="itemLocal.reason"
                                :reduce="name => name.name"
                                class="w-full mt-2 mb-2"
                                autocomplete
                                :options="reasons"
                            >
                                <template #header>
                                    <div style="opacity: .8 font-size: .85rem">
                                        Motif
                                    </div>
                                </template>
                                <template #option="reason">
                                    <span>{{ `${reason.name}` }}</span>
                                </template>
                            </v-select>
                            <vs-input
                                v-if="itemLocal.reason === 'Autre...'"
                                name="custom_reason"
                                class="w-full mb-4 mt-6"
                                placeholder="Motif personnalisé"
                                v-model="custom_reason"
                                v-validate="'required'"
                                :color="
                                    !errors.has('custom_reason')
                                        ? 'success'
                                        : 'danger'
                                "
                            />
                        <span
                            class="text-danger text-sm"
                            v-show="errors.has('reason')"
                            >{{ errors.first("reason") }}</span
                        >
                        <flat-pickr
                            v-if="( itemLocal.reason == 'Autre...' && custom_reason !== '' ) || ( itemLocal.reason !== '' && itemLocal.reason !== 'Autre...' )"
                            name="starts_at"
                            class="w-full mb-4 mt-5"
                            :config="configStartsAtDateTimePicker"
                            v-model="itemLocal.starts_at"
                            placeholder="Date de début"
                            @on-change="onStartsAtChange"
                        />
                        <flat-pickr
                            v-if="( itemLocal.reason == 'Autre...' && custom_reason !== '' ) || ( itemLocal.reason !== '' && itemLocal.reason !== 'Autre...' ) && itemLocal.starts_at"
                            name="ends_at"
                            class="w-full mb-4 mt-5"
                            :config="configEndsAtDateTimePicker"
                            v-model="itemLocal.ends_at"
                            placeholder="Date de fin"
                            @on-change="onEndsAtChange"
                        />
                    </div>
                </div>
            </form>
        </div>
    </vs-prompt>
</template>

<script>
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";
import moment from "moment";
import vSelect from "vue-select";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    components: {
        flatPickr,
        vSelect
    },
    props: {
        itemId: {
            type: Number,
            required: true
        }
    },
    data() {
        const itemLocal = Object.assign(
            {},
            this.$store.getters["unavailabilityManagement/getItem"](this.itemId)
        );
        return {
            itemLocal: itemLocal,

            configStartsAtDateTimePicker: {
                disableMobile: "true",
                enableTime: true,
                locale: FrenchLocale,
                minDate: itemLocal.starts_at,
                maxDate: null
            },
            configEndsAtDateTimePicker: {
                disableMobile: "true",
                enableTime: true,
                locale: FrenchLocale,
                minDate: itemLocal.starts_at
            },
            custom_reason: "",
            reasons: [
                { name: "Utilisation heures suplémentaires" },
                { name: "Réunion" },
                { name: "Rendez-vous" },
                { name: "Congés payés" },
                { name: "Période de cours" },
                { name: "Arrêt de travail" },
                { name: "Autre..." },
            ],
        };
    },
    computed: {
        activePrompt: {
            get() {
                return this.itemId && this.itemId > 0 ? true : false;
            },
            set(value) {
                this.$store
                    .dispatch("unavailabilityManagement/editItem", {})
                    .then(() => {})
                    .catch(err => {
                        console.error(err);
                    });
            }
        },
        validateForm() {
            if (this.itemLocal.reason === "Autre...") {
                return (
                    !this.errors.any() &&
                    this.itemLocal.starts_at &&
                    this.itemLocal.ends_at &&
                    this.itemLocal.reason !== "" &&
                    this.custom_reason !== ""
                );
            } else {
                return (
                    !this.errors.any() &&
                    this.itemLocal.starts_at &&
                    this.itemLocal.ends_at &&
                    this.itemLocal.reason !== ""
                );
            }
        }
    },
    methods: {
        init() {
            this.itemLocal = Object.assign(
                {},
                this.$store.getters["unavailabilityManagement/getItem"](
                    this.itemId
                )
            );

            this.configStartsAtDateTimePicker = {
                disableMobile: "true",
                enableTime: true,
                locale: FrenchLocale,
                minDate: this.itemLocal.starts_at,
                maxDate: null
            };
            this.configEndsAtDateTimePicker = {
                disableMobile: "true",
                enableTime: true,
                locale: FrenchLocale,
                minDate: this.itemLocal.starts_at
            };
        },
        onStartsAtChange(selectedDates, dateStr, instance) {
            this.$set(this.configEndsAtDateTimePicker, "minDate", dateStr);
        },
        onEndsAtChange(selectedDates, dateStr, instance) {
            this.$set(this.configStartsAtDateTimePicker, "maxDate", dateStr);
        },
        submitItem() {
            const item = JSON.parse(JSON.stringify(this.itemLocal));
            item.starts_at = moment(item.starts_at).format("YYYY-MM-DD HH:mm");
            item.ends_at = moment(item.ends_at).format("YYYY-MM-DD HH:mm");
            this.$store
                .dispatch("unavailabilityManagement/updateItem", item)
                .then(() => {
                    this.$vs.loading.close();
                    this.$vs.notify({
                        title: "Modification d'une indisponibilité",
                        text: `Indisponibilité modifiée avec succès`,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "success"
                    });
                })
                .catch(error => {
                    this.$vs.loading.close();
                    this.$vs.notify({
                        title: "Error",
                        text: error.message,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "danger"
                    });
                });
        }
    }
};
</script>

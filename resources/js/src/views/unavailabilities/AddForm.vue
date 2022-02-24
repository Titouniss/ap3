<template>
    <div class="p-3 mb-4 mr-4">
        <vs-button class="items-center p-3 w-full" @click="activePrompt = true" >
            Ajouter une indisponibilité
        </vs-button>
        <vs-prompt
            title="Ajouter une indisponibilité"
            accept-text="Ajouter"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="clearFields"
            @accept="addUnavailability"
            @close="clearFields"
            :is-valid="validateForm"
            :active.sync="activePrompt"
            
        >
            <div>
                <form autocomplete="off" v-on:submit.prevent>
                    <div class="vx-row">
                        <div class="vx-col w-full">
                            <simple-select
                                required
                                class="my-2"
                                header="Motif"
                                label="name"
                                v-model="itemLocal.reason"
                                :options="reasons"
                                :reduce="item => item.name"
                                v-if="Overtimes"
                            />
                                <div
                                v-if="itemLocal.reason =='Utilisation heures supplémentaires' && this.totalOvertimes<0"
                                class="text-danger text-sm"
                            >
                                Attention, vos heures supplémentaires sont dans le négatif
                            </div>
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
                            <flat-pickr
                                v-if="
                                    (itemLocal.reason === 'Autre...' &&
                                        custom_reason !== '') ||
                                        (itemLocal.reason !== '' &&
                                            itemLocal.reason !== 'Autre...')
                                "
                                name="starts_at"
                                class="w-full mb-4 mt-5"
                                :config="configStartsAtDateTimePicker"
                                v-model="itemLocal.starts_at"
                                placeholder="Date de début"
                                @on-change="onStartsAtChange"
                            />
                            <flat-pickr
                                v-if="
                                    (itemLocal.reason === 'Autre...' &&
                                        custom_reason !== '') ||
                                        (itemLocal.reason !== '' &&
                                            itemLocal.reason !== 'Autre...' &&
                                            itemLocal.starts_at &&
                                            itemLocal.reason !== 'Jours fériés')
                                "
                                name="ends_at"
                                class="w-full mb-2 mt-5"
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
    </div>
</template>

<script>
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import flatPickr from "vue-flatpickr-component";
import SimpleSelect from "@/components/inputs/selects/SimpleSelect.vue";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";
import moment from "moment";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    components: {
        flatPickr,
        SimpleSelect
    },
    props: {
        id_user: {
            required: true
        },
        filterParams:
        {
            required: false
        },
        fetchOvertimes:{},
        workHours: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            activePrompt: false,
            totalOvertimes: "",
            itemLocal: {
                user_id: null,
                starts_at: null,
                ends_at: null,
                reason: ""
                
            },
           
            custom_reason: "",
            reasons: [
                { name: "Utilisation heures supplémentaires" },
                { name: "Jours fériés" },
                { name: "Rendez-vous privé" },
                { name: "Congés payés" },
                { name: "Période de cours" },
                { name: "Arrêt de travail" },
                /*{ name: "Autre..." }*/
            ],

            configStartsAtDateTimePicker: {
                disableMobile: "true",
                enableTime: true,
                locale: FrenchLocale,
                minDate: null,
                maxDate: null,
                defaultHour: 9
            },
            configEndsAtDateTimePicker: {
                disableMobile: "true",
                enableTime: true,
                locale: FrenchLocale,
                minDate: null,
                defaultHour: 12
            }
        };
    },
    computed: {
        Overtimes()
        {         
         if(this.activePrompt ==true)
            {  
            const item = JSON.parse(JSON.stringify(this.itemLocal));
            item.user_id = this.id_user;
  
            return(
             this.$store
                    .dispatch(
                        "dealingHoursManagement/getOvertimes",
                        this.id_user
                    )
                    .then(data => {
                        if (data && data.status === 200) {
                            this.overtimes = data.data.success.overtimes;
                            this.totalOvertimes = this.overtimes;
                        }
                    })
                );
            }
           
    
   
        },
        validateForm() {
            /*if (this.itemLocal.reason === "Autre...") {
                return (
                    !this.errors.any() &&
                    this.itemLocal.starts_at &&
                    this.itemLocal.ends_at &&
                    this.itemLocal.reason !== "" &&
                    this.custom_reason !== ""
                );
            } else*/ if (this.itemLocal.reason === "Jours fériés") {
                this.$set(
                    this.configStartsAtDateTimePicker,
                    "enableTime",
                    false
                );
                return (
                    !this.errors.any() &&
                    this.itemLocal.starts_at &&
                    this.itemLocal.reason !== ""
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
        
        clearFields() {
            Object.assign(this.itemLocal, {
                user_id: null,
                starts_at: null,
                ends_at: null,
                reason: "",
                overtimes: null
            });
            this.custom_reason = "";
            Object.assign(this.configStartsAtDateTimePicker, {
                disableMobile: "true",
                enableTime: true,
                locale: FrenchLocale,
                minDate: null,
                maxDate: null,
                defaultHour: 9
            });
            Object.assign(this.configEndsAtDateTimePicker, {
                disableMobile: "true",
                enableTime: true,
                locale: FrenchLocale,
                minDate: null,
                defaultHour: 12
            });
        },
        onStartsAtChange(selectedDates, dateStr, instance) {
            this.$set(this.configEndsAtDateTimePicker, "minDate", dateStr);

            if (
                (dateStr && !this.itemLocal.starts_at) ||
                moment(dateStr).format("dd/MM/yyyy") !==
                    moment(this.itemLocal.starts_at).format("dd/MM/yyyy")
            ) {
                if (this.workHours && this.workHours.length > 0) {
                    const day = this.workHours.find(
                        wh =>
                            wh.is_active &&
                            wh.day === moment(dateStr).format("dddd")
                    );
                    if (day && day.morning_starts_at) {
                        const [hour, minute] = day.morning_starts_at.split(":");
                        this.itemLocal.starts_at = moment(dateStr)
                            .hours(parseInt(hour))
                            .minutes(parseInt(minute))
                            .toDate();
                    }
                }
            }
        },
        onEndsAtChange(selectedDates, dateStr, instance) {
            this.$set(this.configStartsAtDateTimePicker, "maxDate", dateStr);

            if (
                dateStr &&
                this.itemLocal.starts_at &&
                moment(dateStr).format("dd/MM/yyyy") !==
                    moment(this.itemLocal.ends_at).format("dd/MM/yyyy")
            ) {
                if (
                    moment(dateStr).format("dd/MM/yyyy") ===
                    moment(this.itemLocal.starts_at).format("dd/MM/yyyy")
                ) {
                    const startsAt = moment(this.itemLocal.starts_at);
                    this.itemLocal.ends_at = moment(dateStr)
                        .hours(startsAt.hours() + 1)
                        .minutes(startsAt.minutes())
                        .toDate();
                } else {
                    this.itemLocal.ends_at = moment(dateStr)
                        .hours(12)
                        .minutes(0)
                        .toDate();
                }
            }
        },
        addUnavailability() {
            this.$validator.validateAll().then(result => {
                if (result) {
                    const item = JSON.parse(JSON.stringify(this.itemLocal));
                    /*if (this.itemLocal.reason === "Autre...") {
                        item.reason = this.custom_reason;
                    }*/
                    if (this.itemLocal.reason === "Jours fériés") {
                        item.ends_at = moment(item.starts_at).format(
                            "YYYY-MM-DD HH:mm"
                        );
                    } else {
                        item.ends_at = moment(item.ends_at).format(
                            "YYYY-MM-DD HH:mm"
                        );
                    }
                    item.starts_at = moment(item.starts_at).format(
                        "YYYY-MM-DD HH:mm"
                    );

                    item.user_id = this.id_user;
               
                    this.$store
                        .dispatch("unavailabilityManagement/addItem", item)
                        .then(data => {
                            this.fetchOvertimes();
                            this.$emit("on-submit", data);
                            this.$vs.notify({
                                title: "Ajout d'une indisponibilité",
                                text: `Indisponibilité ajoutée avec succès`,
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "success"
                            });
                        })
                        .catch(error => {
                            this.$vs.notify({
                                title: "Erreur",
                                text: error.message,
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "danger",
                                time: 10000
                            });
                        })
                        .finally(() => {
                            this.$vs.loading.close();
                            this.clearFields();
                        });
                }
            });
        }
    },
    mounted() {     
        if (
            this.$store.state.AppActiveUser.is_admin ||
            this.$store.state.AppActiveUser.is_manager
        ) {
            this.reasons.unshift({ name: "Heures supplémentaires payées" });
        }
    }
    
};
</script>

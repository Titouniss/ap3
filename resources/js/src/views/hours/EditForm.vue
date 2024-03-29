<template>
    <vs-prompt
        title="Edition"
        accept-text="Modifier"
        cancel-text="Annuler"
        button-cancel="border"
        @cancel="init"
        @accept="submitHour"
        @close="init"
        :is-valid="validateForm"
        :active.sync="activePrompt"
    >
        <div>
            <form autocomplete="off" v-submit.prevent>
                <div class="vx-row">
                    <div class="vx-col w-full">
                        <infinite-select
                            required
                            header="Projet"
                            model="project"
                            label="name"
                            v-model="itemLocal.project_id"
                            :filters="projectsFilter"
                        />
                        <infinite-select
                            required
                            header="Tâche"
                            model="task"
                            label="name"
                            v-model="itemLocal.task_id"
                            :filters="tasksFilter"
                        />
                        <p class="mt-5">Date</p>
                        <flat-pickr
                            v-validate="'required'"
                            name="startDate"
                            :config="configDatePicker()"
                            class="w-full"
                            v-model="itemLocal.date"
                            :color="
                                !errors.has('startDate') ? 'success' : 'danger'
                            "
                        />
                        <span
                            class="text-danger text-sm"
                            v-show="errors.has('startDate')"
                        >
                            {{ errors.first("startDate") }}
                        </span>

                        <div class="vx-row">
                            <div class="vx-col flex-1">
                                <p class="mt-5">Heure de début</p>
                                <flat-pickr
                                    v-validate="'required'"
                                    name="startHour"
                                    :config="configStartHourPicker"
                                    class="w-full"
                                    v-model="itemLocal.startHour"
                                    :color="
                                        !errors.has('startHour')
                                            ? 'success'
                                            : 'danger'
                                    "
                                />
                                <span
                                    class="text-danger text-sm"
                                    v-show="errors.has('startHour')"
                                >
                                    {{ errors.first("startHour") }}
                                </span>
                            </div>
                            <div class="vx-col flex-1">
                                <p class="mt-5">Heure de fin</p>
                                <flat-pickr
                                    v-validate="'required'"
                                    name="endHour"
                                    :config="configEndHourPicker"
                                    class="w-full"
                                    v-model="itemLocal.endHour"
                                    :color="
                                        !errors.has('endHour')
                                            ? 'success'
                                            : 'danger'
                                    "
                                />
                                <span
                                    class="text-danger text-sm"
                                    v-show="errors.has('endHour')"
                                >
                                    {{ errors.first("endHour") }}
                                </span>
                            </div>
                        </div>

                        <p class="mt-5">Description</p>
                        <vs-textarea
                            class="w-full mt-4"
                            rows="5"
                            label="Description"
                            v-model="itemLocal.description"
                            name="description"
                            v-validate="'max:1500'"
                        />
                    </div>
                </div>
            </form>
        </div>
        <vs-row class="mt-5" vs-type="flex" vs-justify="flex-end">
            <vs-button
                @click="confirmDeleteHour(itemLocal.id)"
                color="danger"
                type="filled"
            >
                Supprimer l'horaire
            </vs-button>
        </vs-row>
    </vs-prompt>
</template>

<script>
// register custom messages
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
Validator.localize("fr", errorMessage);
var model = "tâche";
var modelPlurial = "tâches";

import moment from "moment";
import InfiniteSelect from "@/components/inputs/selects/InfiniteSelect";

// FlatPickr
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

export default {
    components: {
        flatPickr,
        InfiniteSelect
    },
    props: {
        itemId: {
            type: Number,
            required: true
        }
    },
    data() {
        return {
            itemLocal: {},
            workareasSkillsData: {},
            deleteWarning: false,
            configDatePicker: () => ({
                disableMobile: "true",
                enableTime: false,
                locale: FrenchLocale,
                dateFormat: "Y-m-d",
                altFormat: "j F Y",
                altInput: true
            })
        };
    },
    computed: {
        configStartHourPicker: {
            get() {
                return {
                    disableMobile: "true",
                    enableTime: true,
                    locale: FrenchLocale,
                    noCalendar: true,
                    dateFormat: "H:i",
                    altFormat: "H:i",
                    altInput: true,
                    maxTime: this.itemLocal.endHour
                        ? this.itemLocal.endHour.split(":")[1] === "00"
                            ? moment(this.itemLocal.endHour, "HH:mm")
                                  .subtract(1, "h")
                                  .set("m", 55)
                                  .format("HH:mm")
                            : moment(this.itemLocal.endHour, "HH:mm")
                                  .subtract(5, "m")
                                  .format("HH:mm")
                        : null
                };
            },
            set(value) {
                return value;
            }
        },
        configEndHourPicker: {
            get() {
                return {
                    disableMobile: "true",
                    enableTime: true,
                    locale: FrenchLocale,
                    noCalendar: true,
                    dateFormat: "H:i",
                    altFormat: "H:i",
                    altInput: true,
                    minTime: this.itemLocal.startHour
                        ? this.itemLocal.startHour.split(":")[1] === "55"
                            ? moment(this.itemLocal.startHour, "HH:mm")
                                  .set("m", 0)
                                  .add(1, "h")
                                  .format("HH:mm")
                            : moment(this.itemLocal.startHour, "HH:mm")
                                  .add(5, "m")
                                  .format("HH:mm")
                        : null,
                    defaultHour: this.updateEndHour
                };
            },
            set(value) {
                return value;
            }
        },
        activePrompt: {
            get() {
                if (this.itemId && this.itemId > -1) {
                    this.itemLocal = this.$store.getters[
                        "hoursManagement/getItem"
                    ](this.itemId);
                }

                return this.itemId &&
                    this.itemId > -1 &&
                    this.deleteWarning === false
                    ? true
                    : false;
            },
            set(value) {
                this.$store
                    .dispatch("hoursManagement/editItem", {})
                    .then(() => {})
                    .catch(err => {
                        console.error(err);
                    });
            }
        },
        validateForm() {
            return (
                !this.errors.any() &&
                this.itemLocal.project_id !== "" &&
                this.itemLocal.task_id &&
                this.itemLocal.task_id !== "" &&
                this.itemLocal.user_id !== "" &&
                this.itemLocal.date !== "" &&
                this.itemLocal.startHour !== "" &&
                this.itemLocal.endHour !== ""
            );
        },
        projectsFilter() {
            return {
                company_id: this.itemLocal.user.company_id || 0, 
                status: "doing"
            };
        },
        tasksFilter() {
            return {
                project_id: this.itemLocal.project_id || 0
            };
        },
    },
    methods: {
        init() {
            this.itemLocal = this.$store.getters[
                "hoursManagement/getSelectedItem"
            ];
        },
        submitHour() {
            var itemToSave = {};

            //Parse new item to update task
            var itemToSave = {
                id: this.itemLocal.id,
                date: this.itemLocal.date,
                description: this.itemLocal.description,
                user_id: this.itemLocal.user_id,
                project_id: this.itemLocal.project_id,
                task_id: this.itemLocal.task_id,
                start_at: this.itemLocal.date + " " + this.itemLocal.startHour,
                end_at: this.itemLocal.date + " " + this.itemLocal.endHour
            };

            this.$store
                .dispatch("hoursManagement/updateItem", itemToSave)
                .then(data => {
                    this.$vs.notify({
                        title: "Modification d'un horaire",
                        text: `Horaire modifié avec succès`,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "success"
                    });
                })
                .catch(err => {
                    this.$vs.notify({
                        title: "Une erreur est survenue",
                        text: err.message,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "danger"
                    });
                })
                .finally(() => {
                    this.$vs.loading.close();
                });

            this.$store.dispatch("hoursManagement/editItem", {});
        },
        confirmDeleteHour(idHour) {
            this.deleteWarning = true;
            this.$vs.dialog({
                type: "confirm",
                color: "danger",
                title: "Confirmer suppression",
                text: `Vous allez supprimer la saisie horaire "${this.itemLocal
                    .date +
                    " " +
                    this.itemLocal.startHour +
                    " -> " +
                    this.itemLocal.endHour}"`,
                accept: this.deleteHour,
                cancel: this.keepHour,
                acceptText: "Supprimer",
                cancelText: "Annuler"
            });
        },
        keepHour() {
            this.deleteWarning = false;
        },
        deleteHour() {
            this.deleteWarning = false;
            this.$store
                .dispatch("hoursManagement/removeItems", [this.itemLocal.id])
                .then(data => {
                    this.$vs.notify({
                        title: "Suppression d'un horaire",
                        text: `Horaire supprimé avec succès`,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "success"
                    });
                })
                .catch(err => {
                    this.$vs.notify({
                        title: "Une erreur est survenue",
                        text: err.message,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "danger"
                    });
                })
                .finally(() => {
                    this.$vs.loading.close();
                });

            this.init();
            this.$store.dispatch("hoursManagement/editItem", {});
        }
    },
    created() {
        this.init();
    }
};
</script>

<template>
    <div class="my-2">
        <vs-button
            @click="activePrompt = true"
            class="w-full p-3 mb-4 mr-4"
        >
            Ajouter des heures travaillées
        </vs-button>
        <vs-prompt
            title="Ajout d'heures travaillées"
            accept-text="Ajouter"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="activePrompt = false"
            @accept="submitItem"
            @close="activePrompt = false"
            :is-valid="validateForm"
            :active.sync="activePrompt"
            class="task-compose"
            style="z-index: 99999"
        >
            <div>
                <form autocomplete="off" v-on:submit.prevent>
                    <div class="vx-row">
                        <div class="vx-col w-full my-2">
                            <div class="my-2" v-if="itemLocal.status != 'todo'">
                                <small class="date-label">
                                    Temps passé (en h)
                                </small>
                                <vs-input-number
                                    :min="-(itemLocal.time_spent || 0)"
                                    name="timeSpent"
                                    class="inputNumber"
                                    v-model="itemLocal.current_time_spent"
                                />
                            </div>
                            <div class="my-2" v-if="itemLocal.status != 'todo' && isManager">
                                <infinite-select
                                    header="Utilisateur"
                                    label="lastname"
                                    model="user"
                                    v-model="itemLocal.userTask"
                                    :item-fields="['lastname', 'firstname']"
                                    :item-text="
                                        item => `${item.lastname} ${item.firstname}`
                                    "
                                    :filters="usersFilter"
                                />
                            </div>
                            <div class="my-2" v-if="itemLocal.status != 'todo'">
                                <small class="date-label">
                                    Description
                                </small>
                                <vs-textarea
                                    rows="1"
                                    label="Description travail réalisé"
                                    name="descriptionHours"
                                    class="w-full mb-1 mt-1"
                                    v-model="itemLocal.descriptionHours"
                                    :color="validateForm ? 'success' : 'danger'"
                                />
                            </div>
                            <div class="my-2" v-if="itemLocal.status != 'todo'">
                                <small
                                        class="date-label mb-1"
                                        style="display: block"
                                    >
                                    Date
                                </small>
                                <flat-pickr
                                    :config="configDatePicker"
                                    v-model="itemLocal.dateHours"
                                    placeholder="Date"
                                    class="w-full"
                                />
                            </div>
                            <div class="my-2" v-if="itemLocal.status != 'todo'">
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
                                    :onChange="definedMinEndHour()"
                                />
                                <span
                                    class="text-danger text-sm"
                                    v-show="errors.has('startHour')"
                                    >{{ errors.first("startHour") }}</span
                                >
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </vs-prompt>
    </div>
</template>

<script>
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";
import moment from "moment";
import SimpleSelect from "@/components/inputs/selects/SimpleSelect.vue";
import InfiniteSelect from "@/components/inputs/selects/InfiniteSelect";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

import FileInput from "@/components/inputs/FileInput.vue";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    components: {
        SimpleSelect,
        InfiniteSelect,
        flatPickr,
        FileInput
    },
    props: {
        itemId: {
            type: Number,
            required: true
        },
        status: { type: String},
        current_time_spent: { type: Number },
        refreshData: { type: Function }
    },
    data() {
        const item = JSON.parse(
            JSON.stringify(
                this.$store.getters["taskManagement/getItem"](this.itemId)
            )
        );
        item.status = this.status;
        item.current_time_spent = this.current_time_spent;
        item.userTask = null;
        item.descriptionHours = ""
        item.dateHours = moment().format("YYYY-MM-DD")
        item.startHour= moment().format("HH:mm")
        return {
            configDatePicker: {
                disableMobile: "true",
                enableTime: false,
                dateFormat: "Y-m-d",
                locale: FrenchLocale
            },
            itemLocal: item,
            companyId: item.project.company_id,
            activePrompt: false,
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
                };
            },
            set(value) {
                return value;
            }
        },
        isManager() {
            return this.$store.state.AppActiveUser.is_manager;
        },
        usersFilter() {
            return {
                company_id: this.$store.state.AppActiveUser.company_id || 0
            };
        },
        validateForm() {
            const {
                userTask,
                descriptionHours,
                dateHours,
                startHour
            } = this.itemLocal;

            let validFormAddHours=true;
            if(descriptionHours=="" || dateHours==null || (this.isManager && userTask==null)){
                validFormAddHours=false;
            }

            return (
                !this.errors.any() &&
                validFormAddHours
            );
        },
    },
    methods: {
        moment: function(date) {
            moment.locale("fr");
            return moment(date, "YYYY-MM-DD HH:mm:ss").format("DD MMMM YYYY");
        },
        momentTime: function(date) {
            moment.locale("fr");
            return moment(date, "YYYY-MM-DD HH:mm:ss").format("HH:mm");
        },
        definedMinEndHour() {
            if (this.itemLocal !== null && this.itemLocal.startHour !== null) {
                let result =
                    parseInt(this.itemLocal.startHour.split(":")[0]) + 1;
                if (result !== NaN) {
                    this.configEndHourPicker = result;
                }
            }
        },
        submitItem() {
            if (!this.validateForm) return;
            const item = JSON.parse(JSON.stringify(this.itemLocal));
            item.time_spent = this.itemLocal.current_time_spent
            const payload = {
                startHour: item.startHour,
                endHour: moment(item.startHour, "HH:mm").add(item.time_spent, "hours").format("HH:mm"),
                date: item.dateHours,
                description: item.descriptionHours,
                project_id: item.project_id,
                task_id: item.id,
                user_id: item.userTask ? item.userTask : this.$store.state.AppActiveUser.id
            }
            payload.start_at = payload.date + " " + payload.startHour;
            payload.end_at = payload.date + " " + payload.endHour;
            this.$store
                .dispatch("hoursManagement/addItem", payload)
                .then(data => {
                    if (this.refreshData) {
                        this.refreshData();
                    }
                    this.$vs.notify({
                        title: "Ajout d'un horaire",
                        text: `Horaire ajouté avec succès`,
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
                        color: "danger"
                    });
                })
                .finally(() => {
                    this.$vs.loading.close();
                });
        },
    }
};
</script>

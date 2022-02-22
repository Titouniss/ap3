<template>
    <div>
        <vs-button
            v-if="
                project_data.status == 'todo' && project_data.tasks.length > 0
            "
            type="gradient"
            color="#3ad687"
            gradient-color-secondary="#175435"
            icon-pack="feather"
            icon="icon-play"
            @click="display"
        >
            Démarrer le projet
        </vs-button>
        <vs-prompt
            title="Démarrer le projet"
            accept-text="Démarrer"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="clearFields"
            @accept="start"
            @close="clearFields"
            :is-valid="validateForm"
            :active.sync="activePrompt"
        >
            <div>
                <form autocomplete="off" v-if="startIsPossible">
                    <template>
                        <div class="vs-select--label">Date de démarrage</div>
                    </template>
                    <flat-pickr
                        name="starts_at"
                        class="w-full mb-4 mt-5"
                        :config="configStartsAtDateTimePicker"
                        v-model="itemLocal.start_at"
                        placeholder="Saisir une date de démarrage"
                    />
                </form>
                <div v-if="!startIsPossible">
                    <span class="text-danger text-sm"
                        >Démarrage du projet impossible. Merci de modifier la
                        date de livraison du projet.
                    </span>
                </div>
            </div>
        </vs-prompt>
    </div>
</template>

<script>
import flatPickr from "vue-flatpickr-component";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    components: {
        flatPickr
    },
    props: {
        project_data: {
            required: true
        },
        startProject: {
            type: Function
        }
    },
    data() {
        return {
            activePrompt: false,

            itemLocal: {
                start_at: ""
            },
            configStartsAtDateTimePicker: {
                disableMobile: "true",
                enableTime: true,
                locale: FrenchLocale,
                dateFormat: "Y-m-d H:i:ss",
                altFormat: "j F Y H:i:ss",
                altInput: true,
                minDate: new Date(
                    new Date().getFullYear(),
                    new Date().getMonth(),
                    new Date().getDate(),
                    new Date().getHours(),
                    new Date().getMinutes()
                ),
                maxDate: new Date(
                    new Date(this.project_data.date).getFullYear(),
                    new Date(this.project_data.date).getMonth(),
                    new Date(this.project_data.date).getDate() - 1,
                    new Date().getHours(),
                    new Date().getMinutes()
                )
            }
        };
    },
    computed: {
        validateForm() {
            return this.itemLocal.start_at != "";
        },
        startIsPossible() {
            return new Date() < new Date(this.project_data.date);
        }
    },
    methods: {
        display() {
            this.activePrompt = true;
            this.$set(
                this.configStartsAtDateTimePicker,
                "maxDate",
                new Date(
                    new Date(this.project_data.date).getFullYear(),
                    new Date(this.project_data.date).getMonth(),
                    new Date(this.project_data.date).getDate() - 1,
                    new Date().getHours(),
                    new Date().getMinutes()
                ),
            );
        },
        clearFields() {
            Object.assign(this.itemLocal, {
                start_at: ""
            });
        },
        start() {
            console.log(this.itemLocal.start_at);
            this.project_data.start_date = this.itemLocal.start_at;
            this.startProject();
        }
    }
};
</script>
<style>
.vs-tooltip {
    z-index: 99999 !important;
}
</style>

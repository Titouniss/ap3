<template>
    <div v-if="moduleUsesSlug" class="flex flex-row items-center self-end">
        <small class="mr-3">{{ lastSync }}</small>
        <vs-button
            @click="dialogOpen = true"
            type="border"
            icon-pack="feather"
            icon="icon-refresh-cw"
        >
            {{ module.name }}
        </vs-button>

        <vs-prompt
            type="confirm"
            color="primary"
            title="Confirmer"
            @cancel="dialogOpen = false"
            @accept="acceptSync"
            acceptText="Synchroniser"
            cancelText="Annuler"
            :active.sync="dialogOpen"
        >
            <div>
                <div>
                    {{
                        `Êtes vous sûr de vouloir synchroniser les données avec ${this.module.name} ?`
                    }}
                </div>
                <div class="mt-3">
                    <small>
                        Cela pourrait prendre plusieurs minutes.
                    </small>
                </div>
            </div>
        </vs-prompt>
    </div>
</template>

<script>
import moment from "moment";

// Store Module
import moduleModuleManagement from "@/store/module-management/moduleModuleManagement.js";

export default {
    data() {
        return {
            dialogOpen: false
        };
    },
    computed: {
        module() {
            return this.$store.getters.module;
        },
        moduleUsesSlug() {
            return this.$store.getters.moduleUsesSlug(
                this.$route.path.replace("/", "")
            );
        },
        lastSync() {
            return moment(this.module.last_synced_at).fromNow();
        }
    },
    methods: {
        acceptSync() {
            this.$vs.loading();
            this.$store
                .dispatch("moduleManagement/syncItem", this.module)
                .then(response => {
                    this.$vs.notify({
                        title: "Succès",
                        text: `"${this.module.name}" synchronisé avec succès`,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "success"
                    });
                })
                .catch(err => {
                    console.error(err);
                    this.$vs.notify({
                        title: "Erreur",
                        text: error.message,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "danger"
                    });
                })
                .finally(() => this.$vs.loading.close());
        }
    },
    created() {
        if (!moduleModuleManagement.isRegistered) {
            this.$store.registerModule(
                "moduleManagement",
                moduleModuleManagement
            );
            moduleModuleManagement.isRegistered = true;
        }

        moment.updateLocale("fr", {
            relativeTime: {
                future: "dans %s",
                past: "il y a %s",
                s: "quelques secondes",
                ss: "%d secondes",
                m: "une minute",
                mm: "%d minutes",
                h: "une heure",
                hh: "%d heures",
                d: "un jour",
                dd: "%d jours"
            }
        });
        moment.locale("fr");
    },
    beforeDestroy() {
        moduleModuleManagement.isRegistered = false;
        this.$store.unregisterModule("moduleManagement");
    }
};
</script>

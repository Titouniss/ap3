<template>
    <div>
        <vs-button
            @click="activePrompt = true"
            size="small"
            color="success"
            type="gradient"
            icon-pack="feather"
            icon="icon-plus"
        ></vs-button>
        <vs-prompt
            title="Ajouter une gamme au projet"
            accept-text="Ajouter"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="clearFields"
            @accept="addRange"
            @close="clearFields"
            :is-valid="validateForm"
            :active.sync="activePrompt"
        >
            <div>
                <form autocomplete="off">
                    <vx-tooltip
                        style="z-index: 52007"
                        title="Information"
                        color="dark"
                        text="Le préfixe va vous servir pour différencier vos différentes gammes"
                        delay="5000s"
                    >
                        <vs-input
                            icon-pack="feather"
                            icon="icon-info"
                            v-validate="'required|numeric'"
                            name="prefix"
                            class="w-full mb-4 mt-1"
                            placeholder="Préfixe"
                            v-model="itemLocal.prefix"
                            maxlength="5"
                            @input="onPrefixChange"
                        />

                        <infinite-select
                            header="Gamme"
                            model="range"
                            label="name"
                            v-model="itemLocal.rangeId"
                            :filters="{ company_id }"
                        />
                    </vx-tooltip>
                </form>
                <div v-if="rangesData.length === 0" class="mt-12 mb-2">
                    <span label="Compétences" class="msgTxt mt-10">
                        Aucune gammes trouvées.
                    </span>
                    <router-link class="linkTxt" :to="{ path: '/ranges' }">
                        Ajouter une gamme
                    </router-link>
                </div>
            </div>
        </vs-prompt>
    </div>
</template>

<script>
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import InfiniteSelect from "@/components/inputs/selects/InfiniteSelect";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    components: {
        InfiniteSelect
    },
    props: {
        company_id: {
            required: true
        },
        project_id: {
            required: true
        },
        refreshData: {}
    },
    data() {
        return {
            activePrompt: false,

            itemLocal: {
                prefix: "",
                range: ""
            }
        };
    },
    computed: {
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
        validateForm() {
            return this.itemLocal.prefix != "" && this.itemLocal.rangeId;
        },
        rangesData() {
            return this.$store.getters["rangeManagement/getItems"];
        }
    },
    methods: {
        clearFields() {
            Object.assign(this.itemLocal, {
                prefix: "",
                rangeId: ""
            });
        },
        addRange() {
            this.itemLocal.project_id = this.project_id;

            this.$store
                .dispatch(
                    "taskManagement/addItemRange",
                    Object.assign({}, this.itemLocal)
                )
                .then(() => {
                    if (this.refreshData) {
                        this.refreshData();
                    }
                    this.$vs.loading.close();
                    this.$vs.notify({
                        title: "Ajout d'une gamme au projet",
                        text: `"Gamme ajouté avec succès`,
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
        },
        onPrefixChange() {
            this.itemLocal.prefix = this.itemLocal.prefix.toUpperCase();
        }
    }
};
</script>
<style>
.vs-tooltip {
    z-index: 99999 !important;
}
</style>

<template>
    <div class="p-3 mb-4 mr-4">
        <vs-button @click="activePrompt = true" class="w-full">
            Ajouter un pôle de production
        </vs-button>
        <vs-prompt
            title="Ajouter un pôle de production"
            accept-text="Ajouter"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="clearFields"
            @accept="addItem"
            @close="clearFields"
            :is-valid="validateForm"
            :active.sync="activePrompt"
        >
            <div>
                <form autocomplete="off">
                    <div class="vx-row">
                        <div class="vx-col w-full">
                            <vs-input
                                v-validate="'required|max:255'"
                                name="name"
                                class="w-full mb-4 mt-5"
                                placeholder="Nom"
                                v-model="itemLocal.name"
                                :success="
                                    itemLocal.name.length > 0 &&
                                        !errors.has('name')
                                "
                                :danger="errors.has('name')"
                                :danger-text="errors.first('name')"
                            />

                            <div class="ml-1 mb-2">
                                <small>
                                    Nombre d'opérateur maximum
                                </small>
                                <vs-row vs-w="12">
                                    <vs-col vs-w="6">
                                        <vs-input-number
                                            min="1"
                                            max="25"
                                            name="max_users"
                                            class="inputNumber"
                                            v-model="itemLocal.max_users"
                                        />
                                    </vs-col>
                                </vs-row>
                                <span
                                    class="text-danger text-sm"
                                    v-show="errors.has('max_users')"
                                    >{{ errors.first("max_users") }}</span
                                >
                            </div>

                            <div v-if="itemLocal.company_id" class="mt-5">
                                <div v-if="skillsData.length === 0">
                                    <span class="msgTxt">
                                        Aucune compétences trouvées.
                                    </span>
                                    <router-link to="/skills">
                                        <span class="linkTxt">
                                            Ajouter une compétence
                                        </span>
                                    </router-link>
                                </div>
                                <infinite-select
                                    v-else
                                    required
                                    header="Compétences"
                                    label="name"
                                    model="skill"
                                    multiple
                                    v-model="itemLocal.skills"
                                    :filters="skillsFilter"
                                />
                            </div>
                            <div class="vx-row mt-4" v-if="!disabled">
                                <div class="vx-col w-full">
                                    <div class="flex items-end px-3">
                                        <feather-icon
                                            svgClasses="w-6 h-6"
                                            icon="LockIcon"
                                            class="mr-2"
                                        />
                                        <span
                                            class="font-medium text-lg leading-none"
                                        >
                                            Admin
                                        </span>
                                    </div>
                                    <vs-divider />
                                    <div class="w-full mt-5">
                                        <infinite-select
                                            required
                                            header="Société"
                                            label="name"
                                            model="company"
                                            v-model="itemLocal.company_id"
                                            @input="itemLocal.skills = []"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <file-input
                                    :items="uploadedFiles"
                                    :token="token"
                                />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </vs-prompt>
    </div>
</template>

<script>
import InfiniteSelect from "@/components/inputs/selects/InfiniteSelect";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import FileInput from "@/components/inputs/FileInput.vue";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    components: {
        InfiniteSelect,
        FileInput
    },
    data() {
        return {
            activePrompt: false,

            itemLocal: {
                name: "",
                max_users: 1,
                company_id: null,
                skills: []
            },

            token:
                "token_" +
                Math.random()
                    .toString(36)
                    .substring(2, 15),
            uploadedFiles: []
        };
    },
    computed: {
        skillsFilter() {
            return { company_id: this.itemLocal.company_id };
        },
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
        skillsData() {
            return this.$store.getters["skillManagement/getItems"];
        },
        disabled() {
            const user = this.$store.state.AppActiveUser;
            if (this.isAdmin) {
                return false;
            } else {
                this.itemLocal.company_id = this.$store.state.AppActiveUser.company_id;
                return true;
            }
        },
        validateForm() {
            return (
                !this.errors.any() &&
                this.itemLocal.name != "" &&
                this.itemLocal.skills &&
                this.itemLocal.skills.length > 0 &&
                this.itemLocal.company_id != null
            );
        }
    },
    methods: {
        clearFields(deleteFiles = true) {
            if (deleteFiles) {
                this.deleteFiles();
            }
            this.itemLocal = {
                name: "",
                max_users: 1,
                company_id: null,
                skills: []
            };
        },
        addItem() {
            this.$validator.validateAll().then(result => {
                if (result) {
                    const item = JSON.parse(JSON.stringify(this.itemLocal));
                    if (this.uploadedFiles.length) {
                        item.token = this.token;
                    }
                    this.$store
                        .dispatch("workareaManagement/addItem", item)
                        .then(() => {
                            this.$vs.notify({
                                title: "Ajout d'un pôle de production",
                                text: `"${this.itemLocal.name}" ajoutée avec succès`,
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "success"
                            });
                        })
                        .catch(error => {
                            this.$vs.notify({
                                title: "Error",
                                text: error.message,
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "danger"
                            });
                        })
                        .finally(() => {
                            this.clearFields(false);
                            this.$vs.loading.close();
                        });
                }
            });
        },
        deleteFiles() {
            const ids = this.uploadedFiles.map(item => item.id);
            if (ids.length > 0) {
                this.$store
                    .dispatch("documentManagement/removeItems", ids)
                    .then(response => {
                        this.uploadedFiles = [];
                    })
                    .catch(error => {});
            }
        }
    }
};
</script>

<style>
.msgTxt {
    font-size: 0.9em;
    color: #969696;
}
.linkTxt {
    font-size: 0.8em;
    color: #2196f3;
    border-radius: 4px;
    margin: 3px;
    padding: 3px 4px;
    font-weight: 500;
}
.linkTxt:hover {
    cursor: pointer;
    text-transform: underline;
}
</style>

<template>
    <vs-prompt
        title="Editer un pôle de production"
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
            <form autocomplete="off" v-submit.prevent>
                <div class="vx-row">
                    <div class="vx-col w-full">
                        <vs-input
                            v-validate="'required|max:255'"
                            name="name"
                            class="w-full mb-4 mt-5"
                            placeholder="Nom"
                            v-model="itemLocal.name"
                            :success="
                                itemLocal.name.length > 0 && !errors.has('name')
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
                                <div>
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
                                :items="itemLocal.documents"
                                :token="token"
                            />
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </vs-prompt>
</template>

<script>
import InfiniteSelect from "@/components/inputs/selects/InfiniteSelect";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import FileInput from "@/components/inputs/FileInput.vue";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    props: {
        itemId: {
            type: Number,
            required: true
        }
    },
    components: {
        InfiniteSelect,
        FileInput
    },
    data() {
        const item = this.$store.getters["workareaManagement/getItem"](
            this.itemId
        );
        item.skills = item.skills.map(skill => skill.id);
        return {
            itemLocal: item,

            token:
                "token_" +
                Math.random()
                    .toString(36)
                    .substring(2, 15),
            company_id_temps: null
        };
    },
    computed: {
        skillsFilter() {
            return { company_id: this.itemLocal.company_id };
        },
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
        activePrompt: {
            get() {
                return this.itemId && this.itemId > 0 ? true : false;
            },
            set(value) {
                this.$store
                    .dispatch("workareaManagement/editItem", {})
                    .then(() => {})
                    .catch(err => {
                        console.error(err);
                    });
            }
        },
        skillsData() {
            return this.$store.getters["skillManagement/getItems"];
        },
        disabled() {
            const user = this.$store.state.AppActiveUser;
            if (this.isAdmin) {
                return false;
            } else {
                this.itemLocal.company_id = user.company_id;
                return true;
            }
        },
        validateForm() {
            return (
                !this.errors.any() &&
                this.itemLocal.skills &&
                this.itemLocal.skills.length > 0 &&
                this.itemLocal.name != ""
            );
        }
    },
    methods: {
        init() {
            this.deleteFiles();
            this.itemLocal = {};
        },
        submitItem() {
            this.$validator.validateAll().then(result => {
                if (result) {
                    const item = JSON.parse(JSON.stringify(this.itemLocal));
                    item.token = this.token;
                    this.$store
                        .dispatch("workareaManagement/updateItem", item)
                        .then(() => {
                            this.$vs.loading.close();
                            this.$vs.notify({
                                title: "Modification d'un pôle de production",
                                text: `"${this.itemLocal.name}" modifié avec succès`,
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
            });
        },
        deleteFiles() {
            const ids = this.itemLocal.documents
                .filter(item => item.token)
                .map(item => item.id);
            if (ids.length > 0) {
                this.$store
                    .dispatch("documentManagement/removeItems", ids)
                    .catch(error => {});
            }
        }
    }
};
</script>

<!-- =========================================================================================
    File Name: TodoEdit.vue
    Description: Edit todo component
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
    <vs-prompt
        title="Modifier un module"
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
                        <infinite-select
                            header="Société"
                            model="company"
                            label="name"
                            v-model="item.company_id"
                        />
                        <vs-input
                            v-validate="'required|max:255'"
                            label-placeholder="Nom"
                            name="name"
                            class="w-full my-8"
                            v-model="item.name"
                            :success="
                                item.name.length > 0 && errors.has('name')
                            "
                            :danger="errors.has('name')"
                            :danger-text="errors.first('name')"
                        />
                        <div class="w-full flex flex-row justify-between">
                            <div
                                class="flex flex-row justify-center items-center"
                            >
                                <div>Activé</div>
                                <vs-switch
                                    v-model="item.is_active"
                                    icon-pack="feather"
                                    vs-icon-on="icon-check"
                                    vs-icon-off="icon-x"
                                    class="mx-3"
                                />
                            </div>
                            <div class="flex flex-row justify-center">
                                <vs-radio
                                    class="mx-3"
                                    v-model="item.type"
                                    vs-value="sql"
                                >
                                    SQL
                                </vs-radio>
                                <vs-radio
                                    class="mx-3"
                                    v-model="item.type"
                                    vs-value="api"
                                >
                                    API
                                </vs-radio>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </vs-prompt>
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
        itemId: {
            type: Number,
            required: true
        }
    },
    data() {
        return {
            item: {}
        };
    },
    computed: {
        validateForm() {
            return (
                !this.errors.any() &&
                this.item.name != "" &&
                this.item.company_id != null
            );
        },
        activePrompt: {
            get() {
                return this.itemId && this.itemId > 0;
            },
            set(value) {
                this.$store
                    .dispatch("moduleManagement/editItem", {})
                    .catch(err => {
                        console.error(err);
                    });
            }
        }
    },
    methods: {
        init() {
            this.item = this.$store.getters["moduleManagement/getSelectedItem"];
        },
        submitItem() {
            this.$validator.validateAll().then(result => {
                if (result) {
                    const localItem = {
                        id: this.item.id,
                        name: this.item.name,
                        company_id: this.item.company_id,
                        modulable_id: this.item.modulable_id,
                        type: this.item.type,
                        is_active: this.item.is_active
                    };
                    this.$store
                        .dispatch("moduleManagement/updateItem", localItem)
                        .then(() => {
                            this.$vs.notify({
                                title: "Modification d'un module",
                                text: `"${this.item.name}" modifiée avec succès`,
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
                        .finally(() => this.$vs.loading.close());
                }
            });
        }
    },
    created() {
        this.init();
    }
};
</script>

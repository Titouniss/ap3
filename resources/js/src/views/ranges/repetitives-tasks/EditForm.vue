<template>
    <div class="p-3 mb-4 mr-4">
        <vs-prompt
            title="Mofidier une tâche"
            accept-text="Modifier"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="clear"
            @accept="submitItem"
            @close="clear"
            :is-valid="validateForm"
            :active.sync="activePrompt"
            class="task-compose"
        >
            <div>
                <form>
                    <div class="vx-row">
                        <!-- Left -->
                        <div
                            class="vx-col flex-1"
                            style="border-right: 1px solid #d6d6d6;"
                        >
                            <div class="mb-4">
                                <small class="date-label">Ordre</small>
                                <vs-input-number
                                    min="1"
                                    name="order"
                                    class="inputNumber"
                                    v-model="itemLocal.order"
                                />
                            </div>
                            <vs-input
                                v-validate="'required'"
                                name="stepName"
                                class="w-full mb-4 mt-1"
                                placeholder="Intitulé"
                                v-model="itemLocal.name"
                                :color="
                                    !errors.has('stepName')
                                        ? 'success'
                                        : 'danger'
                                "
                            />
                            <span
                                class="text-danger text-sm"
                                v-show="errors.has('stepName')"
                                >{{ errors.first("stepName") }}</span
                            >

                            <div class="my-4">
                                <small class="date-label">Description</small>
                                <vs-textarea
                                    rows="3"
                                    label="Ajouter une description"
                                    name="description"
                                    class="w-full mb-1 mt-1"
                                    v-model="itemLocal.description"
                                />
                            </div>
                        </div>
                        <!-- Right -->
                        <div class="vx-col flex-1">
                            <vs-select
                                label="Compétences"
                                v-on:change="updateWorkareasList"
                                v-model="itemLocal.skills"
                                class="w-full"
                                multiple
                                autocomplete
                                v-validate="'required'"
                                name="skills"
                            >
                                <vs-select-item
                                    :key="index"
                                    :value="item.id"
                                    :text="item.name"
                                    v-for="(item, index) in skillsData"
                                />
                            </vs-select>
                            <span
                                class="text-danger text-sm"
                                v-show="errors.has('skills')"
                                >{{ errors.first("skills") }}</span
                            >

                            <span
                                v-if="
                                    itemLocal.skills.length > 0 &&
                                        workareasDataFiltered.length == 0
                                "
                                class="text-danger text-sm"
                                >Attention, aucun îlot ne possède cette
                                combinaison de compétences</span
                            >

                            <!-- <div v-if="itemLocal.skills.length > 0 && workareasDataFiltered.length > 0">
                      <vs-select name="workarea" label="Ilot" v-model="itemLocal.workarea_id" class="w-full mt-3">
                          <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in workareasDataFiltered" />
                      </vs-select>
                    </div> -->
                            <div class="my-4">
                                <small class="date-label"
                                    >Temps estimé (en h)</small
                                >
                                <vs-input-number
                                    min="1"
                                    name="estimatedTime"
                                    class="inputNumber"
                                    v-model="itemLocal.estimated_time"
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
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    props: {
        itemId: {
            type: Number,
            required: true
        },
        companyId: {
            type: Number,
            required: true
        }
    },
    data() {
        return {
            itemLocal: Object.assign(
                {},
                this.$store.getters["repetitiveTaskManagement/getItem"](
                    this.itemId
                )
            ),
            workareasDataFiltered: []
        };
    },
    computed: {
        validateForm() {
            return (
                !this.errors.any() &&
                this.itemLocal.name != "" &&
                this.itemLocal.order != "" &&
                this.itemLocal.estimated_time != "" &&
                this.itemLocal.skills.length > 0
            );
        },
        activePrompt: {
            get() {
                return this.itemId && this.itemId > 0 ? true : false;
            },
            set(value) {
                this.$store
                    .dispatch("repetitiveTaskManagement/editItem", {})
                    .then(() => {})
                    .catch(err => {
                        console.error(err);
                    });
            }
        },
        workareasData() {
            let $workareasData = this.$store.state.workareaManagement.workareas;
            let $filteredItems = this.filterItemsAdmin($workareasData);
            return $filteredItems;
        },
        skillsData() {
            let $skillsData = this.$store.state.skillManagement.skills;
            this.updateWorkareasList(this.itemLocal.skills);
            return this.filterItemsAdmin($skillsData);
        }
    },
    methods: {
        clear() {
            this.itemLocal = {};
            this.workareasDataFiltered = [];
        },
        submitItem() {
            this.$validator.validateAll().then(result => {
                //this.itemLocal.workarea = this.workareasDataFiltered.find((workarea) => workarea.id === this.itemLocal.workarea_id)

                if (result) {
                    this.$store
                        .dispatch(
                            "repetitiveTaskManagement/updateItem",
                            Object.assign({}, this.itemLocal)
                        )
                        .then(() => {
                            this.$vs.loading.close();
                            this.$vs.notify({
                                title: "Modification d'une étape",
                                text: `"${this.itemLocal.name}" modifiée avec succès`,
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
        updateWorkareasList(ids) {
            this.workareasDataFiltered = this.workareasData.filter(function(
                workarea
            ) {
                for (let i = 0; i < ids.length; i++) {
                    if (
                        workarea.skills.filter(skill => skill.id == ids[i])
                            .length == 0
                    ) {
                        return false;
                    }
                }
                return true;
            });
            if (!this.workareasDataFiltered.length > 0) {
                this.itemLocal.workarea_id = null;
            }
        },
        filterItemsAdmin($items) {
            let $filteredItems = [];
            const user = this.$store.state.AppActiveUser;
            if (user.roles && user.roles.length > 0) {
                if (
                    user.roles.find(
                        r => r.name === "superAdmin" || r.name === "littleAdmin"
                    )
                ) {
                    $filteredItems = $items.filter(
                        item => item.company_id === this.companyId
                    );
                } else {
                    $filteredItems = $items;
                }
            }
            return $filteredItems;
        }
    }
};
</script>
<style>
.con-vs-dialog.task-compose .vs-dialog {
    max-width: 700px;
}
.edit-task-form {
    max-height: 450px;
    overflow-y: auto;
    overflow-x: hidden;
}
.inputNumber {
    justify-content: start;
    max-width: 97px;
}
</style>

<!-- =========================================================================================
  File Name: RoleEdit.vue
  Description: range Edit Page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/range/pixinvent
========================================================================================== -->

<template>
    <div id="page-range-edit">
        <vs-alert
            color="danger"
            title="range Not Found"
            :active.sync="range_not_found"
        >
            <span>La gamme {{ $route.params.id }} est introuvable. </span>
            <span>
                <span>voir </span
                ><router-link
                    :to="{ name: 'ranges' }"
                    class="text-inherit underline"
                    >Toutes les games</router-link
                >
            </span>
        </vs-alert>

        <vx-card v-if="range_data">
            <div slot="no-body" class="tabs-container px-6 pt-6">
                <div class="vx-row">
                    <vs-input
                        class="w-full mt-4"
                        label="Titre"
                        v-model="range_data.name"
                        v-validate="'required'"
                        name="name"
                    />
                    <span
                        class="text-danger text-sm"
                        v-show="errors.has('name')"
                        >{{ errors.first("name") }}</span
                    >
                </div>
                <div class="vx-row">
                    <vs-textarea
                        class="w-full mt-4"
                        rows="5"
                        label="Ajouter description"
                        v-model="range_data.description"
                        name="description"
                    />
                    <span
                        class="text-danger text-sm"
                        v-show="errors.has('description')"
                        >{{ errors.first("description") }}</span
                    >
                </div>
                <!-- Permissions -->
                <div class="vx-row mt-4">
                    <div class="vx-col w-full">
                        <div class="flex items-end px-3">
                            <feather-icon
                                svgClasses="w-6 h-6"
                                icon="PackageIcon"
                                class="mr-2"
                            />
                            <span class="font-medium text-lg leading-none"
                                >Liste des étapes de la gamme</span
                            >
                            <add-form
                                v-if="range_data.company_id != null"
                                :company_id="range_data.company_id"
                            ></add-form>
                        </div>
                        <vs-divider />
                        <vs-table
                            :data="repetitiveTasksData"
                            noDataText="Pas d'étapes à afficher"
                        >
                            <template slot="thead">
                                <vs-th>Ordre</vs-th>
                                <vs-th>Intitulé</vs-th>
                                <vs-th>Compétences</vs-th>
                                <vs-th>Temps</vs-th>
                                <vs-th></vs-th>
                            </template>

                            <template slot-scope="{ data }">
                                <vs-tr
                                    :key="indextr"
                                    v-for="(tr, indextr) in data"
                                >
                                    <vs-td :data="data[indextr].order">
                                        {{ data[indextr].order }}
                                    </vs-td>

                                    <vs-td :data="data[indextr].name">
                                        {{ data[indextr].name }}
                                    </vs-td>

                                    <vs-td :data="data[indextr].skills">{{
                                        data[indextr].skillsNames != ""
                                            ? data[indextr].skillsNames
                                            : "Aucunes"
                                    }}</vs-td>

                                    <vs-td :data="data[indextr].estimated_time">
                                        {{ data[indextr].estimated_time }}
                                    </vs-td>

                                    <vs-td :data="data[indextr]">
                                        <CellRendererActions
                                            :item="data[indextr]"
                                        ></CellRendererActions>
                                    </vs-td>
                                </vs-tr>
                            </template>
                        </vs-table>
                    </div>
                </div>
            </div>
            <!-- Save & Reset Button -->
            <div class="vx-row">
                <div class="vx-col w-full">
                    <div class="mt-8 flex flex-wrap items-center justify-end">
                        <vs-button
                            class="ml-auto mt-2"
                            @click="save_changes"
                            :disabled="!validateForm"
                            >Modifier</vs-button
                        >
                        <vs-button
                            class="ml-4 mt-2"
                            type="border"
                            color="warning"
                            @click="back"
                            >Annuler</vs-button
                        >
                    </div>
                </div>
            </div>
        </vx-card>
        <edit-form
            :itemId="itemIdToEdit"
            :companyId="range_data.company_id"
            v-if="itemIdToEdit"
        />
    </div>
</template>

<script>
import lodash from "lodash";

//Repetitive Task
import AddForm from "./repetitives-tasks/AddForm.vue";
import EditForm from "./repetitives-tasks/EditForm.vue";
import CellRendererActions from "./repetitives-tasks/cell-renderer/CellRendererActions.vue";

// Store Module
import moduleRangeManagement from "@/store/range-management/moduleRangeManagement.js";
import moduleWorkareaManagement from "@/store/workarea-management/moduleWorkareaManagement.js";
import moduleSkillManagement from "@/store/skill-management/moduleSkillManagement.js";
import moduleRepetitiveTaskManagement from "@/store/repetitives-task-management/moduleRepetitiveTaskManagement.js";
import moduleDocumentManagement from "@/store/document-management/moduleDocumentManagement.js";

var model = "range";
var modelPlurial = "ranges";
var modelTitle = "Gamme";

export default {
    components: {
        AddForm,
        EditForm,
        CellRendererActions
    },
    data() {
        return {
            range_data: null,
            selected: [],
            range_not_found: false
        };
    },
    computed: {
        repetitiveTasksData() {
            return this.$store.getters["repetitiveTaskManagement/getItems"]
                .map(task => {
                    if (task.skills.length > 0) {
                        let skillsNames = "";
                        task.skills.forEach(skill_id => {
                            const skills = this.$store.state.skillManagement
                                .skills;
                            let skill = skills.find(
                                s => s.id == parseInt(skill_id)
                            ).name;
                            skillsNames = skill
                                ? skillsNames == ""
                                    ? skill
                                    : skillsNames + " | " + skill
                                : skillsNames;
                        });
                        task.skillsNames = skillsNames;
                    }
                    return task;
                })
                .sort((a, b) => a.order - b.order);
        },
        validateForm() {
            return !this.errors.any();
        },
        itemIdToEdit() {
            return (
                this.$store.getters["repetitiveTaskManagement/getSelectedItem"]
                    .id || 0
            );
        }
    },
    methods: {
        init() {
            this.$vs.loading();
            let id = this.$route.params.id;

            this.$store
                .dispatch("rangeManagement/fetchItem", id)
                .then(data => {
                    const item = data.payload;
                    this.range_data = item;
                })
                .catch(error => {
                    console.log(error);
                })
                .finally(() => this.$vs.loading.close());
        },
        save_changes() {
            /* eslint-disable */
            if (!this.validateForm) return;
            this.$vs.loading();

            const payload = { ...this.range_data };
            payload.repetitive_tasks = this.repetitiveTasksData.map(task => {
                if (task.id && String(task.id).startsWith("TEMPORARY_ID_")) {
                    task.id = undefined;
                }
                return task;
            });
            this.$store
                .dispatch("rangeManagement/updateItem", payload)
                .then(() => {
                    this.$vs.loading.close();
                    this.$vs.notify({
                        title: "Modification",
                        text: "Gamme modifiée avec succès",
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "success"
                    });
                    this.$router.push(`/${modelPlurial}`).catch(() => {});
                })
                .catch(error => {
                    const unauthorize = error.message
                        ? error.message.includes("status code 403")
                        : false;
                    const unauthorizeMessage = `Vous n'avez pas les autorisations pour cette action`;
                    this.$vs.loading.close();
                    this.$vs.notify({
                        title: "Echec",
                        text: unauthorize ? unauthorizeMessage : error.message,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "danger"
                    });
                });
        },
        back() {
            this.$router.push(`/${modelPlurial}`).catch(() => {});
        },
        capitalizeFirstLetter(word) {
            if (typeof word !== "string") return "";
            return word.charAt(0).toUpperCase() + word.slice(1);
        }
    },
    created() {
        // Register Module rangeManagement Module
        if (!moduleRangeManagement.isRegistered) {
            this.$store.registerModule(
                "rangeManagement",
                moduleRangeManagement
            );
            moduleRangeManagement.isRegistered = true;
        }
        if (!moduleSkillManagement.isRegistered) {
            this.$store.registerModule(
                "skillManagement",
                moduleSkillManagement
            );
            moduleSkillManagement.isRegistered = true;
        }
        if (!moduleWorkareaManagement.isRegistered) {
            this.$store.registerModule(
                "workareaManagement",
                moduleWorkareaManagement
            );
            moduleWorkareaManagement.isRegistered = true;
        }
        if (!moduleRepetitiveTaskManagement.isRegistered) {
            this.$store.registerModule(
                "repetitiveTaskManagement",
                moduleRepetitiveTaskManagement
            );
            moduleRepetitiveTaskManagement.isRegistered = true;
        }
        if (!moduleDocumentManagement.isRegistered) {
            this.$store.registerModule(
                "documentManagement",
                moduleDocumentManagement
            );
            moduleDocumentManagement.isRegistered = true;
        }
        this.init();
        this.$store
            .dispatch("skillManagement/fetchItems", { order_by: "name" })
            .catch(err => {
                console.error(err);
            });
        this.$store.dispatch("workareaManagement/fetchItems").catch(err => {
            console.error(err);
        });
        this.$store
            .dispatch("repetitiveTaskManagement/emptyItems")
            .catch(err => {
                console.error(err);
            });
        this.$store
            .dispatch(
                "repetitiveTaskManagement/fetchItemsByRange",
                this.$route.params.id
            )
            .catch(err => {
                console.error(err);
            });
    },
    beforeDestroy() {
        moduleRangeManagement.isRegistered = false;
        moduleSkillManagement.isRegistered = false;
        moduleWorkareaManagement.isRegistered = false;
        moduleRepetitiveTaskManagement.isRegistered = false;
        moduleDocumentManagement.isRegistered = false;
        this.$store.unregisterModule("rangeManagement");
        this.$store.unregisterModule("skillManagement");
        this.$store.unregisterModule("workareaManagement");
        this.$store.unregisterModule("repetitiveTaskManagement");
        this.$store.unregisterModule("documentManagement");
    }
};
</script>

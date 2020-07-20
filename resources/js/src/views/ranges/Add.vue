<!-- =========================================================================================
  File Name: RoleAdd.vue
  Description: role Add Page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/role/pixinvent
========================================================================================== -->

<template>
  <div id="page-role-add">
    <vx-card>
      <div slot="no-body" class="tabs-container px-6 pt-6">
        <div class="vx-row mt-4" v-if="!disabled">
          <div class="flex items-end px-3">
            <feather-icon svgClasses="w-6 h-6" icon="LockIcon" class="mr-2" />
            <span class="font-medium text-lg leading-none">Admin</span>
          </div>
          <vs-divider />
          <vs-select
            name="company"
            v-validate="'required'"
            label="Société"
            v-model="range_data.company_id"
            class="w-full"
          >
            <vs-select-item
              :key="index"
              :value="item.id"
              :text="item.name"
              v-for="(item,index) in companiesData"
            />
          </vs-select>
          <vs-divider />
        </div>

        <div class="vx-row">
          <vs-input
            class="w-full mt-4"
            label="Titre"
            v-model="range_data.name"
            v-validate="'required|max:255'"
            name="name"
          />
          <span class="text-danger text-sm" v-show="errors.has('name')">{{ errors.first('name') }}</span>
        </div>
        <div class="vx-row">
          <vs-textarea
            counter="1500"
            class="w-full mt-4"
            rows="5"
            label="Description"
            placeholder="Ajouter description"
            v-validate="'required|max:1500'"
            v-model="range_data.description"
            name="description"
          />
          <span
            class="text-danger text-sm"
            v-show="errors.has('description')"
          >{{ errors.first('description') }}</span>
        </div>
        <!-- Permissions -->
        <div class="vx-row mt-4">
          <div class="vx-col w-full">
            <div class="flex items-end px-3">
              <feather-icon svgClasses="w-6 h-6" icon="PackageIcon" class="mr-2" />
              <span class="font-medium text-lg leading-none">Liste des étapes de la gamme</span>
              <add-form v-if="range_data.company_id != null" :company_id="range_data.company_id"></add-form>
            </div>
            <vs-divider />
            <vs-table :data="repetitiveTasksData">
              <template slot="thead">
                <vs-th>Ordre</vs-th>
                <vs-th>Intitulé</vs-th>
                <vs-th>Compétences</vs-th>
                <vs-th>Temps</vs-th>
                <vs-th></vs-th>
              </template>

              <template slot-scope="{data}">
                <vs-tr :key="indextr" v-for="(tr, indextr) in data">
                  <vs-td :data="data[indextr].order">{{ data[indextr].order }}</vs-td>

                  <vs-td :data="data[indextr].name">{{ data[indextr].name }}</vs-td>

                  <vs-td
                    :data="data[indextr].skills"
                  >{{ data[indextr].skillsNames != "" ? data[indextr].skillsNames : 'Aucunes' }}</vs-td>

                  <vs-td :data="data[indextr].estimated_time">{{ data[indextr].estimated_time }}</vs-td>

                  <vs-td :data="data[indextr]">
                    <CellRendererActions :item="data[indextr]"></CellRendererActions>
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
            <vs-button class="ml-auto mt-2" @click="save_changes" :disabled="!validateForm">Créer</vs-button>
            <vs-button class="ml-4 mt-2" type="border" color="warning" @click="back">Annuler</vs-button>
          </div>
        </div>
      </div>
    </vx-card>
    <edit-form :itemId="itemIdToEdit" :companyId="range_data.company_id" v-if="itemIdToEdit" />
  </div>
</template>

<script>
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

// register custom messages
Validator.localize("fr", errorMessage);

import lodash from "lodash";

//Repetitive Task
import AddForm from "./repetitives-tasks/AddForm.vue";
import EditForm from "./repetitives-tasks/EditForm.vue";
import CellRendererActions from "./repetitives-tasks/cell-renderer/CellRendererActions.vue";

// Store Module
import moduleRangeManagement from "@/store/range-management/moduleRangeManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";
import moduleWorkareaManagement from "@/store/workarea-management/moduleWorkareaManagement.js";
import moduleSkillManagement from "@/store/skill-management/moduleSkillManagement.js";
import moduleRepetitiveTaskManagement from "@/store/repetitives-task-management/moduleRepetitiveTaskManagement.js";

var model = "range";
var modelPlurial = "ranges";
var modelTitle = "Gammes";

export default {
  components: {
    AddForm,
    EditForm,
    CellRendererActions
  },
  data() {
    return {
      range_data: {
        name: "",
        description: "",
        company_id: null
      },
      selected: []
    };
  },
  computed: {
    companiesData() {
      return this.$store.state.companyManagement.companies;
    },
    repetitiveTasksData() {
      let repetitivesTasks = this.$store.state.repetitiveTaskManagement.repetitivesTasks;

      repetitivesTasks.forEach(task => {
        
        if(task.skills.length > 0){
          let skillsNames = ''
          task.skills.forEach(skill_id => {
            
            const skills = this.$store.state.skillManagement.skills;
            let skill = skills.find( s => s.id == parseInt(skill_id)).name
            skillsNames = skill ? (skillsNames == "" ? skill : skillsNames + ' | ' + skill) : skillsNames

          });
          task.skillsNames = skillsNames
        }
      });

      return repetitivesTasks;
    },
    disabled() {
      const user = this.$store.state.AppActiveUser;
      if (user.roles && user.roles.length > 0) {
        if (
          user.roles.find(
            r => r.name === "superAdmin" || r.name === "littleAdmin"
          )
        ) {
          return false;
        } else {
          this.range_data.company_id = user.company_id;
          return true;
        }
      } else return true;
    },
    validateForm() {
      return !this.errors.any() && this.$store.state.repetitiveTaskManagement.repetitivesTasks.length > 0;
    },
    itemIdToEdit() {
      return this.$store.state.repetitiveTaskManagement.repetitivesTask.id || 0;
    }
  },
  methods: {
    save_changes() {
      /* eslint-disable */
      if (!this.validateForm) return;
      this.$vs.loading();
      this.range_data.permissions = _.keys(_.pickBy(this.selected));

      let payload = { ...this.range_data };
      payload.repetitive_tasks = this.repetitiveTasksData;
      this.$store
        .dispatch("rangeManagement/addItem", payload)
        .then(() => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Ajout",
            text: "Gamme ajoutée avec succès",
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
      this.$store.registerModule("rangeManagement", moduleRangeManagement);
      moduleRangeManagement.isRegistered = true;
    }
    if (!moduleCompanyManagement.isRegistered) {
      this.$store.registerModule("companyManagement", moduleCompanyManagement);
      moduleCompanyManagement.isRegistered = true;
    }
    if (!moduleSkillManagement.isRegistered) {
      this.$store.registerModule("skillManagement", moduleSkillManagement);
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
    this.$store.dispatch("companyManagement/fetchItems").catch(err => {
      console.error(err);
    });
    this.$store.dispatch("skillManagement/fetchItems").catch(err => {
      console.error(err);
    });
    this.$store.dispatch("workareaManagement/fetchItems").catch(err => {
      console.error(err);
    });
    this.$store.dispatch("repetitiveTaskManagement/cleanItems").catch(err => {
      console.error(err);
    });
    // this.$store.dispatch('permissionManagement/fetchItems').catch(err => { console.error(err) }) // TODO get repetitive tasks for company
  },
  beforeDestroy() {
    moduleRangeManagement.isRegistered = false;
    moduleSkillManagement.isRegistered = false;
    moduleCompanyManagement.isRegistered = false;
    moduleWorkareaManagement.isRegistered = false;
    moduleRepetitiveTaskManagement.isRegistered = false;
    this.$store.unregisterModule("rangeManagement");
    this.$store.unregisterModule("companyManagement");
    this.$store.unregisterModule("skillManagement");
    this.$store.unregisterModule("workareaManagement");
    this.$store.unregisterModule("repetitiveTaskManagement");
  }
};
</script>

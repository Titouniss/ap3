<!-- =========================================================================================
  File Name: BugAdd.vue
  Description: bug Add Page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/bug/pixinvent
========================================================================================== -->

<template>
  <div id="page-bug-add">
    <vx-card>
      <div slot="no-body" class="tabs-container px-6 pt-6">
        <vs-row vs-justify="center" vs-type="flex" vs-w="12">
          <vs-col vs-w="6" vs-xs="12" class="mt-6 px-6">
            <v-select
              v-validate="'required'"
              name="module"
              label="name"
              :multiple="false"
              v-model="bug_data.module"
              :reduce="name => name.name"
              class="w-full mt-2 mb-2"
              autocomplete
              :options="modulesOption"
            >
              <template #header>
                  <div style="opacity: .8 font-size: .85rem">
                      Module
                  </div>
              </template>
              <template #option="module">
                  <span>{{ `${module.name}` }}</span>
              </template>
            </v-select>
          </vs-col>
          <vs-col vs-w="6" vs-xs="12" class="mt-6 px-6">
            <v-select
              v-validate="'required'"
              name="type"
              label="name"
              :multiple="false"
              v-model="bug_data.type"
              :reduce="name => name.name"
              class="w-full mt-2 mb-2"
              autocomplete
              :options="typesOption"
            >
              <template #header>
                  <div style="opacity: .8 font-size: .85rem">
                      Type d'erreur
                  </div>
              </template>
              <template #option="type">
                  <span>{{ `${type.name}` }}</span>
              </template>
            </v-select>
          </vs-col>
        </vs-row>
        
        <vs-row vs-justify="left" vs-type="flex" vs-w="12" class="px-6">
          <vs-textarea
            class="w-full mt-4"
            rows="5"
            label="Ajouter description"
            v-model="bug_data.description"
            v-validate="'max:1500'"
            name="description"
          />
          <span
            class="text-danger text-sm"
            v-show="errors.has('description')"
            >{{ errors.first("description") }}</span
          >
          <div class="my-4">
            <file-input
              :items="uploadedFiles"
              :token="token"
            />
          </div>
        </vs-row>
      </div>
      <!-- Save & Reset Button -->
      <div class="vx-row">
        <div class="vx-col w-full">
          <div class="mt-8 flex flex-wrap items-center justify-end">
            <vs-button
              class="ml-auto mt-2"
              @click="save_changes"
              :disabled="!validateForm"
              >Ajouter</vs-button
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
  </div>
</template>

<script>
import lodash from "lodash";
// Store Module
import moduleBugManagement from "@/store/bug-management/moduleBugManagement.js";
import moduleDocumentManagement from "@/store/document-management/moduleDocumentManagement.js";

import { Validator } from "vee-validate";
import vSelect from "vue-select";
import FileInput from "@/components/inputs/FileInput.vue";
import errorMessage from "../bugs/errorValidForm";

// register custom messages
Validator.localize("fr", errorMessage);
var model = "bug";
var modelPlurial = "bugs";
var modelTitle = "Bugs";

export default {
  components: {
    vSelect,
    FileInput
  },
  data() {
    return {
      bug_data: {
        module: null,
        type: null,
        description: "",
        documents: [],
        created_by: null,
        company_id: null,
        role_id: null,
      },
      token:
        "token_" +
        Math.random()
            .toString(36)
            .substring(2, 15),
      uploadedFiles: [],
      modulesOption: [
        { name: "Tableau de bord" },
        { name: "Projets" },
        { name: "Gammes" },
        { name: "Utilisateurs" },
        { name: "Pôles de production" },
        { name: "Planning" },
        { name: "Rôles" },
        { name: "Compétences" },
        { name: "Sociétés" },
        { name: "Clients" },
        { name: "Heures" },
        { name: "Modules" },
        { name: "Gestion du profil" },
        { name: "Autre..." }
      ],
      typesOption: [
        { name: "Erreur 500" },
        { name: "Erreur 4O4" },
        { name: "Permission denied" },
        { name: "Autre..." }
      ],
    };
  },
  computed: {
    isAdmin() {
      return this.$store.state.AppActiveUser.is_admin;
    },
    disabled() {
      if (this.isAdmin) {
        return false;
      }
      return true;
    },
    validateForm() {
      return !this.errors.any() && this.bug_data.module != "" && this.bug_data.type != "" && this.bug_data.description != "";
    },
  },
  methods: {
    save_changes() {
      /* eslint-disable */
      if (!this.validateForm) return;
      this.$vs.loading();

      const payload = { ...this.bug_data };

      if (this.uploadedFiles.length > 0) {
        payload.token = this.token;
      }

      payload.created_by = this.$store.state.AppActiveUser.id
      payload.company_id = this.$store.state.AppActiveUser.company ? this.$store.state.AppActiveUser.company.id : null
      payload.role_id = this.$store.state.AppActiveUser.role ? this.$store.state.AppActiveUser.role.id : null
      payload.status = 'new'

      this.$store
        .dispatch("bugManagement/addItem", payload)
        .then(() => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Ajout",
            text: "Bug remonté avec succès",
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "success",
          });
          this.isAdmin ? this.$router.push(`/${modelPlurial}`).catch(() => {}) : this.$router.push(`/`).catch(() => {});
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
    back() {
      this.isAdmin ? this.$router.push(`/${modelPlurial}`).catch(() => {}) : this.$router.push(`/`).catch(() => {});
    },
    capitalizeFirstLetter(word) {
      if (typeof word !== "string") return "";
      return word.charAt(0).toUpperCase() + word.slice(1);
    },
  },
  created() {
    if (!moduleBugManagement.isRegistered) {
      this.$store.registerModule("bugManagement", moduleBugManagement);
      moduleBugManagement.isRegistered = true;
    }
    this.$store.dispatch("bugManagement/fetchItems").catch((err) => {
      console.error(err);
    });

    if (!moduleDocumentManagement.isRegistered) {
      this.$store.registerModule("documentManagement", moduleDocumentManagement);
      moduleDocumentManagement.isRegistered = true;
    }
  },
  beforeDestroy() {
    moduleDocumentManagement.isRegistered = false;
    moduleBugManagement.isRegistered = false;
    this.$store.unregisterModule("bugManagement");
    this.$store.unregisterModule("documentManagement");
  },
};
</script>

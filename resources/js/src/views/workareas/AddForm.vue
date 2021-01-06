<template>
  <div class="p-3 mb-4 mr-4">
    <vs-button @click="activePrompt = true" class="w-full"
      >Ajouter un pôle de produciton</vs-button
    >
    <vs-prompt
      title="Ajouter un pôle de produciton"
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
              />
              <span class="text-danger text-sm" v-show="errors.has('name')">{{
                errors.first("name")
              }}</span>

              <small class="ml-1 mb-2" for>Nombre d'opérateur maximum</small>
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

              <div v-if="itemLocal.company_id && disabled" class="mt-5">
                <span v-if="skillsData.length == 0" class="msgTxt"
                  >Aucune compétences trouvées.</span
                >
                <span v-if="skillsData.length == 0" class="linkTxt"
                  >Ajouter une compétence</span
                >
                <vs-select
                  v-if="skillsData.length > 0"
                  v-validate="'required'"
                  label="Compétences"
                  v-model="itemLocal.skills"
                  class="w-full"
                  multiple
                  autocomplete
                >
                  <vs-select-item
                    :key="index"
                    :value="item.id"
                    :text="item.name"
                    v-for="(item, index) in skillsData"
                  />
                </vs-select>
              </div>
              <div class="vx-row mt-4" v-if="!disabled">
                <div class="vx-col w-full">
                  <div class="flex items-end px-3">
                    <feather-icon
                      svgClasses="w-6 h-6"
                      icon="LockIcon"
                      class="mr-2"
                    />
                    <span class="font-medium text-lg leading-none">Admin</span>
                  </div>
                  <vs-divider />
                  <div>
                    <v-select
                      v-validate="'required'"
                      @input="selectCompanySkills"
                      name="company"
                      label="name"
                      :multiple="false"
                      v-model="itemLocal.company_id"
                      :reduce="(name) => name.id"
                      class="w-full mt-5"
                      autocomplete
                      :options="companiesData"
                    >
                      <template #header>
                        <div style="opacity: .8 font-size: .85rem">Société</div>
                      </template>
                      <template #option="company">
                        <span>{{ `${company.name}` }}</span>
                      </template>
                    </v-select>
                    <span
                      class="text-danger text-sm"
                      v-show="errors.has('company_id')"
                      >{{ errors.first("company_id") }}</span
                    >
                  </div>
                  <div v-if="itemLocal.company_id" class="mt-5">
                    <span v-if="companySkills.length == 0" class="msgTxt"
                      >Aucune compétences trouvées.</span
                    >
                    <router-link
                      v-if="companySkills.length > 0"
                      class="linkTxt"
                      :to="{ path: '/skills' }"
                      >Ajouter une compétence</router-link
                    >
                    <v-select
                      v-validate="'required'"
                      v-if="companySkills.length !== 0"
                      name="skill"
                      label="name"
                      :multiple="true"
                      v-model="itemLocal.skills"
                      :reduce="(name) => name.id"
                      class="w-full mt-5"
                      autocomplete
                      :options="companySkills"
                    >
                      <template #header>
                        <div style="opacity: .8 font-size: .85rem">
                          Compétences
                        </div>
                      </template>
                      <template #option="companyskill">
                        <span>{{ `${companyskill.name}` }}</span>
                      </template>
                    </v-select>
                    <span
                      class="text-danger text-sm"
                      v-show="errors.has('company_id')"
                      >{{ errors.first("company_id") }}</span
                    >
                  </div>
                </div>
              </div>

              <div class="mt-4">
                <file-input :items="uploadedFiles" :token="token" />
              </div>
            </div>
          </div>
        </form>
      </div>
    </vs-prompt>
  </div>
</template>

<script>
import vSelect from "vue-select";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import FileInput from "@/components/inputs/FileInput.vue";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
  components: {
    vSelect,
    FileInput,
  },
  data() {
    return {
      activePrompt: false,

      itemLocal: {
        name: "",
        max_users: 1,
        company_id: null,
        skills: [],
      },
      companySkills: [],

      token: "token_" + Math.random().toString(36).substring(2, 15),
      uploadedFiles: [],
    };
  },
  computed: {
    companiesData() {
      return this.$store.state.companyManagement.companies;
    },
    skillsData() {
      return this.$store.state.skillManagement.skills;
    },
    disabled() {
      const user = this.$store.state.AppActiveUser;
      if (user.roles && user.roles.length > 0) {
        if (user.roles.find((r) => r.name === "superAdmin")) {
          return false;
        } else {
          this.itemLocal.company_id = user.company_id;
          return true;
        }
      } else return true;
    },
    validateForm() {
      return !this.errors.any() && this.itemLocal.name != "";
    },
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
        skills: [],
      };
    },
    selectCompanySkills(item) {
      this.companySkills = this.companiesData.find(
        (company) => company.id === item
      ).skills;
    },
    addItem() {
      this.$validator.validateAll().then((result) => {
        if (result) {
          const item = JSON.parse(JSON.stringify(this.itemLocal));
          if (this.uploadedFiles.length) {
            item.token = this.token;
          }
          this.$store
            .dispatch("workareaManagement/addItem", item)
            .then(() => {
              this.$vs.notify({
                title: "Ajout d'un pôle de produciton",
                text: `"${this.itemLocal.name}" ajoutée avec succès`,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success",
              });
            })
            .catch((error) => {
              this.$vs.notify({
                title: "Error",
                text: error.message,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "danger",
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
      const ids = this.uploadedFiles.map((item) => item.id);
      if (ids.length > 0) {
        this.$store
          .dispatch("documentManagement/deleteFiles", ids)
          .then((response) => {
            this.uploadedFiles = [];
          })
          .catch((error) => {});
      }
    },
  },
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

<template>
  <vs-prompt
    title="Editer un pôle de produciton"
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
            <vs-input
              v-validate="'required|max:255'"
              name="name"
              class="w-full mb-4 mt-5"
              placeholder="Nom"
              v-model="itemLocal.name"
              :color="validateForm ? 'success' : 'danger'"
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
                    @input="cleanSkillsInput"
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
              </div>
            </div>

            <div v-if="itemLocal.company_id">
              <v-select
                v-validate="'required'"
                name="skill"
                label="name"
                :multiple="true"
                v-model="itemLocal.skills"
                :reduce="(name) => name.id"
                class="w-full mt-5"
                autocomplete
                :options="skillsData"
              >
                <template #header>
                  <div style="opacity: .8 font-size: .85rem">Compétences</div>
                </template>
                <template #option="skill">
                  <span>{{ `${skill.name}` }}</span>
                </template>
              </v-select>
              <span
                class="text-danger text-sm"
                v-show="errors.has('company_id')"
                >{{ errors.first("company_id") }}</span
              >
            </div>

            <div class="mt-4">
              <file-input :items="itemLocal.documents" :token="token" />
            </div>
          </div>
        </div>
      </form>
    </div>
  </vs-prompt>
</template>

<script>
import vSelect from "vue-select";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import FileInput from "@/components/inputs/FileInput.vue";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
  props: {
    itemId: {
      type: Number,
      required: true,
    },
  },
  components: {
    vSelect,
    FileInput,
  },
  data() {
    return {
      itemLocal: JSON.parse(
        JSON.stringify(
          this.$store.getters["workareaManagement/getItem"](this.itemId)
        )
      ),

      token: "token_" + Math.random().toString(36).substring(2, 15),
      company_id_temps: null,
      companySkills: [],
    };
  },
  computed: {
    activePrompt: {
      get() {
        return this.itemId && this.itemId > 0 ? true : false;
      },
      set(value) {
        this.$store
          .dispatch("workareaManagement/editItem", {})
          .then(() => {})
          .catch((err) => {
            console.error(err);
          });
      },
    },
    companiesData() {
      this.companySkills = this.$store.state.companyManagement.companies.find(
        (company) => company.id === this.itemLocal.company_id
      ).skills;
      return this.$store.state.companyManagement.companies;
    },
    skillsData() {
      return this.filterItemsAdmin(this.$store.state.skillManagement.skills);
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
    init() {
      this.deleteFiles();
      this.itemLocal = {};
      this.companySkills = [];
    },
    submitItem() {
      this.$validator.validateAll().then((result) => {
        if (result) {
          const item = JSON.parse(JSON.stringify(this.itemLocal));
          item.token = this.token;
          this.$store
            .dispatch("workareaManagement/updateItem", item)
            .then(() => {
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Modification d'un pôle de produciton",
                text: `"${this.itemLocal.name}" modifié avec succès`,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success",
              });
            })
            .catch((error) => {
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Error",
                text: error.message,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "danger",
              });
            });
        }
      });
    },
    cleanSkillsInput() {
      this.itemLocal.skills = [];
    },
    filterItemsAdmin(items) {
      let filteredItems = [];
      const user = this.$store.state.AppActiveUser;
      if (user.roles && user.roles.length > 0) {
        if (user.roles.find((r) => r.name === "superAdmin")) {
          filteredItems = items.filter(
            (item) => item.company_id === this.itemLocal.company_id
          );
        } else {
          filteredItems = items.filter(
            (item) => item.company_id === user.company_id
          );
        }
      }
      return filteredItems;
    },
    deleteFiles() {
      const ids = this.itemLocal.documents
        .filter((item) => item.token)
        .map((item) => item.id);
      if (ids.length > 0) {
        this.$store
          .dispatch("documentManagement/deleteFiles", ids)
          .catch((error) => {});
      }
    },
  },
};
</script>

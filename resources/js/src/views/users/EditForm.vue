<!-- =========================================================================================
  File Name: UserEdit.vue
  Description: user Edit Page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/role/pixinvent
========================================================================================== -->

<template>
  <div>
    <vx-card>
      <vs-row vs-justify="center" vs-type="flex" vs-w="12">
        <vs-col vs-w="6" vs-xs="12" class="mt-6 px-6">
          <!-- Lastname -->
          <vs-input
            v-validate="'required|max:255'"
            name="lastname"
            class="w-full mb-4"
            label="Nom"
            v-model="itemLocal.lastname"
            :color="!errors.has('lastname') ? 'success' : 'danger'"
          />
          <span class="text-danger text-sm" v-show="errors.has('lastname')">
            {{ errors.first("lastname") }}
          </span>

          <!-- Firstname -->
          <vs-input
            v-validate="'required|max:255'"
            name="firstname"
            class="w-full mb-4 mt-5"
            label="Prénom"
            v-model="itemLocal.firstname"
            :color="!errors.has('firstname') ? 'success' : 'danger'"
          />
          <span class="text-danger text-sm" v-show="errors.has('firstname')">
            {{ errors.first("firstname") }}
          </span>

          <!-- Login -->
          <div class="vs-input--label">Identifiant</div>
          <vx-input-group>
            <template slot="prepend">
              <div class="prepend-text bg-primary">
                <span> {{ company_login }} </span>
              </div>
            </template>

            <vs-input
              v-validate="{
                required: true,
                regex: /^((?![ÇçèéêëÈÉÊËàáâãäå@ÀÁÂÃÄÅìíîïÌÍÎÏðòóôõöÒÓÔÕÖùúûüÙÚÛÜýÿÝ]+).)*$/,
              }"
              name="login"
              class="w-full"
              v-model="itemLocal.login"
            />
          </vx-input-group>
          <span class="text-danger text-sm" v-show="errors.has('login')">
            {{ errors.first("login") }}
          </span>

          <!-- Email -->
          <vs-input
            v-validate="'email'"
            name="email"
            class="w-full mb-4 mt-5"
            label="E-mail"
            v-model="itemLocal.email"
            :color="!errors.has('email') ? 'success' : 'danger'"
          />
          <span class="text-danger text-sm" v-show="errors.has('email')">
            {{ errors.first("email") }}
          </span>
        </vs-col>

        <vs-col vs-w="6" vs-xs="12" class="mt-6 px-6">
          <!-- Role -->
          <div>
            <v-select
              v-validate="'required'"
              name="role"
              label="name"
              :multiple="false"
              v-model="itemLocal.roles"
              :reduce="(name) => name.id"
              class="w-full"
              autocomplete
              :options="rolesData"
            >
              <template #header>
                <div style="opacity: .8 font-size: .60rem">Rôle</div>
              </template>
              <template #option="role">
                <span> {{ `${role.name}` }} </span>
              </template>
            </v-select>
            <span class="text-danger text-sm" v-show="errors.has('role')">
              {{ errors.first("role") }}
            </span>
          </div>

          <!-- Company -->
          <div v-if="!disabled">
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
                <span> {{ `${company.name}` }} </span>
              </template>
            </v-select>
            <span class="text-danger text-sm" v-show="errors.has('company_id')">
              {{ errors.first("company_id") }}
            </span>
          </div>

          <!-- Skills -->
          <div v-if="itemLocal.company_id">
            <div v-if="companySkills.length === 0" class="mt-12 mb-2">
              <span
                label="Compétences"
                v-if="companySkills.length === 0"
                class="msgTxt mt-10"
              >
                Aucune compétences trouvées.
              </span>
              <router-link
                v-if="companySkills.length === 0"
                class="linkTxt"
                :to="{ path: '/skills' }"
              >
                Ajouter une compétence
              </router-link>
            </div>
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
                <div style="opacity: .8 font-size: .85rem">Compétences</div>
              </template>
              <template #option="companyskill">
                <span> {{ `${companyskill.name}` }} </span>
              </template>
            </v-select>
            <span class="text-danger text-sm" v-show="errors.has('company_id')">
              {{ errors.first("company_id") }}
            </span>
          </div>

          <!-- Related users -->
          <div
            v-if="
              itemLocal.company_id === initial_company_id &&
              itemLocal.related_users &&
              itemLocal.related_users.length !== 0
            "
            class="mt-5 p-3 border border-solid rounded"
            style="border-color: rgba(var(--vs-warning), 1)"
          >
            <v-select
              name="user"
              label="firstname"
              v-model="itemLocal.related_user_id"
              :reduce="(user) => user.id"
              class="w-full"
              autocomplete
              :options="itemLocal.related_users"
            >
              <template #header>
                <div style="opacity: .8 font-size: .85rem">
                  Utilisateur à relier
                </div>
              </template>
              <template #option="user">
                <span>
                  {{ `${user.firstname} ${user.lastname} (${user.login})` }}
                </span>
              </template>
              <template #selected-option="user">
                <span>
                  {{ `${user.firstname} ${user.lastname} (${user.login})` }}
                </span>
              </template>
            </v-select>
          </div>
        </vs-col>
      </vs-row>

      <!-- Save & Reset Button -->
      <div class="vx-row">
        <div class="vx-col w-full">
          <div class="mt-8 flex flex-wrap items-center justify-end">
            <vs-button
              class="ml-auto mt-2"
              @click="confirmUpdateItem"
              :disabled="!validateForm"
            >
              Modifier
            </vs-button>
            <vs-button
              class="ml-4 mt-2"
              type="border"
              color="warning"
              @click="back"
            >
              Annuler
            </vs-button>
          </div>
        </div>
      </div>
    </vx-card>

    <work-hours :data="itemLocal" />
  </div>
</template>

<script>
import vSelect from "vue-select";
import WorkHours from "./WorkHours";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

// register custom messages
Validator.localize("fr", errorMessage);

// Store Module
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";
import moduleRoleManagement from "@/store/role-management/moduleRoleManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";
import moduleSkillManagement from "@/store/skill-management/moduleSkillManagement.js";

var model = "user";
var modelPlurial = "users";

export default {
  components: {
    vSelect,
    WorkHours,
  },
  data() {
    return {
      itemLocal: {
        firstname: "",
        lastname: "",
        login: "",
        full_login: "",
        email: "",
        company_id: null,
        roles: [],
        skills: [],
        work_hours: [],
        related_user_id: null,
      },
      initial_company_id: null,
      company_id_temps: null,
      companySkills: [],
      company_login: "",
      selected: [],
    };
  },
  computed: {
    relatedUsers() {
      return this.itemLocal.related_users;
    },
    companiesData() {
      return this.$store.state.companyManagement.companies;
    },
    rolesData() {
      return this.$store.getters["roleManagement/getItemsForCompany"](
        this.itemLocal.company_id
      );
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
      return (
        !this.errors.any() &&
        this.itemLocal.name != "" &&
        this.itemLocal.firstname != "" &&
        this.itemLocal.login != "" &&
        this.itemLocal.company_id != null &&
        this.itemLocal.roles != null
      );
    },
  },
  methods: {
    authorizedTo(action, model = "users") {
      return this.$store.getters.userHasPermissionTo(`${action} ${model}`);
    },
    selectCompanySkills(item) {
      if (
        this.company_id_temps &&
        this.company_id_temps !== this.itemLocal.company_id
      ) {
        this.itemLocal.skills = [];
      }
      this.companySkills = this.companiesData.find(
        (company) => company.id === item
      ).skills;

      this.getCompanyName();
    },
    fetch_data(id) {
      this.$vs.loading();

      this.$store
        .dispatch("userManagement/fetchItem", id)
        .then((res) => {
          let item = res.data.success;
          console.log(item);
          // Get skills
          let skill_ids = [];
          if (item.skills.length > 0) {
            item.skills.forEach((element) => {
              skill_ids.push(element.id);
            });
          }
          item.skills = skill_ids;
          this.itemLocal = item;
          this.itemLocal.roles = this.itemLocal.roles[0].id;
          if (item.company_id) {
            this.initial_company_id = item.company_id;
            this.company_id_temps = item.company_id;
            this.selectCompanySkills(item.company_id);
          }

          // Get login
          if (this.itemLocal.login != null) {
            this.itemLocal.login = this.itemLocal.login.split(".").slice(1);
            //this.getCompanyName();
          } else {
            this.itemLocal.login = "";
            //this.getCompanyName();
          }

          this.$vs.loading.close();
        })
        .catch((err) => {
          this.$vs.loading.close();
          console.error(err);
        });
    },
    confirmUpdateItem() {
      if (this.itemLocal.related_user_id) {
        this.$vs.dialog({
          type: "confirm",
          color: "warning",
          title: "Confirmation de modifications",
          text:
            "Êtes vous sûr de vouloir lier cet utilisateur avec un qui est synchronisé ? Ce dernier sera supprimé définitivement",
          accept: this.updateItem,
          acceptText: "Confirmer",
          cancelText: "Annuler",
        });
      } else {
        this.updateItem();
      }
    },
    updateItem() {
      this.$vs.loading();
      const payload = Object.assign({}, this.itemLocal);
      payload.roles = [
        this.$store.getters["roleManagement/getItem"](this.itemLocal.roles),
      ];

      // Parse login
      payload.full_login = "".concat(this.company_login, this.itemLocal.login);

      this.$store
        .dispatch("userManagement/updateItem", payload)
        .then(() => {
          this.$vs.loading.close();
          this.back();
          this.$vs.notify({
            title: "Modification",
            text: "Utilisateur modifier avec succès",
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "success",
          });
        })
        .catch((error) => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Echec",
            text: error.message,
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "danger",
          });
        });
    },
    back() {
      this.$router.push(`/${modelPlurial}`).catch(() => {});
    },
    activeUserRole() {
      const user = this.$store.state.AppActiveUser;
      if (user.roles && user.roles.length > 0) {
        return user.roles[0].name;
      }
      return false;
    },
    getCompanyName() {
      if (this.activeUserRole() == "superAdmin") {
        if (this.itemLocal.company_id != null) {
          let company = this.$store.getters["companyManagement/getItem"](
            this.itemLocal.company_id
          );

          this.company_login = "".concat(
            company.name.replace(/ /gi, "_").toLowerCase(),
            "."
          );
          this.company_login = this.removeAccents(this.company_login);
        } else {
          this.company_login = "selectionner_société.";
        }
      } else {
        const user = this.$store.state.AppActiveUser;
        this.company_login = "".concat(
          user.company.name.replace(/ /gi, "_").toLowerCase(),
          "."
        );
        this.company_login = this.removeAccents(this.company_login);
      }
    },
    removeAccents(str) {
      let accents =
        "ÀÁÂÃÄÅàáâãäåßÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñŠšŸÿýŽž";
      let accentsOut =
        "AAAAAAaaaaaaBOOOOOOOooooooEEEEeeeeeCcDIIIIiiiiUUUUuuuuNnSsYyyZz";
      str = str.split("");
      str.forEach((letter, index) => {
        let i = accents.indexOf(letter);
        if (i != -1) {
          str[index] = accentsOut[i];
        }
      });
      return str.join("");
    },
  },
  created() {
    if (!moduleUserManagement.isRegistered) {
      this.$store.registerModule("userManagement", moduleUserManagement);
      moduleUserManagement.isRegistered = true;
    }
    if (!moduleRoleManagement.isRegistered) {
      this.$store.registerModule("roleManagement", moduleRoleManagement);
      moduleRoleManagement.isRegistered = true;
    }
    if (!moduleCompanyManagement.isRegistered) {
      this.$store.registerModule("companyManagement", moduleCompanyManagement);
      moduleCompanyManagement.isRegistered = true;
    }
    if (!moduleSkillManagement.isRegistered) {
      this.$store.registerModule("skillManagement", moduleSkillManagement);
      moduleSkillManagement.isRegistered = true;
    }

    if (this.authorizedTo("read", "skills")) {
      this.$store.dispatch("skillManagement/fetchItems");
    }
    if (this.authorizedTo("read", "users")) {
      this.$store.dispatch("userManagement/fetchItems");
    }
    if (this.authorizedTo("read", "companies")) {
      this.$store.dispatch("companyManagement/fetchItems");
    }
    if (this.authorizedTo("read", "roles")) {
      this.$store.dispatch("roleManagement/fetchItems");
    }

    this.fetch_data(parseInt(this.$route.params.userId, 10));
  },
  beforeDestroy() {
    moduleUserManagement.isRegistered = false;
    moduleRoleManagement.isRegistered = false;
    moduleCompanyManagement.isRegistered = false;
    moduleSkillManagement.isRegistered = false;

    this.$store.unregisterModule("skillManagement");
    this.$store.unregisterModule("userManagement");
    this.$store.unregisterModule("roleManagement");
    this.$store.unregisterModule("companyManagement");
  },
};
</script>

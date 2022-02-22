<!-- =========================================================================================
  File Name: RoleEdit.vue
  Description: role Edit Page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/role/pixinvent
========================================================================================== -->

<template>
  <div id="page-role-edit">
    <vs-alert
      color="danger"
      title="role Not Found"
      :active.sync="role_not_found"
    >
      <span>Le rôle {{ $route.params.id }} est introuvable.</span>
      <span>
        <span>voir</span>
        <router-link :to="{ name: 'roles' }" class="text-inherit underline"
          >Tout les rôles</router-link
        >
      </span>
    </vs-alert>

    <vx-card v-if="role_data">
      <div slot="no-body" class="tabs-container px-6 pt-6">
        <div class="vx-row" v-if="!disabled">
          <vs-switch v-model="role_data.is_public" name="is_public">
            <span slot="on">Publique</span>
            <span slot="off">Privé</span>
          </vs-switch>
          <span class="text-danger text-sm" v-show="errors.has('is_public')">{{
            errors.first("is_public")
          }}</span>
        </div>
        <div class="vx-row">
          <vs-input
            class="w-full mt-4"
            label="Titre"
            v-model="role_data.name"
            v-validate="'required'"
            name="name"
          />
          <span class="text-danger text-sm" v-show="errors.has('name')">{{
            errors.first("name")
          }}</span>
        </div>
        <div class="vx-row">
          <vs-textarea
            class="w-full mt-4"
            rows="5"
            label="Ajouter description"
            v-model="role_data.description"
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
              <feather-icon svgClasses="w-6 h-6" icon="LockIcon" class="mr-2" />
              <span class="font-medium text-lg leading-none">Permissions</span>
            </div>
            <vs-divider />
          </div>
        </div>

        <div class="block overflow-x-auto">
          <table class="w-full">
            <tr>
              <!--
                You can also use `Object.keys(Object.values(data_local.permissions)[0])` this logic if you consider,
                our data structure. You just have to loop over above variable to get table headers.
                Below we made it simple. So, everyone can understand.
              -->
              <th
                class="font-semibold text-base text-left px-3 py-2"
                v-for="heading in [
                  'Module',
                  'Tout',
                  'Consulter',
                  'Lecture',
                  'Créer',
                  'Editer',
                  'Supprimer',
                ]"
                :key="heading"
              >
                {{ heading }}
              </th>
            </tr>
            <tr v-for="(items, index) in permissions" :key="index">
              <td class="px-3 py-2">
                {{ capitalizeFirstLetter(index) }}
              </td>
              <td class="px-3 py-2">
                <vs-checkbox
                  v-on:change="checkAll(items)"
                  :checked="checkOrNot(items)"
                />
              </td>
              <td
                v-for="(item, name) in items"
                class="px-3 py-2"
                :key="index + name + item.id"
              >
                <vs-checkbox
                  :disabled="forceLecture(items, item)"
                  v-on:change="checkLecture(items, item)"
                  v-model="selected[item.id]"
                />
              </td>
            </tr>
          </table>
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
  </div>
</template>

<script>
import lodash from "lodash";
// Store Module
import moduleManagement from "@/store/role-management/moduleRoleManagement.js";
import modulePermissionManagement from "@/store/permission-management/modulePermissionManagement.js";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

// register custom messages
Validator.localize("fr", errorMessage);
var model = "role";
var modelPlurial = "roles";
var modelTitle = "Rôle";

export default {
  data() {
    return {
      role_data: null,
      selected: [],
      role_not_found: false,
    };
  },
  computed: {
    isAdmin() {
      return this.$store.state.AppActiveUser.is_admin;
    },
    disabled() {
      const user = this.$store.state.AppActiveUser;
      if (this.isAdmin) {
        return false;
      }
      return true;
    },
    permissions() {
      const permissionsStore = this.$store.getters[
        "permissionManagement/getItems"
      ];
      let permissions = [];
      if (permissionsStore && permissionsStore.length > 0) {
        permissions = permissionsStore.reduce(function (acc, valeurCourante) {
          let permissionName = valeurCourante.name;
          let titles = permissionName.split(" ");
          if (!acc) {
            acc = {};
          }
          if (titles.length > 1) {
            if (!acc[valeurCourante.name_fr]) {
              acc[valeurCourante.name_fr] = {};
            }
            acc[valeurCourante.name_fr][titles[0]] = valeurCourante;
          }
          return acc;
        }, {});
      }
      return permissions;
    },
    validateForm() {
      return !this.errors.any();
    },
  },
  methods: {
    fetch_data(id) {
      this.$store
        .dispatch("roleManagement/fetchItem", id)
        .then((data) => {
          this.role_data = data.payload;
          if (this.role_data && this.role_data.permissions.length) {
            this.role_data.permissions.forEach((permission) => {
              this.selected[permission.id] = true;
            });

            // Required read perms
            this.checkRequired();
          }
        })
        .catch((err) => {
          if (err.response.status === 404) {
            this.role_not_found = true;
            return;
          }
          console.error(err);
        });
    },
    save_changes() {
      /* eslint-disable */
      if (!this.validateForm) return;
      this.$vs.loading();
      this.role_data.permissions = _.keys(_.pickBy(this.selected));

      const payload = { ...this.role_data };
      this.$store
        .dispatch("roleManagement/updateItem", payload)
        .then((response) => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Modification",
            text: "Rôle modifier avec succès",
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "success",
          });
          this.$router.push(`/${modelPlurial}`).catch(() => {});
        })
        .catch((error) => {
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
            color: "danger",
            time: 10000,
          });
        });
    },
    checkOrNot(items) {
      let items_id = [];

      // get items_id
      for (const perm in items) {
        items_id.push(items[perm].id);
      }

      // Check if already full check
      let missPerm = false;
      items_id.forEach((id) => {
        if (!this.selected[id] === true) {
          missPerm = true;
        }
      });

      if (missPerm) {
        return false;
      } else {
        return true;
      }
    },
    checkAll(items) {
      let items_id = [];

      // get items_id
      for (const perm in items) {
        items_id.push(items[perm].id);
      }

      // Check if already full check
      let missPerm = false;
      items_id.forEach((id) => {
        if (!this.selected[id] === true) {
          missPerm = true;
        }
      });

      if (missPerm) {
        // add items_id if not already in
        items_id.forEach((id) => {
          if (!this.selected[id] === true || this.selected[id] === undefined) {
            this.selected[id] = true;
          }
        });
      } else {
        // remove items_id from selected
        items_id.forEach((id) => {
          if (this.selected[id] === true) {
            this.selected[id] = false;
          }
        });
      }

      this.dependencyChecking("check", items.show);

      // Required read perms
      this.checkRequired();

      this.selected = Object.assign({}, this.selected);
    },
    checkLecture(items, item) {
      if (items.read.id !== item.id && !this.selected[items.read.id]) {
        if (
          !this.selected[items.read.id] === true ||
          this.selected[items.read.id] === undefined
        )
          if (
            !this.selected[items.read.id] === true ||
            this.selected[items.read.id] === undefined
          ) {
            this.selected[items.read.id] = true;
          }
      }

      this.dependencyChecking("check", item);

      // Required read perms
      this.checkRequired();
    },
    forceLecture(items, item) {
      if (
        items.read.id === item.id &&
        (this.selected[items.delete.id] === true ||
          this.selected[items.edit.id] === true ||
          this.selected[items.publish.id] === true ||
          this.selected[items.show.id] === true)
      ) {
        return true;
      } else if (item.name.split(" ")[0] === "read") {
        return true;
      }
      // this.dependencyChecking("force", item);

      return false;
    },
    back() {
      this.$router.push(`/${modelPlurial}`).catch(() => {});
    },
    capitalizeFirstLetter(word) {
      if (typeof word !== "string") return "";
      return word.charAt(0).toUpperCase() + word.slice(1);
    },
    checkRequired() {
      // Required read perms
      this.selected[this.permissions.tâches.read.id] = true;
      this.selected[this.permissions.projets.read.id] = true;
      this.selected[this.permissions.indiponibilités.read.id] = true;
      this.selected[this.permissions.heures_supplémentaires.read.id] = true;
      this.selected[this.permissions.todos.read.id] = true;
    },
    dependencyChecking(type, item) {
      switch (item.name) {
        case "show workareas":
          if (this.selected[this.permissions.pôles_de_productions.show.id]) {
            this.selected[this.permissions.pôles_de_productions.read.id] = true;
            this.selected[this.permissions.compétences.read.id] = true;
          } else {
            const dependencies = ["pôles_de_productions", "compétences"];
            this.otherModuleNeedRead("pôles_de_productions", dependencies);
          }
          break;
        case "show users":
          if (this.selected[this.permissions.utilisateurs.show.id]) {
            this.selected[this.permissions.utilisateurs.read.id] = true;
            this.selected[this.permissions.compétences.read.id] = true;
            this.selected[this.permissions.roles.read.id] = true;
          } else {
            const dependencies = [
              "utilisateurs",
              "compétences",
              "roles",
              "entreprises",
            ];
            this.otherModuleNeedRead("utilisateurs", dependencies);
          }
          break;
        case "show unavailabilities":
          if (this.selected[this.permissions.indiponibilités.show.id]) {
            this.selected[this.permissions.indiponibilités.read.id] = true;
          } else {
            const dependencies = ["indiponibilités"];
            this.otherModuleNeedRead("indiponibilités", dependencies);
          }
          break;
        case "show tasks":
          if (this.selected[this.permissions.tâches.show.id]) {
            this.selected[this.permissions.tâches.read.id] = true;
            this.selected[this.permissions.utilisateurs.read.id] = true;
          } else {
            const dependencies = ["tâches", "utilisateurs"];
            this.otherModuleNeedRead("tâches", dependencies);
          }
          break;
        case "show skills":
          if (this.selected[this.permissions.compétences.show.id]) {
            this.selected[this.permissions.compétences.read.id] = true;
            this.selected[this.permissions.pôles_de_productions.read.id] = true;
            this.selected[this.permissions.tâches.read.id] = true;
          } else {
            const dependencies = [
              "compétences",
              "pôles_de_productions",
              "pôles_de_productions",
              "tâches",
            ];
            this.otherModuleNeedRead("compétences", dependencies);
          }
          break;
        case "show schedules":
          if (this.selected[this.permissions.planning.show.id]) {
            this.selected[this.permissions.planning.read.id] = true;
            this.selected[this.permissions.projets.read.id] = true;
            this.selected[this.permissions.pôles_de_productions.read.id] = true;
            this.selected[this.permissions.tâches.read.id] = true;
            this.selected[this.permissions.compétences.read.id] = true;
            this.selected[this.permissions.utilisateurs.read.id] = true;
          } else {
            const dependencies = [
              "planning",
              "projets",
              "pôles_de_productions",
              "tâches",
              "compétences",
              "utilisateurs",
            ];
            this.otherModuleNeedRead("planning", dependencies);
          }

          break;
        case "show roles":
          if (this.selected[this.permissions.roles.show.id]) {
            this.selected[this.permissions.roles.read.id] = true;
            this.selected[this.permissions.utilisateurs.read.id] = true;
          } else {
            const dependencies = ["roles", "utilisateurs"];
            this.otherModuleNeedRead("roles", dependencies);
          }
          break;
        case "show projects":
          if (this.selected[this.permissions.projets.show.id]) {
            this.selected[this.permissions.projets.read.id] = true;
            this.selected[this.permissions.pôles_de_productions.read.id] = true;
            this.selected[this.permissions.compétences.read.id] = true;
            this.selected[this.permissions.clients.read.id] = true;
            this.selected[this.permissions.gammes.read.id] = true;
          } else {
            const dependencies = [
              "projets",
              "pôles_de_productions",
              "compétences",
              "clients",
              "gammes",
            ];
            this.otherModuleNeedRead("projets", dependencies);
          }

          break;
        case "show hours":
          if (this.selected[this.permissions.heures.show.id]) {
            this.selected[this.permissions.heures.read.id] = true;
            this.selected[this.permissions.projets.read.id] = true;
            this.selected[this.permissions.utilisateurs.read.id] = true;
            this.selected[this.permissions.planning.read.id] = true;
          } else {
            const dependencies = [
              "heures",
              "projets",
              "utilisateurs",
              "planning",
            ];
            this.otherModuleNeedRead("heures", dependencies);
          }
          break;
        case "show dealingHours":
          if (this.selected[this.permissions.heures_supplémentaires.show.id]) {
            this.selected[
              this.permissions.heures_supplémentaires.read.id
            ] = true;
          } else {
            const dependencies = ["heures_supplémentaires"];
            this.otherModuleNeedRead("heures_supplémentaires", dependencies);
          }
          break;
        case "show customers":
          if (this.selected[this.permissions.clients.show.id]) {
            this.selected[this.permissions.clients.read.id] = true;
          } else {
            const dependencies = ["clients"];
            this.otherModuleNeedRead("clients", dependencies);
          }
          break;

        default:
          break;
      }
    },
    otherModuleNeedRead(origin, moduleDependencies) {
      // Get all show module
      const modules = [
        "pôles_de_productions",
        "utilisateurs",
        "indiponibilités",
        "compétences",
        "tâches",
        "planning",
        "roles",
        "projets",
        "heures",
        "heures_supplémentaires",
        "clients",
      ];
      let modulesShow = [];
      modules.forEach((m) => {
        if (this.selected[this.permissions[m].show.id] === true) {
          modulesShow.push(m);
        }
      });

      // Define all dependences
      const dependencies = {
        pôles_de_productions: [
          "pôles_de_productions",
          "compétences",
          "entreprise",
        ],
        utilisateurs: ["utilisateurs", "roles", "entreprises", "compétences"],
        indisponibilités: [],
        tâches: ["utilisateurs"],
        compétences: ["compétences", "entreprises", "pôles_de_productions"],
        planning: [
          "planning",
          "pôles_de_productions",
          "compétences",
          "utilisateurs",
        ],
        roles: ["roles", "utilisateurs", "permissions"],
        projets: ["entreprises", "gammes", "clients", "pôles_de_productions"],
        heures: ["heures", "utilisateurs", "entreprise", "planning"],
        clients: ["clients", "entreprise"],
        entreprises: ["entreprises"],
      };
      delete dependencies[origin];

      // Keep only dependencies that are displayed
      let sortedDependencies = [];
      for (const [key, value] of Object.entries(dependencies)) {
        if (modulesShow.includes(key)) {
          if (value) {
            value.forEach((v) => {
              if (sortedDependencies.indexOf(v) === -1) {
                sortedDependencies.push(v);
              }
            });
          }
        }
      }

      // Check if read permission is used in other module
      if (moduleDependencies != []) {
        moduleDependencies.forEach((mD) => {
          if (sortedDependencies.indexOf(mD) === -1) {
            this.selected[this.permissions[mD].read.id] = false;
          } else {
            this.selected[this.permissions[mD].read.id] = true;
          }
        });
      }
    },
  },
  created() {
    // Register roleManagement Module
    if (!moduleManagement.isRegistered) {
      this.$store.registerModule("roleManagement", moduleManagement);
      moduleManagement.isRegistered = true;
    }
    this.$store.dispatch("roleManagement/fetchItems").catch((err) => {
      console.error(err);
    });

    if (!modulePermissionManagement.isRegistered) {
      this.$store.registerModule(
        "permissionManagement",
        modulePermissionManagement
      );
      modulePermissionManagement.isRegistered = true;
    }
    this.$store.dispatch("permissionManagement/fetchItems").catch((err) => {
      console.error(err);
    });
    this.fetch_data(this.$route.params.id);
  },
  beforeDestroy() {
    moduleManagement.isRegistered = false;
    modulePermissionManagement.isRegistered = false;
    this.$store.unregisterModule("roleManagement");
    this.$store.unregisterModule("permissionManagement");
  },
};
</script>

<template>
  <div>
    <vs-button @click="activePrompt = true" class="w-full p-3 mb-1 mr-4">
      Ajouter un tag
    </vs-button>
    <div @click="activePrompt = true" class="m-0">
      <!-- <feather-icon
        icon="PlusIcon"
        svgClasses="h-10 w-10"
        style="color: #fff"
      /> -->
      <div style="font-size: 1.1em; color: #fff">Ajouter un tag</div>
    </div>

    <vs-prompt
      title="Ajouter un Tag"
      accept-text="Ajouter"
      cancel-text="Annuler"
      button-cancel="border"
      @cancel="clearFields"
      @accept="addTag"
      @close="clearFields"
      :is-valid="validateForm"
      :active.sync="activePrompt"
      class="task-compose"
    >
      <div>
        <form autocomplete="off">
          <div>
            <div class="vx-col flex-1" style="border-right: 1px solid #d6d6d6">
              <vs-input
                v-validate="'required'"
                name="title"
                label="Titre"
                class="w-full mb-4 mt-1"
                placeholder="Titre" 
                v-model="tagLocal.title"
                :color="validateForm ? 'success' : 'danger'"
              />
            </div>
            <div>
              <small class="ml-2">Couleur</small>
            </div>

            <div class="pb-2 pl-2">
              <v-swatches
                clas="vs-row"
                :swatches="colors"
                name="color"
                v-model="tagLocal.color"
                swatch-size="40"
              ></v-swatches>
            </div>
          </div>
        </form>
      </div>
    </vs-prompt>
  </div>
</template>
<script>
import VSwatches from "vue-swatches";
import "vue-swatches/dist/vue-swatches.css";
import { tag_colors } from "../../../themeConfig";

export default {
  components: {
    VSwatches,
  },
  data() {
    return {
      props:{
      tag_lists: { required: true },
      },
      activePrompt: false,
      tagLocal: {
        title: "",
        color: "",
      },
        isSubmiting: false,
        colors: tag_colors,
    };
  },
  computed:{
      isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
      validateForm () {
      return !this.errors.any() 
      && this.tagLocal.title !==""
      && this.tagLocal.color !=="";
    },
  },
  methods:
  {
    clearFields () {
    this.tagLocal= {
        title: '',
        color:''
      }
    },
      addTag() {
       if (!this.validateForm || this.isSubmiting) return;

            this.isSubmiting = true;
        const item = JSON.parse(JSON.stringify(this.tagLocal));

      console.log("kalal");
      this.$validator.validateAll().then((result) => {
        if (result) {
          this.$store
            .dispatch("tagManagement/addItem", item)
            .then((data) => {
                    console.log(data);

              this.clearFields(false);
              this.$vs.notify({
                title: "Ajout d'un tag",
                text: `Tag ajouté avec succès`,
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
            }).finally(() => {
                    this.isSubmiting = false;
                    this.$vs.loading.close();
                });
          this.clearFields();
        }
      });
    },
  }
};
</script>

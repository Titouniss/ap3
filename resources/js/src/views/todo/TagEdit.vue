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
    title="Modifier le tag"
    accept-text="Modifier"
    cancel-text="Annuler"
    button-cancel="border"
    @cancel="clear"
    @accept="submitItem"
    @close="clear"
    :is-valid="validateForm"
    :active.sync="activePrompt"
  >
   <div>
        <form autocomplete="off">
          <div>
            <div class="vx-col flex-1" style="border-right: 1px solid #d6d6d6">
              <vs-input
                v-validate="'max:50|required'"
                name="title"
                label="Titre"
                class="w-full mb-4 mt-1"
                placeholder="Titre" 
                v-model="itemLocal.title"
                :success="itemLocal.title != '' && !errors.has('title')"
                :danger="errors.has('title')"
                :danger-text="errors.first('title')"              />
            </div>
            <div>
              <small class="ml-2">Couleur</small>
            </div>

            <div class="pb-2 pl-2">
              <v-swatches
               v-validate="'required'"
                clas="vs-row"
                :swatches="colors"
                name="color"
                v-model="itemLocal.color"
                swatch-size="40"
              ></v-swatches>
            </div>
          </div>
        </form>
      </div>
  </vs-prompt>
</template>

<script>
import VSwatches from "vue-swatches";
import "vue-swatches/dist/vue-swatches.css";
import { tag_colors } from "../../../themeConfig";
export default {
  components:{
VSwatches

  },
  props: {
      displayPrompt: {
      type: Boolean,
      required: true,
    },
    itemId: {
      type:Number,
      required: true,
    },
  },
  
  data() {
   const storeItem = JSON.parse(
            JSON.stringify(
                this.$store.getters["tagManagement/getItem"](this.itemId)
            )
        );
    return {
        colors: tag_colors,
     itemLocal: storeItem,
    };
  },
  
  computed: {
     activePrompt: {
            get() {
              return this.displayPrompt
            },
            set(value) {
            this.$emit("hideDisplayPrompt", value);

            }
        },
    validateForm() {
      return !this.errors.any() 
      && this.itemLocal.title !== ""
       && this.itemLocal.color !== "";
    },
  },
  methods: {
    
      clear () {
       this.itemLocal={
        };
    },
    submitItem() {
            this.$validator.validateAll().then(result => {
                const item = JSON.parse(JSON.stringify(this.itemLocal));
                console.log(item)
                this.$store
                    .dispatch("tagManagement/updateItem", item)
                    .then(data => {
                        this.$vs.loading.close();
                      
                           this.$vs.notify({
                            title: "Modification d'un projet",
                            text: `"${this.itemLocal.title}" modifiée avec succès`,
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
            });
        },
  },
};
</script>

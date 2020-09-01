<template>
    <div id="page-module-view">
        <router-link
            :to="'/modules'"
            class="flex items-center cursor-pointer text-inherit hover:text-primary pt-3 mb-3"
        >
            <feather-icon class="'h-5 w-5" icon="ArrowLeftIcon"></feather-icon>
            <span class="ml-2"> Retour à la liste des modules </span>
        </router-link>
    </div>
</template>

<script>
import { FormWizard, TabContent } from "vue-form-wizard";
import "vue-form-wizard/dist/vue-form-wizard.min.css";

import moduleModuleManagement from "@/store/module-management/moduleModuleManagement.js";

export default {
    data() {
        return {
            module_data: null
        };
    },
    computed: {
        itemIdToEdit() {
            return this.$store.state.moduleManagement.module.id || 0;
        }
    },
    methods: {
        editItem() {
            this.$store
                .dispatch("moduleManagement/editItem", this.module_data)
                .then(() => {})
                .catch(err => {
                    console.error(err);
                });
        }
    },
    created() {
        // Register Module moduleManagement Module
        if (!moduleModuleManagement.isRegistered) {
            this.$store.registerModule(
                "moduleManagement",
                moduleModuleManagement
            );
            moduleModuleManagement.isRegistered = true;
        }

        const moduleId = this.$route.params.id;
        this.$store
            .dispatch("moduleManagement/fetchItem", moduleId)
            .then(res => {
                this.module_data = res.data.success;
            });
    },
    beforeDestroy() {
        moduleModuleManagement.isRegistered = false;
        this.$store.unregisterModule("moduleManagement");
    }
};
</script>

<template>
    <div class="mb-6">
        <vs-row
            v-for="(item, index) in activeDataTypes"
            :key="item.slug"
            type="flex"
            vs-justify="center"
            vs-align="center"
            vs-w="12"
            class="mb-6"
        >
            <vs-col
                vs-type="flex"
                vs-justify="center"
                vs-align="center"
                vs-w="12"
            >
                <vx-card
                    :collapse-action="true"
                    :remove-card-action="true"
                    @remove="removeDataType(index)"
                    :title="item.display_name_plurial"
                    :title-color="statusColor(item.slug)"
                >
                    <form :data-vv-scope="item.slug">
                        <div class="w-full my-6 flex-row items-center">
                            <vs-input
                                name="source"
                                v-validate="'required|max:255'"
                                v-model="item.source"
                                :label-placeholder="
                                    `${
                                        module.type === 'sql'
                                            ? 'Nom de table source'
                                            : 'Route source'
                                    }`
                                "
                                :success="
                                    item.source.length > 0 &&
                                        !errors.has('source', item.slug)
                                "
                                :danger="errors.has('source', item.slug)"
                                :danger-text="errors.first('source', item.slug)"
                            />
                        </div>
                        <vs-table :data="item.module_data_rows" stripe>
                            <template slot="thead">
                                <vs-th>Donnée</vs-th>
                                <vs-th>Type</vs-th>
                                <vs-th>Source</vs-th>
                                <vs-th>Valeur par défaut</vs-th>
                                <vs-th>Détails</vs-th>
                            </template>

                            <template slot-scope="{ data }">
                                <vs-tr
                                    v-for="(row, rowIndex) in data"
                                    :key="
                                        `${item.slug}.${data[rowIndex].field}`
                                    "
                                >
                                    <vs-td :data="data[rowIndex].display_name">
                                        {{ data[rowIndex].display_name }}
                                        <span v-if="data[rowIndex].required">
                                            *
                                        </span>
                                    </vs-td>
                                    <vs-td :data="data[rowIndex].type">
                                        {{
                                            `${data[rowIndex].type
                                                .charAt(0)
                                                .toUpperCase()}${data[
                                                rowIndex
                                            ].type.slice(1)}`
                                        }}
                                    </vs-td>
                                    <vs-td :data="data[rowIndex].source">
                                        <vs-input
                                            :name="
                                                `${data[rowIndex].field}.source`
                                            "
                                            v-validate="
                                                `max:255${
                                                    data[rowIndex].required &&
                                                    !data[rowIndex]
                                                        .default_value
                                                        ? '|required'
                                                        : ''
                                                }`
                                            "
                                            v-model="data[rowIndex].source"
                                            :success="
                                                data[rowIndex].source.length >
                                                    0 &&
                                                    !errors.has(
                                                        `${data[rowIndex].field}.source`,
                                                        item.slug
                                                    )
                                            "
                                            :danger="
                                                errors.has(
                                                    `${data[rowIndex].field}.source`,
                                                    item.slug
                                                )
                                            "
                                            :danger-text="
                                                `${errors.first(
                                                    `${data[rowIndex].field}.source`,
                                                    item.slug
                                                )} ou valeur par défaut`
                                            "
                                        />
                                    </vs-td>
                                    <vs-td :data="data[rowIndex].default_value">
                                        <vs-input
                                            :name="
                                                `${data[rowIndex].field}.default_value`
                                            "
                                            v-validate="
                                                `max:255${
                                                    data[rowIndex].required &&
                                                    !data[rowIndex].source
                                                        ? '|required'
                                                        : ''
                                                }`
                                            "
                                            v-model="
                                                data[rowIndex].default_value
                                            "
                                            :success="
                                                data[rowIndex].default_value
                                                    .length > 0 &&
                                                    !errors.has(
                                                        `${data[rowIndex].field}.default_value`,
                                                        item.slug
                                                    )
                                            "
                                            :danger="
                                                errors.has(
                                                    `${data[rowIndex].field}.default_value`,
                                                    item.slug
                                                )
                                            "
                                            :danger-text="
                                                `${errors.first(
                                                    `${data[rowIndex].field}.default_value`,
                                                    item.slug
                                                )} ou source`
                                            "
                                        />
                                    </vs-td>
                                    <vs-td :data="data[rowIndex].details">
                                        <vs-input
                                            :name="
                                                `${data[rowIndex].field}.details`
                                            "
                                            v-validate="`max:255`"
                                            v-model="data[rowIndex].details"
                                            :success="
                                                data[rowIndex].details.length >
                                                    0 &&
                                                    !errors.has(
                                                        `${data[rowIndex].field}.details`,
                                                        item.slug
                                                    )
                                            "
                                            :danger="
                                                errors.has(
                                                    `${data[rowIndex].field}.details`,
                                                    item.slug
                                                )
                                            "
                                            :danger-text="
                                                errors.first(
                                                    `${data[rowIndex].field}.details`,
                                                    item.slug
                                                )
                                            "
                                        />
                                    </vs-td>
                                </vs-tr>
                            </template>
                        </vs-table>
                        <div class="mt-3 text-sm">Obligatoire*</div>
                    </form>
                </vx-card>
            </vs-col>
        </vs-row>
        <vs-row
            v-if="inactiveDataTypes.length > 0"
            type="flex"
            vs-justify="center"
            vs-align="center"
            vs-w="12"
            class="my-6"
        >
            <vs-col
                vs-type="flex"
                vs-justify="center"
                vs-align="center"
                vs-w="6"
                vs-xs="12"
            >
                <v-select
                    v-model="currentDataType"
                    :options="inactiveDataTypes"
                    label="display_name_plurial"
                    placeholder="Type"
                    class="flex-1 mr-3"
                />

                <vs-button
                    type="filled"
                    @click="activateCurrentDataType"
                    :disabled="!currentDataType"
                >
                    Ajouter
                </vs-button>
            </vs-col>
        </vs-row>
    </div>
</template>

<script>
import vSelect from "vue-select";
import { Validator } from "vee-validate";

// Store Module
import moduleDataTypeManagement from "@/store/data-type-management/moduleDataTypeManagement.js";

import errorMessage from "./errorValidForm";
Validator.localize("fr", errorMessage);

export default {
    props: {
        module
    },
    components: {
        vSelect
    },
    data() {
        return {
            currentDataType: null,
            activeDataTypes: [],
            hasValidated: false
        };
    },
    computed: {
        dataTypes() {
            return this.$store.state.dataTypeManagement.data_types;
        },
        inactiveDataTypes() {
            return this.dataTypes.filter(
                dt =>
                    !this.activeDataTypes
                        .map(t => t.data_type_id)
                        .includes(dt.id)
            );
        }
    },
    methods: {
        statusColor(scope) {
            return !this.hasValidated
                ? "primary"
                : !this.errors.any(scope)
                ? "success"
                : "danger";
        },
        activateCurrentDataType() {
            const mDataType = this.module.module_data_types.find(
                mdt => mdt.data_type_id === this.currentDataType.id
            );
            const dataType = {
                data_type_id: this.currentDataType.id,
                slug: this.currentDataType.slug,
                display_name_plurial: this.currentDataType.display_name_plurial,
                source: mDataType ? mDataType.source : "",
                module_data_rows: this.currentDataType.data_rows.map(dr => {
                    const mDataRow = mDataType
                        ? mDataType.module_data_rows.find(
                              mdr => mdr.data_row_id === dr.id
                          )
                        : null;
                    return {
                        data_row_id: dr.id,
                        field: dr.field,
                        display_name: dr.display_name,
                        type: dr.type,
                        source: (mDataRow && mDataRow.source) || "",
                        default_value:
                            (mDataRow && mDataRow.default_value) || "",
                        details: (mDataRow && mDataRow.details) || "",
                        required: dr.required
                    };
                })
            };
            this.activeDataTypes.push(dataType);
            this.currentDataType = null;
        },
        removeDataType(index) {
            setTimeout(() => {
                this.activeDataTypes.splice(index, 1);
            }, 750);
        },
        beforeChange() {
            return new Promise((resolve, reject) => {
                this.$validator.validate().then(result => {
                    this.hasValidated = true;
                    if (result) {
                        this.updateModuleDataTypes().then(() => resolve(true));
                    } else {
                        reject("Error");
                    }
                });
            });
        },
        updateModuleDataTypes() {
            this.$vs.loading();
            const payload = {
                id: this.module.id,
                module_data_types: this.activeDataTypes.map(mdt => ({
                    data_type_id: mdt.data_type_id,
                    source: mdt.source,
                    module_data_rows: mdt.module_data_rows
                        .filter(mdr => mdr.source || mdr.default_value)
                        .map(mdr => ({
                            data_row_id: mdr.data_row_id,
                            source: mdr.source,
                            default_value: mdr.default_value,
                            details: mdr.details
                        }))
                }))
            };
            return this.$store
                .dispatch("moduleManagement/updateModuleDataTypes", payload)
                .then(response =>
                    this.$vs.notify({
                        title: "Succès",
                        text: "Données misent à jour avec succès",
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "success"
                    })
                )
                .catch(error =>
                    this.$vs.notify({
                        title: "Erreur",
                        text: error.message,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "danger"
                    })
                )
                .finally(() => {
                    this.$vs.loading.close();
                });
        }
    },
    created() {
        if (!moduleDataTypeManagement.isRegistered) {
            this.$store.registerModule(
                "dataTypeManagement",
                moduleDataTypeManagement
            );
            moduleDataTypeManagement.isRegistered = true;
        }

        this.$store
            .dispatch("dataTypeManagement/fetchItems")
            .then(response => {
                this.module.module_data_types.forEach(mdt => {
                    const dt = this.dataTypes.find(
                        dt => dt.id === mdt.data_type_id
                    );
                    if (dt) {
                        this.currentDataType = dt;
                        this.activateCurrentDataType();
                    }
                });
            })
            .catch(err => {
                console.error(err);
            });
    },
    beforeDestroy() {
        moduleDataTypeManagement.isRegistered = false;
        this.$store.unregisterModule("dataTypeManagement");
    }
};
</script>

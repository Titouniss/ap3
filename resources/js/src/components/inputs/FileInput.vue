<template>
    <div style="min-width: 200px;">
        <small class="date-label">Documents</small>
        <div class="filesContainer">
            <div
                v-if="!activeAddUrl"
                class="my-2 flex flex-row items-center justify-between"
            >
                <label for="files" class="fileContainer py-2 px-3 mr-2 w-1/2">
                    <feather-icon icon="UploadIcon" svgClasses="h-4 w-4 mr-2" />
                    Importer
                </label>
                <input
                    type="file"
                    name="files"
                    id="files"
                    ref="files"
                    @click="reset"
                    @change="uploadFile"
                    class="inputfile"
                    accept="application/pdf|text/html|images/*"
                />
                <label
                    class="fileContainer py-2 px-3 w-1/2"
                    @click="activeAddUrl = true"
                >
                    <feather-icon icon="LinkIcon" svgClasses="h-4 w-4 mr-2" />
                    Web
                </label>
            </div>

            <div v-if="activeAddUrl">
                <vs-input
                    v-validate="{ url: { require_protocol: true } }"
                    placeholder="https://..."
                    name="url"
                    v-model="url"
                    class="w-full"
                    :success="url.length > 0 && !errors.has('url')"
                    :danger="url.length > 0 && errors.has('url')"
                />
                <div class="mt-1 flex flex-row justify-end items-center">
                    <feather-icon
                        icon="XIcon"
                        svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
                        @click="activeAddUrl = false"
                    />
                    <feather-icon
                        icon="PlusIcon"
                        :svgClasses="[
                            'h-5 w-5 mx-2',
                            {
                                'hover:text-primary': validateForm,
                                'cursor-pointer': validateForm
                            }
                        ]"
                        @click="addUrl"
                    />
                </div>
            </div>

            <div v-for="item in items" v-bind:key="item.id">
                <div
                    class="fileContainer fileContainerValid my-2 py-2 px-3 justify-between items-center"
                >
                    <div class="truncate">
                        {{ item.name }}
                    </div>
                    <feather-icon
                        icon="TrashIcon"
                        @click="onDelete(item)"
                        svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Validator } from "vee-validate";
export default {
    name: "file-input",
    props: {
        items: {
            type: Array,
            required: true
        },
        onUpload: {
            type: Function,
            required: true
        },
        onDelete: {
            type: Function,
            required: true
        }
    },
    data() {
        return {
            activeAddUrl: false,
            url: ""
        };
    },
    computed: {
        validateForm() {
            return !this.errors.any() && this.url !== "";
        }
    },
    methods: {
        reset() {
            if (this.$refs && this.$refs.files) {
                this.$refs.files.value = "";
            }
            this.url = "";
            this.activeAddUrl = false;
        },
        uploadFile(e) {
            e.preventDefault();
            const files = e.target.files;
            if (files.length > 0) {
                this.onUpload(files[0], true);
            }
        },
        addUrl() {
            if (!this.validateForm) {
                return;
            }

            let name = this.url.split("/").pop();
            if (name.indexOf(".") === -1) {
                name = this.url.replace("https://", "").replace("http://", "");
            }

            this.onUpload(
                {
                    name: name,
                    path: this.url
                },
                false
            );
            this.reset();
        }
    }
};
</script>

<style>
.inputfile {
    width: 0.1px;
    height: 0.1px;
    opacity: 0;
    overflow: hidden;
    position: absolute;
    z-index: -1;
}
.filesContainer {
    flex-direction: column;
    flex-wrap: wrap;
}
.fileContainer {
    display: flex;
    border: 1px dashed #d8d8d8;
    border-radius: 5px;
    font-size: 12px;
    cursor: pointer;
}
.fileContainerValid {
    cursor: auto;
    border-color: #2196f3;
}
</style>

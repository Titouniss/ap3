<template>
    <div style="min-width: 200px;">
        <small class="date-label">Documents</small>
        <div class="filesContainer">
            <div
                v-if="!isUrlMode"
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
                    @click="resetFileInput"
                    @change="onFileAdd"
                    class="inputfile"
                    accept="application/pdf|text/html|images/*"
                />
                <label
                    class="fileContainer py-2 px-3 w-1/2"
                    @click="isUrlMode = true"
                >
                    <feather-icon icon="GlobeIcon" svgClasses="h-4 w-4 mr-2" />
                    Web
                </label>
            </div>

            <div v-if="isUrlMode">
                <vs-input
                    v-validate="{
                        url: { require_protocol: true },
                        required: true,
                        max: 2000
                    }"
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
                        @click="isUrlMode = false"
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
                        @click="onUrlAdd"
                    />
                </div>
            </div>

            <div v-for="item in displayItems" v-bind:key="item.id">
                <div
                    class="fileContainer fileContainerValid my-2 py-2 px-3 justify-between items-center"
                >
                    <div
                        class="truncate hover:text-primary cursor-pointer"
                        @click="openUrl(item.url)"
                    >
                        {{ item.name }}
                    </div>
                    <feather-icon
                        icon="TrashIcon"
                        @click="deleteFile(item)"
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
        token: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            url: "",
            isUrlMode: false,
            hiddenItems: []
        };
    },
    computed: {
        validateForm() {
            return !this.errors.any() && this.url !== "";
        },
        displayItems() {
            return this.items.filter(
                item => this.hiddenItems.indexOf(item) === -1
            );
        }
    },
    methods: {
        openUrl(url) {
            window.open(url);
        },
        resetFileInput() {
            if (this.$refs && this.$refs.files) {
                this.$refs.files.value = "";
            }
        },
        onFileAdd(e) {
            e.preventDefault();
            const files = e.target.files;
            if (files.length > 0) {
                this.uploadFile(files[0], true);
            }
        },
        onUrlAdd() {
            if (!this.validateForm) {
                return;
            }

            let name = this.url.split("/").pop();
            if (name.indexOf(".") === -1) {
                name = this.url.replace("https://", "").replace("http://", "");
            }

            this.uploadFile(
                {
                    name: name,
                    path: this.url
                },
                false
            );
            this.url = "";
            this.isUrlMode = false;
        },
        uploadFile(item, is_file = true) {
            const action = `documentManagement/${
                is_file ? "uploadFile" : "addItem"
            }`;
            const payload = is_file ? {} : item;

            if (is_file) {
                const data = new FormData();
                data.append("files", item);
                payload.files = data;
            }
            payload.token = this.token;

            this.$store
                .dispatch(action, payload)
                .then(response => {
                    this.items.push(response.data.success);
                })
                .catch(error => {});
        },
        deleteFile(file) {
            if (file.token) {
                this.$store
                    .dispatch("documentManagement/deleteFile", file.id)
                    .then(response => {
                        const index = this.items.indexOf(file);
                        if (index > -1) {
                            this.items.splice(index, 1);
                        }
                    })
                    .catch(error => {});
            } else {
                const index = this.items.indexOf(file);
                if (index > -1) {
                    this.items.splice(index, 1);
                }
            }
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

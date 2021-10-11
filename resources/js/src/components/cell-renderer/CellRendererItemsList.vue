<template>
    <vs-dropdown vs-custom-content class="w-full hover:text-primary">
        <div class="p-3 cursor-pointer flex items-end text-lg font-medium">
            <span class="mr-2 leading-none">Voir</span>
            <feather-icon icon="ChevronDownIcon" svgClasses="h-4 w-4" />
        </div>

        <vs-dropdown-menu>
            <div class="p-3 w-full flex items-center justify-start flex-wrap">
                <vs-chip
                    v-for="item in items"
                    :key="item.id"
                    :color="stringToHslColor(itemText(item))"
                    class="m-1"
                >
                    {{ itemText(item) }}
                </vs-chip>
                <span v-if="!items || items.length === 0">
                    Pas de données
                </span>
            </div>
        </vs-dropdown-menu>
    </vs-dropdown>
</template>

<script>
export default {
    name: "CellRendererItemsList",
    computed: {
        items() {
            const items = [...(this.params.value || []).filter(this.itemText)];
            items.sort((a, b) => {
                if (this.itemText(a) === this.itemText(b)) return 0;

                return this.itemText(a) < this.itemText(b) ? -1 : 1;
            });
            return items;
        }
    },
    methods: {
        itemText(item) {
            return this.params.reduce
                ? this.params.reduce(item)
                : item[this.params.label];
        },
        stringToHslColor(str, s = 60, l = 60) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                hash = str.charCodeAt(i) + ((hash << 5) - hash);
            }

            let h = hash % 360;
            return "hsl(" + h + ", " + s + "%, " + l + "%)";
        }
    }
};
</script>

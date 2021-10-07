<template>
    <vs-dropdown vs-custom-content class="w-full hover:text-primary">
        <div class="p-3 cursor-pointer flex items-end text-lg font-medium">
            <span class="mr-2 leading-none">Voir</span>
            <feather-icon icon="ChevronDownIcon" svgClasses="h-4 w-4" />
        </div>

        <vs-dropdown-menu>
            <div class="p-3 w-full flex items-center justify-start flex-wrap">
                <vs-chip
                    v-for="skill in skills"
                    :key="skill.id"
                    :color="stringToHslColor(skill.name)"
                    class="m-1"
                >
                    {{ skill.name }}
                </vs-chip>
                <span v-if="!skills || skills.length === 0"
                    >Pas de compétences</span
                >
            </div>
        </vs-dropdown-menu>
    </vs-dropdown>
</template>

<script>
export default {
    name: "CellRendererSkills",
    computed: {
        skills() {
            const skills = [
                ...(this.params.value || []).filter(skill => skill.name)
            ];
            skills.sort((a, b) => {
                if (a.name === b.name) return 0;

                return a.name < b.name ? -1 : 1;
            });
            return skills;
        }
    },
    methods: {
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

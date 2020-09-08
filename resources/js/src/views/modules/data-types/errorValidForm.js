import fr from "vee-validate/dist/locale/fr";
fr.messages = {
    ...fr.messages,
    required: () => "Obligatoire",
    max: (name, nb) => `${nb} charactères maximum`
};

export default fr;

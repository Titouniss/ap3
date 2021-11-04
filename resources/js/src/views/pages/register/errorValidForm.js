const errorMessage = {
    attributes: {
        firstname: "prénom",
        lastname: "nom",
        contact_function: "fonction",
        email: "e-mail",
        contact_tel1: "n° de téléphone",
        password: "mot de passe",
        confirm_password: "confirmation de mot de passe",
        company: "société"
    },
    custom: {
        firstname: {
            regex: "Le champ prénom ne peut pas contenir des chiffres"
        },
        lastname: {
            regex: "Le champ nom ne peut pas contenir des chiffres"
        }
    }
};

export default errorMessage;

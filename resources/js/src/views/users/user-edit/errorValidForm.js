const errorMessage = {
    custom: {
        old_password: {
            required: "L'ancien mot de passe est requis"
        },
        password: {
            required: "Le nouveau mot de passe est requis",
            max:
                "Le nouveau mot de passe doit être composé de 50 caractères maximum",
            min:
                "Le nouveau mot de passe doit être composé de 8 caractères minimum"
        },
        confirm_password: {
            required: "La confirmation de mot de passe est requis",
            max:
                "La confirmation de mot de passe doit être composée de 50 caractères maximum",
            min:
                "La confirmation de mot de passe doit être composée de 8 caractères minimum",
            confirmed:
                "La confirmation de passe ne correspond pas au mot de passe"
        }
    }
};

export default errorMessage;

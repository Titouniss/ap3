const errorMessage = {
  custom: {
    lastname: {
      required: "Le nom de l\'utilisteur est requis",
      max: "Le nom de l\'utilisteur doit être composé de 255 caractères maximum",
    },
    firstname: {
      required: "Le prénom de l\'utilisteur est requis",
      max: "Le prénom de l\'utilisteur doit être composé de 255 caractères maximum",
    },
    email: {
      required: "L\'email de l\'utilisteur est requis",
      email: "Le format de l'email est invalide"
    },
    company_id: {
      required: "L\'utilisteur doit être rattaché à une société"
    },
    role: {
      required: "L\'utilisteur doit être rattaché à un rôle"
    },
  }
}

export default errorMessage

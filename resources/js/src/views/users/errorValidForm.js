const errorMessage = {
  custom: {
    lastname: {
      required: "Le nom de l\'utilisteur est requis"
    },
    firstname: {
      required: "Le prénom de l\'utilisteur est requis"
    },
    email: {
      required: "L\'email de l\'utilisteur est requis",
      email: "Le format de l'email est invalide"
    },
    company_id: {
      required: "L\'utilisteur doit être rattaché à une compagnie"
    },
    role: {
      required: "L\'utilisteur doit être rattaché à un rôle"
    },
  }
}

export default errorMessage

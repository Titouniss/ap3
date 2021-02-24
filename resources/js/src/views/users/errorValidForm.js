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
    login: {
      required: "L'identifiant' de l\'utilisteur est requis",
      max: "L'identifiant de l\'utilisteur doit être composé de 50 caractères maximum",
      regex: "L'identifiant ne doit pas contenir d'accents ou de caractères spéciaux"
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
    hours: {
        required: "Le nombre d'heures supplémentaires est requis",       
        regex: "Le nombre d'heures peut être positif ou négatif et peut contenir jusqu'à 3 chiffres pour la partie entière et 2 chiffres après la virgule"
    },
  }
}

export default errorMessage

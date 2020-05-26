const errorMessage = {
  custom: {
    name: {
      required: "Le nom d'une société est requis",
      max: "Le nom d'une société doit être composé de 255 chiffres maximum !",
    },
    siret: {
      required: "Le numéro de siret est requis",
      numeric: "Le numéro de siret doit être composé uniquement de chiffre !",
      max: "Le numéro de siret doit être composé de 14 chiffres !",
      min: "Le numéro de siret doit être composé de 14 chiffres !",
    }
  }
}

export default errorMessage

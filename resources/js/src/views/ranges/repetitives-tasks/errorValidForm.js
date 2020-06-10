const errorMessage = {
  custom: {
    name: {
      required: "Le nom d'une étape est requis"
    },
    skills: {
      required: 'Une étape doit être rattachée au minimum à une compétence'
    },
    description: {
      max: "La description ne doit pas dépasser 1500 caractères"
    }
  }
}

export default errorMessage

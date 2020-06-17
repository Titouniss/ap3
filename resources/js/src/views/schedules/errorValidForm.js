const errorMessage = {
  custom: {
    title: {
      required: "Le nom de la tâche est requis",
      max: "Le nom de la tâche doit être composé de 255 caractères maximum",
    },
    startDate: {
      required: "La date de début est requise"
    },
    endDate: {
      required: "La date de fin est requise",
      before: "La date de fin doit être plus grande que la date de début",

    }
  }
}

export default errorMessage

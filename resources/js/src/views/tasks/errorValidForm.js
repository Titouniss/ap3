const errorMessage = {
  custom: {
    name: {
      required: "Le nom d'une tâche est requis",
      max: "Le nom ne doit pas depasser 255 caractères"
    },
    estimatedTime: {
      required: 'L\'estimation en heure d\'une tâche est requise',
      numeric: "L\'estimation doit être positive"
    },
    skills: {
      required: 'Une tache doit être rattachée au minimum à une compétence'
    },
    workarea: {
      required: "Vous devez selectionner un pôle de produciton lorsqu'une ou plusieurs compétences sont selectionnées"
    },
    userId: {
      required: 'Vous devez attribuer cette tâche à un utilisateur'
    },
    projectId: {
      required: "Vous devez définir un projet pour cette tâche"
    }
  }
}

export default errorMessage

const errorMessage = {
  custom: {
    name: {
      required: "Le nom d'une tâche est requis"
    },
    estimatedTime: {
      required: 'L\'estimation en heure d\'une tâche est requise',
      numeric: "L\'estimation doit être positive"
    },
    skills: {
      required: 'Une tache doit être rattachée au minimum à une compétence'
    },
  }
}

export default errorMessage

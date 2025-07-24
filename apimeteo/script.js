// Liste des images d'arrière-plan
const images = [
  "images/2.jpg",
  "images/anime_cloud.jpg",
  "images/04.jpg",
  "images/05.jpg",
  "images/6.jpg",
  "images/11.jpg",
];

let currentImageIndex = 0;
const backgroundContainer = document.querySelector(".background-container");

// Fonction pour changer d'image
function changeBackgroundImage() {
  currentImageIndex = (currentImageIndex + 1) % images.length; // Boucle infinie
  backgroundContainer.style.backgroundImage = `url(${images[currentImageIndex]})`;
}

// Initialisation
backgroundContainer.style.backgroundImage = `url(${images[0]})`; // Image de départ
setInterval(changeBackgroundImage, 10000); // Change toutes les 10 secondes

window.addEventListener("DOMContentLoaded", () => {
  // Animation du texte (titre + paragraphe)
  gsap.from(".hero-content h1", {
    duration: 1,
    opacity: 0,
    y: 50,
    ease: "power3.out",
  });

  gsap.from(".hero-content p", {
    duration: 1,
    opacity: 0,
    y: 30,
    delay: 0.3,
    ease: "power3.out",
  });

  // Animation de la photo
  gsap.from(".hero-image", {
    duration: 1,
    opacity: 0,
    scale: 0.8,
    delay: 0.2,
    ease: "power2.out",
  });

  // Animation flottante des cercles de fond (loop)
  gsap.to(".blur-circle", {
    y: 60,
    x: 60,
    repeat: -1,
    yoyo: true,
    ease: "sine.inOut",
    duration: 3,
  });
});

gsap.fromTo(
  ".hero-button",
  {
    opacity: 0,
    scale: 0.8,
  },
  {
    opacity: 1,
    scale: 1,
    duration: 0.8,
    delay: 0.6,
    ease: "back.out(1.7)",
  }
);

document.querySelectorAll(".service-card").forEach((card) => {
  card.addEventListener("mouseenter", () => {
    gsap.to(card, {
      y: -10,
      scale: 1.03,
      boxShadow: "0 15px 30px rgba(0,0,0,0.2)",
      duration: 0.3,
      ease: "power2.out",
    });
  });

  card.addEventListener("mouseleave", () => {
    gsap.to(card, {
      y: 0,
      scale: 1,
      boxShadow: "0 10px 20px rgba(0,0,0,0.1)",
      duration: 0.3,
      ease: "power2.inOut",
    });
  });
});

// Enregistrement de ScrollTrigger
gsap.registerPlugin(ScrollTrigger);
// Animation des sections au scroll

gsap.from(".service-card", {
  scrollTrigger: {
    trigger: ".services-grid",
    start: "top 80%",
    toggleActions: "play none none none",
  },
  y: 50,
  opacity: 0,
  duration: 1.4,
  stagger: 0.2,
  ease: "power3.out",
});

// Animation des compétences au scroll

gsap.from(".skill-item", {
  scrollTrigger: {
    trigger: ".skills-grid",
    start: "top 80%",
  },
  opacity: 0,
  rotation: 15,
  filter: "blur(10px)",
  duration: 1.6,
  ease: "power3.out",
  stagger: 0.1,
  onUpdate: function () {},
  clearProps: "filter",
});

// Register GSAP plugins
gsap.registerPlugin(ScrollTrigger, TextPlugin);

// Navigation scroll effect
const navContainer = document.querySelector(".nav-container");

window.addEventListener("scroll", () => {
  if (window.scrollY > 50) {
    navContainer.classList.add("nav-scrolled");
  } else {
    navContainer.classList.remove("nav-scrolled");
  }
});

// Mobile navigation toggle
const mobileNavToggle = document.querySelector(".mobile-nav-toggle");
const navLinks = document.querySelector(".nav-links");

mobileNavToggle.addEventListener("click", () => {
  navLinks.classList.toggle("open");
  mobileNavToggle.classList.toggle("mobile-nav-open");
});

// Smooth scroll for navigation links
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();

    const targetId = this.getAttribute("href");
    const targetElement = document.querySelector(targetId);

    if (targetElement) {
      window.scrollTo({
        top: targetElement.offsetTop - 80,
        behavior: "smooth",
      });

      // Close mobile menu if open
      navLinks.classList.remove("open");
      mobileNavToggle.classList.remove("mobile-nav-open");

      // Update active link
      document.querySelectorAll(".nav-links a").forEach((link) => {
        link.classList.remove("active");
      });
      this.classList.add("active");
    }
  });
});

// Active navigation based on scroll position
function updateNavigation() {
  const sections = document.querySelectorAll("section");
  const navLinks = document.querySelectorAll(".nav-links a");

  let currentSectionId = "";

  sections.forEach((section) => {
    const sectionTop = section.offsetTop - 100;
    const sectionHeight = section.offsetHeight;

    if (
      window.scrollY >= sectionTop &&
      window.scrollY < sectionTop + sectionHeight
    ) {
      currentSectionId = section.getAttribute("id");
    }
  });

  navLinks.forEach((link) => {
    link.classList.remove("active");
    if (link.getAttribute("href") === `#${currentSectionId}`) {
      link.classList.add("active");
    }
  });
}

window.addEventListener("scroll", updateNavigation);

// Contact form handling
// document.getElementById("contactForm").addEventListener("submit", function (e) {
//   e.preventDefault();

//   const name = document.getElementById("name").value.trim();
//   const email = document.getElementById("email").value.trim();
//   const phone = document.getElementById("phone").value.trim();
//   const message = document.getElementById("message").value.trim();
//   const formMessage = document.getElementById("formMessage");

//   const radios = document.getElementsByName("radio");
//   let selectedValue = "";
//   for (const radio of radios) {
//     if (radio.checked) {
//       selectedValue = radio.value;
//       break;
//     }
//   }

//   // Validate form
//   if (name === "" || email === "" || phone === "" || message === "") {
//     formMessage.textContent = "Tous les champs sont obligatoires.";
//     formMessage.style.color = "#ff6b6b";
//     return;
//   }

//   // Here you would typically send the form data to your server
//   // For now, we'll just show a success message
//   formMessage.textContent = "Votre message a été envoyé avec succès !";
//   formMessage.style.color = "#14ffec";
//   this.reset();

//   // Add animation to the success message
//   gsap.fromTo(
//     formMessage,
//     { y: 20, opacity: 0 },
//     { y: 0, opacity: 1, duration: 0.5, ease: "power2.out" }
//   );
// });

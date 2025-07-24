document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("contactForm");
  const messageEl = document.getElementById("formMessage");

  if (!form || !messageEl) {
    console.error("Éléments du formulaire introuvables");
    return;
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    // Vider le message précédent
    messageEl.textContent = "";

    // Récupérer les données du formulaire
    const formData = new FormData(form);

    // Vérifier les champs requis
    const name = formData.get("name")?.trim() || "";
    const email = formData.get("email")?.trim() || "";
    const phone = formData.get("phone")?.trim() || "";
    const message = formData.get("message")?.trim() || "";

    // Validation des champs
    const missing = [];
    if (!name) missing.push("nom");
    if (!email) missing.push("email");
    if (!phone) missing.push("téléphone");
    if (!message) missing.push("message");

    if (missing.length > 0) {
      messageEl.textContent =
        "Veuillez remplir tous les champs requis: " + missing.join(", ");
      messageEl.style.color = "red";
      return;
    }

    // Validation de l'email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      messageEl.textContent = "Veuillez saisir une adresse email valide.";
      messageEl.style.color = "red";
      return;
    }

    // Vérifier si reCAPTCHA est disponible
    if (typeof grecaptcha === "undefined") {
      messageEl.textContent = "Erreur : reCAPTCHA non chargé";
      messageEl.style.color = "red";
      return;
    }

    // Désactiver le bouton pendant l'envoi
    const submitBtn = form.querySelector('input[type="submit"]');
    const originalText = submitBtn.value;
    submitBtn.disabled = true;
    submitBtn.value = "Envoi en cours...";

    // Exécuter reCAPTCHA et envoyer
    grecaptcha.ready(function () {
      grecaptcha
        .execute("6Lcc74wrAAAAACk_-SE87CVgqAn7QLOCH-fewhki", {
          action: "submit",
        })
        .then(function (token) {
          // Ajouter le token reCAPTCHA
          formData.append("g-recaptcha-response", token);

          // Envoyer les données
          fetch("service/send.php", {
            method: "POST",
            body: formData,
          })
            .then((res) => {
              if (!res.ok) {
                throw new Error(`Erreur HTTP: ${res.status}`);
              }
              return res.json();
            })
            .then((data) => {
              if (data.success) {
                messageEl.textContent = data.message;
                messageEl.style.color = "#00ff88"; // Vert cyan
                form.reset();
              } else {
                messageEl.textContent = data.message;
                messageEl.style.color = "red";
              }
            })
            .catch((err) => {
              messageEl.textContent =
                "Erreur de connexion. Veuillez réessayer.";
              messageEl.style.color = "red";
            })
            .finally(() => {
              // Réactiver le bouton
              submitBtn.disabled = false;
              submitBtn.value = originalText;
            });
        })
        .catch((err) => {
          messageEl.textContent =
            "Erreur reCAPTCHA. Veuillez recharger la page.";
          messageEl.style.color = "red";
          submitBtn.disabled = false;
          submitBtn.value = originalText;
        });
    });
  });
});

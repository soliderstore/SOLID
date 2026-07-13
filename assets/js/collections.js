/* ===========================================
   COLLECTIONS.JS
   SØLID
=========================================== */

/* ==========================
   FILTRO DAS COLEÇÕES
========================== */

const filterButtons = document.querySelectorAll(".filter-btn");
const collectionCards = document.querySelectorAll(".collection-card");

filterButtons.forEach(button => {

    button.addEventListener("click", () => {

        // Remove botão ativo
        filterButtons.forEach(btn => btn.classList.remove("active"));

        // Ativa botão clicado
        button.classList.add("active");

        const filter = button.dataset.filter;

        collectionCards.forEach(card => {

            const category = card.dataset.category;

            if (filter === "all" || category === filter) {

                card.style.display = "block";

                setTimeout(() => {

                    card.style.opacity = "1";
                    card.style.transform = "scale(1)";

                }, 50);

            } else {

                card.style.opacity = "0";
                card.style.transform = "scale(.9)";

                setTimeout(() => {

                    card.style.display = "none";

                }, 250);

            }

        });

    });

});


/* ==========================
   ANIMAÇÃO AO ROLAR
========================== */

const observer = new IntersectionObserver((entries) => {

    entries.forEach(entry => {

        if (entry.isIntersecting) {

            entry.target.classList.add("show");

        }

    });

}, {
    threshold: .15
});

collectionCards.forEach(card => {

    observer.observe(card);

});


/* ==========================
   EFEITO PARALLAX NO HERO
========================== */

const hero = document.querySelector(".collections-hero");

window.addEventListener("scroll", () => {

    const scroll = window.pageYOffset;

    hero.style.backgroundPositionY = scroll * 0.4 + "px";

});


/* ==========================
   BOTÃO EXPLORE
========================== */

const heroButton = document.querySelector(".hero-btn");

heroButton.addEventListener("click", (event) => {

    event.preventDefault();

    document.querySelector("#collections").scrollIntoView({

        behavior: "smooth"

    });

});


/* ==========================
   HOVER DOS CARDS
========================== */

collectionCards.forEach(card => {

    card.addEventListener("mousemove", (e) => {

        const rect = card.getBoundingClientRect();

        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        card.style.setProperty("--x", x + "px");
        card.style.setProperty("--y", y + "px");

    });

});


/* ==========================
   NEWSLETTER
========================== */

const newsletter = document.querySelector(".newsletter form");

newsletter.addEventListener("submit", function(e){

    e.preventDefault();

    const email = this.querySelector("input").value.trim();

    if(email === ""){

        alert("Digite um e-mail.");

        return;

    }

    alert("Obrigado por fazer parte da SØLID!");

    this.reset();

});


/* ==========================
   LOADING DOS CARDS
========================== */

window.addEventListener("load", () => {

    collectionCards.forEach((card, index) => {

        card.style.opacity = "0";
        card.style.transform = "translateY(50px)";

        setTimeout(() => {

            card.style.transition = ".7s";

            card.style.opacity = "1";
            card.style.transform = "translateY(0)";

        }, index * 180);

    });

});
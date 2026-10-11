/* ======================================================
   Ihsan Setyo Nugroho Portfolio
   JavaScript Interaction
====================================================== */


/* ======================================================
PRELOADER
====================================================== */

window.addEventListener("load", () => {

    const preloader = document.getElementById("preloader");

    setTimeout(() => {

        preloader.style.opacity = "0";

        preloader.style.visibility = "hidden";

    },800);

});



/* ======================================================
HEADER CHANGE ON SCROLL
====================================================== */

const header = document.getElementById("header");


window.addEventListener("scroll",()=>{


    if(window.scrollY > 50){

        header.classList.add("active");

    }else{

        header.classList.remove("active");

    }


});



/* ======================================================
MOBILE MENU
====================================================== */


const menuToggle = document.getElementById("menuToggle");

const navMenu = document.querySelector(".nav-menu");

const navLinks = document.querySelectorAll(".nav-link");



menuToggle.addEventListener("click",()=>{


    navMenu.classList.toggle("active");

    menuToggle.classList.toggle("active");


});



navLinks.forEach(link=>{


    link.addEventListener("click",()=>{


        navMenu.classList.remove("active");

        menuToggle.classList.remove("active");


    });


});



/* ======================================================
ACTIVE NAV LINK
====================================================== */


const sections = document.querySelectorAll("section");


window.addEventListener("scroll",()=>{


    let current="";


    sections.forEach(section=>{


        const sectionTop = section.offsetTop - 120;

        const sectionHeight = section.offsetHeight;


        if(scrollY >= sectionTop &&
           scrollY < sectionTop + sectionHeight){

            current = section.getAttribute("id");

        }


    });



    navLinks.forEach(link=>{


        link.classList.remove("active");


        if(link.getAttribute("href") === "#" + current){

            link.classList.add("active");

        }


    });



});



/* ======================================================
BACK TO TOP
====================================================== */


const scrollTop = document.getElementById("scrollTop");



window.addEventListener("scroll",()=>{


    if(window.scrollY > 500){


        scrollTop.classList.add("active");


    }else{


        scrollTop.classList.remove("active");


    }


});



scrollTop.addEventListener("click",(e)=>{


    e.preventDefault();


    window.scrollTo({

        top:0,

        behavior:"smooth"

    });


});



/* ======================================================
SMOOTH SCROLL ALL LINKS
====================================================== */


document.querySelectorAll('a[href^="#"]').forEach(anchor=>{


    anchor.addEventListener("click",function(e){


        const target = document.querySelector(
            this.getAttribute("href")
        );


        if(target){


            e.preventDefault();


            target.scrollIntoView({

                behavior:"smooth"

            });


        }


    });


});
/* ======================================================
DARK / LIGHT MODE
====================================================== */

const themeButton = document.getElementById("themeButton");

const themeIcon = themeButton.querySelector("i");


themeButton.addEventListener("click",()=>{


    document.body.classList.toggle("light-mode");


    if(document.body.classList.contains("light-mode")){


        themeIcon.classList.remove(
            "ri-moon-clear-line"
        );


        themeIcon.classList.add(
            "ri-sun-line"
        );


        localStorage.setItem(
            "theme",
            "light"
        );


    }else{


        themeIcon.classList.remove(
            "ri-sun-line"
        );


        themeIcon.classList.add(
            "ri-moon-clear-line"
        );


        localStorage.setItem(
            "theme",
            "dark"
        );


    }


});



/* ======================================================
LOAD SAVED THEME
====================================================== */


window.addEventListener("DOMContentLoaded",()=>{


    const savedTheme = localStorage.getItem(
        "theme"
    );


    if(savedTheme === "light"){


        document.body.classList.add(
            "light-mode"
        );


        themeIcon.classList.remove(
            "ri-moon-clear-line"
        );


        themeIcon.classList.add(
            "ri-sun-line"
        );


    }


});



/* ======================================================
SCROLL REVEAL ANIMATION
====================================================== */


const revealElements = document.querySelectorAll(
`
.hero-left,
.hero-right,
.section-header,
.about-image,
.about-content,
.stat-card,
.skill-card,
.project-card,
.organization-left,
.organization-right,
.gallery-item,
.contact-information,
.contact-card
`
);



revealElements.forEach(element=>{


    element.style.opacity="0";

    element.style.transform="translateY(40px)";

    element.style.transition=
    "all .8s ease";


});



function revealOnScroll(){


    const windowHeight =
    window.innerHeight;



    revealElements.forEach(element=>{


        const elementTop =
        element.getBoundingClientRect().top;



        if(elementTop < windowHeight - 100){


            element.style.opacity="1";

            element.style.transform=
            "translateY(0)";


        }


    });


}



window.addEventListener(
    "scroll",
    revealOnScroll
);


window.addEventListener(
    "load",
    revealOnScroll
);



/* ======================================================
TYPING EFFECT
====================================================== */


const heroTitle =
document.querySelector(
".hero-tag"
);



if(heroTitle){


    const originalText =
    heroTitle.innerText;


    heroTitle.innerHTML="";


    let index=0;



    function typing(){


        if(index < originalText.length){


            heroTitle.innerHTML +=
            originalText.charAt(index);


            index++;


            setTimeout(
                typing,
                50
            );


        }


    }



    setTimeout(
        typing,
        1000
    );


}



/* ======================================================
IMAGE HOVER PARALLAX
====================================================== */


const imageBox =
document.querySelector(
".hero-image-box"
);



if(imageBox){


    imageBox.addEventListener(
        "mousemove",
        (e)=>{


            const x =
            (window.innerWidth / 2 -
            e.pageX) / 40;



            const y =
            (window.innerHeight / 2 -
            e.pageY) / 40;



            imageBox.style.transform =
            `
            rotateY(${x}deg)
            rotateX(${y}deg)
            `;


        }
    );



    imageBox.addEventListener(
        "mouseleave",
        ()=>{


            imageBox.style.transform =
            "rotateY(0) rotateX(0)";


        }
    );


}



/* ======================================================
GALLERY LIGHT EFFECT
====================================================== */


const galleryImages =
document.querySelectorAll(
".gallery-item img"
);



galleryImages.forEach(image=>{


    image.addEventListener(
        "click",
        ()=>{


            const overlay =
            document.createElement(
                "div"
            );


            overlay.className =
            "image-preview";


            overlay.innerHTML =
            `
            <img src="${image.src}">
            `;



            document.body.appendChild(
                overlay
            );



            setTimeout(()=>{


                overlay.classList.add(
                    "show"
                );


            },50);



            overlay.addEventListener(
                "click",
                ()=>{


                    overlay.remove();


                }
            );


        }
    );


});



/* ======================================================
ADD LIGHTBOX STYLE
====================================================== */


const lightboxStyle =
document.createElement(
"style"
);



lightboxStyle.innerHTML =
`

.image-preview{

position:fixed;

inset:0;

background:
rgba(0,0,0,.85);

display:flex;

align-items:center;

justify-content:center;

z-index:99999;

opacity:0;

transition:.3s;

padding:30px;

}


.image-preview.show{

opacity:1;

}


.image-preview img{

max-width:900px;

max-height:85vh;

border-radius:25px;

box-shadow:
0 20px 60px rgba(0,0,0,.5);

}

`;



document.head.appendChild(
    lightboxStyle
);



/* ======================================================
DISABLE RIGHT CLICK IMAGE
====================================================== */


document.querySelectorAll(
"img"
).forEach(img=>{


    img.addEventListener(
        "contextmenu",
        e=>{

            e.preventDefault();

        }
    );


});

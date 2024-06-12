


















/**
* Landing page animation
* */
document.addEventListener("DOMContentLoaded", function() {
    const leftCol = document.getElementById('leftCol');
    const rightCol = document.getElementById('rightCol');
    const heroImage = document.getElementById('heroImage');

    // Add animation class to left column on page load
    leftCol.classList.add('animate-left');
    // Add animation class to scale up image on page load
    heroImage.classList.add('fade-in');




    // Get the span element containing the text
    const animatedText = document.getElementById("animatedText");

    // Get the text content
    const textContent = animatedText.innerText;

    // At beginning of animation, write only first letter and that is too hidden by transparent color. Writing empty string loses subtle smoothness
    animatedText.innerHTML = `<span style="color: transparent">${textContent[0]}</span>`;
    let animText = '';
    let delayTime = 700; // Initial delay in milliseconds
    let acceleration = 30; // Amount to reduce delay by in each step

    // Loop through each character of the text content and add them with delay
    for (let i = 0; i < textContent.length; i++) {
        let t = delayTime + acceleration * i;
        setTimeout(function() {
            animText += textContent[i];
            animatedText.innerHTML = animText;
        }, t);
    }

    // Add class for typing animation
    animatedText.classList.add("typing-text");




    /********** Alternating images ***********/
    const imagePath = "https://nanybot-landing.s3.us-west-1.amazonaws.com/assets/images";
    const imageNames = ["ask-me-bot.png", "mobile-chatbot.png"]; // Add more image names as needed

    let currentIndex = 0; // Index to track current image in the array
    const fadeInDuration = 1000; // Duration for fade in transition in milliseconds
    const fadeOutDuration = 1000; // Duration for fade out transition in milliseconds

    // Function to change the image with fade in/out effect
    function changeImage() {
        const heroImage = document.getElementById("heroImage");
        const nextIndex = (currentIndex + 1) % imageNames.length;
        const nextImage = new Image();
        nextImage.src = imagePath + '/' + imageNames[nextIndex];
        nextImage.classList.add("w-full", "md:w-4/5", "hidden", "fade-in");
        nextImage.onload = function() {
            // Fade out current image
            heroImage.style.opacity = 0;
            setTimeout(() => {
                // Change src attribute to next image
                heroImage.src = nextImage.src;
                // Fade in next image
                heroImage.style.opacity = 1;
                // Update currentIndex
                currentIndex = nextIndex;
            }, fadeOutDuration);
        };
    }

    setInterval(changeImage, 5000);







    /**
     *  apply css on menu item when corresponding section is in viewport
     * */

    // Get all menu links
    const menuLinks = document.querySelectorAll('.scrollable');

    // Function to determine if an element is in the viewport
    function isInViewport(element) {
        const rect = element.getBoundingClientRect();
        const windowHeight = window.innerHeight || document.documentElement.clientHeight;

        /* Check if any part of the element is within the viewport */
        return (
            rect.top <= windowHeight * 0.1 /* If top is inside 10% of windowHeight */
            &&
            rect.bottom >= windowHeight * 0.1 /* If bottom is inside 10% of windowHeight */
        );
    }


    // Event listener for scroll
    document.addEventListener('scroll', function() {
        const scrollPosition = window.scrollY;

        // Loop through each menu link
        menuLinks.forEach(function(link) {
            const sectionId = link.getAttribute('href');

            // Get the corresponding section element
            let section;
            try{
                //console.info("sectionId="+sectionId);
                section = document.querySelector(sectionId);
            }catch (e){
                console.error(e);
            }

            // Check if the section is in the viewport
            if (section && isInViewport(section)) {
                //console.log(sectionId+" visibility "+isInViewport(section));

                // Remove any existing border-bottom from other links
                menuLinks.forEach(function(otherLink) {
                    otherLink.style.borderBottom = 'none';
                });

                // Apply border-bottom to the current link
                link.style.borderBottom = '2px solid blue';
                //console.log(link);
            }
        });
    });






    // Find all anchor elements within .service-box
    const serviceBoxLinks = document.querySelectorAll('.service-box a');

    // Loop through each anchor element
    serviceBoxLinks.forEach(function(link) {
        // Add a click event listener to each anchor element
        link.addEventListener('click', function(event) {
            //event.preventDefault();
        });
    });




});




/* solution section */
document.addEventListener("DOMContentLoaded", function() {
    const section = document.querySelector("#solution");
    const options = {
        threshold: 0.2 // Trigger when 20% of the section is in view
    };

    function handleIntersect(entries, observer) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Trigger the animations
                section.classList.add('fade-in');

                const oddDivs = section.querySelectorAll(".flex-wrap > div:nth-child(odd) .p-6 > div");
                oddDivs.forEach(div => {
                    div.classList.add('slide-in-left');
                });

                const evenDivs = section.querySelectorAll(".flex-wrap > div:nth-child(even) .p-6 > div");
                evenDivs.forEach(div => {
                    div.classList.add('slide-in-right');
                });

                const oddImgs = section.querySelectorAll(".flex-wrap > div:nth-child(odd) img");
                oddImgs.forEach(img => {
                    img.classList.add('slide-in-right');
                });

                const evenImgs = section.querySelectorAll(".flex-wrap > div:nth-child(even) img");
                evenImgs.forEach(img => {
                    img.classList.add('slide-in-left');
                });

                // Stop observing after the animation is triggered
                observer.unobserve(entry.target);
            }
        });
    }

    const observer = new IntersectionObserver(handleIntersect, options);
    observer.observe(section);
});




/********** services section *********/
document.addEventListener("DOMContentLoaded", function() {
    const sections = document.querySelectorAll(".service-box");
    const options = {
        threshold: 0.2 // Trigger when 20% of the section is in view
    };

    function handleIntersect(entries, observer) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Trigger the animations for each section
                entry.target.classList.add('in-viewport');

                // Stop observing after the animation is triggered
                observer.unobserve(entry.target);
            }
        });
    }

    const observer = new IntersectionObserver(handleIntersect, options);
    sections.forEach(section => observer.observe(section));
});




/********** pricing section *********/
document.addEventListener("DOMContentLoaded", function() {
    const sections = document.querySelectorAll(".glass-content");
    const options = {
        threshold: 0.5 // Trigger when 50% of the section is in view
    };

    function handleIntersect(entries, observer) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Trigger the animations for each section
                entry.target.classList.add('animate');

                // Stop observing after the animation is triggered
                observer.unobserve(entry.target);
            }
        });
    }

    const observer = new IntersectionObserver(handleIntersect, options);
    sections.forEach(section => observer.observe(section));
});




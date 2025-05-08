document.addEventListener("DOMContentLoaded", function() {

    const scrollUpButton = document.getElementById('scroll-up');

    const tricksSection = document.getElementById('tricks');

    if (scrollUpButton && tricksSection) {

        scrollUpButton.style.display = 'none';

        window.addEventListener('scroll', function() {

            const tricksSectionTop = tricksSection.offsetTop;

            if (window.scrollY > tricksSectionTop) {
                scrollUpButton.style.display = 'flex';
            } else {
                scrollUpButton.style.display = 'none';
            }
        });
    }
});
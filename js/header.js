const header = document.getElementById('glassHeader');
let lastScroll = 0;

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;

    if (currentScroll > 1) {
        header.classList.add('visible');
    } else {
        header.classList.remove('visible');
    }

    lastScroll = currentScroll;
});
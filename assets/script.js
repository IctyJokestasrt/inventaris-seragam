document.addEventListener("DOMContentLoaded", () => {
    
    // 1. ANIMASI ANGKA (COUNT UP)
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const target = +counter.getAttribute('data-target');
        const duration = 2000; // 2 detik
        const increment = target / (duration / 16); // 60fps
        
        let current = 0;
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.innerText = Math.ceil(current).toLocaleString('id-ID');
                requestAnimationFrame(updateCounter);
            } else {
                counter.innerText = target.toLocaleString('id-ID');
            }
        };
        updateCounter();
    });

    // 2. INTERAKSI BACKGROUND (HOVER EFFECT)
    const body = document.body;
    
    // Cari elemen kartu
    const cardOmset = document.getElementById('card-omset');
    const cardStok = document.getElementById('card-stok');
    const cardNombok = document.getElementById('card-nombok');

    // Fungsi helper
    function setTheme(themeName) {
        body.classList.remove('theme-gold', 'theme-green', 'theme-red');
        if(themeName) body.classList.add(themeName);
    }

    if(cardOmset) {
        cardOmset.addEventListener('mouseenter', () => setTheme('theme-gold'));
        cardOmset.addEventListener('mouseleave', () => setTheme(''));
    }

    if(cardStok) {
        cardStok.addEventListener('mouseenter', () => setTheme('theme-green'));
        cardStok.addEventListener('mouseleave', () => setTheme(''));
    }

    if(cardNombok) {
        cardNombok.addEventListener('mouseenter', () => setTheme('theme-red'));
        cardNombok.addEventListener('mouseleave', () => setTheme(''));
    }
});
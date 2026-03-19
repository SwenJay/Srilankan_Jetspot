/* =============================================
   Sri Lankan JetSpot — script.js
   All vanilla JS, InfinityFree compatible
   ============================================= */

(function () {
    'use strict';

    // BASE_URL — auto-detect subfolder (e.g. /jetspot-php or empty for root)
    const BASE_URL = (typeof window.BASE_URL !== 'undefined')
        ? window.BASE_URL
        : window.location.pathname.replace(/\/(index\.php)?$/, '').replace(/\/$/, '');

    /* ===== HEADER SCROLL ===== */
    const header = document.getElementById('header');
    window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 30);
    }, { passive: true });

    /* ===== ACTIVE NAV LINK ===== */
    const sections  = document.querySelectorAll('section[id]');
    const navLinks  = document.querySelectorAll('.nav-link');
    const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                navLinks.forEach(link => {
                    link.classList.toggle('active', link.getAttribute('href') === '#' + entry.target.id);
                });
            }
        });
    }, { rootMargin: '-40% 0px -55% 0px' });
    sections.forEach(s => sectionObserver.observe(s));

    /* ===== MOBILE HAMBURGER ===== */
    const hamburger    = document.getElementById('hamburger');
    const navLinksList = document.getElementById('navLinks');
    if (hamburger) {
        hamburger.addEventListener('click', () => {
            const isOpen = navLinksList.classList.toggle('open');
            hamburger.classList.toggle('open', isOpen);
            hamburger.setAttribute('aria-expanded', isOpen);
        });
        navLinksList.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navLinksList.classList.remove('open');
                hamburger.classList.remove('open');
            });
        });
    }

    /* ===== CAROUSEL ===== */
    const carousel      = document.getElementById('carousel');
    const dotsContainer = document.getElementById('carouselDots');
    const slides        = carousel ? Array.from(carousel.querySelectorAll('.carousel-slide')) : [];
    let currentSlide    = 0;
    let carouselTimer   = null;

    function buildDots() {
        slides.forEach((_, i) => {
            const dot = document.createElement('button');
            dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
            dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
            dot.addEventListener('click', () => { resetTimer(); goToSlide(i); });
            dotsContainer.appendChild(dot);
        });
    }

    function updateDots() {
        dotsContainer.querySelectorAll('.carousel-dot').forEach((d, i) => {
            d.classList.toggle('active', i === currentSlide);
        });
    }

    function goToSlide(index) {
        slides[currentSlide].classList.remove('active');
        currentSlide = (index + slides.length) % slides.length;
        slides[currentSlide].classList.add('active');
        carousel.style.transform = 'translateX(-' + currentSlide * 100 + '%)';
        updateDots();
    }

    if (slides.length) {
        carousel.style.transform = 'translateX(0)';
        buildDots();
        document.getElementById('carouselPrev').addEventListener('click', () => { resetTimer(); goToSlide(currentSlide - 1); });
        document.getElementById('carouselNext').addEventListener('click', () => { resetTimer(); goToSlide(currentSlide + 1); });

        function startTimer() { carouselTimer = setInterval(() => goToSlide(currentSlide + 1), 5000); }
        function resetTimer()  { clearInterval(carouselTimer); startTimer(); }
        startTimer();

        // Touch swipe
        let touchStartX = 0;
        carousel.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
        carousel.addEventListener('touchend', e => {
            const diff = touchStartX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 50) { resetTimer(); goToSlide(diff > 0 ? currentSlide + 1 : currentSlide - 1); }
        });
    }

    /* ===== GALLERY — FILTER + LOAD MORE (work together) ===== */
    const filterBtns   = document.querySelectorAll('.filter-btn');
    const galleryCards = Array.from(document.querySelectorAll('.gallery-card'));
    const loadMoreBtn  = document.getElementById('loadMoreBtn');
    const loadMoreWrap = document.getElementById('loadMoreWrap');
    const PER_PAGE     = loadMoreBtn ? parseInt(loadMoreBtn.dataset.perPage) || 12 : 12;

    // Track state
    let activeFilter   = 'all';
    let visibleCount   = PER_PAGE; // how many cards are currently un-hidden by load more

    // Get cards matching the current filter
    function getFilteredCards() {
        return galleryCards.filter(card =>
            activeFilter === 'all' || card.dataset.type === activeFilter
        );
    }

    // Render gallery: apply filter + load-more-hidden together
    function renderGallery(resetCount) {
        if (resetCount) visibleCount = PER_PAGE;

        const filtered = getFilteredCards();
        let shown = 0;

        galleryCards.forEach(card => {
            const matchesFilter = activeFilter === 'all' || card.dataset.type === activeFilter;
            if (!matchesFilter) {
                // Hide cards that don't match filter
                card.classList.add('hidden');
                card.classList.remove('load-more-hidden');
            } else {
                card.classList.remove('hidden');
                if (shown < visibleCount) {
                    card.classList.remove('load-more-hidden');
                    shown++;
                } else {
                    card.classList.add('load-more-hidden');
                }
            }
        });

        // Update load more button
        if (loadMoreBtn && loadMoreWrap) {
            const remaining = filtered.length - shown;
            if (remaining > 0) {
                loadMoreWrap.style.display = '';
                loadMoreWrap.style.opacity = '1';
                const countEl = loadMoreBtn.querySelector('.load-more-count');
                if (countEl) countEl.textContent = 'Showing ' + shown + ' of ' + filtered.length;
            } else {
                loadMoreWrap.style.display = 'none';
            }
        }
    }

    // Filter button clicks
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeFilter = btn.dataset.filter;
            renderGallery(true); // reset count when filter changes

            // Animate newly visible cards
            let delay = 0;
            galleryCards.forEach(card => {
                if (!card.classList.contains('hidden') && !card.classList.contains('load-more-hidden')) {
                    card.style.animationDelay = (delay++ * 60) + 'ms';
                    card.style.animation = 'none';
                    void card.offsetWidth;
                    card.style.animation = '';
                }
            });
        });
    });

    // Load More button click
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', () => {
            const filtered  = getFilteredCards();
            const prevCount = visibleCount;
            visibleCount   += PER_PAGE;
            renderGallery(false);

            // Fade-in animation for newly revealed cards
            let delay = 0;
            galleryCards.forEach(card => {
                const isNew = !card.classList.contains('hidden') &&
                              !card.classList.contains('load-more-hidden');
                const idx   = parseInt(card.dataset.index);
                if (isNew && idx >= prevCount) {
                    card.style.opacity   = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'opacity 0.4s ease ' + (delay * 60) + 'ms, transform 0.4s ease ' + (delay * 60) + 'ms';
                    requestAnimationFrame(() => {
                        card.style.opacity   = '1';
                        card.style.transform = 'translateY(0)';
                    });
                    delay++;
                }
            });
        });
    }

    // Initial render
    renderGallery(false);

    /* ===== LIGHTBOX ===== */
    const lightbox        = document.getElementById('lightbox');
    const lightboxImg     = document.getElementById('lightboxImg');
    const lightboxCaption = document.getElementById('lightboxCaption');

    // Build image data from cards — use data-full for full image, fall back to src
    const imageData = galleryCards.map(card => {
        const img    = card.querySelector('.card-img-wrap img');
        const badge  = card.querySelector('.airline-badge');
        const type   = card.querySelector('.aircraft-type');
        return {
            src:     img ? (img.dataset.full || img.src) : '',
            alt:     img ? img.alt : '',
            caption: [(badge ? badge.textContent.trim() : ''), (type ? type.textContent.trim() : '')].filter(Boolean).join(' · ')
        };
    });

    let currentLightboxIndex = 0;

    function openLightbox(index) {
        currentLightboxIndex = index;
        const data = imageData[index];
        if (!data || !data.src) return;
        lightboxImg.src = data.src;
        lightboxImg.alt = data.alt;
        lightboxCaption.textContent = data.caption;
        lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        lightbox.classList.remove('open');
        document.body.style.overflow = '';
    }

    function navigateLightbox(dir) {
        let next   = (currentLightboxIndex + dir + imageData.length) % imageData.length;
        let tries  = imageData.length;
        // Skip hidden or load-more-hidden cards
        while (tries-- > 0) {
            const card = galleryCards[next];
            if (card && (card.classList.contains('hidden') || card.classList.contains('load-more-hidden'))) {
                next = (next + dir + imageData.length) % imageData.length;
            } else {
                break;
            }
        }
        lightboxImg.style.opacity = '0';
        setTimeout(() => { openLightbox(next); lightboxImg.style.opacity = '1'; }, 150);
    }

    // Attach zoom buttons
    document.querySelectorAll('.zoom-btn').forEach((btn, i) => {
        btn.addEventListener('click', e => { e.stopPropagation(); openLightbox(i); });
    });

    // Click on card image to open lightbox
    galleryCards.forEach((card, i) => {
        card.querySelector('.card-img-wrap')?.addEventListener('click', () => openLightbox(i));
    });

    document.getElementById('lightboxClose')?.addEventListener('click', closeLightbox);
    document.getElementById('lightboxBackdrop')?.addEventListener('click', closeLightbox);
    document.getElementById('lightboxPrev')?.addEventListener('click', () => navigateLightbox(-1));
    document.getElementById('lightboxNext')?.addEventListener('click', () => navigateLightbox(1));

    document.addEventListener('keydown', e => {
        if (!lightbox.classList.contains('open')) return;
        if (e.key === 'Escape')      closeLightbox();
        if (e.key === 'ArrowLeft')   navigateLightbox(-1);
        if (e.key === 'ArrowRight')  navigateLightbox(1);
    });

    // Lightbox touch swipe
    let lbTouchX = 0;
    lightbox.addEventListener('touchstart', e => { lbTouchX = e.touches[0].clientX; }, { passive: true });
    lightbox.addEventListener('touchend', e => {
        const diff = lbTouchX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) navigateLightbox(diff > 0 ? 1 : -1);
    });

    /* ===== SCROLL REVEAL (only for visible cards) ===== */
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity   = '1';
                entry.target.style.transform = 'translateY(0)';
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    // Only observe gear cards and section headers — not gallery cards
    // (gallery cards manage their own animation via filter/load-more)
    document.querySelectorAll('.gear-card, .section-header').forEach((el, i) => {
        el.style.opacity    = '0';
        el.style.transform  = 'translateY(24px)';
        el.style.transition = 'opacity 0.5s ease ' + (i % 3 * 80) + 'ms, transform 0.5s ease ' + (i % 3 * 80) + 'ms';
        revealObserver.observe(el);
    });

    /* ===== CONTACT FORM ===== */
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', async e => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const msg = document.getElementById('formMessage');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            msg.style.display = 'none';
            try {
                const res  = await fetch(BASE_URL + '/api/contact.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: new FormData(contactForm)
                });
                const contentType = res.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    throw new Error('Server error (HTTP ' + res.status + ')');
                }
                const data = await res.json();
                msg.style.display = 'block';
                if (data.ok) {
                    msg.className   = 'form-message success';
                    msg.textContent = data.message || 'Message sent!';
                    contactForm.reset();
                    // Update CSRF token
                    const csrfInput = contactForm.querySelector('input[name="csrf_token"]');
                    if (csrfInput && data.token) csrfInput.value = data.token;
                } else {
                    msg.className   = 'form-message error';
                    msg.textContent = data.error || 'Something went wrong.';
                }
            } catch (err) {
                msg.style.display = 'block';
                msg.className     = 'form-message error';
                msg.textContent   = 'Network error. Please try again.';
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
        });
    }

})(); // end IIFE
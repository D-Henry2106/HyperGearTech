/**
 * ============================================
 * Hyper Gear Tech - Main JavaScript
 * ============================================
 * Handles scroll reveal animations, cart effects,
 * and smooth interactions.
 */

document.addEventListener('DOMContentLoaded', function() {

    // ========== SCROLL REVEAL ANIMATION ==========
    // Uses IntersectionObserver for performance
    const revealElements = document.querySelectorAll('.scroll-reveal');
    
    if (revealElements.length > 0) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    // Add delay for staggered effect
                    const delay = Array.from(revealElements).indexOf(entry.target) % 4;
                    setTimeout(function() {
                        entry.target.classList.add('revealed');
                    }, delay * 100);
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,    // Trigger when 10% visible
            rootMargin: '0px 0px -50px 0px'
        });

        revealElements.forEach(function(el) {
            observer.observe(el);
        });
    }

    // ========== ADD TO CART ANIMATION ==========
    // Adds a bounce effect when clicking "Add to Cart"
    const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
    
    addToCartBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            this.classList.add('added');
            
            // Remove animation class after it finishes
            setTimeout(function() {
                btn.classList.remove('added');
            }, 500);
        });
    });

    // ========== NAVBAR SCROLL EFFECT ==========
    // Adds shadow on scroll
    const navbar = document.querySelector('.hg-navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 4px 30px rgba(0,0,0,0.4)';
            } else {
                navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.3)';
            }
        });
    }

    // ========== SMOOTH SCROLL FOR ANCHOR LINKS ==========
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ========== AUTO-DISMISS ALERTS ==========
    const alerts = document.querySelectorAll('.alert-success');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 4000);
    });

    // ========== ACTIVE NAV LINK HIGHLIGHT ==========
    const currentPath = window.location.pathname;
    document.querySelectorAll('.hg-navbar .nav-link').forEach(function(link) {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href'))) {
            link.style.color = '#ffc107';
        }
    });

});



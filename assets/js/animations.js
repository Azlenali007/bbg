/**
 * GSAP Animation System for ApexSMM
 * Respects prefers-reduced-motion and focuses on transform and opacity for 60fps performance.
 */

window.ApexAnimations = (function () {
    const isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    return {
        isReducedMotion,

        initHero: function (selector = '.hero-content') {
            if (this.isReducedMotion || !window.gsap) return;
            gsap.from(selector, {
                y: 24,
                opacity: 0,
                duration: 0.8,
                ease: 'power3.out',
                stagger: 0.12
            });
        },

        initStatCards: function (selector = '.stat-card') {
            if (this.isReducedMotion || !window.gsap) return;
            gsap.from(selector, {
                y: 16,
                opacity: 0,
                duration: 0.5,
                ease: 'power2.out',
                stagger: 0.08
            });
        },

        initFlashSale: function (selector = '.flash-sale-card') {
            if (this.isReducedMotion || !window.gsap) return;
            gsap.from(selector, {
                scale: 0.98,
                opacity: 0,
                duration: 0.6,
                ease: 'back.out(1.4)'
            });
        },

        animateToastIn: function (el) {
            if (!window.gsap || this.isReducedMotion) {
                el.style.opacity = '1';
                return;
            }
            gsap.fromTo(el, 
                { y: 20, opacity: 0, scale: 0.95 },
                { y: 0, opacity: 1, scale: 1, duration: 0.35, ease: 'power3.out' }
            );
        },

        animateToastOut: function (el, onComplete) {
            if (!window.gsap || this.isReducedMotion) {
                if (onComplete) onComplete();
                return;
            }
            gsap.to(el, {
                y: -10,
                opacity: 0,
                scale: 0.95,
                duration: 0.25,
                ease: 'power2.in',
                onComplete: onComplete
            });
        },

        animateDropdownOpen: function (el) {
            if (!window.gsap || this.isReducedMotion) return;
            gsap.fromTo(el,
                { opacity: 0, y: -8, scale: 0.98 },
                { opacity: 1, y: 0, scale: 1, duration: 0.2, ease: 'power2.out' }
            );
        }
    };
})();

/**
 * ApexSMM Global Application Script
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize hero and stat animations
    if (window.ApexAnimations) {
        ApexAnimations.initHero('.hero-content');
        ApexAnimations.initStatCards('.stat-card');
        ApexAnimations.initFlashSale('.flash-sale-card');
    }

    // Auto-dismiss alerts after 5 seconds if desired
    document.querySelectorAll('.auto-dismiss').forEach(el => {
        setTimeout(() => {
            if (window.gsap) {
                gsap.to(el, { opacity: 0, height: 0, marginBottom: 0, padding: 0, duration: 0.3, onComplete: () => el.remove() });
            } else {
                el.remove();
            }
        }, 5000);
    });
});

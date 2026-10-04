/**
 * Interactive Components for ApexSMM
 * Includes Custom Service Dropdown, Live Price Calculation & Flash Sale Countdowns
 */

// Custom Service Dropdown Component using Alpine.js or Vanilla
function serviceSelector(initialServices = [], initialCategories = []) {
    return {
        open: false,
        search: '',
        selectedCategory: 0,
        selectedService: null,
        services: initialServices,
        categories: initialCategories,
        quantity: 1000,
        calculatedPrice: '0.0000',

        init() {
            if (this.services.length > 0) {
                // If a pre-selected service ID exists in query param or form
                const urlParams = new URLSearchParams(window.location.search);
                const preSelectId = urlParams.get('service_id');
                if (preSelectId) {
                    const match = this.services.find(s => s.id == preSelectId);
                    if (match) this.selectService(match);
                }
            }
        },

        toggle() {
            this.open = !this.open;
            if (this.open && window.ApexAnimations) {
                this.$nextTick(() => {
                    const panel = this.$refs.dropdownPanel;
                    if (panel) ApexAnimations.animateDropdownOpen(panel);
                    this.$refs.searchInput?.focus();
                });
            }
        },

        close() {
            this.open = false;
        },

        clearSearch() {
            this.search = '';
            this.$refs.searchInput?.focus();
        },

        get filteredServices() {
            return this.services.filter(s => {
                const matchesCat = this.selectedCategory === 0 || s.category_id == this.selectedCategory;
                const q = this.search.trim().toLowerCase();
                if (!q) return matchesCat;
                const matchesText = (
                    s.name.toLowerCase().includes(q) || 
                    s.id.toString().includes(q) ||
                    (s.category_name && s.category_name.toLowerCase().includes(q))
                );
                return matchesCat && matchesText;
            });
        },

        selectCategory(catId) {
            this.selectedCategory = catId;
        },

        selectService(service) {
            this.selectedService = service;
            this.open = false;
            
            // Set min quantity if current quantity is less
            const min = parseInt(service.min_quantity, 10) || 10;
            const max = parseInt(service.max_quantity, 10) || 10000;
            if (this.quantity < min) this.quantity = min;
            if (this.quantity > max) this.quantity = max;

            this.calculate();

            // Set hidden form input
            const input = document.getElementById('selected_service_id');
            if (input) input.value = service.id;
        },

        calculate() {
            if (!this.selectedService) {
                this.calculatedPrice = '0.0000';
                return;
            }
            const rate = parseFloat(this.selectedService.rate) || 0;
            const qty = parseInt(this.quantity, 10) || 0;
            const total = (rate * qty) / 1000;
            this.calculatedPrice = total.toFixed(4);
        }
    };
}

// Flash Sale Countdown Timer
function flashSaleCountdown(initialSeconds) {
    return {
        remaining: initialSeconds,
        days: 0,
        hours: 0,
        minutes: 0,
        seconds: 0,
        timer: null,

        init() {
            this.update();
            this.timer = setInterval(() => {
                if (this.remaining <= 0) {
                    clearInterval(this.timer);
                    this.remaining = 0;
                    this.update();
                } else {
                    this.remaining--;
                    this.update();
                }
            }, 1000);
        },

        update() {
            const sec = Math.max(0, this.remaining);
            this.days = Math.floor(sec / 86400);
            this.hours = Math.floor((sec % 86400) / 3600);
            this.minutes = Math.floor((sec % 3600) / 60);
            this.seconds = sec % 60;
        },

        format(val) {
            return String(val).padStart(2, '0');
        }
    };
}

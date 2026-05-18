// LifePlanner SIM v1.0 — Main JavaScript Entry
// Alpine.js is loaded via Livewire v4 automatically

// Wait for Livewire to initialize Alpine, then register toast store
document.addEventListener('livewire:init', () => {
    // Register Alpine toast store
    if (window.Alpine) {
        window.Alpine.store('toast', {
            show: false,
            message: '',
            type: 'success',

            fire(message, type = 'success') {
                this.message = message;
                this.type = type;
                this.show = true;

                setTimeout(() => {
                    this.show = false;
                }, 4000);
            },
        });
    }

    // Listen for Livewire toast events
    Livewire.on('toast', (data) => {
        if (window.Alpine && window.Alpine.store('toast')) {
            window.Alpine.store('toast').fire(
                data[0]?.message || data.message || 'Success',
                data[0]?.type || data.type || 'success'
            );
        }
    });
});

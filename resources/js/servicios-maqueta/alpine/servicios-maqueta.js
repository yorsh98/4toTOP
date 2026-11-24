function showFallback(stage) {
    const floor = stage.closest('[x-data="serviciosPlano()"]')?.querySelector('.floor-stage');
    if (!floor) return;
    let notice = floor.querySelector('[data-model-fallback]');
    if (!notice) {
        notice = document.createElement('p');
        notice.dataset.modelFallback = '';
        notice.setAttribute('role', 'status');
        floor.prepend(notice);
    }
    notice.textContent = 'La vista 3D no está disponible en este navegador. Se muestra el plano 2D.';
}

/**
 * Create the lazy-loaded WebGL floor component.
 * @returns {object} Alpine component state.
 */
export function serviciosMaqueta() {
    return {
        scene: null,
        disposed: false,
        loading: false,
        init() {
            this.$watch('relief', (value) => {
                if (value) this.load();
            });
            if (this.relief) this.load();
        },
        async load() {
            if (this.scene || this.loading) return;
            this.loading = true;
            try {
                const { createScene } = await import('../scene.js');
                if (this.disposed) return;
                const data = JSON.parse(this.$refs.data.textContent);
                this.scene = createScene(this.$refs.stage, this.$refs.canvas, this.$refs.rooms, data, this);
                ['orbit', 'zoom', 'tilt'].forEach((key) => this.$watch(key, () => this.scene?.requestRender()));
            } catch (error) {
                console.error(error);
                this.relief = false;
                showFallback(this.$refs.stage);
            } finally {
                this.loading = false;
            }
        },
        destroy() {
            this.disposed = true;
            this.scene?.dispose();
        },
    };
}

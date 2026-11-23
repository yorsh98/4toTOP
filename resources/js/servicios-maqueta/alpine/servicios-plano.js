import { DEFAULT_ORBIT, DEFAULT_TILT, DEFAULT_ZOOM, MOBILE_ORBIT, MOBILE_TILT, MOBILE_ZOOM } from '../dimensions.js';

/**
 * Create the shared state for the 2D and 3D floor views.
 * @returns {object} Alpine component state.
 */
export function serviciosPlano() {
    return {
        relief: false,
        orbit: DEFAULT_ORBIT,
        zoom: DEFAULT_ZOOM,
        tilt: DEFAULT_TILT,
        fallback: false,
        init() {
            this.reset();
        },
        reset() {
            const small = matchMedia('(max-width: 640px)').matches;
            this.orbit = small ? MOBILE_ORBIT : DEFAULT_ORBIT;
            this.zoom = small ? MOBILE_ZOOM : DEFAULT_ZOOM;
            this.tilt = small ? MOBILE_TILT : DEFAULT_TILT;
        },
        fullscreen() {
            const stage = this.$el.querySelector('.model-scroll');
            if (document.fullscreenElement) document.exitFullscreen();
            else if (this.fallback) this.fallback = false;
            else if (stage?.requestFullscreen) stage.requestFullscreen().catch(() => (this.fallback = true));
            else this.fallback = true;
        },
    };
}

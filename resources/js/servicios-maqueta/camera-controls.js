import {
    DRAG_SENSITIVITY,
    DRAG_THRESHOLD,
    INERTIA_DECAY,
    INERTIA_MIN_VELOCITY,
    KEY_ORBIT_STEP,
    KEY_ZOOM_STEP,
    MAX_ZOOM,
    MIN_ZOOM,
    WHEEL_SENSITIVITY,
} from './dimensions.js';

/**
 * Attach camera gestures and return their cleanup function.
 * @param {HTMLElement} stage Interactive model container.
 * @param {object} state Camera state.
 * @param {Function} render Requests a frame.
 * @returns {Function} Removes listeners and stops inertia.
 */
export function controls(stage, state, render) {
    const points = new Map();
    let start;
    let moved = false;
    let distance = 0;
    let velocity = 0;
    let frame;

    function setZoom(value) {
        state.zoom = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, value));
    }

    function pointerDown(event) {
        if (event.target.closest('button')) return;
        cancelAnimationFrame(frame);
        points.set(event.pointerId, { x: event.clientX, y: event.clientY });
        start = { x: event.clientX, y: event.clientY };
        moved = false;
        stage.querySelector('canvas').dataset.dragged = 'false';
        stage.setPointerCapture(event.pointerId);
    }

    function pinch() {
        const [first, second] = points.values();
        const current = Math.hypot(first.x - second.x, first.y - second.y);
        if (distance) setZoom((state.zoom * current) / distance);
        distance = current;
    }

    function pointerMove(event) {
        const previous = points.get(event.pointerId);
        if (!previous) return;
        const dx = event.clientX - previous.x;
        points.set(event.pointerId, { x: event.clientX, y: event.clientY });
        moved ||= Math.hypot(event.clientX - start.x, event.clientY - start.y) > DRAG_THRESHOLD;
        if (points.size > 1) {
            pinch();
        } else if (moved) {
            velocity = dx * DRAG_SENSITIVITY;
            state.orbit += velocity;
        }
        render();
    }

    function inertia() {
        velocity *= INERTIA_DECAY;
        if (Math.abs(velocity) <= INERTIA_MIN_VELOCITY) return;
        state.orbit += velocity;
        render();
        frame = requestAnimationFrame(inertia);
    }

    function pointerUp(event) {
        points.delete(event.pointerId);
        distance = 0;
        stage.querySelector('canvas').dataset.dragged = String(moved);
        if (moved && points.size === 0 && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
            frame = requestAnimationFrame(inertia);
        }
    }

    function wheel(event) {
        if (!event.ctrlKey) return;
        event.preventDefault();
        setZoom(state.zoom - event.deltaY * WHEEL_SENSITIVITY);
        render();
    }

    function keyboard(event) {
        if (event.target !== stage) return;
        if (event.key === 'ArrowLeft') state.orbit -= KEY_ORBIT_STEP;
        else if (event.key === 'ArrowRight') state.orbit += KEY_ORBIT_STEP;
        else if (event.key === 'ArrowUp') setZoom(state.zoom + KEY_ZOOM_STEP);
        else if (event.key === 'ArrowDown') setZoom(state.zoom - KEY_ZOOM_STEP);
        else return;
        event.preventDefault();
        render();
    }

    const listeners = {
        pointerdown: pointerDown,
        pointermove: pointerMove,
        pointerup: pointerUp,
        pointercancel: pointerUp,
        wheel,
        keydown: keyboard,
    };
    Object.entries(listeners).forEach(([event, handler]) => stage.addEventListener(event, handler, { passive: false }));
    return () => {
        cancelAnimationFrame(frame);
        Object.entries(listeners).forEach(([event, handler]) => stage.removeEventListener(event, handler));
    };
}

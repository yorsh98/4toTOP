import * as THREE from 'three';
/** Connect pointer picking to the room buttons.
 * Listeners live on the stage: it captures the pointer while dragging, so the browser dispatches
 * `click` to the stage rather than to the canvas.
 * @param {HTMLElement} stage Element that receives pointer events.
 * @param {HTMLCanvasElement} canvas WebGL canvas.
 * @param {THREE.Camera} camera Projection camera.
 * @param {Map} rooms Room meshes.
 * @param {HTMLElement} root Room controls.
 * @param {Function} update Refresh room state.
 * @returns {Function} Remove pointer listeners.
 */
export function picking(stage, canvas, camera, rooms, root, update) {
    const ray = new THREE.Raycaster();
    const point = new THREE.Vector2();
    let hover = null;
    function hit(e) {
        const rect = canvas.getBoundingClientRect();
        point.set(((e.clientX - rect.left) / rect.width) * 2 - 1, (-(e.clientY - rect.top) / rect.height) * 2 + 1);
        ray.setFromCamera(point, camera);
        return ray.intersectObjects([...rooms.values()], false)[0]?.object;
    }
    const move = (e) => {
        const room = hit(e);
        if (room === hover) return;
        if (hover) hover.userData.hover = false;
        hover = room;
        if (hover) hover.userData.hover = true;
        update();
    };
    const click = (e) => {
        if (canvas.dataset.dragged === 'true' || e.target.closest('button')) return;
        const room = hit(e);
        if (room)
            [...root.querySelectorAll('button[data-id]')]
                .find((b) => b.dataset.id === room.userData.identificador)
                ?.click();
    };
    stage.addEventListener('pointermove', move);
    stage.addEventListener('click', click);
    return () => {
        stage.removeEventListener('pointermove', move);
        stage.removeEventListener('click', click);
    };
}

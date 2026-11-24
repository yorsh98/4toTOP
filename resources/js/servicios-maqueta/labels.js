import * as THREE from 'three';

function updateLabel(element, mesh, id, host, camera, state) {
    const room = mesh.userData;
    const active = Boolean(room.hover || room.focus || room.selected);
    const full = state.zoom >= 1.25 || active;
    const display = host.clientWidth < 700 && !(full || room.match) ? 'none' : '';
    const text = (full ? room.label : id.replaceAll('_', ' ')) + (room.count ? ` · ${room.count}` : '');
    if (element.style.display !== display) element.style.display = display;
    if (element.textContent !== text) element.textContent = text;
    // Resting labels stay small; the room under the pointer, focused or selected gets a large one.
    element.classList.toggle('is-active', active);
    const point = new THREE.Vector3(mesh.position.x, mesh.position.y + mesh.scale.y / 2 + 8, mesh.position.z);
    mesh.parent.localToWorld(point);
    point.project(camera);
    const x = ((point.x + 1) * host.clientWidth) / 2;
    const y = ((1 - point.y) * host.clientHeight) / 2;
    element.style.transform = `translate(${x}px, ${y}px) translate(-50%, -50%)`;
}

/** Create projected labels for the rooms.
 * @param {HTMLElement} host Scene container.
 * @param {Map} rooms Room meshes.
 * @param {THREE.Camera} camera Projection camera.
 * @param {object} state Camera state.
 * @returns {{update: Function, dispose: Function}} Label lifecycle.
 */
export function createLabels(host, rooms, camera, state) {
    const layer = document.createElement('div');
    layer.className = 'model-label-layer';
    host.append(layer);
    const labels = new Map();
    rooms.forEach((mesh, id) => {
        const element = document.createElement('span');
        element.className = 'model-label';
        layer.append(element);
        labels.set(id, element);
    });
    return {
        update() {
            rooms.forEach((mesh, id) => updateLabel(labels.get(id), mesh, id, host, camera, state));
        },
        dispose() {
            layer.remove();
        },
    };
}

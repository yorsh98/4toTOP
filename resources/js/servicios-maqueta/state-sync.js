import { resolveRoomAppearance } from './room-state';

function applyButton(button, rooms, materials, selected) {
    const mesh = rooms.get(button.dataset.id);
    if (!mesh) return;
    const room = mesh.userData;
    const wasMatch = room.match;
    room.selected = button.dataset.selected === 'true';
    room.match = button.dataset.search === 'match';
    room.count = Number(button.dataset.count);
    room.height = Number(button.dataset.height);
    room.dimmed = button.dataset.search === 'dimmed' || (selected && !room.selected);
    room.inventory = button.dataset.inventory === 'present';
    const appearance = resolveRoomAppearance(room);
    mesh.material = materials[appearance.material];
    room.outline.visible = room.selected || room.match;
    room.targetHeight = room.height;
    room.targetY = room.height / 2 + appearance.lift;
    if (room.match && !wasMatch) room.pulseStarted = performance.now();
}

/** Synchronize room appearance with the Livewire controls.
 * @param {HTMLElement} root Room controls.
 * @param {Map} rooms Room meshes.
 * @param {object} materials Shared materials.
 * @param {Function} render Request a frame.
 * @returns {{update: Function, dispose: Function}} Synchronization lifecycle.
 */
export function syncState(root, rooms, materials, render) {
    const update = () => {
        const buttons = [...root.querySelectorAll('button[data-id]')];
        const selected = buttons.some((button) => button.dataset.selected === 'true');
        buttons.forEach((button) => applyButton(button, rooms, materials, selected));
        render();
    };
    const observer = new MutationObserver(update);
    observer.observe(root, { subtree: true, childList: true, attributes: true });
    const focus = (event) => {
        const mesh = rooms.get(event.target.dataset.id);
        if (!mesh) return;
        mesh.userData.focus = event.type === 'focusin';
        update();
    };
    root.addEventListener('focusin', focus);
    root.addEventListener('focusout', focus);
    update();
    return {
        update,
        dispose() {
            observer.disconnect();
            root.removeEventListener('focusin', focus);
            root.removeEventListener('focusout', focus);
        },
    };
}

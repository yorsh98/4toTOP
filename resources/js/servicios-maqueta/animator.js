import { FLOOR_ENTRY_DURATION, FLOOR_ENTRY_OFFSET, MATCH_PULSE_DURATION } from './dimensions';

function animateRoom(mesh, factor, now, reducedMotion) {
    const room = mesh.userData;
    if (room.targetY === undefined) return false;
    mesh.position.y += (room.targetY - mesh.position.y) * factor;
    mesh.scale.y += (room.targetHeight - mesh.scale.y) * factor;
    const pulse =
        room.pulseStarted && !reducedMotion ? Math.min(1, (now - room.pulseStarted) / MATCH_PULSE_DURATION) : 1;
    room.emissiveIntensity = 0.35 + Math.sin(pulse * Math.PI) * 0.45;
    return (
        pulse < 1 ||
        Math.abs(mesh.position.y - room.targetY) > 0.02 ||
        Math.abs(mesh.scale.y - room.targetHeight) > 0.02
    );
}

/**
 * Create the floor and room animation controller.
 * @param {{group: THREE.Group, rooms: Map}} model
 * @param {object} materials
 * @param {boolean} reducedMotion
 * @returns {{update: function(number): boolean}}
 */
export function createAnimator(model, materials, reducedMotion) {
    const started = performance.now();
    let last = started;
    const slabMaterials = new Set(materials.slab);
    slabMaterials.forEach((material) => {
        material.transparent = true;
        material.needsUpdate = true;
    });
    model.rooms.forEach((mesh) => {
        mesh.onBeforeRender = () => {
            if (mesh.material === materials.active) {
                materials.active.emissiveIntensity = mesh.userData.emissiveIntensity ?? 0.35;
            }
        };
    });
    return {
        update(now) {
            const factor = reducedMotion ? 1 : Math.min(1, (now - last) / 250);
            last = now;
            let animating = false;
            model.rooms.forEach((mesh) => {
                animating = animateRoom(mesh, factor, now, reducedMotion) || animating;
            });
            const progress = reducedMotion ? 1 : Math.min(1, (now - started) / FLOOR_ENTRY_DURATION);
            model.group.position.y = (1 - progress) * FLOOR_ENTRY_OFFSET;
            slabMaterials.forEach((material) => {
                material.opacity = progress;
            });
            return animating || progress < 1;
        },
    };
}

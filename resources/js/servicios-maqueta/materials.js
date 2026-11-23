import * as THREE from 'three';

/** Create materials shared by the floor model.
 * @returns {object} Materials indexed by appearance.
 */
export function createMaterials() {
    const names = ['floor', 'edge', 'wall', 'present', 'empty', 'dimmed', 'active', 'hover'];
    const materials = Object.fromEntries(
        names.map((name) => [
            name,
            new THREE.MeshStandardMaterial({
                roughness: 0.85,
                transparent: name === 'dimmed',
                opacity: name === 'dimmed' ? 0.55 : 1,
            }),
        ]),
    );
    materials.outline = new THREE.LineBasicMaterial({ color: 0x22d3ee });
    return materials;
}

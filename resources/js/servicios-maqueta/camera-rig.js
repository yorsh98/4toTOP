import * as THREE from 'three';
import { FRAME_FRACTION } from './dimensions';

function modelCorners(data, orbit) {
    const rotation = new THREE.Matrix4().makeRotationY(THREE.MathUtils.degToRad(-orbit));
    const corners = [];
    for (const x of [-data.ancho / 2, data.ancho / 2]) {
        for (const z of [-data.alto / 2, data.alto / 2]) {
            for (const y of [-8, 20]) {
                corners.push(new THREE.Vector3(x, y, z).applyMatrix4(rotation));
            }
        }
    }
    return corners;
}

function frameDistance(camera, corners, direction) {
    const horizontal = Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)) * camera.aspect * FRAME_FRACTION;
    const vertical = Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)) * FRAME_FRACTION;
    const right = new THREE.Vector3(1, 0, 0);
    const up = new THREE.Vector3().crossVectors(direction, right).normalize();
    return Math.max(
        ...corners.map((point) => {
            const depth = point.dot(direction);
            return depth + Math.max(Math.abs(point.dot(right)) / horizontal, Math.abs(point.dot(up)) / vertical);
        }),
    );
}

/**
 * Frame the model and apply the current orbit, tilt, and zoom.
 * @param {THREE.PerspectiveCamera} camera
 * @param {THREE.Group} group
 * @param {object} data
 * @param {object} state
 * @returns {void}
 */
export function updateCameraRig(camera, group, data, state) {
    const angle = THREE.MathUtils.degToRad(90 - Number(state.tilt));
    const direction = new THREE.Vector3(0, Math.sin(angle), Math.cos(angle));
    const distance = frameDistance(camera, modelCorners(data, Number(state.orbit)), direction);
    camera.position.copy(direction.multiplyScalar(distance));
    camera.zoom = Number(state.zoom);
    camera.lookAt(0, 0, 0);
    camera.updateProjectionMatrix();
    group.rotation.y = THREE.MathUtils.degToRad(-Number(state.orbit));
}

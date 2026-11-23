import * as THREE from 'three';

/**
 * Create the WebGL renderer and attach it to the host.
 * @param {HTMLElement} host
 * @returns {THREE.WebGLRenderer}
 */
export function createRenderer(host) {
    const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    // Neutral keeps the light architectural palette true to the CSS tokens; ACES greys it out.
    renderer.toneMapping = THREE.NeutralToneMapping;
    renderer.toneMappingExposure = 1;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    host.append(renderer.domElement);
    return renderer;
}

/**
 * Resize the renderer and camera to match the host.
 * @param {THREE.WebGLRenderer} renderer
 * @param {THREE.PerspectiveCamera} camera
 * @param {HTMLElement} host
 * @returns {boolean} Whether the host has a drawable size.
 */
export function resizeRenderer(renderer, camera, host) {
    const width = host.clientWidth;
    const height = host.clientHeight;
    if (!width || !height) return false;
    renderer.setSize(width, height, false);
    camera.aspect = width / height;
    return true;
}

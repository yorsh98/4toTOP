import * as THREE from 'three';
import { createMaterials } from './materials';
import { observeTheme } from './theme';
import { buildModel } from './model-builder';
import { createLabels } from './labels';
import { syncState } from './state-sync';
import { picking } from './picking';
import { controls } from './camera-controls';
import { createRenderer, resizeRenderer } from './renderer';
import { updateCameraRig } from './camera-rig';
import { createAnimator } from './animator';
import { createRenderLoop } from './render-loop';
import { HEMISPHERE_INTENSITY, SHADOW_NORMAL_BIAS, SUN_INTENSITY } from './dimensions';

function addLighting(scene) {
    scene.add(new THREE.HemisphereLight(0xffffff, 0x8a9bb0, HEMISPHERE_INTENSITY));
    const light = new THREE.DirectionalLight(0xffffff, SUN_INTENSITY);
    light.position.set(-500, 1100, 600);
    light.castShadow = true;
    light.shadow.mapSize.set(2048, 2048);
    light.shadow.radius = 4;
    light.shadow.bias = -0.0002;
    // normalBias removes the striped shadow acne on the flat, translucent room tops.
    light.shadow.normalBias = SHADOW_NORMAL_BIAS;
    Object.assign(light.shadow.camera, { left: -900, right: 900, top: 900, bottom: -900, near: 1, far: 3000 });
    scene.add(light);
    const ground = new THREE.Mesh(new THREE.PlaneGeometry(3000, 3000), new THREE.ShadowMaterial({ opacity: 0.22 }));
    ground.rotation.x = -Math.PI / 2;
    ground.position.y = -9;
    ground.receiveShadow = true;
    scene.add(ground);
}

function disposeScene(scene, materials) {
    scene.traverse((object) => {
        object.geometry?.dispose();
        if (object.material && !Array.isArray(object.material) && !Object.values(materials).includes(object.material)) {
            object.material.dispose();
        }
    });
    Object.values(materials).forEach((material) => material.dispose());
}

/**
 * Compose the interactive WebGL scene.
 * @param {HTMLElement} stage
 * @param {HTMLElement} host
 * @param {HTMLElement} root
 * @param {object} data
 * @param {object} state
 * @returns {{requestRender: function(): void, dispose: function(): void}}
 */
export function createScene(stage, host, root, data, state) {
    const renderer = createRenderer(host);
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(30, 1, 1, 10000);
    const materials = createMaterials();
    addLighting(scene);
    const model = buildModel(data, materials);
    scene.add(model.group);
    const labels = createLabels(host, model.rooms, camera, state);
    const animator = createAnimator(model, materials, matchMedia('(prefers-reduced-motion: reduce)').matches);
    const loop = createRenderLoop((now) => {
        const animating = animator.update(now);
        if (!resizeRenderer(renderer, camera, host)) return false;
        updateCameraRig(camera, model.group, data, state);
        renderer.render(scene, camera);
        labels.update();
        return animating;
    });
    const stopTheme = observeTheme(stage, materials, loop.request);
    const sync = syncState(root, model.rooms, materials, loop.request);
    const stopPicking = picking(stage, renderer.domElement, camera, model.rooms, root, sync.update);
    const stopControls = controls(stage, state, loop.request);
    const resize = new ResizeObserver(loop.request);
    resize.observe(host);
    loop.request();
    return {
        requestRender: loop.request,
        dispose() {
            loop.dispose();
            resize.disconnect();
            stopTheme();
            sync.dispose();
            stopPicking();
            stopControls();
            labels.dispose();
            disposeScene(scene, materials);
            renderer.dispose();
            renderer.forceContextLoss();
            renderer.domElement.remove();
        },
    };
}

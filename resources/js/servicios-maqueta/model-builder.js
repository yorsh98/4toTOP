import * as THREE from 'three';
import { mergeGeometries } from 'three/addons/utils/BufferGeometryUtils.js';
import { ROOM_INSET, SLAB_HEIGHT, WALL_HEIGHT } from './dimensions';

function buildWalls(data, material) {
    const geometries = data.muros.map((wall) => {
        const geometry = new THREE.BoxGeometry(wall.w, WALL_HEIGHT, wall.h);
        geometry.translate(wall.x + wall.w / 2 - data.ancho / 2, WALL_HEIGHT / 2, wall.y + wall.h / 2 - data.alto / 2);
        return geometry;
    });
    if (!geometries.length) return null;
    const merged = mergeGeometries(geometries);
    geometries.forEach((geometry) => geometry.dispose());
    const walls = new THREE.Mesh(merged, material);
    walls.castShadow = true;
    walls.receiveShadow = true;
    return walls;
}

function buildRoom(room, data, materials) {
    const geometry = new THREE.BoxGeometry(Math.max(0.1, room.w - ROOM_INSET), 1, Math.max(0.1, room.h - ROOM_INSET));
    const mesh = new THREE.Mesh(geometry, materials.empty);
    mesh.scale.y = room.altura;
    mesh.position.set(room.x + room.w / 2 - data.ancho / 2, room.altura / 2, room.y + room.h / 2 - data.alto / 2);
    mesh.castShadow = true;
    mesh.receiveShadow = true;
    mesh.userData = { ...room, height: room.altura };
    const outline = new THREE.LineSegments(new THREE.EdgesGeometry(geometry), materials.outline);
    outline.visible = false;
    mesh.add(outline);
    mesh.userData.outline = outline;
    return mesh;
}

/** Build the floor model and its room index.
 * @param {object} data Floor geometry and rooms.
 * @param {object} materials Shared materials.
 * @returns {{group: THREE.Group, rooms: Map}} Model and indexed rooms.
 */
export function buildModel(data, materials) {
    const group = new THREE.Group();
    const rooms = new Map();
    const slab = new THREE.Mesh(new THREE.BoxGeometry(data.ancho, SLAB_HEIGHT, data.alto), [
        materials.edge,
        materials.edge,
        materials.floor,
        materials.edge,
        materials.edge,
        materials.edge,
    ]);
    slab.position.y = -SLAB_HEIGHT / 2;
    slab.receiveShadow = true;
    group.add(slab);
    const walls = buildWalls(data, materials.wall);
    if (walls) group.add(walls);
    data.salas.forEach((room) => {
        const mesh = buildRoom(room, data, materials);
        group.add(mesh);
        rooms.set(room.identificador, mesh);
    });
    return { group, rooms };
}

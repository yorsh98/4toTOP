import { ACTIVE_LIFT, HOVER_LIFT } from './dimensions';

/** Resolve a room's visual state.
 * @param {object} state Room selection, search and interaction flags.
 * @returns {{material: string, lift: number}} Material name and elevation.
 */
export function resolveRoomAppearance(state) {
    if (state.selected || state.match) return { material: 'active', lift: ACTIVE_LIFT };
    if (state.dimmed) return { material: 'dimmed', lift: 0 };
    if (state.hover || state.focus) return { material: 'hover', lift: HOVER_LIFT };
    return { material: state.inventory ? 'present' : 'empty', lift: 0 };
}

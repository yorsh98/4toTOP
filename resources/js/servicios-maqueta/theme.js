/** Apply CSS theme tokens and observe theme changes.
 * @param {HTMLElement} stage Scene container.
 * @param {object} materials Shared materials.
 * @param {Function} render Request a frame.
 * @returns {Function} Disconnect the observer.
 */
export function observeTheme(stage, materials, render) {
    const update = () => {
        const css = getComputedStyle(stage);
        const color = (name) => css.getPropertyValue(name).trim();
        materials.floor.color.set(color('--floor'));
        materials.edge.color.set(color('--slab-edge'));
        materials.wall.color.set(color('--wall-top'));
        materials.present.color.set(color('--room-data'));
        materials.empty.color.set(color('--room-empty'));
        materials.dimmed.color.set(color('--room-empty'));
        materials.active.color.set(color('--room-data-top'));
        materials.active.emissive.set('#22d3ee');
        materials.active.emissiveIntensity = 0.35;
        materials.hover.color.set(color('--room-data-top'));
        render();
    };
    const observer = new MutationObserver(update);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    update();
    return () => observer.disconnect();
}

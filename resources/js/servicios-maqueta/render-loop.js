/**
 * Schedule frames only while an update or animation needs one.
 * @param {function(number): boolean} render
 * @returns {{request: function(): void, dispose: function(): void}}
 */
export function createRenderLoop(render) {
    let frame = 0;
    function draw(now) {
        frame = 0;
        if (render(now)) request();
    }
    function request() {
        if (!frame) frame = requestAnimationFrame(draw);
    }
    return {
        request,
        dispose() {
            cancelAnimationFrame(frame);
            frame = 0;
        },
    };
}

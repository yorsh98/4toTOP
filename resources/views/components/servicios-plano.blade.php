@props(['piso', 'hotspots', 'selectedHotspot' => null])
<div x-data="{
    relief: false, orbit: window.matchMedia('(max-width: 640px)').matches ? 0 : -28, zoom: window.matchMedia('(max-width: 640px)').matches ? 1.2 : 1,
    tilt: window.matchMedia('(max-width: 640px)').matches ? 30 : 48,
    pointers: {}, start: {x: 0, y: 0}, lastX: 0, distance: 0, moved: false, velocity: 0, frame: null, fallback: false,
    reset() { let small = window.matchMedia('(max-width: 640px)').matches; this.orbit = small ? 0 : -28; this.zoom = small ? 1.2 : 1 },
    clampZoom(value) { this.zoom = Math.max(.8, Math.min(2, value)) },
    down(e) { if (e.button !== 0 && e.pointerType === 'mouse') return; cancelAnimationFrame(this.frame); this.pointers[e.pointerId] = {x: e.clientX, y: e.clientY}; this.lastX = e.clientX; this.start = {x: e.clientX, y: e.clientY}; this.moved = false; this.velocity = 0 },
    move(e) { if (!this.pointers[e.pointerId]) return; let old = this.pointers[e.pointerId]; let dx = e.clientX - old.x; let dy = e.clientY - old.y; this.pointers[e.pointerId] = {x: e.clientX, y: e.clientY}; let points = Object.values(this.pointers); if (!this.moved && Math.hypot(e.clientX - this.start.x, e.clientY - this.start.y) > 6) this.moved = true; if (points.length > 1) { let d = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y); if (this.distance) this.clampZoom(this.zoom * d / this.distance); this.distance = d } else if (this.moved) { this.orbit += dx * .45; this.velocity = dx * .45 } },
    up(e) { delete this.pointers[e.pointerId]; this.distance = 0; if (this.moved && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) { let tick = () => { this.velocity *= .9; if (Math.abs(this.velocity) > .05) { this.orbit += this.velocity; this.frame = requestAnimationFrame(tick) } }; this.frame = requestAnimationFrame(tick) } },
    fullscreen() { if (document.fullscreenElement) document.exitFullscreen(); else if (this.fallback) this.fallback = false; else if (this.$refs.stage.requestFullscreen) this.$refs.stage.requestFullscreen().catch(() => this.fallback = true); else this.fallback = true }
}" class="mt-4 min-w-0">
    <div class="mb-3 flex flex-wrap items-center gap-3">
        <div role="group" aria-label="Vista del plano" class="floor-view-switch">
            @foreach (['2D', '3D'] as $mode)
                <button type="button" @click="relief = {{ $mode === '3D' ? 'true' : 'false' }}"
                    :aria-pressed="String({{ $mode === '3D' ? 'relief' : '!relief' }})">{{ $mode }}</button>
            @endforeach
        </div>
            <div x-show="relief" style="display:none" class="model-controls flex flex-wrap items-center gap-3 text-sm">
                <button type="button" @click="orbit -= 90" aria-label="Girar 90 grados a la izquierda">⟲</button>
                <button type="button" @click="orbit += 90" aria-label="Girar 90 grados a la derecha">⟳</button>
                <button type="button" @click="reset()" aria-label="Brújula: volver a la vista inicial" title="Norte · vista inicial">N <span class="inline-block transition-transform" :style="{transform: 'rotate(' + orbit + 'deg)'}" aria-hidden="true">↑</span></button>
                <button type="button" @click="fullscreen()">Pantalla completa</button>
                <label>Girar <input type="range" min="-180" max="180" x-model.number="orbit" :aria-valuetext="orbit + ' grados'"></label>
                <label>Acercar <input type="range" min="0.8" max="2" step=".05" x-model.number="zoom" :aria-valuetext="Math.round(zoom * 100) + '%'"></label>
                <button type="button" class="rounded border px-3 py-1" @click="reset()">Vista inicial</button>
            </div>
    </div>
    @include('components.servicios-maqueta')
    <div x-show="!relief" class="floor-stage">
        {{ $slot }}
    </div>
</div>

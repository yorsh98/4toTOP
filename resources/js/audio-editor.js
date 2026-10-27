import WaveSurfer from 'wavesurfer.js';
import RegionsPlugin from 'wavesurfer.js/dist/plugins/regions.esm.js';
import TimelinePlugin from 'wavesurfer.js/dist/plugins/timeline.esm.js';
import { bump } from './lib/chat-motion';

// Fragment look on the dark editing desk, per mode: fill, edges and a pattern so the meaning never rests on colour alone.
const LOOK = {
    keep: ['rgba(74, 222, 128, 0.24)', '#4ADE80', 'none', 'auto'],
    remove: ['rgba(248, 113, 113, 0.16)', '#F87171', 'repeating-linear-gradient(135deg, rgba(248, 113, 113, 0.55) 0 3px, transparent 3px 9px)', 'auto'],
    silence: ['rgba(148, 163, 184, 0.22)', '#CBD5E1', 'radial-gradient(rgba(226, 232, 240, 0.75) 1px, transparent 1.5px)', '7px 7px'],
};

const rulerLabel = seconds => {
    const s = Math.round(seconds);
    const h = Math.floor(s / 3600);
    const m = Math.floor(s / 60) % 60;
    return (h ? h + ':' + String(m).padStart(2, '0') : String(m)) + ':' + String(s % 60).padStart(2, '0');
};

export function createEditor({ container, file, duration, peaks, onChange, onTime, onPlayable, onSelect, onPlaying }) {
    const bytes = Uint8Array.from(atob(peaks), c => c.charCodeAt(0));
    const media = new Audio();
    const url = URL.createObjectURL(file);
    media.preload = 'metadata';
    media.src = url;
    media.addEventListener('error', () => onPlayable(false));
    media.addEventListener('canplay', () => onPlayable(true));
    if (media.canPlayType(file.type) === '') onPlayable(false);

    const wavesurfer = WaveSurfer.create({
        container, media, peaks: [Float32Array.from(bytes, b => b / 255)], duration,
        height: 132, waveColor: '#7DD3FC', progressColor: '#E0F2FE', cursorColor: '#FBBF24', cursorWidth: 2,
        // No dragToSeek: in wavesurfer 7 it takes the drag away from region selection. A click still seeks.
        barWidth: 2, barGap: 1, barRadius: 2, normalize: true, minPxPerSec: 1,
        plugins: [TimelinePlugin.create({
            height: 22, formatTimeCallback: rulerLabel, secondaryLabelOpacity: 0.55,
            style: { color: '#94A3B8', fontSize: '11px', fontFamily: "'Red Hat Mono', ui-monospace, monospace" },
        })],
    });
    const regions = wavesurfer.registerPlugin(RegionsPlugin.create());
    regions.enableDragSelection({ color: 'rgba(21, 128, 61, 0.22)', minLength: 0.05 });
    for (const event of ['region-created', 'region-updated', 'region-removed']) {
        regions.on(event, region => onChange(regions.getRegions(), region, event));
    }
    regions.on('region-clicked', (region, event) => { event.stopPropagation(); onSelect(region.id); });
    wavesurfer.on('timeupdate', onTime);
    wavesurfer.on('interaction', onTime);
    wavesurfer.on('play', () => onPlaying(true));
    wavesurfer.on('pause', () => onPlaying(false));
    wavesurfer.on('finish', () => onPlaying(false));
    wavesurfer.on('error', () => onPlayable(false));
    return {
        wavesurfer, regions,
        destroy() {
            media.pause();
            wavesurfer.destroy();
            media.removeAttribute('src');
            media.load();
            URL.revokeObjectURL(url);
        },
    };
}

export function createCutter(maxRegions) {
    // Keep browser media/plugin instances outside Alpine's reactive proxies.
    let editor = null;
    let restoring = false;
    let generation = 0;
    let stopAt = null;
    return {
        file: null, peaksEvent: null, hasEditor: false, duration: 0,
        loading: false, error: null, playable: true, playing: false,
        regions: [], selected: null, time: 0, zoom: 1, history: [], future: [], invalid: {},

        load(file) {
            const earlyPeaks = !this.file ? this.peaksEvent : null;
            this.reset();
            this.file = file ?? null;
            this.peaksEvent = earlyPeaks;
            this.loading = !!file;
            this.build();
        },
        // Once the server has measured the audio, the dialog moves on to the waveform by itself.
        loaded(event) {
            if (this.file && event.detail.name && event.detail.name !== this.file.name) return;
            this.peaksEvent = event.detail;
            this.duration = event.detail.duration;
            if (this.step !== 1) return this.build();
            // Forced: canGo(2) waits for the editor, which build() creates once the waveform's step is visible.
            setTimeout(() => { this.go(2, true); this.build(); }, 400);
        },
        build() {
            if (!this.file || !this.peaksEvent || editor) return;
            if (this.peaksEvent.name && this.peaksEvent.name !== this.file.name) return;
            const version = ++generation;
            this.duration = this.peaksEvent.duration;
            this.$nextTick(() => {
                if (version !== generation) return;
                try {
                    editor = createEditor({
                        container: this.$refs.wave, file: this.file, ...this.peaksEvent,
                        onChange: (items, region, event) => this.changed(items, region, event),
                        onSelect: id => this.select(id),
                        onTime: time => {
                            this.time = Math.min(this.duration, time);
                            if (stopAt !== null && time >= stopAt) {
                                editor?.wavesurfer.pause();
                                stopAt = null;
                            }
                        },
                        onPlayable: playable => { this.playable = playable; },
                        onPlaying: playing => { this.playing = playing; },
                    });
                    this.hasEditor = true;
                    this.error = null;
                } catch (error) {
                    console.error(error);
                    this.error = 'No se pudo leer el audio para mostrar su onda. Vuelve a subir el archivo.';
                } finally {
                    this.loading = false;
                }
            });
        },
        reset() {
            generation++;
            restoring = true;
            editor?.destroy();
            editor = null;
            restoring = false;
            stopAt = null;
            Object.assign(this, { file: null, peaksEvent: null, hasEditor: false, duration: 0, regions: [], selected: null, time: 0, zoom: 1, history: [], future: [], invalid: {}, error: null, loading: false, playing: false, playable: true });
            this.sync();
        },
        destroy() { generation++; restoring = true; editor?.destroy(); editor = null; },
        sync() {
            this.$wire.set('options.segments', JSON.stringify(this.regions.map(r => [+r.start.toFixed(3), +r.end.toFixed(3)])), false);
        },
        snapshot() {
            this.history.push(this.regions.map(r => ({ ...r })));
            if (this.history.length > 50) this.history.shift();
            this.future = [];
        },
        changed(items, region, event) {
            if (restoring) return;
            if (items.length > maxRegions) {
                restoring = true;
                region.remove();
                restoring = false;
                this.error = `Puedes marcar hasta ${maxRegions} regiones.`;
                return;
            }
            const next = items.map(({ id, start, end }) => ({ id, start, end })).sort((a, b) => a.start - b.start);
            if (JSON.stringify(next) === JSON.stringify(this.regions)) return;
            this.snapshot();
            this.regions = next;
            this.invalid = {};
            this.error = null;
            this.selected = event === 'region-removed' ? (next[0]?.id ?? null) : region.id;
            this.recolor();
            this.sync();
            this.bumpTotals();
        },
        restore(items) {
            restoring = true;
            try {
                editor.regions.clearRegions();
                for (const item of items) editor.regions.addRegion({ ...item, minLength: 0.05 });
            } finally { restoring = false; }
            this.regions = items.map(r => ({ ...r }));
            this.selected = this.regions[0]?.id ?? null;
            this.invalid = {};
            this.error = null;
            this.recolor();
            this.sync();
            this.bumpTotals();
        },
        undo() {
            if (!editor || !this.history.length) return;
            this.future.push(this.regions.map(r => ({ ...r })));
            this.restore(this.history.pop());
        },
        redo() {
            if (!editor || !this.future.length) return;
            this.history.push(this.regions.map(r => ({ ...r })));
            this.restore(this.future.pop());
        },
        select(id) { this.selected = id; this.recolor(); },
        recolor() {
            if (!editor) return;
            const [color, edge, pattern, size] = LOOK[this.$wire.options.mode] ?? LOOK.keep;
            [...editor.regions.getRegions()].sort((a, b) => a.start - b.start).forEach((region, index) => {
                region.setOptions({ color });
                const el = region.element;
                if (!el) return;
                Object.assign(el.style, {
                    backgroundImage: pattern, backgroundSize: size, borderLeft: '2px solid ' + edge, borderRight: '2px solid ' + edge,
                    outline: region.id === this.selected ? '2px solid #FBBF24' : '', outlineOffset: '-2px',
                });
                let label = el.querySelector('[data-ac-label]');
                if (!label) {
                    label = document.createElement('span');
                    label.dataset.acLabel = '';
                    Object.assign(label.style, {
                        position: 'absolute', top: '4px', left: '6px', font: "600 11px 'Red Hat Mono', ui-monospace, monospace",
                        color: '#0B1B33', background: edge, borderRadius: '4px', padding: '0 5px', pointerEvents: 'none',
                    });
                    el.appendChild(label);
                }
                label.style.background = edge;
                label.textContent = String(index + 1);
            });
        },
        bumpTotals() {
            this.$nextTick(() => ['count', 'final2', 'final3'].forEach(ref => bump(this.$refs[ref])));
        },
        stop() { editor?.wavesurfer.pause(); },
        /**
         * The result the server will deliver, for the "Así quedará tu audio" strip: the same rounding, merging and
         * complement as AudioCutTool::spans(), and one fade less per join.
         */
        plan(mode = this.$wire.options.mode) {
            const total = this.duration;
            if (!total || !this.regions.length) return { segments: [], final: 0, files: 0 };
            const merged = [];
            this.regions.map(r => [Math.max(0, +r.start.toFixed(3)), Math.min(total, +r.end.toFixed(3))])
                .sort((a, b) => a[0] - b[0])
                .forEach(([start, end]) => {
                    const last = merged[merged.length - 1];
                    if (last && start <= last[1]) last[1] = Math.max(last[1], end);
                    else merged.push([start, end]);
                });
            const marked = { keep: 'keep', remove: 'cut', silence: 'mute' }[mode] ?? 'keep';
            const rest = mode === 'keep' ? 'cut' : 'keep';
            const segments = [];
            let cursor = 0;
            for (const [start, end] of merged) {
                if (start - cursor >= 0.01) segments.push({ start: cursor, end: start, kind: rest });
                segments.push({ start, end, kind: marked });
                cursor = end;
            }
            if (total - cursor >= 0.01) segments.push({ start: cursor, end: total, kind: rest });
            const kept = segments.filter(segment => segment.kind === 'keep');
            const options = this.$wire.options;
            const separate = mode === 'keep' && !!options.separate;
            const fade = mode === 'silence' ? 0 : (+options.fade_ms || 0) / 1000;
            const length = kept.reduce((sum, segment) => sum + segment.end - segment.start, 0);
            const final = mode === 'silence' ? total : length - (!separate && kept.length > 1 ? (kept.length - 1) * fade : 0);
            return { segments, final, files: separate ? kept.length : 1 };
        },
        async play() {
            if (!editor || !this.playable) return;
            stopAt = null;
            try { await editor.wavesurfer.playPause(); } catch { this.playable = false; }
        },
        async playRegion(id) {
            const region = this.regions.find(r => r.id === id);
            if (!editor || !this.playable || !region) return;
            this.select(id);
            stopAt = region.end;
            try { await editor.wavesurfer.play(region.start, region.end); } catch { this.playable = false; }
        },
        seek(time) {
            if (!editor) return;
            stopAt = null;
            this.time = Math.max(0, Math.min(this.duration, time));
            editor.wavesurfer.setTime(this.time);
        },
        addAtCursor() {
            if (!editor) return;
            if (this.regions.length >= maxRegions) { this.error = `Puedes marcar hasta ${maxRegions} regiones.`; return; }
            if (this.duration - this.time < 0.05) { this.error = 'Mueve el cursor antes del final para agregar una región.'; return; }
            editor.regions.addRegion({ start: this.time, end: Math.min(this.duration, this.time + 5), minLength: 0.05 });
        },
        splitAtCursor() {
            const region = this.regions.find(r => r.id === this.selected && this.time > r.start && this.time < r.end)
                ?? this.regions.find(r => this.time > r.start && this.time < r.end);
            if (!region || !editor) { this.error = 'Coloca el cursor dentro de una región para dividirla.'; return; }
            if (this.regions.length >= maxRegions) { this.error = `Puedes marcar hasta ${maxRegions} regiones.`; return; }
            const at = +this.time.toFixed(3);
            if (Math.min(at - region.start, region.end - at) < 0.05) { this.error = 'Cada región debe durar al menos 0,05 segundos.'; return; }
            const items = this.regions.flatMap(r => r.id === region.id ? [{ ...r, end: at }, { id: crypto.randomUUID(), start: at, end: r.end }] : [{ ...r }]);
            this.snapshot();
            this.restore(items);
        },
        removeRegion(id) { editor?.regions.getRegions().find(r => r.id === id)?.remove(); },
        setBoundary(field) { if (this.selected) this.setTime(this.selected, field, this.time.toFixed(3)); },
        setTime(id, field, text) {
            const value = this.parse(text);
            const item = this.regions.find(r => r.id === id);
            if (!item || !editor) return;
            const next = { ...item, [field]: value };
            // A start typed past the end moves the whole region, keeping its length.
            if (field === 'start' && value !== null && value >= item.end) next.end = Math.min(this.duration, value + item.end - item.start);
            const key = id + ':' + field;
            if (value === null || value < 0 || value > this.duration || next.end - next.start < 0.05 - 1e-7) {
                this.invalid[key] = true;
                this.error = 'Escribe un tiempo dentro del audio y deja al menos 0,05 segundos entre inicio y fin.';
                return;
            }
            delete this.invalid[key];
            this.error = null;
            if (value === item[field]) return;
            this.snapshot();
            this.restore(this.regions.map(r => r.id === id ? next : { ...r }).sort((a, b) => a.start - b.start));
            this.select(id);
        },
        zoomBy(factor) {
            if (!editor) return;
            this.zoom = Math.max(1, Math.min(1000, this.zoom * factor));
            editor.wavesurfer.zoom(this.zoom);
        },
        keydown(event) {
            if (!editor || event.target.closest('input, select, textarea, [contenteditable="true"]')) return;
            const key = event.key.toLowerCase();
            const ctrl = event.ctrlKey || event.metaKey;
            let action;
            if (ctrl) {
                action = { i: () => this.splitAtCursor(), z: () => event.shiftKey ? this.redo() : this.undo(), y: () => this.redo(), '1': () => this.zoomBy(2), '3': () => this.zoomBy(0.5), '2': () => this.zoomBy(1 / this.zoom) }[key];
            } else if (!event.altKey) {
                action = { ' ': () => event.shiftKey ? this.playRegion(this.selected) : this.play(), home: () => this.seek(0), end: () => this.seek(this.duration), arrowleft: () => this.seek(this.time - (event.shiftKey ? 10 : 1)), arrowright: () => this.seek(this.time + (event.shiftKey ? 10 : 1)), '[': () => this.setBoundary('start'), ']': () => this.setBoundary('end'), delete: () => this.removeRegion(this.selected), backspace: () => this.removeRegion(this.selected) }[key];
            }
            if (action) { event.preventDefault(); action(); }
        },
        clock(seconds) {
            const milliseconds = Math.round(Math.max(0, seconds) * 1000);
            const hours = Math.floor(milliseconds / 3600000);
            const minutes = Math.floor(milliseconds / 60000) % 60;
            return (hours ? hours + ':' + String(minutes).padStart(2, '0') : String(minutes)) + ':' + String(Math.floor(milliseconds / 1000) % 60).padStart(2, '0') + '.' + String(milliseconds % 1000).padStart(3, '0');
        },
        parse(text) {
            const value = String(text).trim();
            if (!/^\d+(?::\d{1,2}){0,2}(?:[.,]\d{1,3})?$/.test(value)) return null;
            const parts = value.replace(',', '.').split(':').map(Number);
            if (parts.slice(1).some(part => part >= 60)) return null;
            const seconds = parts.reduce((total, part) => total * 60 + part, 0);
            return Number.isFinite(seconds) ? +seconds.toFixed(3) : null;
        },
    };
}

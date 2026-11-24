// ── Siren & bell sound engine (wave 55) ──
// School-bell sounds synthesised live in the browser with the Web Audio API —
// no audio files to host, every console (front desk, staff room, head's
// office) can ring them. Used by the Siren module's live console and by the
// global <SirenTicker /> that fires timetabled rings anywhere in the app.

export type SirenSound = "BELL" | "SIREN" | "CHIME" | "KLAXON" | "BUGLE";

export const SIREN_SOUNDS: { id: SirenSound; label: string; hint: string }[] = [
  { id: "BELL", label: "School bell", hint: "Classic hand-bell ring — change of lesson, breaks" },
  { id: "SIREN", label: "Emergency siren", hint: "Rising-falling wail — emergencies & closing time" },
  { id: "CHIME", label: "Assembly chime", hint: "Three-tone chime — assemblies & devotion" },
  { id: "KLAXON", label: "Klaxon horn", hint: "Pulsed horn — alarms & attention" },
  { id: "BUGLE", label: "Bugle call", hint: "Bugle melody — sports, cadets & flag raising" },
];

let ctx: AudioContext | null = null;
const active: { oscs: OscillatorNode[]; timers: number[]; master: GainNode | null } = { oscs: [], timers: [], master: null };

function audioCtx(): AudioContext {
  if (!ctx) {
    const AC = window.AudioContext || (window as any).webkitAudioContext;
    ctx = new AC();
  }
  if (ctx.state === "suspended") void ctx.resume();
  return ctx;
}

function stopAllInternal() {
  for (const t of active.timers) window.clearTimeout(t);
  active.timers = [];
  for (const o of active.oscs) { try { o.stop(); } catch { /* already stopped */ } }
  active.oscs = [];
  if (active.master) { try { active.master.disconnect(); } catch { /* noop */ } active.master = null; }
}

/**
 * Play a synthesised school sound for `durationSec` seconds.
 * Returns a stop() handle. Safe to call repeatedly — previous sounds stop first.
 */
export function playSirenSound(sound: SirenSound, durationSec = 10): () => void {
  stopAllInternal();
  const ac = audioCtx();
  const master = ac.createGain();
  master.gain.value = 0.9;
  master.connect(ac.destination);
  active.master = master;
  const t0 = ac.currentTime;
  const endAt = t0 + Math.max(1, durationSec);

  const osc = (type: OscillatorType, freq: number, at: number, dur: number, vol: number, freqEnd?: number) => {
    const o = ac.createOscillator();
    const g = ac.createGain();
    o.type = type;
    o.frequency.setValueAtTime(freq, at);
    if (freqEnd !== undefined) o.frequency.linearRampToValueAtTime(freqEnd, at + dur);
    g.gain.setValueAtTime(0.0001, at);
    g.gain.linearRampToValueAtTime(vol, at + 0.015);
    g.gain.exponentialRampToValueAtTime(0.0001, at + dur);
    o.connect(g); g.connect(master);
    o.start(at); o.stop(at + dur + 0.05);
    active.oscs.push(o);
    return o;
  };

  if (sound === "BELL") {
    // classic double "ding-ding" every 1.6 s — the school hand bell
    for (let t = t0; t < endAt; t += 1.6) {
      osc("sine", 900, t, 0.9, 0.5);
      osc("sine", 1352, t, 0.7, 0.25); // harmonic shimmer
      osc("sine", 900, t + 0.8, 0.9, 0.5);
      osc("sine", 1352, t + 0.8, 0.7, 0.25);
    }
  } else if (sound === "SIREN") {
    // rising-falling wail, 1.8 s cycle
    const o = ac.createOscillator();
    const g = ac.createGain();
    o.type = "sawtooth";
    g.gain.value = 0.32;
    o.connect(g); g.connect(master);
    o.start(t0); o.stop(endAt + 0.1);
    active.oscs.push(o);
    for (let t = t0; t < endAt; t += 1.8) {
      o.frequency.setValueAtTime(420, t);
      o.frequency.linearRampToValueAtTime(1150, t + 0.9);
      o.frequency.linearRampToValueAtTime(420, t + 1.8);
    }
  } else if (sound === "CHIME") {
    // three-tone assembly chime (E5 → G5 → C6), 2.7 s cycle
    const notes: [number, number][] = [[659.3, 0], [784, 0.9], [1046.5, 1.8]];
    for (let t = t0; t < endAt; t += 2.7) for (const [f, off] of notes) osc("sine", f, t + off, 1.6, 0.4);
  } else if (sound === "KLAXON") {
    // pulsed horn — 0.5 s on / 0.25 s off
    for (let t = t0; t < endAt; t += 0.75) osc("square", 250, t, 0.5, 0.3, 235);
  } else if (sound === "BUGLE") {
    // bugle call — ascending triad figure, 2.4 s cycle
    const notes: [number, number, number][] = [[262, 0, 0.4], [330, 0.4, 0.4], [392, 0.8, 0.4], [523, 1.2, 0.8], [392, 2.0, 0.4]];
    for (let t = t0; t < endAt; t += 2.4) for (const [f, off, dur] of notes) osc("triangle", f, t + off, dur, 0.45);
  }

  const stop = () => stopAllInternal();
  const timer = window.setTimeout(stop, Math.max(1, durationSec) * 1000);
  active.timers.push(timer);
  return stop;
}

/** Friendly label for a stored sound id. */
export const sirenSoundLabel = (id: string): string => SIREN_SOUNDS.find((s) => s.id === id)?.label ?? id;

export const DAY_NAMES = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

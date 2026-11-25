"use client";
// ── Motion layer (wave 61 — app-wide UI/UX modernization) ──
// Tiny shared framer-motion helpers so the whole app moves with one voice:
// module pages fade-and-rise in, KPI numbers count up, and users who prefer
// reduced motion get none (MotionConfig reducedMotion="user" is set at the
// call sites; CountUp checks useReducedMotion itself).
import { ReactNode, useEffect, useRef, useState } from "react";
import { motion, useReducedMotion } from "framer-motion";

/** Module content transition — fade + rise when the module (page) changes. */
export function PageTransition({ moduleKey, children }: { moduleKey: string; children: ReactNode }) {
  const reduce = useReducedMotion();
  return (
    <motion.div
      key={moduleKey}
      initial={reduce ? { opacity: 1 } : { opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.28, ease: [0.22, 1, 0.36, 1] }}
    >
      {children}
    </motion.div>
  );
}

/** Animated number — counts from its previous value to the new one. */
export function CountUp({ value, duration = 0.9, decimals }: { value: number; duration?: number; decimals?: number }) {
  const reduce = useReducedMotion();
  const [display, setDisplay] = useState(0);
  const fromRef = useRef(0);
  useEffect(() => {
    if (reduce) { fromRef.current = value; return; }
    const from = fromRef.current;
    const start = performance.now();
    let raf = 0;
    const step = (t: number) => {
      const p = Math.min(1, (t - start) / (duration * 1000));
      const eased = 1 - Math.pow(1 - p, 3);
      setDisplay(from + (value - from) * eased);
      if (p < 1) raf = requestAnimationFrame(step);
      else fromRef.current = value;
    };
    raf = requestAnimationFrame(step);
    return () => cancelAnimationFrame(raf);
  }, [value, duration, reduce]);
  const dec = decimals ?? (Number.isInteger(value) ? 0 : 1);
  const current = reduce ? value : display;
  const shown = dec > 0 ? current.toFixed(dec) : Math.round(current).toString();
  return <>{shown}</>;
}

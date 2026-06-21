import { useRef } from "react";

const SLIDER_HANDLE_W = 8;

interface TimeRangeSliderProps {
  viewStart: number;
  viewEnd: number;
  onViewChange: (start: number, end: number) => void;
  timeMin: number;
  timeMax: number;
  years: number[];
}

export function TimeRangeSlider({
  viewStart,
  viewEnd,
  onViewChange,
  timeMin,
  timeMax,
  years,
}: TimeRangeSliderProps) {
  const trackRef = useRef<HTMLDivElement>(null);
  const dragging = useRef<"start" | "end" | "middle" | null>(null);
  const dragOrigin = useRef({ x: 0, start: 0, end: 0 });

  const fracToMs = (f: number) => timeMin + f * (timeMax - timeMin);

  const formatLabel = (f: number) => {
    const d = new Date(fracToMs(f));
    return d.toLocaleDateString("en-US", { month: "short", year: "numeric" });
  };

  const getFrac = (clientX: number) => {
    const rect = trackRef.current?.getBoundingClientRect();
    if (!rect) return 0;
    return Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
  };

  const handlePointerDown = (e: React.PointerEvent, target: "start" | "end" | "middle") => {
    e.preventDefault();
    (e.target as HTMLElement).setPointerCapture(e.pointerId);
    dragging.current = target;
    dragOrigin.current = { x: e.clientX, start: viewStart, end: viewEnd };
  };

  const handlePointerMove = (e: React.PointerEvent) => {
    if (!dragging.current) return;
    const minSpan = 0.02;

    if (dragging.current === "start") {
      const frac = getFrac(e.clientX);
      onViewChange(Math.min(frac, viewEnd - minSpan), viewEnd);
    } else if (dragging.current === "end") {
      const frac = getFrac(e.clientX);
      onViewChange(viewStart, Math.max(frac, viewStart + minSpan));
    } else {
      const dx = e.clientX - dragOrigin.current.x;
      const rect = trackRef.current?.getBoundingClientRect();
      if (!rect) return;
      const fracDx = dx / rect.width;
      const span = dragOrigin.current.end - dragOrigin.current.start;
      let newStart = dragOrigin.current.start + fracDx;
      let newEnd = newStart + span;
      if (newStart < 0) { newEnd -= newStart; newStart = 0; }
      if (newEnd > 1) { newStart -= newEnd - 1; newEnd = 1; }
      onViewChange(Math.max(0, newStart), Math.min(1, newEnd));
    }
  };

  const handlePointerUp = () => { dragging.current = null; };

  // Year tick marks for the slider track
  const yearTicks = years.map((y) => {
    const ms = new Date(`${y}-01-01`).getTime();
    return { year: y, frac: (ms - timeMin) / (timeMax - timeMin) };
  }).filter((t) => t.frac > 0.02 && t.frac < 0.98);

  return (
    <div className="flex items-center gap-3 px-4 py-2.5 bg-[var(--surface-base)] border-b border-[var(--surface-overlay)]">
      <span className="text-[10px] text-[var(--text-muted)] tabular-nums shrink-0 w-16 text-right">
        {formatLabel(viewStart)}
      </span>

      {/* Slider track */}
      <div
        ref={trackRef}
        className="relative flex-1 h-6 select-none"
        onPointerMove={handlePointerMove}
        onPointerUp={handlePointerUp}
        onPointerLeave={handlePointerUp}
      >
        {/* Background track */}
        <div className="absolute top-1/2 -translate-y-1/2 left-0 right-0 h-1.5 rounded-full bg-[var(--surface-overlay)]" />

        {/* Year tick marks */}
        {yearTicks.map((t) => (
          <div
            key={t.year}
            className="absolute top-0 flex flex-col items-center"
            style={{ left: `${t.frac * 100}%`, transform: "translateX(-50%)" }}
          >
            <span className="text-[8px] text-[var(--text-ghost)] leading-none">{t.year}</span>
            <div className="w-px h-1 bg-[var(--text-ghost)] opacity-40 mt-0.5" />
          </div>
        ))}

        {/* Active range fill */}
        <div
          className="absolute top-1/2 -translate-y-1/2 h-1.5 rounded-full cursor-grab active:cursor-grabbing"
          style={{
            left: `${viewStart * 100}%`,
            width: `${(viewEnd - viewStart) * 100}%`,
            background: "linear-gradient(90deg, #22D3EE, #00D68F, #9D75F8)",
            opacity: 0.7,
          }}
          onPointerDown={(e) => handlePointerDown(e, "middle")}
        />

        {/* Start handle */}
        <div
          className="absolute top-1/2 -translate-y-1/2 rounded-sm cursor-ew-resize"
          style={{
            left: `calc(${viewStart * 100}% - ${SLIDER_HANDLE_W / 2}px)`,
            width: SLIDER_HANDLE_W,
            height: 18,
            backgroundColor: "#22D3EE",
            border: "1px solid rgba(255,255,255,0.2)",
          }}
          onPointerDown={(e) => handlePointerDown(e, "start")}
        />

        {/* End handle */}
        <div
          className="absolute top-1/2 -translate-y-1/2 rounded-sm cursor-ew-resize"
          style={{
            left: `calc(${viewEnd * 100}% - ${SLIDER_HANDLE_W / 2}px)`,
            width: SLIDER_HANDLE_W,
            height: 18,
            backgroundColor: "#9D75F8",
            border: "1px solid rgba(255,255,255,0.2)",
          }}
          onPointerDown={(e) => handlePointerDown(e, "end")}
        />
      </div>

      <span className="text-[10px] text-[var(--text-muted)] tabular-nums shrink-0 w-16">
        {formatLabel(viewEnd)}
      </span>
    </div>
  );
}

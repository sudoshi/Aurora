import { useState, useCallback } from "react";
import { cn } from "@/lib/utils";
import { useWeightPresets } from "../hooks/useFingerprint";
import type { DimensionWeights } from "../types";

interface WeightControlsProps {
  weights: DimensionWeights;
  onChange: (w: DimensionWeights) => void;
  className?: string;
}

type PresetKey = "balanced" | "genomics-first" | "volumetric" | "custom";

const PRESET_LABELS: Record<PresetKey, string> = {
  balanced: "Balanced",
  "genomics-first": "Genomics-First",
  volumetric: "Volumetric",
  custom: "Custom",
};

const DIMENSION_META: {
  key: keyof DimensionWeights;
  label: string;
  color: string;
}[] = [
  { key: "genomic", label: "Genomic", color: "#A78BFA" },
  { key: "volumetric", label: "Volumetric", color: "#60A5FA" },
  { key: "clinical", label: "Clinical", color: "#34D399" },
];

function normalizeWeights(
  weights: DimensionWeights,
  changedKey: keyof DimensionWeights,
  newValue: number,
): DimensionWeights {
  const clamped = Math.min(1, Math.max(0, newValue));
  const remaining = 1 - clamped;
  const otherKeys = DIMENSION_META
    .map((d) => d.key)
    .filter((k) => k !== changedKey);

  const otherSum = otherKeys.reduce((sum, k) => sum + weights[k], 0);

  const result = { ...weights, [changedKey]: clamped };

  if (otherSum === 0) {
    const split = remaining / otherKeys.length;
    for (const k of otherKeys) {
      result[k] = Math.round(split * 100) / 100;
    }
  } else {
    for (const k of otherKeys) {
      result[k] = Math.round((weights[k] / otherSum) * remaining * 100) / 100;
    }
  }

  // Fix rounding to exactly sum to 1
  const sum = result.genomic + result.volumetric + result.clinical;
  if (Math.abs(sum - 1) > 0.001) {
    const lastKey = otherKeys[otherKeys.length - 1];
    result[lastKey] = Math.round((result[lastKey] + (1 - sum)) * 100) / 100;
  }

  return result;
}

export function WeightControls({ weights, onChange, className }: WeightControlsProps) {
  const { data: presets } = useWeightPresets();
  const [activePreset, setActivePreset] = useState<PresetKey>("balanced");

  const handlePreset = useCallback(
    (key: PresetKey) => {
      setActivePreset(key);
      if (key === "custom") return;

      const preset = presets?.find((p) =>
        p.name.toLowerCase().replace(/\s+/g, "-") === key ||
        p.name.toLowerCase().includes(key.split("-")[0]),
      );

      if (preset) {
        onChange({
          genomic: preset.genomic_weight,
          volumetric: preset.volumetric_weight,
          clinical: preset.clinical_weight,
        });
      } else {
        // Fallback defaults
        const defaults: Record<string, DimensionWeights> = {
          balanced: { genomic: 0.34, volumetric: 0.33, clinical: 0.33 },
          "genomics-first": { genomic: 0.6, volumetric: 0.2, clinical: 0.2 },
          volumetric: { genomic: 0.2, volumetric: 0.6, clinical: 0.2 },
        };
        onChange(defaults[key] ?? { genomic: 0.34, volumetric: 0.33, clinical: 0.33 });
      }
    },
    [presets, onChange],
  );

  const handleSlider = (key: keyof DimensionWeights, value: number) => {
    setActivePreset("custom");
    onChange(normalizeWeights(weights, key, value));
  };

  return (
    <div className={cn("rounded-xl border border-[#1C1C48] bg-[#10102A] p-4", className)}>
      <h3 className="text-sm font-semibold text-[#E8ECF4] mb-3">
        Similarity Weights
      </h3>

      {/* Preset buttons */}
      <div className="flex items-center gap-2 mb-4">
        {(Object.keys(PRESET_LABELS) as PresetKey[]).map((key) => (
          <button
            key={key}
            type="button"
            onClick={() => handlePreset(key)}
            className={cn(
              "rounded-lg px-3 py-1.5 text-xs font-medium transition-colors border",
              activePreset === key
                ? "bg-[#2DD4BF]/10 text-[#2DD4BF] border-[#2DD4BF]/30"
                : "bg-[#0A0A18] text-[#7A8298] border-[#1C1C48] hover:border-[#4A5068] hover:text-[#B4BAC8]",
            )}
          >
            {PRESET_LABELS[key]}
          </button>
        ))}
      </div>

      {/* Sliders */}
      <div className="space-y-3">
        {DIMENSION_META.map(({ key, label, color }) => (
          <div key={key} className="flex items-center gap-3">
            <span className="text-xs text-[#7A8298] w-20 shrink-0">{label}</span>
            <input
              type="range"
              min={0}
              max={100}
              value={Math.round(weights[key] * 100)}
              onChange={(e) => handleSlider(key, Number(e.target.value) / 100)}
              className="flex-1 h-1.5 rounded-full appearance-none cursor-pointer"
              style={{
                background: `linear-gradient(to right, ${color} ${weights[key] * 100}%, #1C1C48 ${weights[key] * 100}%)`,
                accentColor: color,
              }}
            />
            <span
              className="text-xs font-mono w-10 text-right"
              style={{ color }}
            >
              {Math.round(weights[key] * 100)}%
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}

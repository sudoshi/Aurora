import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { ScanLine, ExternalLink, Calendar, Monitor, MapPin } from "lucide-react";
import type { ImagingStudy } from "../types/profile";
import { InlineActionMenu } from "./InlineActionMenu";

const MODALITY_COLORS: Record<string, string> = {
  CT:  "#22D3EE",
  MRI: "#A78BFA",
  PET: "#F0607A",
  US:  "#60A5FA",
  XR:  "#2DD4BF",
  MG:  "#F472B6",
  NM:  "#FBBF24",
};

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

interface PatientImagingTabProps {
  studies: ImagingStudy[];
  patientId: number;
}

export default function PatientImagingTab({ studies, patientId }: PatientImagingTabProps) {
  const navigate = useNavigate();
  const [modalityFilter, setModalityFilter] = useState<string | null>(null);

  const modalities = [...new Set(studies.map((s) => s.modality))].sort();
  const filtered = modalityFilter
    ? studies.filter((s) => s.modality === modalityFilter)
    : studies;

  // Sort by date descending
  const sorted = [...filtered].sort(
    (a, b) => new Date(b.study_date).getTime() - new Date(a.study_date).getTime(),
  );

  if (studies.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-16 text-[var(--text-ghost)]">
        <ScanLine size={36} className="mb-3 opacity-40" />
        <p className="text-sm font-medium text-[var(--text-muted)]">No imaging studies available</p>
        <p className="text-xs mt-1">Imaging data will appear here when studies are uploaded</p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <ScanLine size={16} className="text-[#22D3EE]" />
          <h3 className="text-sm font-semibold text-[var(--text-primary)]">
            Imaging Studies
            <span className="ml-2 text-xs text-[var(--text-ghost)] font-normal">
              ({studies.length} total)
            </span>
          </h3>
        </div>
        <button
          type="button"
          onClick={() => navigate("/imaging")}
          className="inline-flex items-center gap-1.5 text-xs text-[#22D3EE] hover:text-[#67E8F9] transition-colors"
        >
          <ExternalLink size={12} />
          Full Imaging
        </button>
      </div>

      {/* Modality filter pills */}
      {modalities.length > 1 && (
        <div className="flex items-center gap-1.5 flex-wrap">
          <button
            type="button"
            onClick={() => setModalityFilter(null)}
            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-medium border transition-all ${
              modalityFilter === null
                ? "bg-[var(--primary-bg)] text-[var(--primary)] border-[var(--primary-border)]"
                : "bg-transparent text-[var(--text-ghost)] border-[var(--border-default)]"
            }`}
          >
            All ({studies.length})
          </button>
          {modalities.map((mod) => {
            const color = MODALITY_COLORS[mod] ?? "#7A8298";
            const count = studies.filter((s) => s.modality === mod).length;
            const active = modalityFilter === mod;
            return (
              <button
                key={mod}
                type="button"
                onClick={() => setModalityFilter(active ? null : mod)}
                className="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-medium border transition-all"
                style={
                  active
                    ? { backgroundColor: `${color}15`, color, borderColor: `${color}40` }
                    : { backgroundColor: "transparent", color: "var(--text-ghost)", borderColor: "var(--border-default)" }
                }
              >
                {mod} ({count})
              </button>
            );
          })}
        </div>
      )}

      {/* Studies list */}
      <div className="rounded-lg border border-[var(--border-default)] bg-[var(--surface-raised)] divide-y divide-[var(--border-default)]">
        {sorted.map((study) => {
          const color = MODALITY_COLORS[study.modality] ?? "#7A8298";
          return (
            <div
              key={study.id}
              className="flex items-center gap-4 px-4 py-3 hover:bg-[var(--primary-bg)] cursor-pointer transition-colors"
              onClick={() => navigate(`/imaging/studies/${study.id}`)}
            >
              {/* Modality badge */}
              <div
                className="flex items-center justify-center w-10 h-10 rounded-lg text-xs font-bold shrink-0"
                style={{ backgroundColor: `${color}15`, color, border: `1px solid ${color}30` }}
              >
                {study.modality}
              </div>

              {/* Study info */}
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-[var(--text-primary)] truncate">
                  {study.description ?? `${study.modality} Study`}
                </p>
                <div className="flex items-center gap-3 mt-1 text-xs text-[var(--text-muted)]">
                  <span className="inline-flex items-center gap-1">
                    <Calendar size={10} />
                    {formatDate(study.study_date)}
                  </span>
                  {study.body_part && (
                    <span className="inline-flex items-center gap-1">
                      <MapPin size={10} />
                      {study.body_part}
                    </span>
                  )}
                  {(study.num_series > 0 || study.num_instances > 0) && (
                    <span className="inline-flex items-center gap-1">
                      <Monitor size={10} />
                      {study.num_series} series · {study.num_instances} images
                    </span>
                  )}
                </div>
              </div>

              {/* Inline actions — stop propagation so row click doesn't fire */}
              <span
                onClick={(e) => e.stopPropagation()}
                onKeyDown={(e) => e.stopPropagation()}
                className="shrink-0"
              >
                <InlineActionMenu
                  recordRef={`imaging:${study.id}`}
                  domain="imaging"
                  patientId={patientId}
                  onDiscuss={() => {}}
                />
              </span>
            </div>
          );
        })}
      </div>
    </div>
  );
}

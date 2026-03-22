/**
 * PatientImagingTab -- Embeddable component for the patient profile page.
 * Shows the patient's imaging studies in a timeline view with measurement trends.
 */

import { useState } from "react";
import { Link } from "react-router-dom";
import {
  ScanLine, Ruler, Loader2, ChevronRight, Activity, AlertCircle,
} from "lucide-react";
import { usePatientTimeline, usePatientStudies } from "../hooks/useImaging";
import { MultiTrendChart } from "./MeasurementTrendChart";
import StudyBrowser from "./StudyBrowser";
import StudyComparisonViewer from "./StudyComparisonViewer";
import type { ImagingStudy, TimelineStudy } from "../types";

type AnyStudy = TimelineStudy | ImagingStudy;

interface PatientImagingTabProps {
  patientId: number;
}

export default function PatientImagingTab({ patientId }: PatientImagingTabProps) {
  const { data: timeline, isLoading: timelineLoading, error: timelineError } = usePatientTimeline(patientId);
  const { data: studies, isLoading: studiesLoading } = usePatientStudies(patientId);

  const [comparisonPair, setComparisonPair] = useState<[AnyStudy, AnyStudy] | null>(null);

  const isLoading = timelineLoading || studiesLoading;

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-16">
        <Loader2 size={24} className="animate-spin text-[#2DD4BF]" />
      </div>
    );
  }

  if (timelineError) {
    return (
      <div className="rounded-lg border border-[#F0607A]/30 bg-[#F0607A]/10 p-6 flex items-center gap-3">
        <AlertCircle size={18} className="text-[#F0607A] flex-shrink-0" />
        <p className="text-sm text-[#F0607A]">
          Failed to load imaging data: {(timelineError as Error).message}
        </p>
      </div>
    );
  }

  const hasStudies = (timeline?.studies?.length ?? 0) > 0 || (studies?.length ?? 0) > 0;
  const hasMeasurements = (timeline?.measurements?.length ?? 0) > 0;

  if (!hasStudies) {
    return (
      <div className="rounded-lg border border-dashed border-[#2A2A60] bg-[#10102A] p-10 text-center">
        <ScanLine size={28} className="text-[#1C1C48] mx-auto mb-3" />
        <p className="text-sm text-[#4A5068]">No imaging studies found for this patient.</p>
        <p className="text-xs text-[#4A5068] mt-1">
          Studies will appear here once DICOM data is imported and linked to this patient.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Summary stats */}
      {timeline && (
        <div className="grid grid-cols-3 gap-3">
          <div className="rounded-lg border border-[#1C1C48] bg-[#10102A] p-4">
            <div className="flex items-center gap-2 mb-2">
              <ScanLine size={14} className="text-[#60A5FA]" />
              <span className="text-[10px] text-[#4A5068] uppercase tracking-wider">Studies</span>
            </div>
            <p className="text-lg text-[#60A5FA] font-semibold font-mono">
              {timeline.summary.total_studies}
            </p>
            <p className="text-xs text-[#7A8298] mt-1">
              {timeline.summary.modalities.join(", ") || "--"}
              {timeline.summary.imaging_span_days
                ? ` · ${timeline.summary.imaging_span_days}d span`
                : ""}
            </p>
          </div>

          <div className="rounded-lg border border-[#1C1C48] bg-[#10102A] p-4">
            <div className="flex items-center gap-2 mb-2">
              <Ruler size={14} className="text-[#2DD4BF]" />
              <span className="text-[10px] text-[#4A5068] uppercase tracking-wider">Measurements</span>
            </div>
            <p className="text-lg text-[#2DD4BF] font-semibold font-mono">
              {timeline.summary.total_measurements}
            </p>
            <p className="text-xs text-[#7A8298] mt-1">
              {timeline.summary.measurement_types.length > 0
                ? `${timeline.summary.measurement_types.length} type${timeline.summary.measurement_types.length !== 1 ? "s" : ""}`
                : "None recorded"}
            </p>
          </div>

          <div className="rounded-lg border border-[#1C1C48] bg-[#10102A] p-4">
            <div className="flex items-center gap-2 mb-2">
              <Activity size={14} className="text-[#A78BFA]" />
              <span className="text-[10px] text-[#4A5068] uppercase tracking-wider">Date Range</span>
            </div>
            <p className="text-xs text-[#B4BAC8] mt-2">
              {timeline.summary.date_range.first
                ? new Date(timeline.summary.date_range.first).toLocaleDateString()
                : "--"}
              {" -- "}
              {timeline.summary.date_range.last
                ? new Date(timeline.summary.date_range.last).toLocaleDateString()
                : "--"}
            </p>
          </div>
        </div>
      )}

      {/* Measurement trends */}
      {hasMeasurements && timeline && (
        <MultiTrendChart measurements={timeline.measurements} />
      )}

      {/* Comparison viewer (when two studies are selected) */}
      {comparisonPair && (
        <StudyComparisonViewer
          baseline={comparisonPair[0]}
          followUp={comparisonPair[1]}
          onClose={() => setComparisonPair(null)}
          onSwap={() => setComparisonPair([comparisonPair[1], comparisonPair[0]])}
        />
      )}

      {/* Study browser */}
      {timeline && (
        <StudyBrowser
          studies={timeline.studies}
          onCompareSelect={(pair) => setComparisonPair(pair)}
          title={`Imaging Studies (${timeline.studies.length})`}
        />
      )}

      {/* Link to full imaging page */}
      <div className="flex justify-end">
        <Link
          to="/imaging"
          className="inline-flex items-center gap-1.5 text-xs text-[#2DD4BF] hover:text-[#26B8A5] transition-colors"
        >
          View full Imaging workspace <ChevronRight size={12} />
        </Link>
      </div>
    </div>
  );
}

import { useParams, Link } from "react-router-dom";
import { useState } from "react";
import { ArrowLeft, Layers, Brain, Loader2, ScanLine, Monitor, Ruler } from "lucide-react";
import { useImagingStudy, useIndexSeries, useExtractNlp, useImagingFeatures } from "../hooks/useImaging";
import type { ImagingSeries, ImagingFeature } from "../types";
import OhifViewer from "../components/OhifViewer";
import MeasurementPanel from "../components/MeasurementPanel";

const STUDY_TABS = [
  { id: "metadata", label: "Metadata", icon: ScanLine },
  { id: "measurements", label: "Measurements", icon: Ruler },
  { id: "viewer",   label: "View Scan", icon: Monitor },
] as const;

type StudyTab = (typeof STUDY_TABS)[number]["id"];

export default function ImagingStudyPage() {
  const { id } = useParams<{ id: string }>();
  const studyId = parseInt(id ?? "0");
  const [activeTab, setActiveTab] = useState<StudyTab>("metadata");

  const { data: study, isLoading } = useImagingStudy(studyId);
  const { data: features } = useImagingFeatures({ study_id: studyId, per_page: 50 });
  const indexSeries = useIndexSeries();
  const extractNlp = useExtractNlp();

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 size={28} className="animate-spin text-[#2DD4BF]" />
      </div>
    );
  }

  if (!study) {
    return (
      <div className="flex items-center justify-center py-24 text-[#7A8298]">
        Study not found.
      </div>
    );
  }

  const fields = [
    { label: "Study Instance UID", value: study.study_instance_uid },
    { label: "Accession Number", value: study.accession_number ?? "--" },
    { label: "Modality", value: study.modality ?? "--" },
    { label: "Body Part", value: study.body_part_examined ?? "--" },
    { label: "Description", value: study.study_description ?? "--" },
    { label: "Study Date", value: study.study_date ?? "--" },
    { label: "Series Count", value: study.num_series },
    { label: "Image Count", value: study.num_images },
    { label: "Person ID", value: study.person_id ?? "--" },
    { label: "Status", value: study.status },
  ];

  return (
    <div className="space-y-6">
      {/* Back nav */}
      <Link
        to="/imaging"
        className="inline-flex items-center gap-1.5 text-sm text-[#7A8298] hover:text-[#E8ECF4] transition-colors"
      >
        <ArrowLeft size={14} />
        Back to Imaging
      </Link>

      {/* Header */}
      <div className="flex items-start justify-between">
        <div className="flex items-center gap-3">
          <div className="flex items-center justify-center w-9 h-9 rounded-md bg-[#60A5FA]/12 flex-shrink-0">
            <ScanLine size={18} style={{ color: "#60A5FA" }} />
          </div>
          <div>
            <h1 className="text-xl font-bold text-[#E8ECF4]">DICOM Study</h1>
            <p className="text-sm text-[#4A5068] font-mono mt-0.5 truncate max-w-xl">
              {study.study_instance_uid}
            </p>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => indexSeries.mutate(studyId)}
            disabled={indexSeries.isPending}
            className="inline-flex items-center gap-2 rounded-lg border border-[#222256] bg-[#10102A] px-4 py-2 text-sm font-medium text-[#7A8298] hover:text-[#B4BAC8] hover:border-[#2A2A60] disabled:opacity-50 transition-colors"
          >
            {indexSeries.isPending ? (
              <Loader2 size={14} className="animate-spin" />
            ) : (
              <Layers size={14} />
            )}
            Index Series
          </button>
          {study.person_id && (
            <button
              type="button"
              onClick={() => extractNlp.mutate(studyId)}
              disabled={extractNlp.isPending}
              className="inline-flex items-center gap-2 rounded-lg bg-[#2DD4BF] px-4 py-2 text-sm font-medium text-[#0A0A18] hover:bg-[#26B8A5] disabled:opacity-50 transition-colors"
            >
              {extractNlp.isPending ? (
                <Loader2 size={14} className="animate-spin" />
              ) : (
                <Brain size={14} />
              )}
              Extract NLP
            </button>
          )}
        </div>
      </div>

      {indexSeries.isSuccess && (
        <div className="rounded-lg border border-[#2DD4BF]/30 bg-[#2DD4BF]/10 px-4 py-3 text-sm text-[#2DD4BF]">
          Indexed {(indexSeries.data as { indexed: number }).indexed} series.
        </div>
      )}
      {extractNlp.isSuccess && (
        <div className="rounded-lg border border-[#2DD4BF]/30 bg-[#2DD4BF]/10 px-4 py-3 text-sm text-[#2DD4BF]">
          Extracted {(extractNlp.data as { extracted: number }).extracted} findings,{" "}
          {(extractNlp.data as { mapped: number }).mapped} OMOP-mapped.
        </div>
      )}

      {/* Tab bar */}
      <div className="flex gap-0 border-b border-[#1C1C48]">
        {STUDY_TABS.map((tab) => (
          <button
            key={tab.id}
            type="button"
            onClick={() => setActiveTab(tab.id)}
            className={`inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px ${
              activeTab === tab.id
                ? "border-[#2DD4BF] text-[#2DD4BF]"
                : "border-transparent text-[#4A5068] hover:text-[#7A8298]"
            }`}
          >
            <tab.icon size={14} />
            {tab.label}
          </button>
        ))}
      </div>

      {/* Measurements tab */}
      {activeTab === "measurements" && (
        <MeasurementPanel studyId={studyId} personId={study.person_id} />
      )}

      {/* Viewer tab */}
      {activeTab === "viewer" && study.status === "indexed" && (
        <OhifViewer
          studyInstanceUid={study.study_instance_uid}
          studyId={studyId}
          personId={study.person_id}
        />
      )}
      {activeTab === "viewer" && study.status !== "indexed" && (
        <div className="rounded-lg border border-[#F0607A]/30 bg-[#F0607A]/10 px-4 py-8 text-center">
          <p className="text-sm text-[#F0607A]">
            This study has no DICOM data in the PACS server (status: {study.status}).
          </p>
          <p className="text-xs text-[#7A8298] mt-1">
            Only studies indexed from Orthanc can be viewed in OHIF.
          </p>
        </div>
      )}

      {activeTab !== "viewer" && (
      <>
      {/* Metadata */}
      <div className="rounded-lg border border-[#1C1C48] bg-[#10102A] p-4">
        <h2 className="text-sm font-semibold text-[#E8ECF4] mb-4">Study Metadata</h2>
        <dl className="grid grid-cols-2 gap-x-8 gap-y-2.5">
          {fields.map(({ label, value }) => (
            <div key={label} className="flex gap-3">
              <dt className="text-[#4A5068] text-xs w-36 shrink-0 pt-0.5">{label}</dt>
              <dd className="text-xs font-medium text-[#B4BAC8] break-all">{String(value)}</dd>
            </div>
          ))}
        </dl>
      </div>

      {/* Series */}
      {study.series && study.series.length > 0 && (
        <div className="rounded-lg border border-[#1C1C48] bg-[#10102A]">
          <div className="px-4 py-3 border-b border-[#1C1C48] flex items-center gap-2">
            <Layers size={14} className="text-[#60A5FA]" />
            <h2 className="text-sm font-semibold text-[#E8ECF4]">
              Series ({study.series.length})
            </h2>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-[#1C1C48]">
                  {["#", "Modality", "Description", "Images", "Slice Thickness", "Manufacturer"].map(
                    (h) => (
                      <th
                        key={h}
                        className="px-4 py-2.5 text-left text-[10px] font-medium text-[#4A5068] uppercase tracking-wider"
                      >
                        {h}
                      </th>
                    )
                  )}
                </tr>
              </thead>
              <tbody className="divide-y divide-[#16163A]">
                {study.series.map((s: ImagingSeries) => (
                  <tr key={s.id} className="hover:bg-[#16163A] transition-colors">
                    <td className="px-4 py-3 text-[#7A8298] text-xs">{s.series_number ?? "--"}</td>
                    <td className="px-4 py-3 text-xs font-semibold text-[#B4BAC8]">
                      {s.modality ?? "--"}
                    </td>
                    <td className="px-4 py-3 text-[#7A8298] text-xs">{s.series_description ?? "--"}</td>
                    <td className="px-4 py-3 text-[#B4BAC8] text-xs">{s.num_images}</td>
                    <td className="px-4 py-3 text-[#7A8298] text-xs">
                      {s.slice_thickness_mm !== null ? `${s.slice_thickness_mm} mm` : "--"}
                    </td>
                    <td className="px-4 py-3 text-[#4A5068] text-xs">
                      {[s.manufacturer, s.manufacturer_model].filter(Boolean).join(" · ") || "--"}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Features */}
      {features && features.total > 0 && (
        <div className="rounded-lg border border-[#1C1C48] bg-[#10102A]">
          <div className="px-4 py-3 border-b border-[#1C1C48] flex items-center gap-2">
            <Brain size={14} className="text-[#A78BFA]" />
            <h2 className="text-sm font-semibold text-[#E8ECF4]">AI Features ({features.total})</h2>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-[#1C1C48]">
                  {["Finding", "Type", "Body Site", "Value", "Confidence", "OMOP"].map((h) => (
                    <th
                      key={h}
                      className="px-4 py-2.5 text-left text-[10px] font-medium text-[#4A5068] uppercase tracking-wider"
                    >
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-[#16163A]">
                {features.data.map((f: ImagingFeature) => (
                  <tr key={f.id} className="hover:bg-[#16163A] transition-colors">
                    <td className="px-4 py-3 font-medium text-[#E8ECF4] text-xs">{f.feature_name}</td>
                    <td className="px-4 py-3">
                      <span className="inline-block rounded-full px-2 py-0.5 text-[10px] font-medium bg-[#1C1C48] text-[#7A8298]">
                        {f.feature_type}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-[#7A8298] text-xs">{f.body_site ?? "--"}</td>
                    <td className="px-4 py-3 text-[#B4BAC8] text-xs">
                      {f.value_as_number !== null
                        ? `${f.value_as_number} ${f.unit_source_value ?? ""}`
                        : f.value_as_string ?? "--"}
                    </td>
                    <td className="px-4 py-3 text-xs text-[#7A8298]">
                      {f.confidence !== null ? `${Math.round(f.confidence * 100)}%` : "--"}
                    </td>
                    <td className="px-4 py-3 text-xs font-mono text-[#4A5068]">
                      {f.value_concept_id ?? "--"}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
      </>
      )}
    </div>
  );
}

import { useState } from "react";
import { Link } from "react-router-dom";
import {
  ScanLine,
  Layers,
  BarChart3,
  Filter,
  RefreshCw,
  Brain,
  ChevronRight,
  Loader2,
  Users,
  Trash2,
  FolderInput,
  Activity,
} from "lucide-react";
import {
  useImagingStats,
  useImagingStudies,
  useImagingFeatures,
  useImagingCriteria,
  useDeleteImagingCriterion,
  useIndexFromDicomweb,
  usePopulationAnalytics,
  useImportLocalDicom,
} from "../hooks/useImaging";
import type { ImagingStudy, ImagingFeature } from "../types";
import PatientImagingTimeline from "../components/PatientImagingTimeline";

const TABS = [
  { id: "studies", label: "Studies", icon: ScanLine },
  { id: "features", label: "AI Features", icon: Brain },
  { id: "criteria", label: "Imaging Criteria", icon: Filter },
  { id: "timeline", label: "Patient Timeline", icon: Activity },
  { id: "analytics", label: "Population Analytics", icon: BarChart3 },
] as const;

type Tab = (typeof TABS)[number]["id"];

const MODALITY_COLORS: Record<string, string> = {
  CT: "bg-blue-400/15 text-blue-400",
  MR: "bg-[#A78BFA]/15 text-[#A78BFA]",
  PT: "bg-orange-400/15 text-orange-400",
  US: "bg-[#2DD4BF]/15 text-[#2DD4BF]",
  CR: "bg-[#7A8298]/15 text-[#7A8298]",
  DX: "bg-[#7A8298]/15 text-[#7A8298]",
  MG: "bg-pink-400/15 text-pink-400",
};

function ModalityBadge({ modality }: { modality: string | null }) {
  if (!modality) return <span className="text-[#4A5068] text-sm">--</span>;
  const cls = MODALITY_COLORS[modality] ?? "bg-[#1C1C48] text-[#7A8298]";
  return (
    <span className={`inline-block rounded px-2 py-0.5 text-[10px] font-semibold ${cls}`}>
      {modality}
    </span>
  );
}

function StudyStatusBadge({ status }: { status: string }) {
  const cls =
    status === "processed"
      ? "bg-[#2DD4BF]/15 text-[#2DD4BF]"
      : status === "error"
        ? "bg-[#F0607A]/15 text-[#F0607A]"
        : "bg-[#1C1C48] text-[#7A8298]";
  return (
    <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-medium ${cls}`}>
      {status}
    </span>
  );
}

function StatsBar() {
  const { data: stats, isLoading } = useImagingStats();

  const items = [
    { label: "Total Studies", value: stats?.total_studies ?? 0, icon: ScanLine, color: "#60A5FA" },
    { label: "AI Features", value: stats?.total_features ?? 0, icon: Brain, color: "#A78BFA" },
    {
      label: "Persons with Imaging",
      value: stats?.persons_with_imaging ?? 0,
      icon: Users,
      color: "#2DD4BF",
    },
  ];

  return (
    <div className="grid grid-cols-3 gap-3">
      {items.map((item) => (
        <div
          key={item.label}
          className="flex items-center gap-3 rounded-lg border border-[#1C1C48] bg-[#10102A] px-4 py-3"
        >
          <div
            className="flex items-center justify-center w-8 h-8 rounded-md flex-shrink-0"
            style={{ backgroundColor: `${item.color}18` }}
          >
            <item.icon size={16} style={{ color: item.color }} />
          </div>
          <div>
            <p
              className="text-lg font-semibold font-['IBM_Plex_Mono',monospace]"
              style={{ color: item.color }}
            >
              {isLoading ? "--" : item.value?.toLocaleString() ?? "0"}
            </p>
            <p className="text-[10px] text-[#4A5068] uppercase tracking-wider">{item.label}</p>
          </div>
        </div>
      ))}
    </div>
  );
}

function LocalImportPanel() {
  const [dir, setDir] = useState("dicom_samples");
  const importMutation = useImportLocalDicom();

  const handleImport = () => {
    importMutation.mutate({ dir });
  };

  return (
    <div className="rounded-lg border border-[#1C1C48] bg-[#10102A] p-4 space-y-3">
      <div className="flex items-center gap-2 mb-1">
        <FolderInput size={14} className="text-[#60A5FA]" />
        <h3 className="text-sm font-semibold text-[#E8ECF4]">Import Local DICOM Files</h3>
        <span className="ml-auto text-[10px] text-[#4A5068] uppercase tracking-wider">Server-side scan</span>
      </div>
      <div className="flex items-end gap-3 flex-wrap">
        <div className="flex-1 min-w-[200px]">
          <label className="block text-xs text-[#7A8298] mb-1.5">Directory (relative to repo root)</label>
          <input
            className="w-full rounded-lg bg-[#0A0A18] border border-[#1C1C48] px-3 py-2 text-sm text-[#E8ECF4] placeholder:text-[#4A5068] focus:outline-none focus:border-[#2DD4BF] focus:ring-1 focus:ring-[#2DD4BF]/40 transition-colors font-mono"
            value={dir}
            onChange={(e) => setDir(e.target.value)}
            placeholder="dicom_samples"
          />
        </div>
        <button
          type="button"
          onClick={handleImport}
          disabled={importMutation.isPending}
          className="inline-flex items-center gap-2 rounded-lg bg-[#2DD4BF] px-4 py-2 text-sm font-medium text-[#0A0A18] hover:bg-[#26B8A5] disabled:opacity-50 transition-colors"
        >
          {importMutation.isPending ? (
            <Loader2 size={14} className="animate-spin" />
          ) : (
            <FolderInput size={14} />
          )}
          {importMutation.isPending ? "Scanning..." : "Import"}
        </button>
      </div>
      {importMutation.isSuccess && (
        <div className="rounded-lg border border-[#2DD4BF]/30 bg-[#2DD4BF]/10 px-4 py-3 text-sm text-[#2DD4BF]">
          Import complete -- {(importMutation.data as Record<string, number>)?.studies_imported ?? 0} studies,{" "}
          {(importMutation.data as Record<string, number>)?.series_imported ?? 0} series,{" "}
          {(importMutation.data as Record<string, number>)?.instances_imported ?? 0} instances
        </div>
      )}
      {importMutation.isError && (
        <div className="rounded-lg border border-[#F0607A]/30 bg-[#F0607A]/10 px-4 py-3 text-sm text-[#F0607A]">
          Import failed: {importMutation.error instanceof Error ? importMutation.error.message : "Unknown error"}
        </div>
      )}
      <p className="text-[10px] text-[#4A5068]">
        Scans DICOM files on the server at the specified path. Files must be accessible from the Aurora backend container.
      </p>
    </div>
  );
}

function StudiesTab() {
  const [modality, setModality] = useState("");
  const { data, isLoading } = useImagingStudies({
    modality: modality || undefined,
    per_page: 25,
  });
  const indexMutation = useIndexFromDicomweb();

  return (
    <div className="space-y-4">
      {/* Local DICOM Import */}
      <LocalImportPanel />

      {/* DICOMweb filter + index */}
      <div className="flex items-end gap-3">
        <div>
          <label className="block text-xs text-[#7A8298] mb-1.5">Modality</label>
          <input
            className="w-40 rounded-lg bg-[#10102A] border border-[#1C1C48] px-3 py-2 text-sm text-[#E8ECF4] placeholder:text-[#4A5068] focus:outline-none focus:border-[#2DD4BF] focus:ring-1 focus:ring-[#2DD4BF]/40 transition-colors"
            placeholder="CT, MR..."
            value={modality}
            onChange={(e) => setModality(e.target.value)}
          />
        </div>
        <button
          type="button"
          onClick={() =>
            indexMutation.mutate({
              modality: modality || undefined,
            })
          }
          disabled={indexMutation.isPending}
          className="inline-flex items-center gap-2 rounded-lg border border-[#222256] bg-[#10102A] px-4 py-2 text-sm font-medium text-[#7A8298] hover:text-[#B4BAC8] hover:border-[#2A2A60] disabled:opacity-50 transition-colors"
        >
          <RefreshCw size={14} className={indexMutation.isPending ? "animate-spin" : ""} />
          Index from DICOMweb
        </button>
      </div>

      {indexMutation.isSuccess && (
        <div className="rounded-lg border border-[#2DD4BF]/30 bg-[#2DD4BF]/10 px-4 py-3 text-sm text-[#2DD4BF]">
          Indexed {(indexMutation.data as { indexed: number }).indexed} new /{" "}
          updated {(indexMutation.data as { updated: number }).updated} studies
        </div>
      )}

      <div className="rounded-lg border border-[#1C1C48] bg-[#10102A]">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-[#1C1C48]">
                {["Study Date", "Modality", "Body Part", "Description", "Series", "Images", "Person", "Status", ""].map(
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
              {isLoading && (
                <tr>
                  <td colSpan={9} className="text-center py-10">
                    <Loader2 size={20} className="animate-spin text-[#2DD4BF] mx-auto" />
                  </td>
                </tr>
              )}
              {!isLoading && !data?.data?.length && (
                <tr>
                  <td colSpan={9} className="text-center py-10 text-sm text-[#4A5068]">
                    No studies indexed. Use "Import Local DICOM Files" above or click "Index from DICOMweb".
                  </td>
                </tr>
              )}
              {data?.data?.map((study: ImagingStudy) => (
                <tr key={study.id} className="hover:bg-[#16163A] transition-colors">
                  <td className="px-4 py-3 text-[#B4BAC8] text-xs">{study.study_date ?? "--"}</td>
                  <td className="px-4 py-3">
                    <ModalityBadge modality={study.modality} />
                  </td>
                  <td className="px-4 py-3 text-[#7A8298] text-xs">
                    {study.body_part_examined ?? "--"}
                  </td>
                  <td className="px-4 py-3 text-[#7A8298] text-xs max-w-xs truncate">
                    {study.study_description ?? "--"}
                  </td>
                  <td className="px-4 py-3 text-[#B4BAC8] text-xs text-center">
                    {study.num_series}
                  </td>
                  <td className="px-4 py-3 text-[#B4BAC8] text-xs text-center">
                    {study.num_images}
                  </td>
                  <td className="px-4 py-3 text-[#7A8298] text-xs">{study.person_id ?? "--"}</td>
                  <td className="px-4 py-3">
                    <StudyStatusBadge status={study.status} />
                  </td>
                  <td className="px-4 py-3">
                    <Link
                      to={`/imaging/studies/${study.id}`}
                      className="inline-flex items-center gap-1 text-xs text-[#2DD4BF] hover:text-[#26B8A5] transition-colors"
                    >
                      Details <ChevronRight size={12} />
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {data && (
          <div className="px-4 py-2.5 text-xs text-[#4A5068] border-t border-[#1C1C48]">
            {data.total?.toLocaleString() ?? "0"} total studies · page {data.current_page} of{" "}
            {data.last_page}
          </div>
        )}
      </div>
    </div>
  );
}

function FeaturesTab() {
  const [featureType, setFeatureType] = useState("");
  const { data, isLoading } = useImagingFeatures({
    feature_type: featureType || undefined,
    per_page: 50,
  });

  const ConfidenceBar = ({ v }: { v: number | null }) => {
    if (v === null) return <span className="text-[#4A5068]">--</span>;
    const pct = Math.round(v * 100);
    const barColor =
      pct >= 80 ? "#2DD4BF" : pct >= 60 ? "#F59E0B" : "#F0607A";
    return (
      <div className="flex items-center gap-2">
        <div className="flex-1 h-1.5 bg-[#0A0A18] rounded-full overflow-hidden">
          <div className="h-full rounded-full" style={{ width: `${pct}%`, backgroundColor: barColor }} />
        </div>
        <span className="text-xs text-[#7A8298] w-8 text-right">{pct}%</span>
      </div>
    );
  };

  return (
    <div className="space-y-4">
      <div>
        <label className="block text-xs text-[#7A8298] mb-1.5">Feature Type</label>
        <select
          className="w-52 rounded-lg bg-[#10102A] border border-[#1C1C48] px-3 py-2 text-sm text-[#E8ECF4] focus:outline-none focus:border-[#2DD4BF] transition-colors"
          value={featureType}
          onChange={(e) => setFeatureType(e.target.value)}
        >
          <option value="">All feature types</option>
          <option value="nlp_finding">NLP Finding</option>
          <option value="ai_classification">AI Classification</option>
          <option value="radiomic">Radiomic</option>
          <option value="manual">Manual</option>
        </select>
      </div>

      <div className="rounded-lg border border-[#1C1C48] bg-[#10102A]">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-[#1C1C48]">
                {["Feature", "Type", "Body Site", "Value", "Algorithm", "Confidence", "OMOP Concept"].map(
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
              {isLoading && (
                <tr>
                  <td colSpan={7} className="text-center py-10">
                    <Loader2 size={20} className="animate-spin text-[#2DD4BF] mx-auto" />
                  </td>
                </tr>
              )}
              {!isLoading && !data?.data?.length && (
                <tr>
                  <td colSpan={7} className="text-center py-10 text-sm text-[#4A5068]">
                    No features extracted yet. Use "Extract NLP" on a study to populate.
                  </td>
                </tr>
              )}
              {data?.data?.map((f: ImagingFeature) => (
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
                  <td className="px-4 py-3 text-[#4A5068] text-xs">{f.algorithm_name ?? "--"}</td>
                  <td className="px-4 py-3" style={{ width: 140 }}>
                    <ConfidenceBar v={f.confidence} />
                  </td>
                  <td className="px-4 py-3 text-xs font-mono text-[#4A5068]">
                    {f.value_concept_id ?? "--"}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {data && (
          <div className="px-4 py-2.5 text-xs text-[#4A5068] border-t border-[#1C1C48]">
            {data.total?.toLocaleString() ?? "0"} total features
          </div>
        )}
      </div>
    </div>
  );
}

function CriteriaTab() {
  const { data: criteria, isLoading } = useImagingCriteria();
  const deleteMutation = useDeleteImagingCriterion();

  const TYPE_LABELS: Record<string, string> = {
    modality: "Modality",
    anatomy: "Anatomy",
    quantitative: "Quantitative",
    ai_classification: "AI Classification",
    dose: "Radiation Dose",
  };

  return (
    <div className="space-y-4">
      <p className="text-sm text-[#7A8298]">
        Saved imaging cohort criteria. Use these in the Cohort Builder to select patients based on
        imaging characteristics.
      </p>

      {isLoading && (
        <div className="flex items-center gap-2 text-[#4A5068]">
          <Loader2 size={14} className="animate-spin" />
          <span className="text-sm">Loading...</span>
        </div>
      )}

      {!isLoading && !criteria?.length && (
        <div className="rounded-lg border border-[#1C1C48] bg-[#10102A] p-10 text-center text-sm text-[#4A5068]">
          No imaging criteria saved yet.
        </div>
      )}

      <div className="space-y-2">
        {criteria?.map((c) => (
          <div
            key={c.id}
            className="rounded-lg border border-[#1C1C48] bg-[#10102A] p-4 flex items-start gap-4"
          >
            <div className="flex-1 min-w-0">
              <div className="flex items-center gap-2 mb-1 flex-wrap">
                <span className="font-medium text-[#E8ECF4] text-sm">{c.name}</span>
                <span className="inline-block rounded-full px-2 py-0.5 text-[10px] font-medium bg-[#1C1C48] text-[#7A8298]">
                  {TYPE_LABELS[c.criteria_type] ?? c.criteria_type}
                </span>
                {c.is_shared && (
                  <span className="inline-block rounded-full px-2 py-0.5 text-[10px] font-medium bg-[#2DD4BF]/15 text-[#2DD4BF]">
                    Shared
                  </span>
                )}
              </div>
              {c.description && (
                <p className="text-sm text-[#7A8298] mb-2">{c.description}</p>
              )}
              <pre className="text-xs text-[#4A5068] mt-2 bg-[#0A0A18] border border-[#1C1C48] rounded-lg p-2 overflow-auto">
                {JSON.stringify(c.criteria_definition, null, 2)}
              </pre>
            </div>
            <button
              type="button"
              onClick={() => deleteMutation.mutate(c.id)}
              disabled={deleteMutation.isPending}
              className="p-1.5 rounded text-[#4A5068] hover:text-[#F0607A] hover:bg-[#F0607A]/10 disabled:opacity-40 transition-colors flex-shrink-0"
              title="Delete criterion"
            >
              <Trash2 size={13} />
            </button>
          </div>
        ))}
      </div>
    </div>
  );
}

function AnalyticsTab() {
  const { data, isLoading } = usePopulationAnalytics();

  const maxModalityN = data ? Math.max(...data.by_modality.map((m) => m.n), 1) : 1;
  const maxBodyN = data ? Math.max(...data.by_body_part.map((b) => b.n), 1) : 1;

  return (
    <div className="space-y-6">
      {isLoading && (
        <div className="flex items-center gap-2 text-[#4A5068]">
          <Loader2 size={14} className="animate-spin text-[#2DD4BF]" />
          <span className="text-sm">Loading analytics...</span>
        </div>
      )}

      {!isLoading && !data && (
        <div className="rounded-lg border border-[#1C1C48] bg-[#10102A] p-10 text-center text-sm text-[#4A5068]">
          No imaging analytics data available yet.
        </div>
      )}

      {data && (
        <div className="grid grid-cols-2 gap-4">
          {/* By Modality */}
          <div className="rounded-lg border border-[#1C1C48] bg-[#10102A] p-4">
            <h3 className="text-sm font-semibold text-[#E8ECF4] mb-4 flex items-center gap-2">
              <ScanLine size={14} className="text-[#60A5FA]" />
              Studies by Modality
            </h3>
            <div className="space-y-2.5">
              {data.by_modality.map((row) => (
                <div key={row.modality}>
                  <div className="flex justify-between text-xs mb-1">
                    <span className="font-mono font-semibold text-[#B4BAC8]">{row.modality}</span>
                    <span className="text-[#4A5068]">
                      {row.n?.toLocaleString() ?? "0"} ({row.unique_persons?.toLocaleString() ?? "0"} persons)
                    </span>
                  </div>
                  <div className="h-1.5 bg-[#0A0A18] rounded-full overflow-hidden">
                    <div
                      className="h-full rounded-full"
                      style={{
                        width: `${(row.n / maxModalityN) * 100}%`,
                        backgroundColor: "#2DD4BF",
                      }}
                    />
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* By Body Part */}
          <div className="rounded-lg border border-[#1C1C48] bg-[#10102A] p-4">
            <h3 className="text-sm font-semibold text-[#E8ECF4] mb-4 flex items-center gap-2">
              <Layers size={14} className="text-[#60A5FA]" />
              Studies by Body Part
            </h3>
            <div className="space-y-2.5">
              {data.by_body_part.map((row) => (
                <div key={row.body_part_examined}>
                  <div className="flex justify-between text-xs mb-1">
                    <span className="text-[#B4BAC8]">{row.body_part_examined}</span>
                    <span className="text-[#4A5068]">{row.n?.toLocaleString() ?? "0"}</span>
                  </div>
                  <div className="h-1.5 bg-[#0A0A18] rounded-full overflow-hidden">
                    <div
                      className="h-full rounded-full"
                      style={{
                        width: `${(row.n / maxBodyN) * 100}%`,
                        backgroundColor: "#60A5FA",
                      }}
                    />
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Top Features */}
          {data.top_features.length > 0 && (
            <div className="rounded-lg border border-[#1C1C48] bg-[#10102A] p-4 col-span-2">
              <h3 className="text-sm font-semibold text-[#E8ECF4] mb-4 flex items-center gap-2">
                <Brain size={14} className="text-[#A78BFA]" />
                Top AI / NLP Features
              </h3>
              <div className="grid grid-cols-4 gap-3">
                {data.top_features.map((f, i) => (
                  <div key={i} className="rounded-lg bg-[#0A0A18] border border-[#1C1C48] p-3">
                    <p className="font-medium text-sm text-[#E8ECF4] truncate">{f.feature_name}</p>
                    <p className="text-xs text-[#4A5068] mt-0.5">{f.feature_type}</p>
                    <p
                      className="text-lg font-semibold font-['IBM_Plex_Mono',monospace] text-[#A78BFA] mt-1"
                    >
                      {f.n?.toLocaleString() ?? "0"}
                    </p>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export default function ImagingPage() {
  const [tab, setTab] = useState<Tab>("studies");

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-3">
        <div className="flex items-center justify-center w-9 h-9 rounded-md bg-[#60A5FA]/12 flex-shrink-0">
          <ScanLine size={18} style={{ color: "#60A5FA" }} />
        </div>
        <div>
          <h1 className="text-2xl font-bold text-[#E8ECF4]">Medical Imaging</h1>
          <p className="text-sm text-[#7A8298]">
            Longitudinal imaging analysis, treatment response assessment, and outcomes research
          </p>
        </div>
      </div>

      <StatsBar />

      {/* Tab bar */}
      <div className="flex gap-1 border-b border-[#1C1C48]">
        {TABS.map(({ id, label, icon: Icon }) => (
          <button
            key={id}
            type="button"
            onClick={() => setTab(id)}
            className={`flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px ${
              tab === id
                ? "border-[#2DD4BF] text-[#2DD4BF]"
                : "border-transparent text-[#4A5068] hover:text-[#7A8298]"
            }`}
          >
            <Icon size={14} />
            {label}
          </button>
        ))}
      </div>

      {tab === "timeline" && <PatientImagingTimeline />}
      {tab === "studies" && <StudiesTab />}
      {tab === "features" && <FeaturesTab />}
      {tab === "criteria" && <CriteriaTab />}
      {tab === "analytics" && <AnalyticsTab />}
    </div>
  );
}

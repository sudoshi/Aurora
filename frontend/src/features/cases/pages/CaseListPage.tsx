import { useState } from "react";
import {
  Plus, ChevronLeft, ChevronRight, Loader2,
  LayoutGrid, List, Briefcase,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { useCases, useCreateCase } from "../hooks/useCases";
import { CaseCard } from "../components/CaseCard";
import { CaseForm } from "../components/CaseForm";
import type { CaseFilters, CaseStatus, CaseSpecialty, CaseUrgency, CreateCaseData, UpdateCaseData } from "../types/case";

// ── Filter options ───────────────────────────────────────────────────────────

const STATUS_CHIPS: { value: CaseStatus | "all"; label: string }[] = [
  { value: "all", label: "All" },
  { value: "active", label: "Active" },
  { value: "in_review", label: "In Review" },
  { value: "draft", label: "Draft" },
  { value: "closed", label: "Closed" },
];

const SPECIALTY_OPTIONS: { value: CaseSpecialty | ""; label: string }[] = [
  { value: "", label: "All Specialties" },
  { value: "oncology", label: "Oncology" },
  { value: "surgical", label: "Surgical" },
  { value: "rare_disease", label: "Rare Disease" },
  { value: "complex_medical", label: "Complex Medical" },
];

const URGENCY_OPTIONS: { value: CaseUrgency | ""; label: string }[] = [
  { value: "", label: "All Urgencies" },
  { value: "routine", label: "Routine" },
  { value: "urgent", label: "Urgent" },
  { value: "emergent", label: "Emergent" },
];

// ── Main page ────────────────────────────────────────────────────────────────

export default function CaseListPage() {
  const [filters, setFilters] = useState<CaseFilters>({ page: 1, per_page: 12 });
  const [statusFilter, setStatusFilter] = useState<CaseStatus | "all">("all");
  const [specialtyFilter, setSpecialtyFilter] = useState<CaseSpecialty | "">("");
  const [urgencyFilter, setUrgencyFilter] = useState<CaseUrgency | "">("");
  const [search, setSearch] = useState("");
  const [showForm, setShowForm] = useState(false);
  const [viewMode, setViewMode] = useState<"grid" | "list">("grid");

  const activeFilters: CaseFilters = {
    ...filters,
    status: statusFilter === "all" ? undefined : statusFilter,
    specialty: specialtyFilter || undefined,
    search: search || undefined,
  };

  const { data, isLoading } = useCases(activeFilters);
  const createCase = useCreateCase();

  const cases = data?.data ?? [];

  const handleCreateCase = (formData: CreateCaseData | UpdateCaseData) => {
    createCase.mutate(formData as CreateCaseData, {
      onSuccess: () => setShowForm(false),
    });
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-[#F0EDE8]">Cases</h1>
          <p className="mt-1 text-sm text-[#8A857D]">
            <span className="font-['IBM_Plex_Mono',monospace] text-[#C5C0B8]">
              {data?.total ?? 0}
            </span>{" "}
            clinical cases
          </p>
        </div>
        <button
          type="button"
          onClick={() => setShowForm(true)}
          className="inline-flex items-center gap-2 rounded-lg bg-[#2DD4BF] px-4 py-2 text-sm font-semibold text-[#0E0E11] transition-colors hover:bg-[#25B8A5]"
        >
          <Plus size={16} />
          New Case
        </button>
      </div>

      {/* Filter bar */}
      <div className="flex flex-wrap items-center gap-3">
        {/* Status chips */}
        <div className="flex gap-1.5">
          {STATUS_CHIPS.map((chip) => (
            <button
              key={chip.value}
              type="button"
              onClick={() => {
                setStatusFilter(chip.value);
                setFilters((f) => ({ ...f, page: 1 }));
              }}
              className={cn(
                "rounded-full px-3 py-1 text-xs font-medium transition-colors",
                statusFilter === chip.value
                  ? "bg-[#2DD4BF]/10 text-[#2DD4BF] border border-[#2DD4BF]/30"
                  : "bg-[#1C1C20] text-[#8A857D] border border-[#232328] hover:border-[#3A3A42]",
              )}
            >
              {chip.label}
            </button>
          ))}
        </div>

        {/* Specialty filter */}
        <select
          value={specialtyFilter}
          onChange={(e) => {
            setSpecialtyFilter(e.target.value as CaseSpecialty | "");
            setFilters((f) => ({ ...f, page: 1 }));
          }}
          className="rounded-lg border border-[#232328] bg-[#151518] px-3 py-1.5 text-xs text-[#C5C0B8] focus:border-[#2DD4BF] focus:outline-none transition-colors"
        >
          {SPECIALTY_OPTIONS.map((o) => (
            <option key={o.value} value={o.value}>
              {o.label}
            </option>
          ))}
        </select>

        {/* Urgency filter */}
        <select
          value={urgencyFilter}
          onChange={(e) => {
            setUrgencyFilter(e.target.value as CaseUrgency | "");
            setFilters((f) => ({ ...f, page: 1 }));
          }}
          className="rounded-lg border border-[#232328] bg-[#151518] px-3 py-1.5 text-xs text-[#C5C0B8] focus:border-[#2DD4BF] focus:outline-none transition-colors"
        >
          {URGENCY_OPTIONS.map((o) => (
            <option key={o.value} value={o.value}>
              {o.label}
            </option>
          ))}
        </select>

        {/* Search */}
        <div className="relative ml-auto max-w-xs flex-1">
          <input
            type="text"
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setFilters((f) => ({ ...f, page: 1 }));
            }}
            placeholder="Search cases..."
            className="w-full rounded-lg border border-[#232328] bg-[#151518] px-3 py-1.5 text-xs text-[#F0EDE8] placeholder:text-[#5A5650] focus:border-[#2DD4BF] focus:outline-none transition-colors"
          />
        </div>

        {/* View toggle */}
        <div className="flex gap-1 rounded-lg border border-[#232328] bg-[#151518] p-0.5">
          <button
            type="button"
            onClick={() => setViewMode("grid")}
            className={cn(
              "flex h-7 w-7 items-center justify-center rounded-md transition-colors",
              viewMode === "grid"
                ? "bg-[#232328] text-[#2DD4BF]"
                : "text-[#5A5650] hover:text-[#8A857D]",
            )}
          >
            <LayoutGrid size={14} />
          </button>
          <button
            type="button"
            onClick={() => setViewMode("list")}
            className={cn(
              "flex h-7 w-7 items-center justify-center rounded-md transition-colors",
              viewMode === "list"
                ? "bg-[#232328] text-[#2DD4BF]"
                : "text-[#5A5650] hover:text-[#8A857D]",
            )}
          >
            <List size={14} />
          </button>
        </div>
      </div>

      {/* Content */}
      {isLoading ? (
        <div className="flex items-center justify-center py-16">
          <Loader2 size={24} className="animate-spin text-[#5A5650]" />
        </div>
      ) : cases.length === 0 ? (
        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed border-[#323238] bg-[#151518] py-16">
          <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#1C1C20]">
            <Briefcase size={24} className="text-[#8A857D]" />
          </div>
          <h3 className="text-lg font-semibold text-[#F0EDE8]">No cases found</h3>
          <p className="mt-2 text-sm text-[#8A857D]">
            Try adjusting your filters or create a new case.
          </p>
          <button
            type="button"
            onClick={() => setShowForm(true)}
            className="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#2DD4BF] px-4 py-2 text-sm font-semibold text-[#0E0E11] transition-colors hover:bg-[#25B8A5]"
          >
            <Plus size={16} />
            New Case
          </button>
        </div>
      ) : viewMode === "grid" ? (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {cases.map((c) => (
            <CaseCard key={c.id} clinicalCase={c} />
          ))}
        </div>
      ) : (
        <div className="space-y-2">
          {cases.map((c) => (
            <CaseCard key={c.id} clinicalCase={c} />
          ))}
        </div>
      )}

      {/* Pagination */}
      {data && data.last_page > 1 && (
        <div className="flex items-center justify-between text-sm text-[#5A5650]">
          <span>
            Page{" "}
            <span className="font-['IBM_Plex_Mono',monospace] text-[#8A857D]">
              {data.current_page}
            </span>{" "}
            of{" "}
            <span className="font-['IBM_Plex_Mono',monospace] text-[#8A857D]">
              {data.last_page}
            </span>
            {" "}&middot;{" "}
            <span className="font-['IBM_Plex_Mono',monospace] text-[#C5C0B8]">
              {data.total.toLocaleString()}
            </span>{" "}
            cases
          </span>
          <div className="flex items-center gap-2">
            <button
              type="button"
              disabled={data.current_page === 1}
              onClick={() => setFilters((f) => ({ ...f, page: (f.page ?? 1) - 1 }))}
              className="inline-flex items-center justify-center rounded-lg border border-[#2A2A30] bg-[#151518] p-1.5 text-[#8A857D] transition-colors hover:border-[#3A3A42] hover:text-[#C5C0B8] disabled:opacity-40 disabled:cursor-not-allowed"
            >
              <ChevronLeft size={16} />
            </button>
            <button
              type="button"
              disabled={data.current_page === data.last_page}
              onClick={() => setFilters((f) => ({ ...f, page: (f.page ?? 1) + 1 }))}
              className="inline-flex items-center justify-center rounded-lg border border-[#2A2A30] bg-[#151518] p-1.5 text-[#8A857D] transition-colors hover:border-[#3A3A42] hover:text-[#C5C0B8] disabled:opacity-40 disabled:cursor-not-allowed"
            >
              <ChevronRight size={16} />
            </button>
          </div>
        </div>
      )}

      {/* Create case modal */}
      {showForm && (
        <CaseForm
          isPending={createCase.isPending}
          onSubmit={handleCreateCase as (data: CreateCaseData | UpdateCaseData) => void}
          onClose={() => setShowForm(false)}
        />
      )}
    </div>
  );
}

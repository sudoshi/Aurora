import type { ClinicalEvent } from "../types/profile";

export function downloadEventsAsCsv(events: ClinicalEvent[], filename: string) {
  if (events.length === 0) return;
  const headers = ["domain", "concept_code", "concept_name", "start_date", "end_date", "value", "unit"];
  const rows = events.map((e) =>
    [e.domain, e.concept_code ?? "", `"${(e.concept_name ?? "").replace(/"/g, '""')}"`, e.start_date, e.end_date ?? "", e.value_as_string ?? e.value_numeric ?? "", e.unit ?? ""].join(","),
  );
  const csv = [headers.join(","), ...rows].join("\n");
  const blob = new Blob([csv], { type: "text/csv" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}

import { Download } from "lucide-react";
import { useExportPhenopacket } from "../hooks/useRareDisease";

export function PhenopacketExportButton({ odysseyId }: { odysseyId: number }) {
  const exportMut = useExportPhenopacket();

  function run() {
    exportMut.mutate(odysseyId, {
      onSuccess: (packet) => {
        const blob = new Blob([JSON.stringify(packet, null, 2)], { type: "application/json" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = `aurora-odyssey-${odysseyId}.json`;
        a.click();
        URL.revokeObjectURL(url);
      },
    });
  }

  return (
    <button
      type="button"
      onClick={run}
      disabled={exportMut.isPending}
      className="inline-flex items-center gap-1 rounded-md border border-[var(--surface-elevated)] px-3 py-1 text-sm text-[var(--text-secondary)] hover:bg-[var(--surface-elevated)] disabled:opacity-50"
    >
      <Download size={14} /> Export Phenopacket
    </button>
  );
}

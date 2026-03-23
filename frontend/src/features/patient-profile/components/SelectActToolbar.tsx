import { AnimatePresence, motion } from 'framer-motion';
import { MessageSquare, Flag, Download, X } from 'lucide-react';
import type { ClinicalDomain } from '../types/collaboration';

interface SelectActToolbarProps {
  selectedCount: number;
  selectedRefs: string[];
  domain: ClinicalDomain;
  patientId: number;
  onClear: () => void;
  onDiscuss: () => void;
  onFlag: () => void;
  onExport: () => void;
}

export function SelectActToolbar({
  selectedCount,
  onClear,
  onDiscuss,
  onFlag,
  onExport,
}: SelectActToolbarProps) {
  return (
    <AnimatePresence>
      {selectedCount > 0 && (
        <motion.div
          initial={{ y: 20, opacity: 0 }}
          animate={{ y: 0, opacity: 1 }}
          exit={{ y: 20, opacity: 0 }}
          transition={{ duration: 0.2 }}
          className="fixed bottom-12 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 px-4 py-2.5 rounded-lg border border-white/10 bg-neutral-900/90 backdrop-blur shadow-xl"
        >
          <span className="text-sm font-medium text-[var(--color-accent)] whitespace-nowrap">
            {selectedCount} selected:
          </span>

          <div className="flex items-center gap-1">
            <button
              onClick={onDiscuss}
              className="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm text-neutral-200 hover:bg-white/10 transition-colors"
            >
              <MessageSquare className="w-4 h-4" />
              Discuss
            </button>

            <button
              onClick={onFlag}
              className="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm text-neutral-200 hover:bg-white/10 transition-colors"
            >
              <Flag className="w-4 h-4" />
              Flag
            </button>

            <button
              onClick={onExport}
              className="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm text-neutral-200 hover:bg-white/10 transition-colors"
            >
              <Download className="w-4 h-4" />
              Export
            </button>
          </div>

          <div className="w-px h-5 bg-white/10" />

          <button
            onClick={onClear}
            className="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm text-neutral-400 hover:text-neutral-200 hover:bg-white/10 transition-colors"
          >
            <X className="w-4 h-4" />
            Clear
          </button>
        </motion.div>
      )}
    </AnimatePresence>
  );
}

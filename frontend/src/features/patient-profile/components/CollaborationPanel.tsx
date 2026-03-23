import { useState } from 'react';
import { AnimatePresence, motion } from 'framer-motion';
import { X } from 'lucide-react';
import type { ClinicalDomain } from '../types/collaboration';

type PanelTab = 'discuss' | 'tasks' | 'flags' | 'decisions';

interface CollaborationPanelProps {
  patientId: number;
  domain?: ClinicalDomain;
  isOpen: boolean;
  onClose: () => void;
  initialTab?: PanelTab;
  initialRecordRef?: string;
}

const DOMAIN_LABELS: Record<string, string> = {
  condition: 'Conditions',
  medication: 'Medications',
  procedure: 'Procedures',
  measurement: 'Labs',
  observation: 'Observations',
  genomic: 'Genomics',
  imaging: 'Imaging',
  general: 'General',
};

const TABS: { key: PanelTab; label: string }[] = [
  { key: 'discuss', label: 'Discuss' },
  { key: 'tasks', label: 'Tasks' },
  { key: 'flags', label: 'Flags' },
  { key: 'decisions', label: 'Decisions' },
];

export function CollaborationPanel({
  patientId: _patientId,
  domain,
  isOpen,
  onClose,
  initialTab = 'discuss',
  initialRecordRef: _initialRecordRef,
}: CollaborationPanelProps) {
  const [activeTab, setActiveTab] = useState<PanelTab>(initialTab);

  const headerTitle = domain
    ? `${DOMAIN_LABELS[domain] ?? domain} Context`
    : 'All Activity';

  return (
    <AnimatePresence>
      {isOpen && (
        <motion.div
          initial={{ x: 320 }}
          animate={{ x: 0 }}
          exit={{ x: 320 }}
          transition={{ type: 'spring', damping: 25, stiffness: 200 }}
          className="fixed right-0 top-0 h-full w-80 z-40 flex flex-col shadow-lg"
          style={{
            background: '#1a1a2e',
            borderLeft: '1px solid rgba(255, 255, 255, 0.08)',
          }}
        >
          {/* Header */}
          <div
            className="flex items-center justify-between px-4 py-3 shrink-0"
            style={{ borderBottom: '1px solid rgba(255, 255, 255, 0.08)' }}
          >
            <span className="text-sm font-semibold text-white truncate">
              {headerTitle}
            </span>
            <button
              onClick={onClose}
              className="ml-2 p-1 rounded text-gray-400 hover:text-white hover:bg-white/10 transition-colors shrink-0"
              aria-label="Close panel"
            >
              <X size={16} />
            </button>
          </div>

          {/* Tabs */}
          <div
            className="flex shrink-0"
            style={{ borderBottom: '1px solid rgba(255, 255, 255, 0.08)' }}
          >
            {TABS.map((tab) => (
              <button
                key={tab.key}
                onClick={() => setActiveTab(tab.key)}
                className="flex-1 py-2 text-xs font-medium transition-colors relative"
                style={{
                  color: activeTab === tab.key ? '#a78bfa' : 'rgba(255,255,255,0.5)',
                }}
              >
                {tab.label}
                {activeTab === tab.key && (
                  <span
                    className="absolute bottom-0 left-0 right-0 h-0.5 rounded-t"
                    style={{ background: '#a78bfa' }}
                  />
                )}
              </button>
            ))}
          </div>

          {/* Tab content */}
          <div className="flex-1 overflow-y-auto">
            {activeTab === 'discuss' && (
              <div className="p-3 text-sm text-gray-500">
                Discussion threads will appear here
              </div>
            )}
            {activeTab === 'tasks' && (
              <div className="p-3 text-sm text-gray-500">
                Tasks will appear here
              </div>
            )}
            {activeTab === 'flags' && (
              <div className="p-3 text-sm text-gray-500">
                Flags will appear here
              </div>
            )}
            {activeTab === 'decisions' && (
              <div className="p-3 text-sm text-gray-500">
                Decisions will appear here
              </div>
            )}
          </div>
        </motion.div>
      )}
    </AnimatePresence>
  );
}

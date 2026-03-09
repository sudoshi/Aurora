import React, { useEffect } from 'react';
import { Command } from 'cmdk';
import { useNavigate } from 'react-router-dom';
import { useUIStore } from '../../stores/uiStore';

interface NavItem {
  label: string;
  path: string;
  group: string;
}

const NAV_ITEMS: NavItem[] = [
  { label: 'Home', path: '/', group: 'Navigation' },
  { label: 'About', path: '/about', group: 'Navigation' },
  { label: 'Schedule', path: '/schedule', group: 'Calendar' },
  { label: 'Availability', path: '/availability', group: 'Calendar' },
  { label: 'Video Conferences', path: '/video-conferences', group: 'Collaboration' },
  { label: 'Screen Sharing', path: '/screen-sharing', group: 'Collaboration' },
  { label: 'Whiteboard', path: '/whiteboard', group: 'Collaboration' },
  { label: 'Active Sessions', path: '/active-sessions', group: 'Collaboration' },
  { label: 'Join Meeting', path: '/join-meeting', group: 'Collaboration' },
  { label: 'Case Discussions', path: '/case-discussions', group: 'Cases' },
  { label: 'Tasks', path: '/tasks', group: 'Cases' },
  { label: 'Files', path: '/files', group: 'Cases' },
  { label: 'Decision Support', path: '/decision-support', group: 'Clinical' },
  { label: 'Lab Results', path: '/lab-results', group: 'Clinical' },
  { label: 'Medications', path: '/medications', group: 'Clinical' },
  { label: 'Risk Assessment', path: '/risk-assessment', group: 'Clinical' },
  { label: 'Guidelines', path: '/guidelines', group: 'Clinical' },
];

const GROUPS = [...new Set(NAV_ITEMS.map((item) => item.group))];

export function CommandPalette() {
  const navigate = useNavigate();
  const { commandPaletteOpen, setCommandPaletteOpen } = useUIStore();

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        setCommandPaletteOpen(!commandPaletteOpen);
      }
    };
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [commandPaletteOpen, setCommandPaletteOpen]);

  const handleSelect = (path: string) => {
    navigate(path);
    setCommandPaletteOpen(false);
  };

  if (!commandPaletteOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-start justify-center pt-[20vh]">
      <div
        className="fixed inset-0 bg-black/50"
        onClick={() => setCommandPaletteOpen(false)}
      />
      <Command
        className="relative w-full max-w-lg rounded-xl border border-gray-700 bg-gray-900 shadow-2xl"
        onKeyDown={(e: React.KeyboardEvent) => {
          if (e.key === 'Escape') {
            setCommandPaletteOpen(false);
          }
        }}
      >
        <Command.Input
          autoFocus
          placeholder="Type a command or search..."
          className="w-full border-b border-gray-700 bg-transparent px-4 py-3 text-sm text-gray-100 placeholder-gray-500 outline-none"
        />
        <Command.List className="max-h-80 overflow-y-auto p-2">
          <Command.Empty className="px-4 py-6 text-center text-sm text-gray-500">
            No results found.
          </Command.Empty>
          {GROUPS.map((group) => (
            <Command.Group
              key={group}
              heading={group}
              className="[&_[cmdk-group-heading]]:px-2 [&_[cmdk-group-heading]]:py-1.5 [&_[cmdk-group-heading]]:text-xs [&_[cmdk-group-heading]]:font-semibold [&_[cmdk-group-heading]]:text-gray-500"
            >
              {NAV_ITEMS.filter((item) => item.group === group).map((item) => (
                <Command.Item
                  key={item.path}
                  value={item.label}
                  onSelect={() => handleSelect(item.path)}
                  className="cursor-pointer rounded-md px-3 py-2 text-sm text-gray-300 aria-selected:bg-gray-800 aria-selected:text-gray-100"
                >
                  {item.label}
                </Command.Item>
              ))}
            </Command.Group>
          ))}
        </Command.List>
      </Command>
    </div>
  );
}

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import React from 'react';
import { useUIStore } from '@/stores/uiStore';

// Mock react-router-dom
const mockNavigate = vi.fn();
vi.mock('react-router-dom', () => ({
    useNavigate: () => mockNavigate,
}));

// Mock cmdk to avoid complex DOM interactions in unit tests
vi.mock('cmdk', () => {
    const Command = ({ children, ...props }: { children: React.ReactNode } & Record<string, unknown>) => (
        <div data-testid="command-root" {...props}>{children}</div>
    );
    Command.Input = (props: Record<string, unknown>) => (
        <input data-testid="command-input" {...props} />
    );
    Command.List = ({ children }: { children: React.ReactNode }) => (
        <div data-testid="command-list">{children}</div>
    );
    Command.Empty = ({ children }: { children: React.ReactNode }) => (
        <div data-testid="command-empty">{children}</div>
    );
    Command.Group = ({
        children,
        heading,
    }: {
        children: React.ReactNode;
        heading: string;
    }) => (
        <div data-testid={`command-group-${heading}`}>{children}</div>
    );
    Command.Item = ({
        children,
        onSelect,
        value,
    }: {
        children: React.ReactNode;
        onSelect: () => void;
        value: string;
    }) => (
        <div
            data-testid={`command-item-${value}`}
            onClick={onSelect}
            role="option"
        >
            {children}
        </div>
    );
    return { Command };
});

// Import after mocks
import { CommandPalette } from '@/components/ui/CommandPalette';

describe('CommandPalette', () => {
    beforeEach(() => {
        mockNavigate.mockClear();
        useUIStore.setState({
            commandPaletteOpen: false,
        });
    });

    it('renders nothing when closed', () => {
        const { container } = render(<CommandPalette />);

        expect(container.innerHTML).toBe('');
    });

    it('renders command palette when open', () => {
        useUIStore.setState({ commandPaletteOpen: true });

        render(<CommandPalette />);

        expect(screen.getByTestId('command-root')).toBeDefined();
        expect(screen.getByTestId('command-input')).toBeDefined();
    });

    it('renders navigation groups', () => {
        useUIStore.setState({ commandPaletteOpen: true });

        render(<CommandPalette />);

        expect(screen.getByTestId('command-group-Navigation')).toBeDefined();
        expect(screen.getByTestId('command-group-Calendar')).toBeDefined();
        expect(screen.getByTestId('command-group-Collaboration')).toBeDefined();
        expect(screen.getByTestId('command-group-Cases')).toBeDefined();
        expect(screen.getByTestId('command-group-Clinical')).toBeDefined();
    });

    it('renders navigation items', () => {
        useUIStore.setState({ commandPaletteOpen: true });

        render(<CommandPalette />);

        expect(screen.getByTestId('command-item-Home')).toBeDefined();
        expect(screen.getByTestId('command-item-Schedule')).toBeDefined();
        expect(screen.getByTestId('command-item-Case Discussions')).toBeDefined();
    });

    it('navigates and closes palette when item is selected', () => {
        useUIStore.setState({ commandPaletteOpen: true });

        render(<CommandPalette />);

        fireEvent.click(screen.getByTestId('command-item-Home'));

        expect(mockNavigate).toHaveBeenCalledWith('/');
        expect(useUIStore.getState().commandPaletteOpen).toBe(false);
    });

    it('closes when backdrop is clicked', () => {
        useUIStore.setState({ commandPaletteOpen: true });

        const { container } = render(<CommandPalette />);

        // The backdrop is the div with bg-black/50
        const backdrop = container.querySelector('.bg-black\\/50');
        if (backdrop) {
            fireEvent.click(backdrop);
        }

        expect(useUIStore.getState().commandPaletteOpen).toBe(false);
    });
});

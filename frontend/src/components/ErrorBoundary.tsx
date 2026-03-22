import { Component, type ErrorInfo, type ReactNode } from "react";
import { AlertTriangle, RefreshCw } from "lucide-react";

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    console.error("[ErrorBoundary] Uncaught error:", error, info.componentStack);
  }

  private handleReload = (): void => {
    window.location.reload();
  };

  render(): ReactNode {
    if (!this.state.hasError) {
      return this.props.children;
    }

    if (this.props.fallback) {
      return this.props.fallback;
    }

    return (
      <div className="flex min-h-screen items-center justify-center bg-[#0A0A18] p-8">
        <div className="max-w-md space-y-4 text-center">
          <AlertTriangle className="mx-auto h-12 w-12 text-[#2DD4BF]" />
          <h1 className="text-xl font-semibold text-[#E8ECF4]">
            Something went wrong
          </h1>
          <p className="text-sm text-[#7A8298]">
            An unexpected error occurred. Try reloading the page.
          </p>
          {this.state.error && (
            <pre className="mt-2 max-h-32 overflow-auto rounded-lg border border-[#1C1C48] bg-[#10102A] p-3 text-left text-xs text-[#00D68F]">
              {this.state.error.message}
            </pre>
          )}
          <button
            onClick={this.handleReload}
            className="inline-flex items-center gap-2 rounded-lg bg-[#2DD4BF] px-4 py-2 text-sm font-medium text-[#0A0A18] transition-colors hover:bg-[#2DD4BF]/90"
          >
            <RefreshCw size={14} />
            Reload page
          </button>
        </div>
      </div>
    );
  }
}

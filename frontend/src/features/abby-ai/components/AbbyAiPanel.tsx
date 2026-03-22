import { useState, useRef, useEffect } from "react";
import { X, Sparkles, Loader2, AlertTriangle, ChevronRight } from "lucide-react";
import { cn } from "@/lib/utils";
import { useAnalyzeCase, useRefineAnalysis } from "../hooks/useAbbyAi";
import type { AbbyAnalyzeResponse } from "../types/abby";

interface AbbyAiPanelProps {
  isOpen: boolean;
  onClose: () => void;
  onApply: (expression: Record<string, unknown>) => void;
}

const EXAMPLE_PROMPTS = [
  "Summarize this patient's medication history",
  "Identify potential drug interactions for this case",
  "Analyze lab trends and flag abnormalities",
  "Suggest differential diagnoses based on symptoms",
];

const MAX_CHARS = 1000;

export function AbbyAiPanel({ isOpen, onClose, onApply }: AbbyAiPanelProps) {
  const [prompt, setPrompt] = useState("");
  const [result, setResult] = useState<AbbyAnalyzeResponse | null>(null);
  const [showExamples, setShowExamples] = useState(true);
  const [refineMode, setRefineMode] = useState(false);
  const [refinePrompt, setRefinePrompt] = useState("");
  const textareaRef = useRef<HTMLTextAreaElement>(null);
  const resultsRef = useRef<HTMLDivElement>(null);

  const analyzeMutation = useAnalyzeCase();
  const refineMutation = useRefineAnalysis();

  const isLoading = analyzeMutation.isPending || refineMutation.isPending;

  // Focus textarea when panel opens
  useEffect(() => {
    if (isOpen && textareaRef.current) {
      const timer = setTimeout(() => textareaRef.current?.focus(), 300);
      return () => clearTimeout(timer);
    }
  }, [isOpen]);

  // Scroll to results when they arrive
  useEffect(() => {
    if (result && resultsRef.current) {
      resultsRef.current.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  }, [result]);

  const handleAnalyze = () => {
    if (!prompt.trim() || isLoading) return;
    analyzeMutation.mutate(
      { prompt: prompt.trim() },
      {
        onSuccess: (data) => {
          setResult(data);
          setShowExamples(false);
          setRefineMode(false);
          setRefinePrompt("");
        },
      },
    );
  };

  const handleRefine = () => {
    if (!refinePrompt.trim() || !result || isLoading) return;
    refineMutation.mutate(
      { expression: result.expression, prompt: refinePrompt.trim() },
      {
        onSuccess: (data) => {
          setResult(data);
          setRefineMode(false);
          setRefinePrompt("");
        },
      },
    );
  };

  const handleExampleClick = (example: string) => {
    setPrompt(example);
    textareaRef.current?.focus();
  };

  const handleApply = () => {
    if (!result) return;
    onApply(result.expression);
  };

  const handleClose = () => {
    onClose();
  };

  const handleReset = () => {
    setPrompt("");
    setResult(null);
    setShowExamples(true);
    setRefineMode(false);
    setRefinePrompt("");
    analyzeMutation.reset();
    refineMutation.reset();
  };

  return (
    <>
      {/* Backdrop */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/40 z-40 backdrop-blur-sm"
          onClick={handleClose}
        />
      )}

      {/* Panel */}
      <div
        className={cn(
          "fixed top-0 right-0 z-50 h-full w-[480px] max-w-full",
          "transition-transform duration-300 ease-in-out",
          isOpen ? "translate-x-0" : "translate-x-full",
        )}
      >
        {/* Gradient border wrapper */}
        <div className="h-full bg-gradient-to-b from-[#2DD4BF] to-[#A78BFA] p-[1px] shadow-[0_0_40px_rgba(45,212,191,0.15)]">
          <div className="h-full bg-[#0A0A18] flex flex-col">
            {/* Header */}
            <div className="flex items-center justify-between px-5 py-4 border-b border-[#1C1C48]">
              <div className="flex items-center gap-2.5">
                <div className="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-[#2DD4BF]/20 to-[#A78BFA]/20">
                  <Sparkles size={16} className="text-[#2DD4BF]" />
                </div>
                <div>
                  <h2 className="text-sm font-semibold text-[#E8ECF4]">
                    Abby AI
                  </h2>
                  <p className="text-[10px] text-[#7A8298]">
                    Clinical Case Intelligence
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-1">
                {result && (
                  <button
                    type="button"
                    onClick={handleReset}
                    className="inline-flex items-center px-2 py-1 rounded text-[10px] font-medium text-[#7A8298] hover:text-[#E8ECF4] hover:bg-[#16163A] transition-colors"
                  >
                    New query
                  </button>
                )}
                <button
                  type="button"
                  onClick={handleClose}
                  className="inline-flex items-center justify-center w-7 h-7 rounded-md text-[#7A8298] hover:text-[#E8ECF4] hover:bg-[#16163A] transition-colors"
                >
                  <X size={16} />
                </button>
              </div>
            </div>

            {/* Content — scrollable */}
            <div className="flex-1 overflow-y-auto px-5 py-4 space-y-4">
              {/* Example prompts */}
              {showExamples && !result && (
                <div className="space-y-2">
                  <p className="text-xs font-medium text-[#7A8298] uppercase tracking-wider">
                    Try an example
                  </p>
                  <div className="space-y-1.5">
                    {EXAMPLE_PROMPTS.map((example) => (
                      <button
                        key={example}
                        type="button"
                        onClick={() => handleExampleClick(example)}
                        className={cn(
                          "w-full text-left px-3 py-2.5 rounded-lg text-sm",
                          "border border-[#1C1C48] bg-[#10102A]",
                          "text-[#B4BAC8] hover:text-[#E8ECF4]",
                          "hover:border-[#2DD4BF]/30 hover:bg-[#2DD4BF]/5",
                          "transition-all group flex items-center gap-2",
                        )}
                      >
                        <ChevronRight
                          size={12}
                          className="text-[#4A5068] group-hover:text-[#2DD4BF] transition-colors shrink-0"
                        />
                        <span>{example}</span>
                      </button>
                    ))}
                  </div>
                </div>
              )}

              {/* Loading state */}
              {isLoading && (
                <div className="flex flex-col items-center justify-center py-12 gap-4">
                  <div className="relative">
                    <div className="w-10 h-10 rounded-full border-2 border-[#2DD4BF]/20 border-t-[#2DD4BF] animate-spin" />
                    <div className="absolute inset-0 rounded-full animate-pulse bg-[#2DD4BF]/5" />
                  </div>
                  <p className="text-sm text-[#7A8298]">
                    Abby is analyzing the clinical data
                    <span className="inline-flex ml-0.5">
                      <span className="animate-[bounce_1.4s_infinite_0ms] inline-block">.</span>
                      <span className="animate-[bounce_1.4s_infinite_200ms] inline-block">.</span>
                      <span className="animate-[bounce_1.4s_infinite_400ms] inline-block">.</span>
                    </span>
                  </p>
                </div>
              )}

              {/* Error */}
              {(analyzeMutation.isError || refineMutation.isError) && (
                <div className="rounded-lg border border-[#F0607A]/30 bg-[#F0607A]/5 px-4 py-3">
                  <div className="flex items-start gap-2">
                    <AlertTriangle size={14} className="text-[#F0607A] mt-0.5 shrink-0" />
                    <div>
                      <p className="text-sm font-medium text-[#F0607A]">
                        Something went wrong
                      </p>
                      <p className="text-xs text-[#7A8298] mt-1">
                        {(analyzeMutation.error ?? refineMutation.error)?.message ??
                          "Failed to process your request. Please try again."}
                      </p>
                    </div>
                  </div>
                </div>
              )}

              {/* Results */}
              {result && !isLoading && (
                <div ref={resultsRef} className="space-y-4">
                  {/* Explanation */}
                  <div className="rounded-lg border border-[#1C1C48] bg-[#16163A] px-4 py-3">
                    <p className="text-xs font-medium text-[#7A8298] uppercase tracking-wider mb-2">
                      Analysis
                    </p>
                    <p className="text-sm text-[#B4BAC8] leading-relaxed whitespace-pre-wrap">
                      {result.explanation}
                    </p>
                  </div>

                  {/* Clinical findings */}
                  {result.clinical_findings.length > 0 && (
                    <div>
                      <p className="text-xs font-medium text-[#7A8298] uppercase tracking-wider mb-2">
                        Clinical Findings ({result.clinical_findings.length})
                      </p>
                      <div className="space-y-1.5">
                        {result.clinical_findings.map((finding) => (
                          <div
                            key={finding.name}
                            className="flex items-center justify-between px-3 py-2 rounded-lg border border-[#1C1C48] bg-[#10102A]"
                          >
                            <span className="text-sm text-[#E8ECF4] truncate">
                              {finding.name}
                            </span>
                            <span className="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium bg-[#2DD4BF]/10 text-[#2DD4BF] shrink-0 ml-2">
                              {finding.details.length} details
                            </span>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Warnings */}
                  {result.warnings.length > 0 && (
                    <div className="rounded-lg border border-[#2DD4BF]/30 bg-[#2DD4BF]/10 px-4 py-3">
                      <div className="flex items-start gap-2">
                        <AlertTriangle
                          size={14}
                          className="text-[#2DD4BF] mt-0.5 shrink-0"
                        />
                        <div className="space-y-1">
                          {result.warnings.map((warning) => (
                            <p
                              key={warning}
                              className="text-xs text-[#2DD4BF]"
                            >
                              {warning}
                            </p>
                          ))}
                        </div>
                      </div>
                    </div>
                  )}

                  {/* Action buttons */}
                  <div className="flex items-center gap-2 pt-2">
                    <button
                      type="button"
                      onClick={handleApply}
                      className={cn(
                        "flex-1 inline-flex items-center justify-center gap-1.5",
                        "rounded-lg px-4 py-2.5 text-sm font-medium",
                        "bg-[#2DD4BF] text-[#0A0A18] hover:bg-[#26B8A5]",
                        "transition-colors",
                      )}
                    >
                      Apply to Case
                    </button>
                    <button
                      type="button"
                      onClick={() => setRefineMode(!refineMode)}
                      className={cn(
                        "inline-flex items-center justify-center gap-1.5",
                        "rounded-lg px-4 py-2.5 text-sm font-medium",
                        "border border-[#1C1C48] bg-[#10102A]",
                        "text-[#7A8298] hover:text-[#E8ECF4] hover:border-[#2DD4BF]/30",
                        "transition-colors",
                        refineMode && "border-[#A78BFA]/30 text-[#A78BFA]",
                      )}
                    >
                      Refine
                    </button>
                  </div>

                  {/* Refinement input */}
                  {refineMode && (
                    <div className="space-y-2 pt-1">
                      <textarea
                        value={refinePrompt}
                        onChange={(e) => setRefinePrompt(e.target.value)}
                        placeholder="How would you like to refine this analysis?"
                        rows={3}
                        className={cn(
                          "w-full rounded-lg border border-[#A78BFA]/30 bg-[#10102A] px-3 py-2.5",
                          "text-sm text-[#E8ECF4] placeholder:text-[#4A5068]",
                          "focus:outline-none focus:border-[#A78BFA]/60",
                          "resize-none transition-colors",
                        )}
                      />
                      <button
                        type="button"
                        onClick={handleRefine}
                        disabled={!refinePrompt.trim() || isLoading}
                        className={cn(
                          "w-full inline-flex items-center justify-center gap-1.5",
                          "rounded-lg px-4 py-2 text-sm font-medium",
                          "bg-gradient-to-r from-[#2DD4BF] to-[#A78BFA]",
                          "text-[#0A0A18] hover:opacity-90",
                          "transition-opacity disabled:opacity-40 disabled:cursor-not-allowed",
                        )}
                      >
                        {refineMutation.isPending ? (
                          <Loader2 size={14} className="animate-spin" />
                        ) : (
                          <Sparkles size={14} />
                        )}
                        Refine Analysis
                      </button>
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* Input area — fixed at bottom */}
            <div className="border-t border-[#1C1C48] px-5 py-4 space-y-2">
              <textarea
                ref={textareaRef}
                value={prompt}
                onChange={(e) =>
                  setPrompt(e.target.value.slice(0, MAX_CHARS))
                }
                onKeyDown={(e) => {
                  if (e.key === "Enter" && (e.metaKey || e.ctrlKey)) {
                    e.preventDefault();
                    handleAnalyze();
                  }
                }}
                placeholder="Describe what you'd like Abby to analyze..."
                rows={3}
                disabled={isLoading}
                className={cn(
                  "w-full rounded-lg border border-[#1C1C48] bg-[#10102A] px-3 py-2.5",
                  "text-sm text-[#E8ECF4] placeholder:text-[#4A5068]",
                  "focus:outline-none focus:border-[#2DD4BF]/40",
                  "resize-none transition-colors",
                  "disabled:opacity-50",
                )}
              />
              <div className="flex items-center justify-between">
                <span className="text-[10px] text-[#4A5068]">
                  {prompt.length}/{MAX_CHARS}
                </span>
                <button
                  type="button"
                  onClick={handleAnalyze}
                  disabled={!prompt.trim() || isLoading}
                  className={cn(
                    "inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium",
                    "bg-[#2DD4BF] text-[#0A0A18] hover:bg-[#26B8A5]",
                    "transition-colors disabled:opacity-40 disabled:cursor-not-allowed",
                  )}
                >
                  {analyzeMutation.isPending ? (
                    <Loader2 size={14} className="animate-spin" />
                  ) : (
                    <Sparkles size={14} />
                  )}
                  Analyze Case
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}

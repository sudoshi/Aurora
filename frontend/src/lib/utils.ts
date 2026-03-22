/**
 * Utility to conditionally join class names.
 * Lightweight alternative to clsx/classnames.
 */
export function cn(...inputs: (string | false | null | undefined)[]): string {
  return inputs.filter(Boolean).join(" ");
}

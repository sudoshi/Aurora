import { useEffect } from "react";
import { useLocation } from "react-router-dom";
import { useAbbyStore } from "@/stores/abbyStore";

const ROUTE_CONTEXT_MAP: [RegExp, string, string][] = [
  [/^\/profiles\/\d+/, "patient_profile", "Patient Profile"],
  [/^\/profiles/, "patient_profiles", "Patient Profiles"],
  [/^\/commons/, "commons", "Commons"],
  [/^\/admin/, "administration", "Administration"],
  [/^\/settings/, "settings", "Settings"],
  [/^\/$/, "dashboard", "Dashboard"],
];

export function useAbbyContext(): { pageContext: string; pageName: string } {
  const location = useLocation();
  const setPageContext = useAbbyStore((s) => s.setPageContext);
  const pageContext = useAbbyStore((s) => s.pageContext);

  useEffect(() => {
    const path = location.pathname;
    for (const [pattern, ctx] of ROUTE_CONTEXT_MAP) {
      if (pattern.test(path)) {
        setPageContext(ctx);
        return;
      }
    }
    setPageContext("general");
  }, [location.pathname, setPageContext]);

  const match = ROUTE_CONTEXT_MAP.find(([pattern]) =>
    pattern.test(location.pathname),
  );

  return {
    pageContext,
    pageName: match?.[2] ?? "General",
  };
}

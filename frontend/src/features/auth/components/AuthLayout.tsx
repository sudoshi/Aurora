import { useEffect, useState, type ReactNode } from "react";
import "./auth-layout.css";

const IMAGES = [
  "/images/jonatan-pie-FOcMXBbe5rU-unsplash.jpg",
  "/images/jonatan-pie-r42PtGYCF7U-unsplash.jpg",
  "/images/ken-cheung-MsQDkYw-PTk-unsplash.jpg",
  "/images/matt-houghton-q_X-lyHxcdk-unsplash.jpg",
  "/images/serey-kim-vUePu7hAYAQ-unsplash.jpg",
  "/images/thomas-lipke-oIuDXlOJSiE-unsplash.jpg",
];

const FADE_INTERVAL = 8000;

const FEATURES = [
  { icon: "\u2726", label: "Live Tumor Board Sessions", desc: "Real-time presenter sync, shared annotations, structured voting" },
  { icon: "\u2726", label: "Abby AI Clinical Copilot", desc: "Case briefs, patient summaries, post-session notes" },
  { icon: "\u2726", label: "Patients Like This", desc: "Genomics-weighted similarity engine across institutions" },
  { icon: "\u2726", label: "DICOM Imaging Suite", desc: "Cornerstone3D viewer with volumetric analysis and segmentation" },
  { icon: "\u2726", label: "Decision Intelligence", desc: "Tracked recommendations, guideline concordance, outcome recording" },
  { icon: "\u2726", label: "Clinical Trial Matching", desc: "ClinicalTrials.gov integration with automatic eligibility screening" },
];

const ARCH_PILLS = [
  "OMOP CDM",
  "FHIR R4",
  "pgvector",
  "Laravel Reverb",
  "Sanctum Auth",
  "Federation",
];

const CAPABILITY_PILLS = [
  "Oncology",
  "Rare Disease",
  "Surgical Planning",
  "Genomic Review",
  "Molecular Board",
  "Complex Medical",
];

const SECURITY_PILLS = [
  "HIPAA Compliant",
  "SOC 2 Type II",
  "mTLS Federation",
  "RBAC (8 Roles)",
  "PHI Isolation",
  "Audit Logging",
  "Patient Opt-Out",
  "End-to-End Encryption",
];

interface AuthLayoutProps {
  children: ReactNode;
}

export default function AuthLayout({ children }: AuthLayoutProps) {
  const [currentIndex, setCurrentIndex] = useState(0);

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentIndex((prev) => (prev + 1) % IMAGES.length);
    }, FADE_INTERVAL);
    return () => clearInterval(timer);
  }, []);

  return (
    <div className="auth-layout">
      {/* Background slideshow */}
      <div className="auth-bg">
        {IMAGES.map((src, i) => (
          <div
            key={src}
            className={`auth-bg__slide ${i === currentIndex ? "auth-bg__slide--active" : ""}`}
            style={{ backgroundImage: `url(${src})` }}
          />
        ))}
        <div className="auth-bg__overlay" />
      </div>

      {/* Content */}
      <div className="auth-content">
        {/* Left hero */}
        <div className="auth-hero">
          <div className="auth-hero__glass">
            {/* Header */}
            <div className="auth-hero__header">
              <h1 className="auth-hero__title">Aurora</h1>
              <span className="auth-hero__version">v2.0</span>
            </div>
            <p className="auth-hero__subtitle">
              Clinical Case Intelligence Platform
            </p>
            <div className="auth-hero__divider" />
            <p className="auth-hero__description">
              Federated, AI-powered tumor board and multidisciplinary case review
              platform. Built for oncology, rare disease, surgical planning, and
              complex medical decision-making across institutions.
            </p>

            {/* Features */}
            <div className="auth-hero__features">
              {FEATURES.map((f) => (
                <div key={f.label} className="auth-hero__feature">
                  <span className="auth-hero__feature-icon">{f.icon}</span>
                  <div>
                    <span className="auth-hero__feature-label">{f.label}</span>
                    <span className="auth-hero__feature-desc">{f.desc}</span>
                  </div>
                </div>
              ))}
            </div>

            {/* Pill sections */}
            <div className="auth-hero__pills-section">
              <p className="auth-hero__pills-label">Architecture</p>
              <div className="auth-hero__pills">
                {ARCH_PILLS.map((p) => (
                  <span key={p} className="auth-hero__pill auth-hero__pill--arch">{p}</span>
                ))}
              </div>
            </div>

            <div className="auth-hero__pills-section">
              <p className="auth-hero__pills-label">Specialties</p>
              <div className="auth-hero__pills">
                {CAPABILITY_PILLS.map((p) => (
                  <span key={p} className="auth-hero__pill auth-hero__pill--cap">{p}</span>
                ))}
              </div>
            </div>

            <div className="auth-hero__pills-section">
              <p className="auth-hero__pills-label">Security &amp; Compliance</p>
              <div className="auth-hero__pills">
                {SECURITY_PILLS.map((p) => (
                  <span key={p} className="auth-hero__pill auth-hero__pill--sec">{p}</span>
                ))}
              </div>
            </div>

            {/* Footer tagline */}
            <div className="auth-hero__footer">
              <span className="auth-hero__footer-text">
                Powered by Abby AI &middot; Acumenus Data Sciences
              </span>
            </div>
          </div>
        </div>

        {/* Right form panel */}
        <div className="auth-form-wrapper">
          <div className="auth-form-panel">
            <div className="auth-form-panel__shimmer" />
            <div className="auth-form-panel__inner">
              {children}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

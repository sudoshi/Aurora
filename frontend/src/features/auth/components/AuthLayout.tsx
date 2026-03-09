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
            <h1 className="auth-hero__title">Aurora</h1>
            <p className="auth-hero__subtitle">
              Clinical Case Intelligence
            </p>
            <div className="auth-hero__divider" />
            <p className="auth-hero__description">
              Advanced tumor board platform for multidisciplinary case review,
              clinical decision support, and collaborative patient care.
            </p>
            <div className="auth-hero__features">
              <div className="auth-hero__feature">
                <span className="auth-hero__feature-icon">&#9670;</span>
                <span>Real-time case collaboration</span>
              </div>
              <div className="auth-hero__feature">
                <span className="auth-hero__feature-icon">&#9670;</span>
                <span>AI-assisted clinical insights</span>
              </div>
              <div className="auth-hero__feature">
                <span className="auth-hero__feature-icon">&#9670;</span>
                <span>OMOP-standardized data</span>
              </div>
              <div className="auth-hero__feature">
                <span className="auth-hero__feature-icon">&#9670;</span>
                <span>Integrated DICOM imaging</span>
              </div>
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

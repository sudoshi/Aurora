import { useEffect, useState, type FormEvent } from "react";
import { useNavigate, Link } from "react-router-dom";
import { KeyRound } from "lucide-react";
import { authApi } from "@/features/auth/api/authApi";
import type { AuthProviderDiscovery } from "@/features/auth/api/authApi";
import { useAuthStore } from "@/stores/authStore";
import { AxiosError } from "axios";
import AuthLayout from "@/features/auth/components/AuthLayout";

export default function LoginPage() {
  const navigate = useNavigate();
  const setAuth = useAuthStore((s) => s.setAuth);

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [providers, setProviders] = useState<AuthProviderDiscovery | null>(null);

  useEffect(() => {
    let active = true;

    authApi.getProviders()
      .then(({ data }) => {
        if (active) {
          setProviders(data);
        }
      })
      .catch(() => {
        if (active) {
          setProviders(null);
        }
      });

    return () => {
      active = false;
    };
  }, []);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      const { data } = await authApi.login(email, password);
      const token = data.token ?? data.access_token;
      if (!token) {
        throw new Error("Missing authentication token.");
      }
      setAuth(token, data.user);
      navigate("/");
    } catch (err) {
      if (err instanceof AxiosError) {
        setError(
          err.response?.data?.message ?? "Invalid credentials. Please try again.",
        );
      } else {
        setError("An unexpected error occurred.");
      }
    } finally {
      setLoading(false);
    }
  };

  const handleSsoLogin = () => {
    if (providers?.oidc_redirect_path) {
      window.location.assign(providers.oidc_redirect_path);
    }
  };

  return (
    <AuthLayout>
      <div className="flex justify-center mb-6">
        <img src="/image/aurora.svg" alt="Aurora" className="w-32 h-32" />
      </div>
      <h2>Sign In</h2>
      <p className="auth-form-subtitle">Welcome back to Aurora</p>

      <form onSubmit={handleSubmit}>
        {error && <div className="auth-form-error">{error}</div>}

        <div className="auth-field">
          <label htmlFor="email" className="auth-label">Email</label>
          <input
            id="email"
            type="email"
            required
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            autoComplete="email"
            className="auth-input"
            placeholder="you@example.com"
          />
        </div>

        <div className="auth-field">
          <label htmlFor="password" className="auth-label">Password</label>
          <input
            id="password"
            type="password"
            required
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            autoComplete="current-password"
            className="auth-input"
          />
        </div>

        <button type="submit" disabled={loading} className="auth-submit">
          {loading ? "Signing in..." : "Sign In"}
        </button>
      </form>

      {providers?.oidc_enabled && (
        <>
          <div className="auth-divider">
            <span>or</span>
          </div>
          <button type="button" className="auth-sso-button" onClick={handleSsoLogin}>
            <KeyRound size={17} aria-hidden="true" />
            <span>{providers.oidc_label || "Sign in with Authentik"}</span>
          </button>
        </>
      )}

      <p className="auth-footer">
        Don&apos;t have an account?{" "}
        <Link to="/register">Create Account</Link>
      </p>
    </AuthLayout>
  );
}

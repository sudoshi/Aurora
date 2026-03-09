import { useState, type FormEvent } from "react";
import { useNavigate, Link } from "react-router-dom";
import { authApi } from "@/features/auth/api/authApi";
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

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      const { data } = await authApi.login(email, password);
      setAuth(data.access_token, data.user);
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

  return (
    <AuthLayout>
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

      <p className="auth-footer">
        Don&apos;t have an account?{" "}
        <Link to="/register">Create Account</Link>
      </p>
    </AuthLayout>
  );
}

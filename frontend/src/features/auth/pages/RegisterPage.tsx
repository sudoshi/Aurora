import { useState, type FormEvent } from "react";
import { Link } from "react-router-dom";
import { authApi } from "@/features/auth/api/authApi";
import { AxiosError } from "axios";
import AuthLayout from "@/features/auth/components/AuthLayout";

export default function RegisterPage() {
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [error, setError] = useState("");
  const [success, setSuccess] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError("");
    setSuccess(false);
    setLoading(true);

    try {
      await authApi.register(name, email, phone || undefined);
      setSuccess(true);
    } catch (err) {
      if (err instanceof AxiosError) {
        setError(
          err.response?.data?.message ?? "Registration failed. Please try again.",
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
      <h2>Create Account</h2>
      <p className="auth-form-subtitle">Join Aurora</p>

      {success ? (
        <>
          <div className="auth-form-success">
            Check your email for a temporary password
          </div>
          <p className="auth-footer">
            <Link to="/login">Back to Login</Link>
          </p>
        </>
      ) : (
        <form onSubmit={handleSubmit}>
          {error && <div className="auth-form-error">{error}</div>}

          <div className="auth-field">
            <label htmlFor="name" className="auth-label">Full Name</label>
            <input
              id="name"
              type="text"
              required
              value={name}
              onChange={(e) => setName(e.target.value)}
              autoComplete="name"
              className="auth-input"
              placeholder="Your full name"
            />
          </div>

          <div className="auth-field">
            <label htmlFor="reg-email" className="auth-label">Email</label>
            <input
              id="reg-email"
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
            <label htmlFor="phone" className="auth-label">
              Phone <span className="auth-optional">(optional)</span>
            </label>
            <input
              id="phone"
              type="tel"
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              autoComplete="tel"
              className="auth-input"
            />
          </div>

          <button type="submit" disabled={loading} className="auth-submit">
            {loading ? "Creating Account..." : "Create Account"}
          </button>

          <p className="auth-footer">
            Already have an account?{" "}
            <Link to="/login">Back to Login</Link>
          </p>
        </form>
      )}
    </AuthLayout>
  );
}

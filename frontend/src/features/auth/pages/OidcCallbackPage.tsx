import { useEffect, useRef, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { Loader2 } from "lucide-react";
import { AxiosError } from "axios";
import { authApi } from "@/features/auth/api/authApi";
import AuthLayout from "@/features/auth/components/AuthLayout";
import { useAuthStore } from "@/stores/authStore";

export default function OidcCallbackPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const setAuth = useAuthStore((s) => s.setAuth);
  const exchanged = useRef(false);
  const [error, setError] = useState("");

  useEffect(() => {
    const code = searchParams.get("code");

    if (!code) {
      setError("The single sign-on response did not include a valid login code.");
      return;
    }

    if (exchanged.current) {
      return;
    }

    exchanged.current = true;

    authApi.exchangeOidcCode(code)
      .then(({ data }) => {
        const token = data.token ?? data.access_token;
        if (!token) {
          throw new Error("The single sign-on response did not include an access token.");
        }

        setAuth(token, data.user);
        navigate("/", { replace: true });
      })
      .catch((err: unknown) => {
        if (err instanceof AxiosError) {
          setError(
            err.response?.data?.message
              ?? err.response?.data?.reason
              ?? "Single sign-on could not be completed.",
          );
          return;
        }

        setError(err instanceof Error ? err.message : "Single sign-on could not be completed.");
      });
  }, [navigate, searchParams, setAuth]);

  return (
    <AuthLayout>
      <div className="flex justify-center mb-6">
        <img src="/image/aurora.svg" alt="Aurora" className="w-32 h-32" />
      </div>
      <h2>Single Sign-On</h2>
      <p className="auth-form-subtitle">Completing your Authentik login</p>

      {error ? (
        <>
          <div className="auth-form-error">{error}</div>
          <p className="auth-footer">
            <Link to="/login">Return to sign in</Link>
          </p>
        </>
      ) : (
        <div className="auth-callback-status">
          <Loader2 className="auth-callback-spinner" size={22} aria-hidden="true" />
          <span>Verifying session...</span>
        </div>
      )}
    </AuthLayout>
  );
}

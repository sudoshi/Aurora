import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import axios from 'axios';
import AuthInput from './auth/AuthInput';
import AuthButton from './auth/AuthButton';
import LoginHeader from './auth/LoginHeader';

const RegisterPage = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
  });

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      await axios.post('/api/register', {
        name: formData.name,
        email: formData.email,
        phone: formData.phone || undefined,
      });

      setSuccess(true);
    } catch (err) {
      const message =
        err.response?.data?.message ||
        err.response?.data?.errors?.email?.[0] ||
        err.response?.data?.errors?.name?.[0] ||
        'Registration failed. Please try again.';
      setError(message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex flex-col justify-center bg-gradient-to-b from-gray-900 to-gray-800">
      <div className="relative z-10 flex-1 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
          <div className="bg-gray-800/50 backdrop-blur-sm py-8 px-4 shadow-xl rounded-lg border border-gray-700 sm:px-10">
            <LoginHeader />

            {success ? (
              <div className="text-center space-y-6">
                <div className="bg-green-900/30 border border-green-700 rounded-lg p-6">
                  <svg
                    className="w-12 h-12 text-green-400 mx-auto mb-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                    />
                  </svg>
                  <h3 className="text-lg font-semibold text-green-300 mb-2">
                    Check Your Inbox
                  </h3>
                  <p className="text-gray-300 text-sm">
                    If your email is not already registered, you will receive your temporary password shortly. Use it to log in for the first time.
                  </p>
                </div>
                <Link
                  to="/login"
                  className="btn btn-primary w-full"
                >
                  Go to Login
                </Link>
              </div>
            ) : (
              <div className="card bg-gray-800 border border-gray-700 w-full max-w-md mx-auto">
                <div className="card-body p-8">
                  <h2 className="text-xl font-semibold text-gray-100 text-center mb-6">
                    Create Your Account
                  </h2>
                  <form onSubmit={handleSubmit} className="space-y-6">
                    {error && (
                      <div className="alert alert-error">
                        <span>{error}</span>
                      </div>
                    )}

                    <AuthInput
                      label="Full Name"
                      type="text"
                      name="name"
                      value={formData.name}
                      onChange={handleChange}
                      placeholder="Enter your full name"
                      required
                    />

                    <AuthInput
                      label="Email"
                      type="email"
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      placeholder="Enter your email"
                      required
                    />

                    <AuthInput
                      label="Phone (optional)"
                      type="tel"
                      name="phone"
                      value={formData.phone}
                      onChange={handleChange}
                      placeholder="Enter your phone number"
                    />

                    <AuthButton
                      type="submit"
                      loading={loading}
                      variant="primary"
                    >
                      Create Account
                    </AuthButton>

                    <div className="text-center mt-4">
                      <span className="text-gray-400 text-sm">Already have an account? </span>
                      <Link
                        to="/login"
                        className="text-blue-400 hover:text-blue-300 text-sm transition-colors"
                      >
                        Sign In
                      </Link>
                    </div>
                  </form>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Decorative Elements */}
        <div className="absolute top-0 left-0 -ml-24 -mt-24 w-96 h-96 blur-3xl bg-blue-500/20 rounded-full mix-blend-multiply" />
        <div className="absolute bottom-0 right-0 -mr-24 -mb-24 w-96 h-96 blur-3xl bg-indigo-500/20 rounded-full mix-blend-multiply" />
      </div>

      {/* Version Info */}
      <div className="relative z-10 text-center pb-4">
        <span className="text-gray-500 text-xs">
          Version 1.0.0
        </span>
      </div>
    </div>
  );
};

export default RegisterPage;

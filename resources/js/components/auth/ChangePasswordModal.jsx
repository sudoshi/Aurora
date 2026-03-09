import React, { useState } from 'react';
import axios from 'axios';
import AuthInput from './AuthInput';
import AuthButton from './AuthButton';
import { useAuth } from '../../context/AuthContext';

const ChangePasswordModal = () => {
  const { login } = useAuth();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [formData, setFormData] = useState({
    current_password: '',
    new_password: '',
    confirm_password: '',
  });

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value,
    }));
  };

  const validate = () => {
    if (formData.new_password.length < 8) {
      setError('New password must be at least 8 characters.');
      return false;
    }
    if (formData.new_password !== formData.confirm_password) {
      setError('New password and confirmation do not match.');
      return false;
    }
    if (formData.current_password === formData.new_password) {
      setError('New password must be different from your current password.');
      return false;
    }
    return true;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    if (!validate()) {
      return;
    }

    setLoading(true);

    try {
      const response = await axios.post('/api/change-password', {
        current_password: formData.current_password,
        new_password: formData.new_password,
      });

      // Update auth context with new token and user data
      login(response.data.user, response.data.access_token);
    } catch (err) {
      const message =
        err.response?.data?.message ||
        'Failed to change password. Please try again.';
      setError(message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm">
      <div className="bg-gray-800 border border-gray-700 rounded-lg shadow-2xl w-full max-w-md mx-4 p-8">
        <div className="text-center mb-6">
          <div className="w-16 h-16 mx-auto mb-4 bg-yellow-500/10 rounded-full flex items-center justify-center">
            <svg
              className="w-8 h-8 text-yellow-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8V7m-6 4v2m4-2v2"
              />
            </svg>
          </div>
          <h2 className="text-xl font-bold text-gray-100">
            Password Change Required
          </h2>
          <p className="text-gray-400 text-sm mt-2">
            You must change your temporary password before continuing.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-5">
          {error && (
            <div className="alert alert-error text-sm">
              <span>{error}</span>
            </div>
          )}

          <AuthInput
            label="Current (Temporary) Password"
            type="password"
            name="current_password"
            value={formData.current_password}
            onChange={handleChange}
            placeholder="Enter your temporary password"
            required
          />

          <AuthInput
            label="New Password"
            type="password"
            name="new_password"
            value={formData.new_password}
            onChange={handleChange}
            placeholder="Minimum 8 characters"
            required
          />

          <AuthInput
            label="Confirm New Password"
            type="password"
            name="confirm_password"
            value={formData.confirm_password}
            onChange={handleChange}
            placeholder="Re-enter your new password"
            required
          />

          <AuthButton
            type="submit"
            loading={loading}
            variant="primary"
          >
            Change Password
          </AuthButton>
        </form>
      </div>
    </div>
  );
};

export default ChangePasswordModal;

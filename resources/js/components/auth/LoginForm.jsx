import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import AuthInput from './AuthInput';
import AuthButton from './AuthButton';
import { useAuth } from '../../context/AuthContext';

const LoginForm = () => {
  const navigate = useNavigate();
  const { login } = useAuth();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [formData, setFormData] = useState({
    email: '',
    password: ''
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      await login(formData.email, formData.password);
      navigate('/');
    } catch (err) {
      setError(err.message || 'Failed to log in');
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  return (
    <div className="card bg-gray-800 border border-gray-700 w-full max-w-md mx-auto">
      <div className="card-body p-8">
        <form onSubmit={handleSubmit} className="space-y-6">
          {error && (
            <div className="alert alert-error">
              <span>{error}</span>
            </div>
          )}
          
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
            label="Password"
            type="password"
            name="password"
            value={formData.password}
            onChange={handleChange}
            placeholder="Enter your password"
            required
          />

          <div className="flex items-center justify-between">
            <label className="label cursor-pointer">
              <input type="checkbox" className="checkbox checkbox-primary mr-2" />
              <span className="label-text text-gray-300">Remember me</span>
            </label>
            <button type="button" className="btn btn-link text-primary">
              Forgot password?
            </button>
          </div>

          <AuthButton
            type="submit"
            loading={loading}
            variant="primary"
          >
            Sign In
          </AuthButton>
        </form>
      </div>
    </div>
  );
};

export default LoginForm;

import React from 'react';
import LoadingSpinner from '../ui/LoadingSpinner';

const AuthButton = ({ 
  children, 
  loading = false, 
  variant = 'primary',
  className = '', 
  ...props 
}) => {
  const variants = {
    primary: 'btn-primary',
    secondary: 'btn-secondary',
    ghost: 'btn-ghost',
    outline: 'btn-outline'
  };

  return (
    <button
      className={`
        btn 
        ${variants[variant]} 
        w-full
        ${loading ? 'loading' : ''}
        ${className}
      `}
      disabled={loading}
      {...props}
    >
      {loading ? (
        <LoadingSpinner size="sm" />
      ) : children}
    </button>
  );
};

export default AuthButton;

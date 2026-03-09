import React from 'react';
import type { ButtonHTMLAttributes, ReactNode } from 'react';
import LoadingSpinner from '../ui/LoadingSpinner';

type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'outline';

interface AuthButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  children: ReactNode;
  loading?: boolean;
  variant?: ButtonVariant;
}

const variants: Record<ButtonVariant, string> = {
  primary: 'btn-primary',
  secondary: 'btn-secondary',
  ghost: 'btn-ghost',
  outline: 'btn-outline'
};

const AuthButton = ({
  children,
  loading = false,
  variant = 'primary',
  className = '',
  ...props
}: AuthButtonProps) => {
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

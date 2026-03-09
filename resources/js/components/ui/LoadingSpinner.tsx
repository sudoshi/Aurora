import React from 'react';

type SpinnerSize = 'sm' | 'md' | 'lg' | 'xl';

interface LoadingSpinnerProps {
  size?: SpinnerSize;
  className?: string;
}

const sizes: Record<SpinnerSize, string> = {
  sm: 'loading-xs',
  md: 'loading-md',
  lg: 'loading-lg',
  xl: 'loading-xl'
};

const LoadingSpinner = ({ size = 'md', className = '' }: LoadingSpinnerProps) => {
  return (
    <div className={`flex items-center justify-center ${className}`}>
      <span className={`loading loading-spinner ${sizes[size]} text-primary`} />
    </div>
  );
};

export default LoadingSpinner;

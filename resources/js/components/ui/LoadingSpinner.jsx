import React from 'react';

const LoadingSpinner = ({ size = 'md', className = '' }) => {
  const sizes = {
    sm: 'loading-xs',
    md: 'loading-md',
    lg: 'loading-lg',
    xl: 'loading-xl'
  };

  return (
    <div className={`flex items-center justify-center ${className}`}>
      <span className={`loading loading-spinner ${sizes[size]} text-primary`} />
    </div>
  );
};

export default LoadingSpinner;

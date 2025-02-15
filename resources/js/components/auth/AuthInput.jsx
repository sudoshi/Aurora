import React from 'react';

const AuthInput = ({ 
  type = 'text', 
  label, 
  error, 
  className = '', 
  ...props 
}) => {
  return (
    <div className="form-control w-full">
      {label && (
        <label className="label">
          <span className="label-text text-gray-300">{label}</span>
        </label>
      )}
      <input
        type={type}
        className={`
          input input-primary bg-gray-800 
          border-gray-700 
          text-gray-100 
          placeholder:text-gray-500
          w-full
          ${error ? 'input-error' : ''}
          ${className}
        `}
        {...props}
      />
      {error && (
        <label className="label">
          <span className="label-text-alt text-error">{error}</span>
        </label>
      )}
    </div>
  );
};

export default AuthInput;

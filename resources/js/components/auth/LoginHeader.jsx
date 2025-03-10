import React from 'react';

const LoginHeader = () => {
  return (
    <div className="text-center mb-8">
      {/* Aurora Logo */}
      <div className="w-32 h-32 mx-auto mb-4">
        <img
          src="/image/aurora.svg"
          alt="Aurora Logo"
          className="w-full h-full"
        />
      </div>

      {/* Welcome Text */}
      <h1 className="text-3xl font-bold text-gray-100 mb-2">
        Welcome to Aurora
      </h1>
      <p className="text-gray-400 text-sm max-w-sm mx-auto">
        Secure access to your healthcare collaboration platform
      </p>

      {/* Healthcare Security Badge */}
      <div className="mt-4 inline-flex items-center px-3 py-1 rounded-full bg-gray-800 text-gray-300 text-xs">
        <svg
          className="w-4 h-4 mr-2 text-green-500"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
          />
        </svg>
        HIPAA Compliant
      </div>
    </div>
  );
};

export default LoginHeader;

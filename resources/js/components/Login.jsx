import React from 'react';
import LoginHeader from './auth/LoginHeader';
import LoginForm from './auth/LoginForm';
import LoginFooter from './auth/LoginFooter';

const Login = () => {
  return (
    <div className="min-h-screen flex flex-col justify-center bg-gradient-to-b from-gray-900 to-gray-800">
      {/* Content Container */}
      <div className="relative z-10 flex-1 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
          <div className="bg-gray-800/50 backdrop-blur-sm py-8 px-4 shadow-xl rounded-lg border border-gray-700 sm:px-10">
            <LoginHeader />
            <LoginForm />
            <LoginFooter />
          </div>
        </div>

        {/* Decorative Elements */}
        <div className="absolute top-0 left-0 -ml-24 -mt-24 w-96 h-96 blur-3xl bg-blue-500/20 rounded-full mix-blend-multiply" />
        <div className="absolute bottom-0 right-0 -mr-24 -mb-24 w-96 h-96 blur-3xl bg-indigo-500/20 rounded-full mix-blend-multiply" />
      </div>

      {/* Version Info */}
      <div className="relative z-10 text-center pb-4">
        <span className="text-gray-500 text-xs">
          Version 1.0.0 • Build 2025.02.15
        </span>
      </div>

      {/* Accessibility Skip Link */}
      <a
        href="#main-content"
        className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white px-4 py-2 rounded-md"
      >
        Skip to main content
      </a>
    </div>
  );
};

export default Login;

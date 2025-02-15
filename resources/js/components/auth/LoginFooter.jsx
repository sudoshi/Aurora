import React from 'react';
import { Link } from 'react-router-dom';

const LoginFooter = () => {
  const currentYear = new Date().getFullYear();

  return (
    <div className="mt-8 space-y-4">
      {/* Help Links */}
      <div className="flex justify-center space-x-6 text-sm">
        <Link
          to="/forgot-password"
          className="text-blue-400 hover:text-blue-300 transition-colors"
        >
          Forgot Password?
        </Link>
        <Link
          to="/help"
          className="text-blue-400 hover:text-blue-300 transition-colors"
        >
          Need Help?
        </Link>
        <a
          href="mailto:support@aurora-health.com"
          className="text-blue-400 hover:text-blue-300 transition-colors"
        >
          Contact Support
        </a>
      </div>

      {/* System Requirements */}
      <div className="text-center text-gray-500 text-xs">
        <p>Recommended browsers: Chrome, Firefox, Safari, Edge</p>
        <p>Required: JavaScript enabled, Cookies enabled</p>
      </div>

      {/* Security Info */}
      <div className="flex justify-center items-center text-xs text-gray-500 space-x-2">
        <svg
          className="w-4 h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8V7m-6 4v2m4-2v2"
          />
        </svg>
        <span>256-bit SSL encrypted connection</span>
      </div>

      {/* Copyright */}
      <div className="text-center text-gray-600 text-xs">
        <p>© {currentYear} Aurora Healthcare. All rights reserved.</p>
        <div className="mt-1 space-x-3">
          <Link
            to="/privacy"
            className="hover:text-gray-400 transition-colors"
          >
            Privacy Policy
          </Link>
          <span>•</span>
          <Link
            to="/terms"
            className="hover:text-gray-400 transition-colors"
          >
            Terms of Service
          </Link>
        </div>
      </div>

      {/* Session Warning */}
      <div className="text-center text-yellow-500 text-xs">
        <p>Your session will timeout after 30 minutes of inactivity</p>
      </div>
    </div>
  );
};

export default LoginFooter;

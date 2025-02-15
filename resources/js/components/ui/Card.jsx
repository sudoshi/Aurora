import React from 'react';

export function Card({ children, className }) {
  return (
    <div className={`bg-white shadow rounded ${className}`}>
      {children}
    </div>
  );
}

export function CardHeader({ children, className }) {
  return (
    <div className={`border-b px-4 py-2 ${className}`}>
      {children}
    </div>
  );
}

export function CardTitle({ children, className }) {
  return (
    <h2 className={`text-xl font-bold ${className}`}>
      {children}
    </h2>
  );
}

export function CardDescription({ children, className }) {
  return (
    <p className={`text-gray-600 ${className}`}>
      {children}
    </p>
  );
}

export function CardContent({ children, className }) {
  return (
    <div className={`p-4 ${className}`}>
      {children}
    </div>
  );
}

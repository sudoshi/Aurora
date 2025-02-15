import React from 'react';

const Panel = ({ 
  title, 
  children, 
  className = '', 
  headerActions,
  noPadding = false 
}) => {
  return (
    <div className={`
      card bg-gray-800 
      border border-gray-700 
      ${className}
    `}>
      {title && (
        <div className="card-header border-b border-gray-700 px-6 py-4 flex justify-between items-center">
          <h2 className="card-title text-lg font-semibold text-gray-100">{title}</h2>
          {headerActions && (
            <div className="flex items-center space-x-2">
              {headerActions}
            </div>
          )}
        </div>
      )}
      <div className={`card-body ${noPadding ? 'p-0' : 'p-6'}`}>
        {children}
      </div>
    </div>
  );
};

export default Panel;

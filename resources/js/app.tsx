// Import and execute bootstrap first to configure axios
import './bootstrap';

import React from 'react';
import ReactDOM from 'react-dom/client';
import '../css/app.css';
import App from './components/App';

const rootElement = document.getElementById('root');
if (!rootElement) {
  throw new Error('Root element not found');
}

const root = ReactDOM.createRoot(rootElement);
root.render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);

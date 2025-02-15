// Import and execute bootstrap first to configure axios
import './bootstrap';

import React from 'react';
import ReactDOM from 'react-dom/client';
import '../css/app.css';
import App from './components/App.jsx';

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);

import React from 'react';
import ReactDOM from 'react-dom/client';
import Dashboard from './components/Dashboard';

const root = ReactDOM.createRoot(document.getElementById('app'));
root.render(
  <React.StrictMode>
    <h1>Chunked Upload Demo</h1>
    <Dashboard />
  </React.StrictMode>
);

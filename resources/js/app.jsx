import React from 'react';
import ReactDOM from 'react-dom/client';
import FileUploader from './components/FileUploader';

const root = ReactDOM.createRoot(document.getElementById('app'));
root.render(
  <React.StrictMode>
    <h1>Chunked Upload Demo</h1>
    <FileUploader />
  </React.StrictMode>
);

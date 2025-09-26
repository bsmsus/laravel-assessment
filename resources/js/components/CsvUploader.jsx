import React, { useState } from "react";
import axios from "axios";

axios.defaults.baseURL = "http://127.0.0.1:8080";

export default function CsvUploader({ onImport }) {
  const [uploading, setUploading] = useState(false);

  const handleSelect = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("file", file);

    try {
      setUploading(true);

      // Upload file → backend returns summary_id
      const { data } = await axios.post("/api/products/import", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });

      const summaryId = data.summary_id;

      // Let parent know we’ve started processing
      if (onImport) onImport({ liveSummary: { status: "processing" }, completed: false });

      // Begin polling with that specific ID
      pollSummary(onImport, summaryId);
    } catch (err) {
      console.error("CSV import failed:", err);
      alert("CSV import failed.");
    } finally {
      setUploading(false);
    }
  };

  const pollSummary = (onImport, summaryId) => {
    let attempts = 0;
    const interval = setInterval(async () => {
      attempts++;
      try {
        const res = await axios.get(`/api/products/import/summary/${summaryId}`);
        const summary = res.data;

        if (onImport) {
          onImport({ liveSummary: summary, completed: summary.status === "completed" });
        }

        if (summary.status === "completed") {
          clearInterval(interval);
        }
      } catch (err) {
        console.error("Polling summary failed", err);
      }

      // safety stop (~10 minutes @ 2s interval)
      if (attempts > 300) {
        clearInterval(interval);
        alert("Import polling timed out. Please refresh.");
      }
    }, 2000);
  };

  return (
    <div
      className="w-full max-w-xl mx-auto space-y-6"
      onDrop={(e) => {
        e.preventDefault();
        const file = e.dataTransfer.files[0];
        if (file) handleSelect({ target: { files: [file] } });
      }}
      onDragOver={(e) => e.preventDefault()}
    >
      <div className="border-dashed border-2 rounded-lg p-6 shadow-sm text-center cursor-pointer hover:bg-gray-50">
        <label
          htmlFor="csvUpload"
          className="block text-sm font-medium text-gray-700 mb-2"
        >
          Drag & drop to upload CSV, or click to select
        </label>

        <input
          id="csvUpload"
          type="file"
          accept=".csv"
          className="hidden"
          onChange={handleSelect}
        />

        <label
          htmlFor="csvUpload"
          className="inline-block px-4 py-2 mt-2 bg-green-100 text-green-700 text-sm font-semibold rounded cursor-pointer hover:bg-green-200"
        >
          Browse
        </label>

        {uploading && <p className="text-sm text-gray-500 mt-3">Uploading CSV...</p>}
      </div>
    </div>
  );
}

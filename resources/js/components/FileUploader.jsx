import React, { useState } from "react";
import axios from "axios";

axios.defaults.baseURL = "http://127.0.0.1:8000"; // update for Railway later

const CHUNK_SIZE = 1024 * 500; // 5 MB per chunk

export default function FileUploader() {
  const [progress, setProgress] = useState(0);
  const [uploading, setUploading] = useState(false);

  const handleFile = async (file) => {
    if (!file) return;

    try {
      setUploading(true);
      setProgress(0);

      // Step 1: INIT
      const checksum = await calculateChecksum(file); // SHA-256
      const initRes = await axios.post("/api/uploads/init", {
        filename: file.name,
        size: file.size,
        checksum,
      });
      const uploadId = initRes.data.upload_id;

      // Step 2: STATUS (resumable support)
      const statusRes = await axios.get(`/api/uploads/status/${uploadId}`);
      const alreadyUploaded = statusRes.data.uploaded_chunks.map(Number);

      // Step 3: CHUNK UPLOAD
      const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
      for (let i = 0; i < totalChunks; i++) {
        if (alreadyUploaded.includes(i)) {
          console.log(`Skipping chunk ${i}, already uploaded`);
          continue;
        }

        const start = i * CHUNK_SIZE;
        const end = Math.min(start + CHUNK_SIZE, file.size);
        const blob = file.slice(start, end);

        const formData = new FormData();
        formData.append("upload_id", uploadId);
        formData.append("chunk_index", i);
        formData.append("file", blob);

        await axios.post("/api/uploads/chunk", formData, {
          headers: { "Content-Type": "multipart/form-data" },
        });

        setProgress(Math.round(((i + 1) / totalChunks) * 100));
      }

      // Step 4: COMPLETE
      await axios.post("/api/uploads/complete", {
        upload_id: uploadId,
        total_chunks: Math.ceil(file.size / CHUNK_SIZE),
      });

      alert("Upload complete!");
    } catch (err) {
      console.error("Upload failed:", err);
      alert("Upload failed. Check console for details.");
    } finally {
      setUploading(false);
    }
  };

  const handleDrop = (event) => {
    event.preventDefault();
    const file = event.dataTransfer.files[0];
    handleFile(file);
  };

  const handleSelect = (event) => {
    const file = event.target.files[0];
    handleFile(file);
  };

  const calculateChecksum = async (file) => {
    const buffer = await file.arrayBuffer();
    const hashBuffer = await crypto.subtle.digest("SHA-256", buffer);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map((b) => b.toString(16).padStart(2, "0")).join("");
  };

  return (
    <div className="flex flex-col items-center space-y-4">
      <div
        onDrop={handleDrop}
        onDragOver={(e) => e.preventDefault()}
        className="w-full max-w-lg border-2 border-dashed border-gray-400 rounded p-6 text-center bg-gray-50"
      >
        <p className="text-gray-700">Drag & Drop a file here</p>
        <p className="text-gray-500">or</p>
        <input
          type="file"
          id="fileInput"
          className="hidden"
          onChange={handleSelect}
        />
        <button
          type="button"
          onClick={() => document.getElementById("fileInput").click()}
          className="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          Select File
        </button>
      </div>

      {uploading && (
        <div className="w-full max-w-lg">
          <progress value={progress} max="100" className="w-full"></progress>
          <p className="text-center text-sm text-gray-600 mt-2">
            {progress}% uploaded
          </p>
        </div>
      )}
    </div>
  );
}

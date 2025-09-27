import React, { useState, useCallback } from "react";
import axios from "axios";

axios.defaults.baseURL = window.location.origin;

const CHUNK_SIZE = 1024 * 100; // 0.5 MB

export default function ImageUploader() {
  const [progress, setProgress] = useState(0);
  const [uploading, setUploading] = useState(false);
  const [variants, setVariants] = useState(null);
  const [message, setMessage] = useState("");

  const handleFile = async (file) => {
    if (!file) return;
    try {
      setUploading(true);
      setProgress(0);
      setMessage("");

      const checksum = await calculateChecksum(file);
      const initRes = await axios.post("/api/uploads/init", {
        filename: file.name,
        size: file.size,
        checksum,
      });
      const uploadId = initRes.data.upload_id;

      const statusRes = await axios.get(`/api/uploads/status/${uploadId}`);
      const alreadyUploaded = statusRes.data.uploaded_chunks.map(Number);

      const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
      for (let i = 0; i < totalChunks; i++) {
        if (alreadyUploaded.includes(i)) continue;
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

      await axios.post("/api/uploads/complete", {
        upload_id: uploadId,
        total_chunks: totalChunks,
      });

      const details = await axios.get(`/api/uploads/${uploadId}/details`);
      setVariants(details.data.variants);
      setMessage("Image upload complete!");
    } catch (err) {
      console.error("Image upload failed:", err);
      setMessage("Upload failed. Check console.");
    } finally {
      setUploading(false);
    }
  };

  const handleSelect = (event) => handleFile(event.target.files[0]);

  // drag-and-drop support
  const handleDrop = useCallback((event) => {
    event.preventDefault();
    const file = event.dataTransfer.files[0];
    handleFile(file);
  }, []);

  const handleDragOver = (event) => {
    event.preventDefault();
  };

  const calculateChecksum = async (file) => {
    const buffer = await file.arrayBuffer();
    const hashBuffer = await crypto.subtle.digest("SHA-256", buffer);
    return Array.from(new Uint8Array(hashBuffer))
      .map((b) => b.toString(16).padStart(2, "0"))
      .join("");
  };

  return (
    <div className="w-full max-w-xl mx-auto space-y-6">
      <div
        className="border-dashed border-2 rounded-lg p-6 shadow-sm text-center cursor-pointer hover:bg-gray-50"
        onDrop={handleDrop}
        onDragOver={handleDragOver}
      >
        <p className="text-sm text-gray-600 mb-2">
          Drag & drop an image here, or click to select
        </p>
        <input
          id="imageUpload"
          type="file"
          accept="image/*"
          className="hidden"
          onChange={handleSelect}
        />
        <label
          htmlFor="imageUpload"
          className="inline-block px-4 py-2 mt-2 bg-blue-100 text-blue-700 text-sm font-semibold rounded cursor-pointer hover:bg-blue-200"
        >
          Browse
        </label>

        {uploading && (
          <div className="mt-3">
            <progress value={progress} max="100" className="w-full" />
            <p className="text-sm text-gray-600 mt-1">{progress}%</p>
          </div>
        )}
        {message && <p className="mt-2">{message}</p>}
        {variants && (
          <div className="grid grid-cols-3 gap-3 mt-4">
            {Object.entries(variants).map(([size, url]) => (
              <div key={size} className="text-center">
                <p className="text-sm text-gray-600 mb-1">{size}px</p>
                <img
                  src={url}
                  alt={`Variant ${size}`}
                  className="w-32 h-32 object-contain bg-gray-100 border rounded mx-auto"
                />
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

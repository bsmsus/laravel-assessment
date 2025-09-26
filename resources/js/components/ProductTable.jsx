import React, { useEffect, useState } from "react";
import axios from "axios";

axios.defaults.baseURL = "http://127.0.0.1:8080"; // update for Railway later

export default function ProductTable({ refreshKey, liveSummary }) {
  const [products, setProducts] = useState([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loaded, setLoaded] = useState(false);
  const [summary, setSummary] = useState(liveSummary || null);

  useEffect(() => {
    fetchProducts(page);
    if (!liveSummary) fetchSummary();
  }, [page, refreshKey]);

  // when liveSummary prop changes, update local state
  useEffect(() => {
    if (liveSummary) setSummary(liveSummary);
  }, [liveSummary]);

  const fetchProducts = async (page) => {
    try {
      const res = await axios.get(`/api/products?page=${page}`);
      setProducts(res.data.data);
      setLastPage(res.data.last_page);
    } catch (err) {
      console.error("Failed to fetch products", err);
    } finally {
      setLoaded(true);
    }
  };

  const fetchSummary = async () => {
    try {
      const res = await axios.get("/api/products/import/summary");
      setSummary(res.data);
    } catch (err) {
      console.error("Failed to fetch import summary", err);
    }
  };

  if (!loaded && !summary) return <p className="text-center">Loading...</p>;

  return (
    <div className="w-full max-w-4xl mx-auto mt-10">
      <h2 className="text-xl font-bold mb-4">Imported Products</h2>

      {/* Import Summary */}
      {summary && (
        <div className="grid grid-cols-5 gap-4 mb-6 text-center">
          <div className="p-3 bg-blue-100 rounded">
            <p className="font-bold">{summary.total}</p>
            <p className="text-sm text-gray-600">Total</p>
          </div>
          <div className="p-3 bg-green-100 rounded">
            <p className="font-bold">{summary.imported}</p>
            <p className="text-sm text-gray-600">Imported</p>
          </div>
          <div className="p-3 bg-yellow-100 rounded">
            <p className="font-bold">{summary.updated}</p>
            <p className="text-sm text-gray-600">Updated</p>
          </div>
          <div className="p-3 bg-red-100 rounded">
            <p className="font-bold">{summary.invalid}</p>
            <p className="text-sm text-gray-600">Invalid</p>
          </div>
          <div className="p-3 bg-gray-200 rounded">
            <p className="font-bold">{summary.duplicates}</p>
            <p className="text-sm text-gray-600">Duplicates</p>
          </div>
        </div>
      )}

      {/* Product Table */}
      {products.length > 0 ? (
        <>
          <table className="w-full border-collapse border border-gray-300 text-sm">
            <thead>
              <tr className="bg-gray-100">
                <th className="border border-gray-300 px-3 py-2">SKU</th>
                <th className="border border-gray-300 px-3 py-2">Name</th>
                <th className="border border-gray-300 px-3 py-2">Price</th>
                <th className="border border-gray-300 px-3 py-2">Image</th>
              </tr>
            </thead>
            <tbody>
              {products.map((p) => (
                <tr key={p.sku}>
                  <td className="border border-gray-300 px-3 py-2">{p.sku}</td>
                  <td className="border border-gray-300 px-3 py-2">{p.name}</td>
                  <td className="border border-gray-300 px-3 py-2">{p.price}</td>
                  <td className="border border-gray-300 px-3 py-2 text-center">
                    {p.image_path ? (
                      <img
                        src={`/storage/${p.image_path}`}
                        alt={p.name}
                        className="w-16 h-16 object-contain mx-auto border rounded"
                      />
                    ) : (
                      <span className="text-gray-400">—</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {/* Pagination */}
          <div className="flex justify-center space-x-2 mt-4">
            <button
              disabled={page === 1}
              onClick={() => setPage((p) => p - 1)}
              className="px-3 py-1 border rounded bg-gray-200 disabled:opacity-50"
            >
              Prev
            </button>
            <span className="px-3 py-1">
              {page} / {lastPage}
            </span>
            <button
              disabled={page === lastPage}
              onClick={() => setPage((p) => p + 1)}
              className="px-3 py-1 border rounded bg-gray-200 disabled:opacity-50"
            >
              Next
            </button>
          </div>
        </>
      ) : (
        <p className="text-center text-gray-500">
          {summary?.status === "processing"
            ? "Import in progress…"
            : "No products to display yet."}
        </p>
      )}
    </div>
  );
}

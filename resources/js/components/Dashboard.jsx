import React from "react";
import ImageUploader from "./ImageUploader";
import CsvUploader from "./CsvUploader";
import ProductTable from "./ProductTable";

export default function Dashboard() {
  const [refreshKey, setRefreshKey] = React.useState(0);
  const [liveSummary, setLiveSummary] = React.useState(null);
  const [processing, setProcessing] = React.useState(false);

  const handleCsvUploaded = ({ liveSummary: s, completed }) => {
    if (s) {
      setLiveSummary(s);
      setProcessing(s.status === "processing");
    }
    if (completed) {
      setProcessing(false);
      setRefreshKey((prev) => prev + 1);
    }
  };

  return (
    <div className="p-6 space-y-10">
      <ImageUploader />
      <CsvUploader onImport={handleCsvUploaded} />
      {(processing || liveSummary) && (
        <ProductTable key={refreshKey} liveSummary={liveSummary} />
      )}
    </div>
  );
}

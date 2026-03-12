import React, { useEffect, useState } from "react";
import ReactDOM from "react-dom/client";
import { fetchArticles } from "./api/ArticleApi";
import ArticleCard from "./components/ArticleCard";

function App() {
  const [articles, setArticles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const load = async () => {
      try {
        const data = await fetchArticles();
        setArticles(data);
      } catch (err) {
        setError(err.message || "Failed to load articles");
      } finally {
        setLoading(false);
      }
    };

    load();
  }, []);

  return (
    <div
      style={{
        fontFamily: "system-ui, sans-serif",
        padding: "2rem",
        maxWidth: 900,
        margin: "0 auto"
      }}
    >
      <h1 style={{ marginBottom: "1rem" }}>NewsCurator</h1>
      {loading && <p>Loading articles...</p>}
      {error && <p style={{ color: "red" }}>{error}</p>}
      {!loading && !error && articles.length === 0 && <p>No articles available.</p>}
      <div style={{ display: "grid", gap: "1rem" }}>
        {articles.map((article) => (
          <ArticleCard key={article.id} article={article} />
        ))}
      </div>
    </div>
  );
}

const container = document.getElementById("root");
if (container) {
  const root = ReactDOM.createRoot(container);
  root.render(<App />);
}


import React from "react";

export default function ArticleCard({ article }) {
  if (!article) return null;

  const { title, description, source, publishedAt } = article;

  return (
    <article
      style={{
        padding: "1rem 1.25rem",
        borderRadius: "0.75rem",
        border: "1px solid #e2e8f0",
        boxShadow: "0 4px 12px rgba(15, 23, 42, 0.05)",
        backgroundColor: "#ffffff"
      }}
    >
      {title && (
        <h2 style={{ fontSize: "1.1rem", marginBottom: "0.5rem" }}>
          {title}
        </h2>
      )}
      {description && (
        <p style={{ marginBottom: "0.5rem", color: "#4b5563" }}>
          {description}
        </p>
      )}
      <div style={{ fontSize: "0.85rem", color: "#6b7280" }}>
        {source && <span>Source: {source}</span>}
        {publishedAt && (
          <span style={{ marginLeft: source ? "0.75rem" : 0 }}>
            {new Date(publishedAt).toLocaleString()}
          </span>
        )}
      </div>
    </article>
  );
}
